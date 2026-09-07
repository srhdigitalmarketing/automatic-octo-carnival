# UPNShare dan VidHide video health checks (aaPanel)

Integrasi menggunakan GET detail video (`/api/v1/video/manage/{id}`) dan, untuk memvalidasi respons Not found, GET daftar video (`/api/v1/video/manage?page=1&perPage=1`). Host API tetap `https://upnshare.com`; token dikirim sebagai header `api-token`, hanya dari server. Tidak ada operasi menghapus file provider. VidHide memakai GET `https://earnvidsapi.com/api/file/info` dengan parameter `key` dan `file_code`, sesuai dokumentasi https://www.vidhideapi.com/api.html. HTTP error/top-level API error tidak dianggap Deleted; hanya status not-found pada record file yang ID-nya cocok.

Dokumentasi: https://upnshare.com/api-document/index.html
OpenAPI: https://upnshare.com/api/v1/openapi.json

## Pemasangan

Dari folder proyek di server, gunakan PHP CLI 8.2 milik aaPanel:

```sh
git pull origin main
/www/server/php/82/bin/php spark migrate
/www/server/php/82/bin/php spark cache:clear
```

1. Buka **API & R2 Storage → Add UPNShare** atau **Add VidHide** di admin. Buat konfigurasi terpisah untuk setiap provider.
2. Isi nama akun dan token dari **UPNShare → API Access → New API Token**, atau API key VidHide. Jangan memasukkan token ke Git atau mengirimkannya ke chat. Field token selalu kosong saat edit; biarkan kosong untuk mempertahankan token tersimpan.
3. Isi **Embed hostnames** sesuai hostname link yang tersimpan di Stream Links. Contoh link `https://player.example/e/abc123` berarti hostname `player.example`. Pisahkan beberapa hostname dengan koma, tanpa protokol/path. Jangan memasukkan domain host video lain.
4. Pilih **Active**, lalu simpan. API dipilih otomatis hanya berdasarkan **Embed hostnames**; tidak ada dropdown akun pada Stream Links. Contoh: `ustreamplay.online` di konfigurasi UPNShare, dan hostname embed VidHide di konfigurasi VidHide. Satu hostname tidak boleh digunakan pada beberapa konfigurasi aktif. Duplikasi ditolak saat menyimpan; duplikasi lama menghasilkan Check failed. Asosiasi akun lama pada link tidak mengalahkan hostname. ID berasal dari URL, termasuk format VidHide `/e/ID`, `/ID.html`, atau `/embed-ID.html`. Link harus berupa URL video lengkap, bukan hanya domain utama.
5. Jalankan satu batch:

```sh
/www/server/php/82/bin/php spark streams:health-check --limit 100
```

6. Di **aaPanel → Cron → Shell Script**, jadwalkan setiap 5 menit. Ganti direktori contoh dengan folder proyek Anda; folder tersebut harus berisi `spark`. Gunakan user yang dapat membaca konfigurasi aplikasi dan menulis ke `writable`.

```sh
cd /www/wwwroot/DOMAIN_ANDA || exit 1
flock -n writable/upnshare-health.lock /www/server/php/82/bin/php spark streams:health-check --limit 100
```

`flock` mencegah batch bertumpuk. Pemeriksaan memakai batch 1–500 link dan berputar, termasuk link yang sebelumnya terhapus agar bisa pulih. Sebagian kapasitas diberikan ke laporan kegagalan player. Perintah memeriksa seluruh host stream, memakai API untuk hostname UPNShare/VidHide yang dikonfigurasi dan mekanisme sebelumnya untuk host lain. Satu batch tidak berarti seluruh database sudah diperiksa. Sesuaikan interval/limit dengan jumlah link dan batas API akun. Pantau log cron untuk Check failed/HTTP 429; kurangi frekuensi bila terkena pembatasan.

Waktu rotasi cron terpisah dari waktu akses pengunjung, sehingga video populer tetap mendapat giliran. Tidak ada panggilan API provider pada request pengunjung; player memakai status terakhir. Perubahan pada provider terlihat setelah giliran cron berikutnya.

## Arti badge

- **Deleted**: API menyatakan deleted/removed, atau detail mengembalikan JSON `Not found` dengan HTTP 404 dan token berhasil membaca daftar video. **404 berarti file tidak ditemukan pada akun yang dikonfigurasi**; ID keliru atau akun yang bukan pemilik juga dapat menghasilkan 404. Pastikan pemetaan akun/ID benar sebelum mengambil keputusan administratif.
- **Error**: status error/failed/unavailable dari UPNShare, atau VidHide mengembalikan `canplay=0` (tidak dapat diputar; belum tentu dihapus).
- **Processing**: video masih diproses.
- **Healthy/Available**: provider melaporkan video tersedia.
- **Check failed**: token ditolak, batas API, gangguan jaringan, format respons/ID atau nilai status belum dikenali. Tidak dianggap sebagai bukti penghapusan. Jika sebelumnya sudah Deleted/Error/Processing, status tersebut dipertahankan sampai ada konfirmasi tersedia; tooltip menampilkan kegagalan pengecekan terbaru.

Deleted/Error/Processing dikeluarkan dari kandidat playback. Jika API kembali melaporkan tersedia, link otomatis dipulihkan. Mengganti URL pada link menghapus status lama dan mengantrekannya untuk pemeriksaan ulang. Badge tampil di **All Videos → Server** dan **Edit Video → Stream Links**. File/video database tidak dihapus oleh fitur ini.

Skema resmi mendefinisikan status sebagai string tanpa daftar enum. Status yang tidak dikenal ditampilkan sebagai Check failed, bukan ditebak. Koneksi akun nyata harus diverifikasi setelah token dipasang; pengujian pengembangan memakai respons API tiruan dan MySQL lokal terisolasi.

## Pengujian pengembangan

```sh
php tests/upnshare_health_test.php
node tests/upnshare_link_form_test.js
# Hanya untuk MySQL disposable di localhost:13389 (root tanpa password):
php tests/upnshare_mysql_test.php 13389
```

Tes MySQL membuat dan menghapus database acak khusus tes. Jangan arahkan pengujian ke database produksi.
