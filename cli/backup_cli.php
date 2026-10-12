<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/config.php';

function backup_option_value(string $value): string
{
    return '"' . addcslashes($value, "\\\"\r\n") . '"';
}

function backup_remove_tree(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir() && !$item->isLink()) {
            rmdir($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }

    rmdir($path);
}

$config = get_database_config();
$backupPassword = getenv('CLO_BACKUP_PASSWORD');
$backupPassword = $backupPassword !== false ? $backupPassword : '';
if ($backupPassword !== '' && strlen($backupPassword) < 16) {
    fwrite(STDERR, "CLO_BACKUP_PASSWORD must be at least 16 characters.\n");
    exit(1);
}
$documentRoot = realpath(dirname(__DIR__));
$defaultBackupDirectory = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'CLO_Backups';
$backupDirectory = env_value('CLO_BACKUP_DIR', $defaultBackupDirectory);

if (!is_dir($backupDirectory) && !mkdir($backupDirectory, 0700, true) && !is_dir($backupDirectory)) {
    fwrite(STDERR, "Unable to create backup directory: {$backupDirectory}\n");
    exit(1);
}

$backupDirectory = realpath($backupDirectory);
if ($backupDirectory === false || $documentRoot === false) {
    fwrite(STDERR, "Unable to resolve the backup or web-root directory.\n");
    exit(1);
}

