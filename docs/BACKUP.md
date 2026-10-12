# Database and Upload Backups

`cli/backup_cli.php` creates a ZIP archive containing a consistent MySQL dump and the complete `uploads/` directory. It refuses to write inside the public web root and verifies the ZIP and SQL entry before reporting success. Backups older than 30 days are removed by default. Set `CLO_BACKUP_PASSWORD` in the Windows account environment used by the scheduled task to encrypt every archive entry with AES-256; it must be at least 16 characters. Without it, the script warns that the archive is unencrypted.

## Run once

From PowerShell or Command Prompt:

```powershell
C:\xampp\php\php.exe C:\xampp\htdocs\CLO\cli\backup_cli.php
```

The default destination is `C:\xampp\CLO_Backups`, outside `htdocs`.

## Schedule daily in Windows Task Scheduler

Create a Basic Task with a daily trigger. Set:

- Program: `C:\xampp\php\php.exe`
- Arguments: `C:\xampp\htdocs\CLO\cli\backup_cli.php`
- Start in: `C:\xampp\htdocs\CLO`

Run it under a Windows account that can write to the backup directory. Configure `CLO_BACKUP_PASSWORD` in that account's environment before scheduling the task, and keep the password in an approved secrets manager; do not put it in the web root or source control. The task uses the database settings already configured for the application. Set `MYSQLDUMP_PATH` if `mysqldump.exe` is installed in a different location. Set `CLO_BACKUP_DIR` or `CLO_BACKUP_RETENTION_DAYS` to override the destination or retention period.

## Restore test

Do not test against the live database. Use a separate empty test database and test uploads directory:

1. If the archive was encrypted, open it with a ZIP utility that supports AES-256 and provide the configured backup password. Extract the archive.
2. Create an empty test database, then import `database.sql` into it with MySQL/MariaDB tools.
3. Extract the `uploads/` folder into the test app copy.
4. Point a test app configuration at those restored locations and verify login, document lists, and attachment access.

Keep backup files and the encryption password access-controlled; they contain confidential document records and uploaded files. An encrypted archive cannot be restored without its password.
