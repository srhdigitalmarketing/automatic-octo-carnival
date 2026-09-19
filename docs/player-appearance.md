# Player appearance

Buka **Settings > Player**, pilih **Loading color**, lalu klik **Save player appearance**. Preview memperlihatkan warna spinner sebelum disimpan. Sebelum warna loading diatur, spinner mengikuti warna tombol Play.

Thumbnail ditampilkan utuh sesuai rasio gambar, tanpa blur atau cropping. Ruang kosong di sekeliling gambar memakai latar gelap. Jika thumbnail gagal dimuat, player mencoba default banner sekali; tombol Play tetap tersedia jika keduanya tidak tersedia.

Player menggunakan tombol native, tinggi viewport dinamis, dan iframe yang tetap memiliki ukuran selama loading. Timer host dihentikan ketika halaman berada di latar belakang, lalu dimulai kembali saat halaman aktif. Animasi menghormati preferensi Reduce Motion. Teks status tetap tersedia untuk pembaca layar.

Pengaturan memakai tabel `settings` yang sudah ada dan tidak membutuhkan migrasi. Data movies, links, kredensial, dan pengaturan pengecekan host tidak diubah.

## iPhone

Pengujian otomatis mencakup WebKit dengan viewport dan touch iPhone, portrait/landscape, iframe lambat, thumbnail gagal, dan simulasi suspend/resume. Ini bukan pengujian pada iPhone fisik atau jaringan operator. Playback di dalam iframe pihak ketiga tetap bergantung pada dukungan media dan kebijakan autoplay provider. Pengunjung mungkin perlu menekan Play di player provider.

## Pengujian lokal

Dengan PHP dan Playwright tersedia, pasang browser uji menggunakan `playwright install webkit` dan siapkan Microsoft Edge. Jalankan `node tests/player_appearance_ui_test.js`; set `PHP_BINARY` jika PHP tidak berada di PATH. `PLAYER_SCREENSHOT_DIR` opsional untuk menyimpan screenshot.

`php tests/player_appearance_mysql_test.php 13389` hanya untuk MySQL pengujian terisolasi pada localhost port 13389. Tes membuat dan menghapus database sementara sendiri. Jangan memakai instance database produksi.
