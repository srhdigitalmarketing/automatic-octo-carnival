# Google Analytics 4

Buka **Settings → Site → Google Analytics 4**. Masukkan Measurement ID dari GA4 (G-XXXXXXXXXX), pilih cakupan, centang Aktifkan Google Analytics, lalu Simpan Google Analytics. Tanpa ID valid dan aktivasi, tag tidak dimuat. Default cakupan adalah embed saja; opsi publik juga mencakup halaman tema Pirate dan halaman utama Storage. Halaman admin tidak dihitung.

Pengaturan disimpan sebagai tiga entri di tabel settings saat disimpan pertama kali. Tidak perlu impor SQL, tabel statistik, atau cron. Konten movies, links, dan konfigurasi database tidak diubah.

Loader lokal menggunakan defer, lalu memuat tag Google secara async pada waktu idle setelah DOM siap. Tidak ada heartbeat, retry, atau penulisan statistik ke database aplikasi. Kegagalan pemuatan Google tidak menjadi syarat untuk menjalankan player. Analytics tetap menambah unduhan dan pekerjaan browser ketika aktif; ini bukan jaminan tanpa overhead.

Hapus pemasangan GA/GTM manual sebelum memakai fitur ini. Loader melewati instalasi jika menemukan gtag/GTM yang sudah ada. Page view menggunakan URL tanpa query/fragment, judul umum, dan referrer hanya origin. Google Signals dan personalisasi iklan dimatikan oleh loader. Jangan menaruh informasi sensitif pada path URL. Tinjau Enhanced Measurement di GA4 dan nonaktifkan event yang tidak diperlukan; pengaturan tersebut dapat mengirim event tambahan. Fitur ini tidak menyertakan pengelola persetujuan cookie.

Lihat pengunjung melalui GA4 Realtime setelah konfigurasi dan deployment. Ad blocker dapat membuat kunjungan tidak tercatat. Kunjungan embed tidak sama dengan pemutaran video; aktivitas di iframe host video tidak otomatis terukur. Pengujian otomatis memakai respons Google tiruan dan tidak mengirim event ke GA4.

Referensi: [Google tag](https://developers.google.com/tag-platform/gtagjs), [parameter konfigurasi GA4](https://developers.google.com/analytics/devguides/collection/ga4/reference/config).
