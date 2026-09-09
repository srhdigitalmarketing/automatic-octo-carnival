# Settings → Backup

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


## Full Backup

Select **Full Backup - Files dan Database** in the Backup Files scope selector. The result is one `full-backup-*.zip` download containing `files.zip` (the existing application backup scope), `database.sql`, `secure-backup.json` with component sizes and SHA-256 hashes, and `README.txt` with recovery instructions. Application files include local uploads and `.env`; the existing exclusions (writable, development metadata, node_modules, symlinks and remote R2 objects) still apply.

Database and files are captured sequentially, not as an atomic snapshot. Pause content changes, uploads and scheduled writes while creating the backup. Full Backup requires both PHP ZIP support and the database export prerequisites described above. Allow disk space for the temporary components plus the final package. Intermediate archives live in a private `full-work-*` directory and are cleaned on success or a caught failure; no incomplete package appears in the normal backup list. After an interrupted/killed worker, confirm that no backup operation is active before removing orphaned staging files.

To restore, download and unpack the full package on a private computer. Upload its `files.zip` and `database.sql` components to Backup and restore them separately, verifying the destination configuration/database first. The existing Restore action explicitly rejects the outer package with these instructions so it cannot accidentally copy the SQL dump into the application. Never unpack the outer package in the website's public directory. The existing 512 MB upload limit still applies when uploading archives.


## FTP, Google Drive and S3 destinations

In Backup, expand **Tujuan Backup > Pengaturan FTP, Google Drive dan S3**, fill in the desired provider and save. Select that destination before Backup Files, Full Backup or Backup Database to create a local archive and send it immediately afterward. For existing ZIP/SQL archives, select the destination then click **Kirim**. Upload Backup itself only stores the provided archive locally. There is no recurring schedule or remote restore/download browser in this feature.

Local archives are retained after success or failure. Successful uploads show the destination and timestamp in the backup list. Remote failure refreshes the list so the local archive can be downloaded or retried. Provider errors do not include raw responses, passwords or tokens. An interrupted response can mean that the remote upload completed; check the destination before retrying (Drive can create duplicate filenames).

Credentials are encrypted with AES-256-GCM in `writable/secure-backups/.remote-settings`; the random installation key is `.remote-key` in the same private directory. File/directory permissions restrict access; encryption does not protect against someone who can read both the key and ciphertext. Neither file is included in application/full backups because writable is excluded. Keep a separate secure copy of both if migrating the remote configuration; losing the key makes those settings unreadable. Secret inputs are never populated from saved values; blank inputs preserve existing secrets. Session Token has an explicit clear checkbox.

### FTP / FTPS

Use a hostname, port, username, password and a folder relative to the FTP user's login directory. The folder should be outside any public web root. FTPS is the default and requires explicit TLS, normally on port 21, with certificate verification enabled. Plain FTP is available only by selecting it explicitly and does not encrypt credentials or backup data. Passive transfers require the server's passive data ports to be reachable. The server user needs create/write/rename permission; uploads use a `.part` filename and rename after successful transfer. Failed transfers may leave a `.part` file, which is not a completed backup. SFTP is a separate protocol and is not supported by this option.

### Google Drive

