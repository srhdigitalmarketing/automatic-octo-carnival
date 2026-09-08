# Settings → Secure

Create and download ZIP file backups or a full SQL database dump. Upload ZIP/SQL archives, then choose Restore in the backup list. Upload alone does not modify live data. Restore first validates the selected archive and displays its destination; the administrator must type RESTORE to confirm. The server requires a session-bound, single-use confirmation valid for ten minutes and rechecks the archive checksum before applying it.

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

No database migration is required. Backup credentials use a private temporary options file, not a command-line password, and are removed when the operation finishes. Uploaded archives change active application data only after the separate Restore confirmation.


## Restore

- ZIP entries must be relative to the application root, as in ZIPs created here (`public/uploads/...` for media). Repackage archives that contain an extra parent directory. The preview shows uploads versus application scope.
- Restore writes only files present in the archive; unrelated files remain. An application ZIP may restore `.env`, so verify that its configuration belongs to this server. R2 data and the database are not included in ZIP restoration.
- The entire ZIP is validated and staged before overwriting live files. Path traversal, absolute paths, symlinks, special files, duplicate names, file/directory conflicts and protected root directories are rejected. Limits: 200,000 entries and 2 GiB expanded data.
- A new ZIP backup of the affected scope is created before applying files. Catchable file-write failures attempt to roll back files already changed. A killed PHP worker, server shutdown or disk failure cannot guarantee rollback; recover from the safety backup in aaPanel if necessary. Orphaned `restore-*` directories from a killed process can be removed only after confirming that no restore is running.
- SQL restoration creates a database backup first; if backup creation fails, import does not start. It streams SQL into the installed `mysql`/`mariadb` client and stops on the first SQL error. There is no automatic SQL rollback: MySQL DDL is not transactionally reversible. Errors and timeouts explicitly warn that the database may be partially restored.
- SQL must be a trusted backup for this website's configured database. The account must have privileges only on that database; root/global privileges, cross-database privileges, roles and GRANT OPTION are rejected. Escaped database wildcard characters are required in grants. Configure a dedicated website database user in aaPanel. Definers or incompatible SQL from another server can still cause import failure.
- Both `mysql`/`mariadb` and `mysqldump`/`mariadb-dump` must be present under `/www/server/mysql/bin/` or `/usr/bin/`. Enable `proc_open`, `proc_get_status`, `proc_close`, `proc_terminate`. Each database subprocess is limited to four minutes; the request allows ten minutes to cover safety backup plus import. Configure proxy/PHP-FPM timeouts accordingly or use aaPanel for large restores.
- The MySQL client uses batch/binary mode to disable interactive client commands, and disables local file imports. See the [official MySQL client options](https://dev.mysql.com/doc/refman/8.0/en/mysql-command-options.html). SQL is not treated as a general-purpose sandbox; import only backups whose origin you trust.
- Pause website writes, scheduled jobs, uploads and deployments during restore, using server maintenance controls as appropriate. The Secure lock prevents overlapping Secure operations, but does not stop other application requests.
- Keep this page open. A browser/proxy disconnect does not prove that restore stopped; check the website and backups before retrying. After application or SQL restore, reload the page and sign in again if required.

Validation: PHP fixture tests restore real temporary ZIPs and verify archive rejection and backup preservation. SQL tests simulate the Linux process boundary and verify flags, backup ordering, error handling and credential cleanup; they do not replace a staging restore against your actual aaPanel MySQL/MariaDB version.
