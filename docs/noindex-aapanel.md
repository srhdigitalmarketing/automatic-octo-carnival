# Index / No Index di Settings Site

## Mengubah pengaturan

1. Buka **Settings > Site** pada website yang akan diatur.
2. Pada panel **Index / No Index**, pilih:
   - **No Index - jangan indeks seluruh halaman**: semua halaman aplikasi mengirim `X-Robots-Tag: noindex, nofollow, noimageindex, nosnippet`. Meta robots pada halaman publik mengikuti pilihan ini, termasuk beranda, film/serial, player/embed, daftar konten, halaman informasi dan download.
   - **Index - izinkan indeks halaman publik**: halaman publik HTML yang berhasil dimuat (GET/HEAD, HTTP 200) mengirim `index, follow` melalui header dan meta. Admin, login, respons API/AJAX, redirect dan error tetap No Index.
3. Klik **Simpan pengaturan indeks**.

Default tetap **No Index**, sesuai perilaku aplikasi sebelumnya. Pengaturan berlaku per website/database; mengubah satu website tidak mengubah website lain. Pengaturan memakai key `site_noindex` pada tabel `settings` yang sudah ada, tanpa migrasi SQL. Isi video, URL link, akun API, kredensial, kode tracking dan pengaturan lain tidak diubah. Pembacaan memakai konfigurasi yang disimpan selama satu request, tanpa panggilan jaringan tambahan atau penulisan database saat halaman dibuka.

Sitemap lama tetap HTTP 410; fitur ini tidak membangun ulang generator sitemap. Halaman publik dapat diindeks tanpa sitemap jika ditemukan crawler melalui tautan.

## aaPanel, Apache dan CDN

Filter PHP menentukan header semua halaman yang dirender aplikasi. Header fallback sebelum bootstrap tetap No Index untuk kegagalan awal; filter menggantinya pada respons publik yang mengizinkan Index. Apache `public/.htaccess` sekarang meneruskan keputusan PHP untuk `index.php`, termasuk URL rewrite. File yang dilayani langsung oleh Apache, termasuk HTML statis, gambar dan dokumen, tetap mendapat No Index.

**Jika pernah memasang aturan No Index permanen pada Nginx atau Cloudflare, selaraskan sekali saat deploy.** Header tambahan di luar aplikasi dapat terus melarang indeks walaupun panel memilih Index:

- Di aaPanel Nginx, hapus aturan `add_header X-Robots-Tag ...` permanen dari scope yang memengaruhi halaman PHP, dan jangan menyembunyikan header `X-Robots-Tag` dari upstream. Biarkan header halaman berasal dari aplikasi. Jangan mengganti seluruh konfigurasi website.
- Pada blok `location` yang hanya melayani file statis/gambar, aturan No Index tetap dapat dipertahankan. [Snippet statis](nginx-noindex.conf) hanya untuk scope file statis, bukan seluruh `server` atau handler PHP. Setelah perubahan konfigurasi Nginx, jalankan tes konfigurasi lalu reload melalui aaPanel.
- Jika ada Cloudflare Response Header Transform Rule yang memaksakan No Index ke seluruh website, sesuaikan scope-nya agar tidak menimpa halaman PHP. Aturan untuk hostname gambar/R2 dapat dipertahankan terpisah.
- Setelah deploy atau mengganti mode, purge cache HTML lama di CDN/reverse proxy bila halaman HTML dicache. Periksa juga kode meta robots manual di Custom Header Codes apabila mode Index masih menghasilkan No Index.

Sakelar aplikasi tidak mengubah file konfigurasi Nginx, aturan Cloudflare, file statis yang dilayani langsung oleh Nginx, ataupun URL di domain penyedia video/R2 lain. Untuk menolak indeks file/gambar langsung pada Nginx, gunakan header berikut pada lokasi statis yang relevan:

```nginx
add_header X-Robots-Tag "noindex, nofollow, noimageindex, nosnippet" always;
```

## Verifikasi setelah deploy

Ganti domain dan URL player di bawah dengan halaman yang memang tersedia:

```bash
curl -I https://DOMAIN_ANDA/
curl -I https://DOMAIN_ANDA/play/ID_VIDEO
curl -I https://DOMAIN_ANDA/admin_login
```

Saat No Index, seluruh respons aplikasi harus berisi No Index. Saat Index, halaman publik HTTP 200 harus berisi satu `X-Robots-Tag: index, follow`; admin tetap No Index. Periksa meta robots di source halaman juga. Ulangi pada domain dan URL alternatif yang digunakan.

`robots.txt` tetap mengizinkan crawling supaya mesin pencari dapat membaca No Index. Jangan memakai `Disallow: /` sebagai pengganti No Index. URL yang sudah terindeks baru diperbarui setelah crawler memproses halaman kembali; pengaturan ini tidak menghapus hasil Google seketika dan tidak menjamin semua bot mematuhinya. Lihat [panduan resmi Google](https://developers.google.com/search/docs/crawling-indexing/block-indexing).

Referensi header Apache: [mod_headers](https://httpd.apache.org/docs/2.4/mod/mod_headers.html).
