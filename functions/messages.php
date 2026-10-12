<?php

declare(strict_types=1);

function message_normalize_text(?string $message, int $maxLength = 500): string
{
    $clean = strip_tags((string) $message);
    $clean = trim(preg_replace('/\s+/u', ' ', $clean) ?? $clean);

    if ($clean === '') {
        return '';
    }

    if (mb_strlen($clean, 'UTF-8') > $maxLength) {
        $clean = mb_substr($clean, 0, $maxLength, 'UTF-8');
        $clean = rtrim($clean);
    }

    return $clean;
}

function message_format_timestamp(?string $dateTime): string
{
    $rawValue = trim((string) ($dateTime ?? ''));
    if ($rawValue === '') {
        return '';
    }

    $timestamp = strtotime($rawValue);
    if ($timestamp === false) {
        return '';
    }

    return date('g:i A', $timestamp);
}

function user_get_conversation_messages(int $userId, int $otherUserId, int $limit = 50): array
{
    if ($userId <= 0 || $otherUserId <= 0 || $limit <= 0) {
        return [];
    }

    $stmt = db_execute(
        'SELECT
            m.message_id,
            m.sender_id,
            m.recipient_id,
            m.message,
            m.read_at,
            m.created_at,
            sender.first_name AS sender_first_name,
            sender.last_name AS sender_last_name,
            sender.profile_picture AS sender_profile_picture,
            recipient.first_name AS recipient_first_name,
            recipient.last_name AS recipient_last_name,
            recipient.profile_picture AS recipient_profile_picture
         FROM user_messages m
         LEFT JOIN users sender ON sender.user_id = m.sender_id
         LEFT JOIN users recipient ON recipient.user_id = m.recipient_id
         INNER JOIN (
             SELECT message_id
             FROM user_messages
             WHERE ((sender_id = ? AND recipient_id = ?)
                OR (sender_id = ? AND recipient_id = ?))
               AND NOT EXISTS (
                   SELECT 1
                   FROM user_message_deletions hidden
                   WHERE hidden.message_id = user_messages.message_id
                     AND hidden.user_id = ?
               )
             ORDER BY created_at DESC, message_id DESC
             LIMIT ?
         ) latest ON latest.message_id = m.message_id
         ORDER BY m.created_at ASC, m.message_id ASC',
        'iiiiii',
        [$userId, $otherUserId, $otherUserId, $userId, $userId, $limit]
    );

    $rows = db_fetch_all_assoc($stmt);
    $stmt->close();

    return $rows;
}

function user_get_message_recipients(int $userId): array
{
    if ($userId <= 0) {
        return [];
    }

    $stmt = db_execute(
        'SELECT user_id, first_name, last_name
         FROM users
         WHERE user_id != ?
         ORDER BY first_name, last_name
         LIMIT 50',
        'i',
        [$userId]
    );

    $rows = db_fetch_all_assoc($stmt);
    $stmt->close();

    return $rows;
}

function user_get_recent_messages_for_user(int $userId, int $limit = 10): array
{
    if ($userId <= 0 || $limit <= 0) {
        return [];
    }

    $stmt = db_execute(
        'SELECT
            m.message_id,
            m.sender_id,
            m.recipient_id,
            m.message,
            m.read_at,
            m.created_at,
            sender.first_name AS sender_first_name,
            sender.last_name AS sender_last_name,
            sender.profile_picture AS sender_profile_picture,
            recipient.first_name AS recipient_first_name,
            recipient.last_name AS recipient_last_name,
            recipient.profile_picture AS recipient_profile_picture
         FROM user_messages m
         INNER JOIN (
             SELECT MAX(message_id) AS latest_message_id
             FROM user_messages
             WHERE (sender_id = ? OR recipient_id = ?)
               AND NOT EXISTS (
                   SELECT 1
                   FROM user_message_deletions hidden
                   WHERE hidden.message_id = user_messages.message_id
                     AND hidden.user_id = ?
               )
             GROUP BY CASE WHEN sender_id = ? THEN recipient_id ELSE sender_id END
         ) latest ON latest.latest_message_id = m.message_id
         LEFT JOIN users sender ON sender.user_id = m.sender_id
         LEFT JOIN users recipient ON recipient.user_id = m.recipient_id
         ORDER BY m.created_at DESC
         LIMIT ?',
        'iiiii',
        [$userId, $userId, $userId, $userId, $limit]
    );

    $rows = db_fetch_all_assoc($stmt);
    $stmt->close();

    foreach ($rows as $index => $row) {
        $senderId = (int) ($row['sender_id'] ?? 0);
        $recipientId = (int) ($row['recipient_id'] ?? 0);
        $otherUserId = $senderId === $userId ? $recipientId : $senderId;
        $rows[$index]['conversation_unread_count'] = user_get_conversation_unread_count($userId, $otherUserId);
    }

    return $rows;
}

