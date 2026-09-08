# VOD title search

Di API & R2 Storage pilih Add VOD API. Masukkan nama dan hostname saja, misalnya catalog.example, lalu Active dan Save. Maksimal tiga konfigurasi aktif pertama digunakan untuk pencarian. Result menguji respons JSON, bukan ketersediaan setiap video.

Saat admin mengetik Title, server memanggil GET https://HOST/api.php/provide/vod?ac=detail&wd=TITLE. Hasil tampil di area saran Title dan menggantikan pencarian TMDB untuk film selama API VOD aktif. Jika semua konfigurasi VOD paused, pencarian sebelumnya digunakan kembali.

Respons yang didukung: JSON dengan array list, setiap item berisi vod_name, vod_pic, dan vod_content. Pilih hasil untuk mengisi Title, Short Description dan Image URL. Gunakan Grab Image to R2 untuk menyalin poster ke R2. Format tambahan: name, poster_url (fallback thumb_url), description, dan episodes.server_data.*.link_embed. Episodes juga boleh berupa array server. Stream URL unik ditambahkan ke kolom kosong atau kolom baru; link lama dan Video ID tidak ditimpa. Respons dibatasi 2 MiB, 20 hasil per API, timeout 7 detik, cache pencarian 120 detik. Hanya HTTPS hostname publik, tanpa redirect.

Parser diuji dengan sampel JSON pengguna. Koneksi hostname produksi tetap perlu diverifikasi menggunakan Result dan pencarian judul setelah konfigurasi.


## Auto Grab di General

Panel Auto Grab ada di bawah Link reporting. Pilih katalog aktif lalu Mulai Auto Grab. Hanya film dengan status No Image yang diproses (aturan yang sama dengan filter All Videos). Satu request memproses satu film; halaman harus tetap terbuka. Tidak ada cron baru. Tombol Berhenti menghentikan setelah request berjalan selesai. Menjalankan ulang akan melewati film yang kini memiliki image; item gagal/dilewati dapat dicoba lagi.

Kode diambil dari judul kode tunggal atau kode dalam kurung siku di awal judul, dibandingkan persis tanpa membedakan huruf besar. Hasil memakai movie_code, atau kode judul bila movie_code kosong. English-Subtitle ditolak, hasil ambigu dilewati. Hanya poster_url yang dipakai untuk upload R2. Stream embed unik ditambahkan tanpa mengubah link sebelumnya. Banner dan link disimpan dalam transaksi; upload baru dibersihkan bila penyimpanan gagal. Proses memakai lock lokal untuk mencegah dua request auto grab bersamaan pada server yang sama. Panel menampilkan total berhasil/dilewati/gagal dan 200 hasil terakhir, tidak menyimpan log permanen ke database.


## Cek file menggunakan VOD

Edit VOD API dan isi Embed hostnames dengan domain URL stream (contoh upload18.org). API hostname tetap hostname layanan katalog; endpoint GET /api.php/provide/vod?ac=detail&wd=KODE. Cek file dan cron mingguan memilih konfigurasi berdasarkan embed hostname. Lepaskan hostname dari provider lain atau pause provider tersebut sebelum memetakannya ke VOD.

Link embed dicocokkan persis. Hasil yang ditemukan maupun kosong tetap Unknown dengan keterangan karena JSON katalog tidak memuat status kesehatan file. Status Release tidak dianggap Healthy. HTTP endpoint 404/410/522 dilewati untuk rotasi; 404 masuk Broken link, tanpa klaim Deleted. Status Deleted/Error sebelumnya tidak dipulihkan hanya karena link terdaftar di katalog.


## Grab Latest Video

General memiliki panel Grab Latest Video. Pilih API, masukkan 1–100 (default 5), lalu mulai. Permintaan daftar adalah GET /api.php/provide/vod?ac=detail tanpa wd. Urutan terbaru mengikuti urutan API; maksimal 1000 entri dari respons pertama (batas respons tetap 2 MiB). Jika API memberi kurang dari jumlah target, proses selesai dengan jumlah yang tersedia. Tidak ada pagination otomatis karena kontrak pagination belum diberikan.

Satu video diimpor per request sampai target berhasil tercapai atau daftar habis. Video duplikat berdasarkan ID internal deterministik dari movie_code atau URL stream dilewati. Kode wajib, English-Subtitle ditolak, poster_url dan stream wajib tersedia. Impor mengisi judul berkode, deskripsi, tahun, kualitas, negara, durasi, banner R2 dan link stream. Video ID tt... adalah ID internal yang dibuat sistem, bukan klaim ID IMDb asli. Status video baru Public. Metadata aktor/director/kategori dari API belum dipetakan ke tabel relasi aplikasi. Daftar proses disimpan di cache selama 2 jam dan dibatasi sesi admin. Halaman harus terbuka, tombol Berhenti menyelesaikan request aktif dahulu. Kegagalan transaksi membersihkan upload poster baru.

Pilihan Kategori pada Grab Latest Video dimuat melalui tombol Muat kategori atau ketika API diganti. Pilihan berasal dari field category dalam daftar terbaru yang tersedia, bukan daftar kategori global provider. Semua kategori tidak menerapkan filter; pilihan lain dicocokkan persis di server sebelum antrean impor dibuat.
