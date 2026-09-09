# Evaluasi tabel dan file lama

Evaluasi berdasarkan kode setelah c5b43c5. Database/server production belum diakses.
Dokumen ini hanya rekomendasi. Tidak menambahkan atau menjalankan SQL penghapusan.

## Kandidat untuk ditinjau sebelum penghapusan manual

| Tabel | Pemakaian kode saat ini |
| --- | --- |
| live_traffic | Heartbeat dan panel Live Traffic dihapus pada patch ini; deploy patch dahulu. |
| traffic_daily_player_metrics | Penulisan impression/play harian dihapus pada c5b43c5; hanya migrasi dan cleanup legacy tersisa. |
| traffic_daily_visitors | Pencatatan audience 30 hari dan panelnya tidak digunakan lagi. |
| analytics_daily | Tabel analytics legacy, hanya tersisa dukungan cleanup opsional. |
| video_daily_views | Today/Weekly/Monthly Popular Videos sudah dihapus; total movies.views tetap digunakan. |

Tabel-tabel tersebut bukan kebutuhan fitur aktif pada versi ini, tetapi penghapusan
harus diputuskan setelah backup dan pemeriksaan ketergantungan server. Instruksi lama
pernah mempertahankan traffic_daily_player_metrics; jangan menghapusnya hanya karena
ada dalam daftar ini tanpa memastikan riwayatnya memang tidak diperlukan.

Pastikan versi terbaru benar-benar terpasang, bukan hanya HEAD Git berubah. Periksa
cron eksternal, trigger, view, foreign key dan prefix tabel di server. SQL cleanup lama
hanya mencakup traffic_daily_visitors dan analytics_daily; tidak diperluas dalam audit ini.
Jangan menjalankan seluruh migrasi lama hanya untuk cleanup karena migrasi lain bisa
mengubah integrasi/data. Cron analytics:prune melewati tabel yang tidak ada.

Menghapus tabel tidak dengan sendirinya mempercepat halaman. Perbaikan utama adalah
menghentikan query penulisan yang bermasalah; hal ini sudah dilakukan pada c5b43c5.

## Tabel yang tetap diperlukan

- movies, links: konten, URL stream/download, counter dan status kesehatan.
- ads, popup_ad_units: iklan dan konfigurasi monetisasi.
- third_party_apis: UPNShare, VOD, R2 dan custom hostname.
- admin, settings, migrations: akun admin, konfigurasi dan riwayat migrasi.
- genres, movie_genre, series, seasons, series_genre: katalog dan relasi.
- movie_translations, genre_translations, page_translations: fitur terjemahan.
- pages, requests, requests_subscription, failed_movies: halaman, permintaan dan hasil grab.

Jumlah baris nol tidak membuktikan tabel tidak digunakan. Nama tabel lokal yang berbeda
harus dicocokkan dahulu, bukan diasumsikan sama dengan tabel standar aplikasi.

## Pembersihan file dalam repository

Dihapus setelah pemeriksaan referensi di kode:
- app/Libraries/MysqlAudience.php beserta tests/mysql_audience_test.php.
- app/Views/admin/dashboard/charts_js.php.
- app/Views/admin/dashboard/x_panel/charts/visitor_statistics.php.
- app/Views/admin/dashboard/x_panel/daily_player_analytics.php.

Tes MySQL tetap memeriksa live traffic dan cleanup legacy. Migrasi historis tidak
dihapus karena diperlukan riwayat instalasi/rollback. StreamHgClient masih dirujuk
tes kompatibilitas; tidak dihapus tanpa meninjau keseluruhan kontrak tersebut.

File ?? pada server ID bukan otomatis sampah. .well-known, writable/secure-backups,
konfigurasi dan uploads harus dipertahankan. Modul updater/backup remote hasil upload
manual tidak tersedia dalam checkout ini untuk diaudit penuh. Periksa Routes, Services,
cron dan pemanggil server sebelum memindahkan file tersebut. Jangan memakai git clean
untuk pembersihan massal.

## Live Traffic dinonaktifkan

Heartbeat browser, polling/panel dashboard dan model LiveTrafficModel dihapus.
Endpoint lama /traffic/embed hanya mengembalikan tracking=disabled agar tab lama
 tidak menimbulkan error berulang. Pemilihan iklan tidak membaca live_traffic lagi;
 memakai fallback sebelumnya (40% eksplorasi setelah sampel network cukup).
Tabel live_traffic tidak dihapus otomatis. Google Analytics belum dipasang oleh patch
 ini; hook custom footer tetap tersedia untuk tag milik admin.
