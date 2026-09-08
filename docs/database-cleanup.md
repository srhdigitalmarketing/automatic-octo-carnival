# Pembersihan tabel analytics yang sudah tidak digunakan

Deploy versi terbaru dahulu. Pilih database aplikasi yang benar di phpMyAdmin aaPanel, ekspor traffic_daily_visitors dan analytics_daily jika riwayatnya perlu disimpan, lalu jalankan docs/sql/remove-unused-analytics.sql pada tab SQL.

Tabel yang dihapus:
- traffic_daily_visitors: pencatatan pengunjung harian dan panel audience sudah dinonaktifkan.
- analytics_daily: tabel legacy; kode aktif hanya mengecek keberadaannya saat cleanup.

Jangan hapus traffic_daily_player_metrics (impressions/play clicks harian), live_traffic (pengunjung aktif). Tabel kosong untuk series, seasons, translations, requests dan relasi masih dirujuk oleh model/fitur aplikasi; jumlah baris nol bukan bukti aman dihapus.

SQL ini tidak dijalankan otomatis saat migrate dan tidak mengubah tabel lainnya. Server production belum diperiksa untuk trigger, view, atau aplikasi lain yang mungkin memakai tabel tersebut. Setelah eksekusi, cron analytics:prune tetap berjalan karena melewati tabel yang sudah tidak ada. Rollback ke versi lama yang mencatat audience memerlukan pemulihan tabel dari backup.

Today/Weekly/Monthly Popular Videos telah dihapus. Jalankan php spark migrate pada versi terbaru untuk menghapus video_daily_views beserta riwayatnya. Total movies.views serta traffic_daily_player_metrics tetap dipertahankan.
