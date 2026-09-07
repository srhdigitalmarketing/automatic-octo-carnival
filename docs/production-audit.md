# Audit production — 7 September 2026

## Status

Perbaikan aplikasi telah diuji lokal. Belum merupakan persetujuan bahwa seluruh sistem aman untuk production: framework bundled masih CodeIgniter 4.1.7, server aaPanel/PHP 8.2 dan layanan eksternal belum diuji langsung. Data production tidak dibaca, dimodifikasi, atau dihapus dalam audit ini.

## Masalah yang diperbaiki

- HTTP 500 pada bootstrap ketika pengaturan `is_multi_lang` tidak tersedia; daftar bahasa kosong kini memiliki default array. Dikonfirmasi dengan HTTP smoke test sebelum/sesudah.
- Pengunjung aktif: mengganti SELECT lalu INSERT/UPDATE dengan atomic MySQL upsert agar request bersamaan tidak menyebabkan duplicate-key error.
- Heartbeat player bersamaan dicegah agar impresi awal tidak dikirim dua kali saat perubahan visibility.
- Kegagalan penyimpanan metrik sekarang diteruskan ke handler error endpoint, bukan diabaikan sebagai sukses.
- Login menolak input array/terlalu panjang, dibatasi 10 percobaan per IP per 5 menit, meregenerasi sesi dengan penghapusan ID lama, dan tidak menyimpan password gagal ke flash input.
- Filter autentikasi global mengenali namespace controller admin, termasuk URL alias/casing dari legacy routing. Public closure routes tetap didukung.
- Aksi admin yang mengubah data, termasuk GET delete/clear, memerlukan Origin atau Referer dari origin konfigurasi `app.baseURL`. Origin diprioritaskan. Permintaan tanpa bukti origin ditolak. Ini perlindungan untuk UI browser lama; bukan migrasi semua endpoint GET menjadi POST dengan token CSRF.
- Upload lokal memakai ekstensi dari isi gambar, bukan ekstensi nama kiriman. Favicon disimpan sebagai .ico setelah validasi MIME. File poster/banner lama baru dihapus setelah file baru berhasil dipindahkan. Form pengaturan tanpa file tidak lagi memanggil isValid() pada null.
- reCAPTCHA menggunakan POST, verifikasi TLS aktif, timeout terbatas, dan gagal tertutup jika respons tidak valid.
- Downloader gambar lama memakai validasi public IP/DNS pinning, HTTPS verification, batas 4 MB, timeout, dan pemeriksaan isi gambar. Redirect ditolak: gunakan URL gambar langsung.

## Validasi

- Lint penuh 846 file PHP (app, system, public entry point, tests): lulus pada PHP 8.3.33 lokal.
- Syntax check 13 file JavaScript pada direktori aplikasi: lulus.
- Tes PHP: retensi 30 hari, kontrak audience/cache, validasi R2 JPG/PNG/WebP, remote image/SSRF/cleanup, keamanan admin dan konfigurasi kosong: lulus.
- MySQL 8.4.3 sementara: query aplikasi audience, live visitor upsert, DISTINCT pengunjung lintas hari, perangkat, batas 30 hari, dan pengulangan pembersihan: lulus. Database uji sintetis dihapus setelah tes.
- Browser Edge/Playwright: template Excel, download Excel 25 baris, export seluruh 2.050 baris dalam tiga batch dengan filter/page tetap: lulus. Ini bukan uji beban jutaan baris.
- HTTP smoke dengan mode production dan fixture settings minimal: GET login 200, admin tanpa sesi 307, POST login input array 303, POST login cross-origin 403.
- `composer audit --locked`: tidak melaporkan advisory untuk paket lock. Framework di folder system tidak tercakup karena bukan dependency framework dalam lock tersebut.

## Hal yang masih menghalangi kesimpulan production-ready

1. **Framework lama:** `system/CodeIgniter.php` menyatakan 4.1.7. Advisory resmi mencakup versi ini, termasuk validasi upload sebelum 4.7.4 dan validation placeholders. Mitigasi upload aplikasi di atas bukan pengganti upgrade framework. Upgrade ke rilis patched yang kompatibel perlu disertai uji migrasi routing, session, validation, database, dan PHP 8.2; audit ini tidak mengganti seluruh framework.
   - https://github.com/codeigniter4/CodeIgniter4/security/advisories
   - https://github.com/codeigniter4/CodeIgniter4/security/advisories/GHSA-mmj4-63m4-r6h5
   - https://github.com/codeigniter4/CodeIgniter4/security/advisories/GHSA-m6m8-6gq8-c9fj
2. **Server asli:** belum ada verifikasi PHP-FPM 8.2, konfigurasi Nginx, certificate chain, schema/migrations production, izin direktori, dan fungsi R2/API eksternal dengan kredensial asli. Tes R2 tidak melakukan upload ke bucket asli.
3. **Kapasitas:** belum ada load test 200–500 ribu unik/hari. Retensi 30 hari masih bisa berarti 6–15 juta visitor-day rows. Pantau slow query, CPU, RAM, I/O, file cache/session, dan Cron. Cache dashboard menggunakan driver file yang sudah dikonfigurasi proyek.
4. **Cakupan:** sintaks seluruh PHP diperiksa; seluruh kombinasi fitur, semua data database production, dan semua dependency frontend vendor tidak dapat dianggap tervalidasi hanya dari tes ini.

## Langkah aaPanel sebelum pengujian staging

- Backup database dan konfigurasi server. Pull kode melalui branch main; jangan overwrite kredensial database dengan .env.example.
- Pastikan Website > Site directory > Running directory menunjuk `public`, bukan root repository. Larang eksekusi PHP/script di `public/uploads` pada Nginx/Apache; .htaccess saja tidak berlaku pada Nginx.
- Pastikan `app.baseURL` sama dengan origin HTTPS website yang dipakai admin. Asal form admin sekarang diverifikasi; perbedaan domain/skema/port mengakibatkan 403. Jangan gunakan header Referrer-Policy no-referrer untuk tautan GET admin yang mengubah data.
- Gunakan `CI_ENVIRONMENT = production` pada environment server atau .env lokal yang sudah ada. Aktifkan secure cookie untuk HTTPS melalui konfigurasi server (`cookie.secure = true` dan `app.cookieSecure = true`); pastikan SSL/proxy sesuai terlebih dahulu. Jangan commit rahasia.
- Pastikan PHP 8.2 CLI dan FPM memiliki ekstensi composer.json, CA certificates, dan akses tulis writable/cache, writable/logs, writable/session serta uploads.
- Jalankan `php spark migrate` dan `php spark cache:clear` memakai `/www/server/php/82/bin/php` dari folder spark.
- Pertahankan Cron hourly analytics:prune seperti docs/analytics-retention-aapanel.md. Tidak ada Cron atau data server diubah oleh audit ini.
- Uji login, edit/save tanpa upload, upload lokal/R2, grab URL langsung, export, playback, audience, serta Cron pada staging dengan konfigurasi setara server production. Jangan menganggap perbaikan ini otomatis menutup semua advisory framework.
