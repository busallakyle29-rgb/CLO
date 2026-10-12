<?php

declare(strict_types=1);

require_once __DIR__ . '/../functions/messages.php';

$message = 'hello team';
$clean = message_normalize_text("  <b>Hello</b> team   ");
if ($clean !== 'Hello team') {
    fwrite(STDERR, "Expected normalized message but got: {$clean}\n");
    exit(1);
}

$timestamp = message_format_timestamp('2026-10-09 14:30:00');
if ($timestamp !== '2:30 PM') {
    fwrite(STDERR, "Expected message timestamp but got: {$timestamp}\n");
    exit(1);
}

if (user_delete_conversation_message(0, 1)) {
    fwrite(STDERR, "Expected invalid message deletion to be rejected\n");
    exit(1);
}

if (user_hide_conversation_message(0, 1)
    || user_clear_conversation_for_user(1, 1) !== 0
    || user_clear_conversation_for_user(0, 2) !== 0) {
    fwrite(STDERR, "Expected invalid user-only deletion requests to be rejected\n");
    exit(1);
}

if (user_edit_message(0, 1, 'updated') !== null
    || user_edit_message(1, 0, 'updated') !== null
    || user_edit_message(1, 1, '   ') !== null) {
    fwrite(STDERR, "Expected invalid message edits to be rejected\n");
    exit(1);
}

if (user_clear_conversation(1, 1) !== 0 || user_clear_conversation(0, 2) !== 0) {
    fwrite(STDERR, "Expected invalid conversation clearing to be rejected\n");
    exit(1);
}

if (user_set_message_typing(0, 1, true) || user_is_message_typing(0, 1)) {
    fwrite(STDERR, "Expected invalid typing status requests to be rejected\n");
    exit(1);
}

if (user_record_message_presence(0) || user_get_online_message_user_ids(0) !== []) {
    fwrite(STDERR, "Expected invalid online presence requests to be rejected\n");
    exit(1);
}

echo "Message helper validation passed\n";
