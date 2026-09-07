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

6. Di **aaPanel → Cron → Shell Script**, ubah task pemeriksaan yang sudah ada menjadi **Weekly / seminggu sekali**, misalnya **Senin pukul 03:00 WIB**. Pastikan zona waktu server sesuai WIB; jangan membuat task tambahan sementara task lama 5 menit masih aktif. Ganti direktori contoh dengan folder proyek Anda; folder tersebut harus berisi `spark`. Gunakan user yang dapat membaca konfigurasi aplikasi dan menulis ke `writable`.

```sh
cd /www/wwwroot/DOMAIN_ANDA || exit 1
flock -n writable/upnshare-health.lock /www/server/php/82/bin/php spark streams:health-check --limit 100
```

Jadwal mingguan dijalankan oleh aaPanel, bukan diatur oleh kode PHP atau `git pull`. Simpan perubahan jadwal pada task cron yang sudah ada.

`flock` mencegah batch bertumpuk. Pemeriksaan memakai batch 1–500 link dan berputar, termasuk link yang sebelumnya terhapus agar bisa pulih. Sebagian kapasitas diberikan ke laporan kegagalan player. Perintah memeriksa seluruh host stream, memakai API untuk hostname UPNShare/VidHide yang dikonfigurasi dan mekanisme sebelumnya untuk host lain. Dengan `--limit 100`, jadwal mingguan memeriksa maksimal 100 link per minggu, bukan seluruh database. Satu batch tidak berarti seluruh database sudah diperiksa. Sesuaikan interval/limit dengan jumlah link dan batas API akun. Pantau log cron untuk Check failed/HTTP 429; kurangi frekuensi bila terkena pembatasan.

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

## Rotasi jika file terhapus

Status API Deleted/Error/Processing selalu dikeluarkan dari kandidat, termasuk saat link itu dipilih player atau memiliki prioritas tertinggi. Contoh: host A prioritas 100 terhapus, host B prioritas 1 tersedia → player memakai B. Prioritas hanya mengurutkan host yang memenuhi syarat, bukan syarat minimum. Cache sukses lama tidak mengalahkan status Deleted. Jika tidak ada host tersedia, player menampilkan pesan tidak ada host sehat. Pemilihan berikutnya memakai status terbaru yang telah disimpan cron; iframe yang sudah terbuka tidak diputus secara paksa.

## Result koneksi API & R2 Storage

Kolom Result diperiksa otomatis saat halaman dibuka, tanpa menunggu API untuk menampilkan tabel. Terhubung berarti autentikasi dan respons API valid; Tidak terhubung berarti pemeriksaan gagal, termasuk token/izin atau gangguan jaringan. Paused ditampilkan sebagai Tidak diperiksa. Tombol Cek ulang memakai cache maksimal 60 detik; perubahan konfigurasi/kredensial otomatis memakai hasil baru. Waktu hasil ditampilkan pada baris. Tes R2 menggunakan HeadBucket, sehingga tidak membuat/menghapus objek dan tidak menjamin izin upload atau akses URL gambar publik. Kegagalan permintaan browser ditampilkan Gagal memeriksa, bukan dianggap bukti API mati.

## Halaman player berhasil dimuat tetapi video terhapus

HTTP 200 hanya membuktikan halaman web merespons. Contoh `https://ustreamplay.online/#9aboc`: request HTTP biasa tidak mengirim fragmen `9aboc`, sehingga halaman utama dapat tetap merespons 200 ketika videonya terhapus. Label **HTTP reachable** (abu-abu) tidak berarti video sehat. **Healthy/API available** hanya berasal dari konfirmasi provider. Pemeriksaan API yang gagal tidak lagi dinaikkan menjadi sukses oleh probe HTTP.

Pada Edit Video → Stream Links, gunakan **Cek file via API** untuk memeriksa URL tersimpan saat itu tanpa menunggu giliran cron. Simpan dahulu jika URL baru diubah. Jika hostname tidak memiliki konfigurasi API aktif, muncul penjelasan untuk memperbaiki Embed hostnames. Cache tombol maksimal 15 detik. Cron tetap diperlukan untuk pemeriksaan seluruh koleksi.

