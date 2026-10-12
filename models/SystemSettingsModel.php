<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SYSTEM SETTINGS MODEL
|--------------------------------------------------------------------------
|
| Handles:
| - Reading system settings
| - Updating system settings
|
| Uses the application's existing MySQLi connection.
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

if (!function_exists('db_connection')) {
    require_once __DIR__ . '/../config/database.php';
}

/*
|--------------------------------------------------------------------------
| GET SETTINGS
|--------------------------------------------------------------------------
*/

function system_settings_get(): array
{
    $conn = db_connection();

    $sql = "
        SELECT
            setting_id,
            office_name,
            logo_path,
            address,
            system_title,
            updated_by,
            updated_at
        FROM system_settings
        ORDER BY setting_id ASC
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new RuntimeException(
            'Failed to prepare system settings query: '
            . $conn->error
        );
    }

    $stmt->execute();

    $result = $stmt->get_result();

    $row = $result
        ? $result->fetch_assoc()
        : null;

    $stmt->close();

    /*
    |--------------------------------------------------------------------------
    | CREATE DEFAULT RECORD IF NONE EXISTS
    |--------------------------------------------------------------------------
    */

    if ($row === null) {

        $officeName = 'City Legal Office';
        $logoPath = null;
        $address = '';
        $systemTitle = 'Document Tracking';

        $insertSql = "
            INSERT INTO system_settings
            (
                office_name,
                logo_path,
                address,
                system_title,
                updated_at
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                NOW()
            )
        ";

        $insertStmt =
            $conn->prepare($insertSql);

        if (!$insertStmt) {
            throw new RuntimeException(
                'Failed to create default system settings: '
                . $conn->error
            );
        }

        $insertStmt->bind_param(
            'ssss',
            $officeName,
            $logoPath,
            $address,
            $systemTitle
        );

        $insertStmt->execute();

        $settingId =
            (int) $insertStmt->insert_id;

        $insertStmt->close();

        return [
            'setting_id' => $settingId,
            'office_name' => $officeName,
            'logo_path' => null,
            'address' => $address,
            'system_title' => $systemTitle,
            'updated_by' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }

    return $row;
}

/*
|--------------------------------------------------------------------------
| UPDATE SETTINGS
|--------------------------------------------------------------------------
*/

function system_settings_update(
    string $officeName,
    ?string $logoPath,
    string $address,
    string $systemTitle,
    ?int $updatedBy
): bool {

    $conn = db_connection();

    /*
    |--------------------------------------------------------------------------
    | Find current record
    |--------------------------------------------------------------------------
    */

    $current = system_settings_get();

    $settingId =
        (int) (
            $current['setting_id']
            ?? 0
        );

    if ($settingId <= 0) {
        throw new RuntimeException(
            'System settings record could not be found.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Keep existing logo when no new logo was uploaded.
    |--------------------------------------------------------------------------
    */

    if ($logoPath === null) {

        $logoPath =
            isset($current['logo_path'])
                && $current['logo_path'] !== ''
                    ? (string) $current['logo_path']
                    : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    $sql = "
        UPDATE system_settings
        SET
            office_name = ?,
            logo_path = ?,
            address = ?,
            system_title = ?,
            updated_by = ?,
            updated_at = NOW()
        WHERE setting_id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new RuntimeException(
            'Failed to prepare system settings update: '
            . $conn->error
        );
    }

    $stmt->bind_param(
        'ssssii',
        $officeName,
        $logoPath,
        $address,
        $systemTitle,
        $updatedBy,
        $settingId
    );

    $success =
        $stmt->execute();

    $stmt->close();

    return $success;
}
