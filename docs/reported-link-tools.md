# Bulk Clear Reports dan Export Error Links

Buka Links → Reported Links. Pilih All hosts untuk seluruh laporan, atau hostname untuk laporan link stream dari host tersebut. Tombol memakai pilihan dropdown saat diklik, mencakup semua halaman tabel. Kolom pencarian tabel tidak membatasi operasi ini.

**Bulk Clear Reports** meminta konfirmasi, kemudian mengosongkan reports_not_working dan reports_wrong_link per 250 link. Operasi ini sama dengan Clear manual: tidak mengecek ulang video, tidak memperbaiki URL, tidak menghapus link/movie, dan tidak mengubah is_broken/provider_status/requests. Karena laporan dibersihkan, export terlebih dahulu jika masih diperlukan. Stop menghentikan setelah permintaan yang berjalan selesai; batch yang selesai tidak dibatalkan.

**Export Error Links (CSV)** mengambil seluruh link yang masih memiliki laporan dalam cakupan host. Berisi Judul dan Link Error, bukan hanya link yang terkonfirmasi 404; laporan wrong video juga termasuk. Judul diambil dari movies.title; link tanpa record movie tetap diexport dengan penanda Video tidak ditemukan. File UTF-8 dengan BOM mendukung Excel, tanda kutip/baris baru di-escape dan awalan formula dinetralkan. Export tidak mengubah database. Jika gagal atau dihentikan, tidak ada file parsial yang diunduh.

Kedua aksi menggunakan cursor ID dengan batas max_id saat mulai. Data tidak dikunci sepanjang proses: laporan baru pada ID lama atau clear dari admin lain dapat memengaruhi jumlah akhir. Link baru dengan ID di atas batas tidak ikut. Permintaan backend dibatasi 250 baris; CSV akhir dirakit di browser, sehingga ukuran total export tetap membutuhkan memori browser. Tidak ada panggilan API video host atau cron.

Endpoint POST /admin/reported-link-tools/run mengikuti proteksi login dan origin admin yang sudah ada, memerlukan AJAX, memvalidasi host/action/cursor dan mengirim no-store. Tidak memerlukan migrasi database. Statistik need review dan tabel dimuat ulang setelah clear.