UPNShare `Check failed (HTTP 404)` juga dikeluarkan dari playback tanpa diberi label Deleted. Resolver mencoba host berikutnya, termasuk link tanpa API dan dengan prioritas lebih rendah. Cron atau Cek file via API tetap dapat memulihkan link ketika API kembali menyatakan tersedia. Hasil 404 lama perlu diperiksa ulang setelah pembaruan agar penandaan ini tersimpan.

Dengan jadwal mingguan, perubahan status file bisa baru terdeteksi hingga sekitar satu minggu kemudian untuk link yang masuk batch; antrean lebih besar memerlukan beberapa minggu. Gunakan Cek file via API untuk pemeriksaan manual segera.

VidHide/EarnVids HTTP/API 404 atau 410 juga melewati host tanpa menganggapnya pasti Deleted. Status 404/410 pada record file dengan ID yang cocok tetap Deleted. Error favicon `manifest.json` bukan status file video. Player tidak menampilkan panel/tombol pemilihan server. Rotasi berlangsung otomatis saat API menyatakan link harus dilewati, atau iframe gagal dimuat/timeout 15 detik. Host gagal dikecualikan dari percobaan selanjutnya pada sesi penonton. Jika halaman iframe sudah selesai dimuat tetapi stream internal mengalami CORS/timeout, kegagalan tersebut tidak selalu dapat diamati oleh halaman induk. Browser induk tidak dapat membaca error internal iframe lintas domain secara langsung.

Status **522** dari UPNShare atau VidHide/EarnVids juga membuat link dilewati (Check failed, bukan Deleted). Host berikutnya dapat berupa link tanpa API dengan prioritas lebih rendah. Jika pemeriksaan HTTP host tanpa API menerima 522, resolver juga melanjutkan ke host berikutnya. Link API dapat dipulihkan pada pemeriksaan berikutnya saat provider kembali menyatakan tersedia. Deteksi ini berlaku untuk respons yang diterima server pemeriksa; status internal iframe lintas domain tetap tidak selalu terlihat oleh halaman induk.

## Custom hostname tanpa token

Pilih **API & R2 Storage → Add Custom hostname**. Isi Display name, Embed hostnames, dan Active; tidak ada API token. Pemetaan hostname berlaku otomatis seperti provider API, dengan satu konfigurasi aktif per hostname. Gunakan **Cek file** pada link tersimpan atau cron mingguan yang sudah ada.

Pemeriksaan HTTP hanya menerima URL publik HTTP(S) port 80/443, memvalidasi DNS lalu mengunci alamat koneksi, membatasi waktu/ukuran respons, dan tidak mengikuti redirect. HTTP 2xx menjadi **HTTP reachable**, bukan Healthy. HTTP 4xx/5xx (termasuk 404/410/522) atau koneksi gagal membuat link dilewati tanpa menyebutnya Deleted. Respons 3xx memerlukan URL embed langsung. HTTP 200 dari halaman error/JavaScript atau URL #ID tidak membuktikan video ada. Host tanpa API tidak dapat menjamin deteksi error stream internal.

Kolom Result konfigurasi ini menampilkan **Tanpa API**; koneksi diperiksa per URL file, bukan sekadar halaman utama hostname.

### Batas pemuatan iframe VidHide/EarnVids

Player memberi waktu 5 detik untuk memuat halaman iframe hostname VidHide/EarnVids yang dikonfigurasi aktif, serta vidplayerpro.online, earnvids.com dan vidhide.com. Jika halaman belum selesai dimuat, player otomatis mencoba link berikutnya tanpa panel ganti server. Host lain tetap 15 detik.

Batas ini hanya mengukur pemuatan halaman iframe. Spinner video, buffering dan waktu kembali ke 00:00 setelah iframe selesai dimuat tidak dapat diamati oleh halaman induk lintas domain tanpa event playback dari provider. Timer dihentikan ketika halaman iframe dimuat agar video yang berjalan normal tidak diputus setiap 5 detik. Pemeriksaan API/HTTP tetap menentukan link yang dikeluarkan dari rotasi.
