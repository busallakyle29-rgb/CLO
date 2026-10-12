<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function dt_get_document_types(): array
{
    $sql = 'SELECT document_type_id, type_code, type_name, description, retention_days, requires_acknowledgment, is_active, created_at
            FROM document_types
            ORDER BY type_name ASC';

    $stmt = db_execute($sql);
    $rows = db_fetch_all_assoc($stmt);
    $stmt->close();

    return $rows;
}

function dt_create_document_type(array $data): int
{
    $sql = 'INSERT INTO document_types (type_code, type_name, description, retention_days, requires_acknowledgment, is_active)
            VALUES (?, ?, ?, ?, ?, ?)';

    $stmt = db_execute(
        $sql,
        'sssiis',
        [
            $data['type_code'],
            $data['type_name'],
            $data['description'],
            (int) $data['retention_days'],
            (int) $data['requires_acknowledgment'],
            (int) $data['is_active'],
        ]
    );

    $id = (int) $stmt->insert_id;
    $stmt->close();

    return $id;
}

function dt_update_document_type(int $documentTypeId, array $data): void
{
    $sql = 'UPDATE document_types
            SET type_code = ?, type_name = ?, description = ?, retention_days = ?, requires_acknowledgment = ?, is_active = ?
            WHERE document_type_id = ?';

    $stmt = db_execute(
        $sql,
        'sssiiis',
        [
            $data['type_code'],
            $data['type_name'],
            $data['description'],
            (int) $data['retention_days'],
            (int) $data['requires_acknowledgment'],
            (int) $data['is_active'],
            $documentTypeId,
        ]
    );
    $stmt->close();
}

function dt_delete_document_type(int $documentTypeId): void
{
    $stmt = db_execute('DELETE FROM document_types WHERE document_type_id = ?', 'i', [$documentTypeId]);
    $stmt->close();
}