function user_count_unread_messages(int $userId): int
{
    if ($userId <= 0) {
        return 0;
    }

    $stmt = db_execute(
        'SELECT COUNT(*) AS unread_count
         FROM user_messages
         WHERE recipient_id = ?
           AND read_at IS NULL
           AND NOT EXISTS (
               SELECT 1
               FROM user_message_deletions hidden
               WHERE hidden.message_id = user_messages.message_id
                 AND hidden.user_id = ?
           )',
        'ii',
        [$userId, $userId]
    );

    $row = db_fetch_one_assoc($stmt);
    $stmt->close();

    return (int) ($row['unread_count'] ?? 0);
}

function user_get_conversation_unread_count(int $userId, int $otherUserId): int
{
    if ($userId <= 0 || $otherUserId <= 0) {
        return 0;
    }

    $stmt = db_execute(
        'SELECT COUNT(*) AS unread_count
         FROM user_messages
         WHERE sender_id = ? AND recipient_id = ? AND read_at IS NULL
           AND NOT EXISTS (
               SELECT 1
               FROM user_message_deletions hidden
               WHERE hidden.message_id = user_messages.message_id
                 AND hidden.user_id = ?
           )',
        'iii',
        [$otherUserId, $userId, $userId]
    );

    $row = db_fetch_one_assoc($stmt);
    $stmt->close();

    return (int) ($row['unread_count'] ?? 0);
}

function user_mark_message_read(int $userId, int $messageId): bool
{
    if ($userId <= 0 || $messageId <= 0) {
        return false;
    }

    $stmt = db_execute(
        'UPDATE user_messages
         SET read_at = NOW()
         WHERE message_id = ? AND recipient_id = ? AND read_at IS NULL
           AND NOT EXISTS (
               SELECT 1
               FROM user_message_deletions hidden
               WHERE hidden.message_id = user_messages.message_id
                 AND hidden.user_id = ?
           )',
        'iii',
        [$messageId, $userId, $userId]
    );

    $success = $stmt->affected_rows > 0;
    $stmt->close();

    return $success;
}

function user_mark_conversation_read(int $userId, int $otherUserId): int
{
    if ($userId <= 0 || $otherUserId <= 0) {
        return 0;
    }

    $stmt = db_execute(
        'UPDATE user_messages
         SET read_at = NOW()
         WHERE sender_id = ? AND recipient_id = ? AND read_at IS NULL
           AND NOT EXISTS (
               SELECT 1
               FROM user_message_deletions hidden
               WHERE hidden.message_id = user_messages.message_id
                 AND hidden.user_id = ?
           )',
        'iii',
        [$otherUserId, $userId, $userId]
    );

    $affected = (int) $stmt->affected_rows;
    $stmt->close();

    return $affected;
}

