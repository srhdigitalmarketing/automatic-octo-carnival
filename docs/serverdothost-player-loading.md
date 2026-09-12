# ServerDotHost: status link dan player mobile

ServerDotHost adalah sumber katalog/stream, bukan provider pengecekan file. Host pada `embed_domains` ServerDotHost aktif dikecualikan dari pengecekan HTTP admin dan cron serta laporan otomatis timeout player. Form tidak menampilkan Server status/Cek file untuk host tersebut. Konfigurasi API dan Auto Grab tetap tersedia.

Link yang sebelumnya ditandai Broken otomatis kembali Active saat dipilih player atau diproses cron health. URL, judul, isi video, prioritas, akun API dan laporan pengunjung tetap dipertahankan. Untuk memulihkan semuanya sekaligus setelah update, jalankan dari direktori aplikasi:

```bash
php spark streams:activate-unregistered
```

Perintah ini juga mencakup host tanpa API pemeriksaan. Tidak menghubungi URL stream dan tidak mengubah status host lain yang masih menggunakan pengecekan aktif.

Pemuatan player menggunakan AJAX asinkron ke origin halaman yang sama, termasuk instalasi subdirektori/index.php. Tidak lagi mengikuti hostname/port dari base URL lama untuk AJAX. Respons stream/token tidak boleh disimpan cache (`Cache-Control: no-store`). Aset jQuery 3.6.0 dan Bootstrap 5.1.3 yang sama dengan versi CDN sebelumnya disediakan lokal; checksum SRI diverifikasi saat menyalin. Fallback aset player juga memakai origin halaman. CDN tetap dapat digunakan untuk aset statis lain.

Timeout iframe normal 30 detik, diperpanjang menjadi minimal 60 detik jika browser melaporkan koneksi 2G/3G atau RTT tinggi. Double tap tidak membuat permintaan paralel. Kegagalan AJAX tidak mengirim laporan broken. Rotasi host saat iframe gagal hanya menyimpan pengecualian di browser untuk ServerDotHost. Tombol Coba lagi mengulang pemuatan setelah jaringan pulih.

## Jika hanya jaringan seluler tertentu yang masih gagal

Perlu URL player dan detail error Console/Network dari jaringan yang gagal. CORS pada API internal dihindari dengan same-origin; header CORS pada video/HLS di domain penyedia tetap harus dikonfigurasi oleh penyedia. Halaman induk tidak bisa mengubah header milik iframe pihak ketiga. Event `load` iframe juga tidak membuktikan video berhasil diputar.

- `Access-Control-Allow-Origin`/CORS pada fetch HLS: periksa header pada domain media/CDN penyedia, termasuk respons error dan preflight yang memang digunakan.
- `ERR_BLOCKED_BY_LOCAL_NETWORK_ACCESS_CHECKS`: periksa domain tujuan, redirect dan hasil DNS pada jaringan tersebut; ini berbeda dari CORS biasa.
- `ERR_NAME_NOT_RESOLVED`, TLS, timeout: periksa DNS, sertifikat, IPv4/IPv6 dan koneksi domain penyedia dari jaringan terdampak.
- `frame-ancestors` atau `X-Frame-Options`: penyedia perlu mengizinkan embedding dari domain website.

Jangan menambahkan wildcard CORS global atau menonaktifkan keamanan browser untuk menyamarkan masalah. Perbaikan ini tidak membuat proxy video atau mengubah URL host milik pengguna.

Referensi: https://developer.mozilla.org/en-US/docs/Web/HTTP/Guides/CORS dan https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/iframe
