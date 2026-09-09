# Bulk Clear Reports dan Export Error Links

Buka Links → Reported Links. Pilih All hosts untuk seluruh laporan, atau hostname untuk laporan link stream dari host tersebut. Tombol memakai pilihan dropdown saat diklik, mencakup semua halaman tabel. Kolom pencarian tabel tidak membatasi operasi ini.

**Bulk Clear Reports** meminta konfirmasi, kemudian mengosongkan reports_not_working dan reports_wrong_link per 250 link. Operasi ini sama dengan Clear manual: tidak mengecek ulang video, tidak memperbaiki URL, tidak menghapus link/movie, dan tidak mengubah is_broken/provider_status/requests. Karena laporan dibersihkan, export terlebih dahulu jika masih diperlukan. Stop menghentikan setelah permintaan yang berjalan selesai; batch yang selesai tidak dibatalkan.

**Export Error Links (Excel)** mengambil seluruh link yang masih memiliki laporan dalam cakupan host. Berisi Judul dan Link Error, bukan hanya link yang terkonfirmasi 404; laporan wrong video juga termasuk. Judul diambil dari movies.title; link tanpa record movie tetap diexport dengan penanda Video tidak ditemukan. File XLSX asli memiliki kolom Judul dan Link Error, header beku dan autofilter. Semua sel disimpan sebagai teks literal, termasuk judul yang menyerupai formula. Export tidak mengubah database. Jika gagal atau dihentikan, tidak ada file parsial yang diunduh.

Kedua aksi menggunakan cursor ID dengan batas max_id saat mulai. Data tidak dikunci sepanjang proses: laporan baru pada ID lama atau clear dari admin lain dapat memengaruhi jumlah akhir. Link baru dengan ID di atas batas tidak ikut. Permintaan backend dibatasi 250 baris; Excel akhir dirakit di browser, sehingga ukuran total export tetap membutuhkan memori browser. Tidak ada panggilan API video host atau cron.

Endpoint POST /admin/reported-link-tools/run mengikuti proteksi login dan origin admin yang sudah ada, memerlukan AJAX, memvalidasi host/action/cursor dan mengirim no-store. Tidak memerlukan migrasi database. Statistik need review dan tabel dimuat ulang setelah clear.

## Duplikat pada export Excel

Pilih Satu per link, Satu per judul, atau Satu per link / judul (default). Penyaringan dilakukan setelah semua batch yang masih memiliki laporan dikumpulkan dalam cakupan host. Baris pertama menurut ID menaik dipertahankan. Perbandingan link mengabaikan spasi di tepi, tetapi mempertahankan kapitalisasi path/ID dan fragment. Perbandingan judul mengabaikan kapitalisasi dan merapikan spasi. Judul kosong/penanda video tidak ditemukan tidak digabung hanya karena tidak memiliki judul. Filter tidak memengaruhi tabel maupun Bulk Clear Reports dan tidak menghapus data database.

Excel dibuat dengan JSZip 3.10.1 yang disertakan lokal beserta lisensinya: https://github.com/Stuk/jszip/tree/v3.10.1. Tidak ada unduhan library tambahan dari CDN saat export. Batas Excel satu sheet 1.048.575 baris data dan 32.767 karakter per sel menghasilkan pesan error jika terlampaui, bukan pemotongan data diam-diam.