function user_send_message(int $senderId, int $recipientId, string $message): bool
{
    if ($senderId <= 0 || $recipientId <= 0 || $senderId === $recipientId) {
        return false;
    }

    $cleanMessage = message_normalize_text($message, 500);
    if ($cleanMessage === '') {
        return false;
    }

    $recipientExists = db_execute(
        'SELECT user_id FROM users WHERE user_id = ? LIMIT 1',
        'i',
        [$recipientId]
    );

    $recipientRow = db_fetch_one_assoc($recipientExists);
    $recipientExists->close();

    if ($recipientRow === null) {
        return false;
    }

    $stmt = db_execute(
        'INSERT INTO user_messages (sender_id, recipient_id, message) VALUES (?, ?, ?)',
        'iis',
        [$senderId, $recipientId, $cleanMessage]
    );

    $success = $stmt->affected_rows > 0;
    $stmt->close();

    return $success;
}

function user_delete_conversation_message(int $userId, int $messageId): bool
{
    if ($userId <= 0 || $messageId <= 0) {
        return false;
    }

    $stmt = db_execute(
        'DELETE FROM user_messages
         WHERE message_id = ?
           AND sender_id = ?',
        'ii',
        [$messageId, $userId]
    );

    $deleted = $stmt->affected_rows > 0;
    $stmt->close();

    return $deleted;
}

function user_hide_conversation_message(int $userId, int $messageId): bool
{
    if ($userId <= 0 || $messageId <= 0) {
        return false;
    }

    $stmt = db_execute(
        'INSERT IGNORE INTO user_message_deletions (message_id, user_id)
         SELECT message_id, ?
         FROM user_messages
         WHERE message_id = ?
           AND (sender_id = ? OR recipient_id = ?)',
        'iiii',
        [$userId, $messageId, $userId, $userId]
    );
    $hidden = $stmt->affected_rows > 0;
    $stmt->close();

    return $hidden;
}

function user_clear_conversation_for_user(int $userId, int $otherUserId): int
{
    if ($userId <= 0 || $otherUserId <= 0 || $userId === $otherUserId) {
        return 0;
    }

    $stmt = db_execute(
        'INSERT IGNORE INTO user_message_deletions (message_id, user_id)
         SELECT message_id, ?
         FROM user_messages
         WHERE (sender_id = ? AND recipient_id = ?)
            OR (sender_id = ? AND recipient_id = ?)',
        'iiiii',
        [$userId, $userId, $otherUserId, $otherUserId, $userId]
    );
    $hiddenCount = (int) $stmt->affected_rows;
    $stmt->close();

    return $hiddenCount;
}

function user_edit_message(int $userId, int $messageId, ?string $message): ?string
{
    if ($userId <= 0 || $messageId <= 0) {
        return null;
    }

    $cleanMessage = message_normalize_text($message, 500);
    if ($cleanMessage === '') {
        return null;
    }

    $existingStmt = db_execute(
        'SELECT message
         FROM user_messages
         WHERE message_id = ? AND sender_id = ?
           AND NOT EXISTS (
               SELECT 1
               FROM user_message_deletions hidden
               WHERE hidden.message_id = user_messages.message_id
                 AND hidden.user_id = ?
           )
         LIMIT 1',
        'iii',
        [$messageId, $userId, $userId]
    );
    $existing = db_fetch_one_assoc($existingStmt);
    $existingStmt->close();

    if ($existing === null) {
        return null;
    }

    if ((string) ($existing['message'] ?? '') === $cleanMessage) {
        return $cleanMessage;
    }

    $stmt = db_execute(
        'UPDATE user_messages
         SET message = ?
         WHERE message_id = ? AND sender_id = ?',
        'sii',
        [$cleanMessage, $messageId, $userId]
    );
    $stmt->close();

    return $cleanMessage;
}

