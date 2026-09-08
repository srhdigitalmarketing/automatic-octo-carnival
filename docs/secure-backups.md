# Settings → Secure

Create and download ZIP file backups or a full SQL database dump. Upload ZIP/SQL archives for safekeeping; uploaded files are not extracted and SQL is not executed. Restoration is performed separately in aaPanel/MySQL administration tools.

Files are kept in writable/secure-backups with random IDs. The admin download endpoint requires login. Use public/ as the site document root, and keep writable inaccessible over HTTP. The supplied .htaccess blocks Apache access; nginx must deny writable if the site's document root is the project root. Do not publish backup archives or add them to Git. Application archives can include .env and database dumps can contain API keys and private data.

File backup defaults to public/uploads. Application ZIPs include application files and uploads, excluding .git, .codex, .agents, node_modules and writable. R2 objects are not downloaded. No backups are deleted automatically. Copy important backups to another device and periodically remove unneeded archives using the Secure page.

## aaPanel requirements

- PHP zip extension for ZIP creation/validation.
- Writable storage and enough free disk for the archive.
- proc_open enabled for database exports.
- mysqldump or mariadb-dump at /www/server/mysql/bin/ or /usr/bin/.
- MySQL/MariaDB permissions for table data, views, triggers, routines and events.
- PHP upload_max_filesize/post_max_size and nginx client_max_body_size govern uploads, capped by this feature at 512 MB.
- Database export stops after four minutes. File archives are limited to 200,000 files. Use aaPanel scheduled backups for larger installations and keep backups outside the live server.

SQL export uses --single-transaction and --quick. Avoid schema changes during export; nontransactional tables do not receive the same snapshot guarantees as InnoDB. File backup reads live files, so avoid deploys and uploads while creating a snapshot. SQL and ZIP backups are separate operations.

No database migration is required. Backup credentials use a private temporary options file, not a command-line password, and are removed when the operation finishes. Uploaded archives do not change active application data.