$backupDirectoryPrefix = rtrim($backupDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
$documentRootPrefix = rtrim($documentRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
if (
    strcasecmp(rtrim($backupDirectory, DIRECTORY_SEPARATOR), rtrim($documentRoot, DIRECTORY_SEPARATOR)) === 0
    || strncasecmp($backupDirectoryPrefix, $documentRootPrefix, strlen($documentRootPrefix)) === 0
) {
    fwrite(STDERR, "Backups must be stored outside the public web root.\n");
    exit(1);
}

$dumpPath = getenv('MYSQLDUMP_PATH');
if ($dumpPath === false || trim($dumpPath) === '') {
    $dumpPath = PHP_OS_FAMILY === 'Windows'
        ? 'C:\\xampp\\mysql\\bin\\mysqldump.exe'
        : 'mysqldump';
}
if (PHP_OS_FAMILY === 'Windows' && !is_file($dumpPath)) {
    fwrite(STDERR, "mysqldump was not found at {$dumpPath}. Set MYSQLDUMP_PATH.\n");
    exit(1);
}

$timestamp = date('Ymd_His');
$randomSuffix = bin2hex(random_bytes(4));
$archivePath = $backupDirectory . DIRECTORY_SEPARATOR . "clo-backup-{$timestamp}-{$randomSuffix}.zip";
$workDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'clo-backup-' . bin2hex(random_bytes(12));
if (!mkdir($workDirectory, 0700, true)) {
    fwrite(STDERR, "Unable to create a temporary backup workspace.\n");
    exit(1);
}

$optionFile = $workDirectory . DIRECTORY_SEPARATOR . 'client.cnf';
$sqlPath = $workDirectory . DIRECTORY_SEPARATOR . 'database.sql';
$archiveCreated = false;
$exitCode = 0;

try {
    $optionContents = "[client]\n"
        . 'host=' . backup_option_value((string) $config['host']) . "\n"
        . 'port=' . (int) $config['port'] . "\n"
        . 'user=' . backup_option_value((string) $config['user']) . "\n"
        . 'password=' . backup_option_value((string) $config['pass']) . "\n"
        . 'default-character-set=' . backup_option_value((string) $config['charset']) . "\n";

    if (file_put_contents($optionFile, $optionContents, LOCK_EX) === false) {
        throw new RuntimeException('Unable to write temporary MySQL client settings.');
    }
    @chmod($optionFile, 0600);

    $command = [
        $dumpPath,
        '--defaults-extra-file=' . $optionFile,
        '--single-transaction',
        '--routines',
        '--triggers',
        '--events',
        '--hex-blob',
        (string) $config['name'],
    ];
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['file', $sqlPath, 'wb'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open($command, $descriptors, $pipes, dirname(__DIR__), null, ['bypass_shell' => true]);
    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start mysqldump.');
    }

    fclose($pipes[0]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if ($exitCode !== 0 || !is_file($sqlPath) || filesize($sqlPath) === 0) {
        throw new RuntimeException('Database dump failed: ' . trim((string) $stderr));
    }

    $zip = new ZipArchive();
    if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
        throw new RuntimeException('Unable to create the backup archive.');
    }
    $archiveCreated = true;
    if ($backupPassword !== '' && !$zip->setPassword($backupPassword)) {
        throw new RuntimeException('Unable to configure backup encryption.');
    }
    if (!$zip->addFile($sqlPath, 'database.sql')) {
        throw new RuntimeException('Unable to add the database dump to the archive.');
    }
    if (
        $backupPassword !== ''
        && !$zip->setEncryptionName('database.sql', ZipArchive::EM_AES_256)
    ) {
        throw new RuntimeException('Unable to encrypt the database dump.');
    }

    $uploadsDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
    $uploadsRoot = realpath($uploadsDirectory);
    $uploadedFileCount = 0;
    if ($uploadsRoot !== false) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($uploadsRoot, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->isLink()) {
                continue;
            }
            $relativePath = substr($file->getPathname(), strlen($uploadsRoot) + 1);
            $archiveName = 'uploads/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
            if (!$zip->addFile($file->getPathname(), $archiveName)) {
                throw new RuntimeException('Unable to add an uploaded file to the archive.');
            }
            if (
                $backupPassword !== ''
                && !$zip->setEncryptionName($archiveName, ZipArchive::EM_AES_256)
            ) {
                throw new RuntimeException('Unable to encrypt an uploaded file in the archive.');
            }
            $uploadedFileCount++;
        }
    }

    $manifest = "CLO Document Tracking backup\n"
        . 'Created: ' . date(DATE_ATOM) . "\n"
        . 'Database: ' . (string) $config['name'] . "\n"
        . 'Uploaded files: ' . $uploadedFileCount . "\n"
        . "Contents: database.sql and uploads/\n";
    if (!$zip->addFromString('manifest.txt', $manifest)) {
        throw new RuntimeException('Unable to add the backup manifest.');
    }
    if (
        $backupPassword !== ''
        && !$zip->setEncryptionName('manifest.txt', ZipArchive::EM_AES_256)
    ) {
        throw new RuntimeException('Unable to encrypt the backup manifest.');
    }
    if (!$zip->close()) {
        throw new RuntimeException('Unable to finalize the backup archive.');
    }

    $verify = new ZipArchive();
    if ($verify->open($archivePath, ZipArchive::CHECKCONS) !== true) {
        throw new RuntimeException('Backup archive integrity check failed.');
    }
    $databaseEntry = $verify->statName('database.sql');
    $verify->close();
    if ($databaseEntry === false || (int) ($databaseEntry['size'] ?? 0) <= 0) {
        throw new RuntimeException('Backup archive does not contain a valid database dump.');
    }

    $retentionDays = max(1, (int) env_value('CLO_BACKUP_RETENTION_DAYS', '30'));
    $cutoff = time() - ($retentionDays * 86400);
    foreach (glob($backupDirectory . DIRECTORY_SEPARATOR . 'clo-backup-*.zip') ?: [] as $oldArchive) {
        if (is_file($oldArchive) && filemtime($oldArchive) < $cutoff) {
            @unlink($oldArchive);
        }
    }

    if ($backupPassword === '') {
        fwrite(STDERR, "Warning: CLO_BACKUP_PASSWORD is not set; this archive is not encrypted.\n");
    }
    echo "Backup created and verified: {$archivePath}\n";
} catch (Throwable $exception) {
    if ($archiveCreated && is_file($archivePath)) {
        @unlink($archivePath);
    }
    fwrite(STDERR, $exception->getMessage() . "\n");
    $exitCode = 1;
} finally {
    backup_remove_tree($workDirectory);
}

exit($exitCode);
