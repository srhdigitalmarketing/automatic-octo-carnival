# Noindex untuk seluruh website, file, dan gambar

## Perubahan aplikasi

Semua respons PHP diberi X-Robots-Tag sebelum bootstrap dan melalui filter global. Template HTML juga diberi meta robots. Sitemap mengembalikan HTTP 410. Pada Apache, public/.htaccess menambahkan header untuk file statis bila mod_headers aktif.

robots.txt sengaja mengizinkan crawling supaya crawler dapat melihat noindex. Jangan menambahkan Disallow: / ketika memakai pendekatan ini. Aturan tersebut mencegah pembacaan header dan URL masih dapat muncul jika ditautkan situs lain.

## aaPanel dengan Nginx — wajib untuk file statis

Website > situs Anda > Configuration. Tambahkan di dalam blok server:

```nginx
add_header X-Robots-Tag "noindex, nofollow, noimageindex, nosnippet" always;
```

Periksa setiap blok location yang sudah mempunyai add_header, termasuk location gambar, CSS, JS, atau cache. Pada konfigurasi Nginx dengan inheritance standar, add_header pada child location menggantikan inheritance parent; ulangi X-Robots-Tag di blok tersebut juga. Jangan menimpa seluruh konfigurasi server dengan snippet ini. Tes konfigurasi Nginx lalu reload melalui aaPanel.

Jika memakai Apache, aktifkan mod_headers dan pastikan AllowOverride mengizinkan public/.htaccess.

## Cloudflare dan gambar R2 — wajib pada setiap hostname gambar

Header website tidak berlaku untuk URL gambar pada hostname lain. Pada Cloudflare untuk setiap custom domain website/gambar yang Anda kendalikan, buat HTTP Response Header Transform Rule dengan tindakan Set static:

- Nama: X-Robots-Tag
- Nilai: noindex, nofollow, noimageindex, nosnippet
- Kondisi: hostname sama dengan domain yang hendak dikeluarkan dari indeks (seluruh path).

Pastikan aturan berlaku pada respons cache juga. Purge cache HTML/file/gambar yang telah tersimpan setelah deployment. Header metadata x-amz-meta-* bukan pengganti X-Robots-Tag. URL publik r2.dev tidak otomatis mendapatkan aturan zona custom domain; gunakan domain R2 yang dapat dikendalikan, dan nonaktifkan akses r2.dev yang tidak diperlukan setelah memastikan seluruh URL aplikasi menggunakan custom domain. Jangan memutus URL gambar yang masih dipakai. URL eksternal di luar kendali Anda tidak dapat diberi aturan oleh aplikasi ini.

## Deploy dan verifikasi

```bash
git pull origin main
/www/server/php/82/bin/php spark cache:clear
curl -I https://DOMAIN_ANDA/
curl -I https://DOMAIN_ANDA/admin_login
curl -I https://DOMAIN_ANDA/FILE_STATIS.jpg
curl -I https://DOMAIN_R2_ANDA/GAMBAR.jpg
curl -I https://DOMAIN_ANDA/sitemap.xml
```

Ganti placeholder dengan URL nyata, termasuk satu file statis yang memang ada. Semua respons harus memiliki X-Robots-Tag noindex; sitemap harus 410. Periksa juga URL alternatif, subdomain, HTTP/HTTPS dan CDN. Pull kode saja belum mengubah konfigurasi Nginx/Cloudflare.

Noindex berlaku bagi mesin pencari yang mematuhinya, bukan kontrol akses atau jaminan untuk bot yang mengabaikan aturan. Hasil yang sudah terindeks tidak langsung hilang; crawler perlu memproses ulang. Untuk percepatan gunakan Search Console Removals dan sarana webmaster mesin pencari lain. Tidak ada penghapusan indeks eksternal atau perubahan server yang dilakukan oleh commit ini.

Referensi:
- https://developers.google.com/search/docs/crawling-indexing/block-indexing
- https://developers.google.com/search/docs/crawling-indexing/prevent-images-on-your-page