function user_clear_conversation(int $userId, int $otherUserId): int
{
    if ($userId <= 0 || $otherUserId <= 0 || $userId === $otherUserId) {
        return 0;
    }

    $stmt = db_execute(
        'DELETE FROM user_messages
         WHERE (sender_id = ? AND recipient_id = ?)
            OR (sender_id = ? AND recipient_id = ?)',
        'iiii',
        [$userId, $otherUserId, $otherUserId, $userId]
    );

    $deletedCount = (int) $stmt->affected_rows;
    $stmt->close();

    $typingStmt = db_execute(
        'DELETE FROM user_message_typing
         WHERE (sender_id = ? AND recipient_id = ?)
            OR (sender_id = ? AND recipient_id = ?)',
        'iiii',
        [$userId, $otherUserId, $otherUserId, $userId]
    );
    $typingStmt->close();

    return $deletedCount;
}

function user_set_message_typing(int $senderId, int $recipientId, bool $isTyping): bool
{
    if ($senderId <= 0 || $recipientId <= 0 || $senderId === $recipientId) {
        return false;
    }

    if ($isTyping) {
        $recipientStmt = db_execute(
            'SELECT user_id FROM users WHERE user_id = ? LIMIT 1',
            'i',
            [$recipientId]
        );
        $recipientExists = db_fetch_one_assoc($recipientStmt) !== null;
        $recipientStmt->close();

        if (!$recipientExists) {
            return false;
        }
    }

    if (!$isTyping) {
        $stmt = db_execute(
            'DELETE FROM user_message_typing
             WHERE sender_id = ? AND recipient_id = ?',
            'ii',
            [$senderId, $recipientId]
        );
        $stmt->close();
        return true;
    }

    $stmt = db_execute(
        'INSERT INTO user_message_typing (sender_id, recipient_id, updated_at)
         VALUES (?, ?, NOW())
         ON DUPLICATE KEY UPDATE updated_at = NOW()',
        'ii',
        [$senderId, $recipientId]
    );
    $stmt->close();

    return true;
}

function user_is_message_typing(int $senderId, int $recipientId): bool
{
    if ($senderId <= 0 || $recipientId <= 0 || $senderId === $recipientId) {
        return false;
    }

    $stmt = db_execute(
        'SELECT 1
         FROM user_message_typing
         WHERE sender_id = ?
           AND recipient_id = ?
           AND updated_at >= DATE_SUB(NOW(), INTERVAL 6 SECOND)
         LIMIT 1',
        'ii',
        [$senderId, $recipientId]
    );

    $isTyping = db_fetch_one_assoc($stmt) !== null;
    $stmt->close();

    return $isTyping;
}

function user_record_message_presence(int $userId): bool
{
    if ($userId <= 0) {
        return false;
    }

    $userStmt = db_execute(
        'SELECT user_id FROM users WHERE user_id = ? AND is_active = 1 LIMIT 1',
        'i',
        [$userId]
    );
    $userExists = db_fetch_one_assoc($userStmt) !== null;
    $userStmt->close();

    if (!$userExists) {
        return false;
    }

    $stmt = db_execute(
        'INSERT INTO user_message_presence (user_id, last_seen_at)
         VALUES (?, NOW())
         ON DUPLICATE KEY UPDATE last_seen_at = NOW()',
        'i',
        [$userId]
    );

    $stmt->close();

    return true;
}

function user_get_online_message_user_ids(int $excludeUserId): array
{
    if ($excludeUserId <= 0) {
        return [];
    }

    $stmt = db_execute(
        'SELECT presence.user_id
         FROM user_message_presence presence
         INNER JOIN users ON users.user_id = presence.user_id
         INNER JOIN active_user_sessions active_session
            ON active_session.user_id = presence.user_id
         WHERE presence.user_id <> ?
           AND users.is_active = 1
           AND active_session.updated_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
           AND presence.last_seen_at >= DATE_SUB(NOW(), INTERVAL 30 SECOND)',
        'i',
        [$excludeUserId]
    );

    $rows = db_fetch_all_assoc($stmt);
    $stmt->close();

    return array_map(
        static fn (array $row): int => (int) ($row['user_id'] ?? 0),
        $rows
    );
}