1. Enable Google Drive API in your Google Cloud project and configure the OAuth consent screen.
2. Create an OAuth client for your own application. Obtain an offline refresh token using that same Client ID and Client Secret, consenting with the Google account that can write to the destination folder. Use the [Google OAuth web-server flow](https://developers.google.com/identity/protocols/oauth2/web-server) (`access_type=offline`); the refresh token must include a Drive scope that grants access to the selected folder. A `drive.file` token only accesses files/folders available to that app, not every pre-existing folder. Do not paste tokens into public tools or source files.
3. Enter Client ID, Client Secret, Refresh Token, and the folder ID from its Drive URL in Secure. This integration uses the user's OAuth storage quota, not a service-account JSON credential.
4. The server refreshes the access token, creates a [resumable upload session](https://developers.google.com/workspace/drive/api/guides/manage-uploads), streams the archive, and checks the returned size and MD5 before marking success. The UI does not currently resume interrupted sessions; verify the remote folder before starting a fresh attempt. Shared Drive uploads use `supportsAllDrives=true` and still require appropriate folder permissions. OAuth apps left in testing may require renewed consent/tokens according to Google's policies.

### S3 Storage

Enter the HTTPS service endpoint (no bucket/path/query in the URL), region, bucket, optional prefix, Access Key ID and Secret Access Key. Temporary credentials may also require Session Token. For example, AWS Singapore uses `https://s3.ap-southeast-1.amazonaws.com` and region `ap-southeast-1`; an R2 S3 endpoint uses its account endpoint and region `auto`. The provider must support path-style S3 URLs and AWS Signature Version 4. Use a private bucket and scoped `s3:PutObject` permission for the backup prefix; no public ACL is requested. Bucket policy/encryption requirements must allow this uploader; custom KMS/object-lock headers are not configured here.

Uploads use a [signed S3 PutObject request](https://docs.aws.amazon.com/AmazonS3/latest/developerguide/sig-v4-header-based-auth.html) with the archive's SHA-256. The object name includes the local archive ID, so retries for that archive target the same key. Do not configure a public bucket or CDN for backups containing database/configuration secrets.

### Transfer limits and validation

PHP cURL and OpenSSL are required. Files stream from disk rather than being read entirely into PHP memory. Each remote transfer is limited to 5 GiB per archive and 300 seconds per HTTP/FTP request; there is no multipart S3 uploader. PHP-FPM/nginx request limits must accommodate backup creation plus upload. The Backup operation lock also serializes backup/restore/remote settings changes. Large or slow transfers may need an external backup client or aaPanel job.

Tests use mocked provider transports to check encrypted settings, secret retention/redaction, FTPS flags and final rename, S3 signing inputs, Drive OAuth/upload verification, destination validation, local retention, and UI status/error behavior. They do not validate connectivity or credentials against live FTP, Google Drive or S3 accounts.

## Nama menu dan pilihan kuota gratis

Menu sekarang **Settings → Backup**, dengan URL `/admin/settings/backup`. URL lama `/admin/settings/secure` tetap berfungsi. Nama internal file, token, format arsip dan folder `writable/secure-backups` tetap dipertahankan agar backup lama bisa dibaca. Tidak ada migrasi atau perubahan tabel database.

Fitur transfer ini tidak memiliki biaya lisensi tambahan. Kuota dan biaya akun storage berbeda:

- [Google Drive](https://support.google.com/drive/answer/9312312?hl=en): hingga 15 GB tanpa biaya, dibagi dengan Gmail dan Google Photos; periksa kuota akun sendiri.
- [Cloudflare R2](https://developers.cloudflare.com/r2/pricing/): Standard storage memiliki kuota gratis 10 GB-month/bulan dan kuota operasi terbatas. Gunakan endpoint S3 akun R2, region `auto`, bucket privat.
- [Backblaze B2](https://www.backblaze.com/cloud-storage/pricing): 10 GB storage pertama gratis. Gunakan S3 endpoint serta region bucket dari console B2, application key yang boleh menulis bucket, dan pilih S3 / R2 / Backblaze B2 dalam pengaturan.
- FTP/FTPS tidak menyediakan kuota sendiri; memakai disk akun hosting/server tujuan. FTPS menggunakan TLS eksplisit, bukan SFTP.

Pemakaian di atas kuota dan operasi/transfer tertentu bisa berbayar. Tidak ada akun yang otomatis dibuat, paket dibeli, atau data dikirim sebelum admin mengisi pengaturan lalu memilih tujuan. Tidak ada polling atau pekerjaan remote pada halaman player. Upload remote melepas lock sesi PHP agar tab admin lain tidak tertahan; lock backup terpisah tetap mencegah backup dan restore bersamaan.


## Restore lokal dan remote

- Lokal: upload ZIP/SQL (maksimal 512 MB, mengikuti batas PHP), kemudian pilih **Restore** pada daftar, periksa tujuan dan ketik `RESTORE`.
- Remote: simpan konfigurasi tujuan, pilih FTP/FTPS, Drive atau S3, masukkan nama arsip (FTP/S3) atau File ID (Drive), lalu **Unduh untuk Restore**. Gunakan nama file tanpa path di folder/prefix tersimpan; Drive harus berada dalam folder tersimpan. Diperlukan izin baca/GetObject. Tidak menerima URL bebas.
- Download maksimal 5 GB, streaming ke disk privat dengan timeout 300 detik; file parsial dibersihkan. Google Drive diperiksa ukuran dan MD5. FTP/S3 memakai transfer selesai dan validasi ZIP; SHA-256 lokal dicatat untuk mendeteksi perubahan sebelum restore, bukan bukti checksum remote.
- Download tidak menjalankan SQL atau menimpa file website. Setelah download, pilih Restore dan konfirmasi. Restore membuat backup pengaman sebelum penimpaan.
- Full Backup masih dipulihkan sebagai dua komponen: unduh paket, ekstrak di komputer pribadi, lalu upload `files.zip` dan `database.sql` dan restore masing-masing. Jangan mengekstrak paket ke public.
