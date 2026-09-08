# VOD title search

Di API & R2 Storage pilih Add VOD API. Masukkan nama dan hostname saja, misalnya catalog.example, lalu Active dan Save. Maksimal tiga konfigurasi aktif pertama digunakan untuk pencarian. Result menguji respons JSON, bukan ketersediaan setiap video.

Saat admin mengetik Title, server memanggil GET https://HOST/api.php/provide/vod?ac=detail&wd=TITLE. Hasil tampil di area saran Title dan menggantikan pencarian TMDB untuk film selama API VOD aktif. Jika semua konfigurasi VOD paused, pencarian sebelumnya digunakan kembali.

Respons yang didukung: JSON dengan array list, setiap item berisi vod_name, vod_pic, dan vod_content. Pilih hasil untuk mengisi Title, Short Description dan Image URL. Gunakan Grab Image to R2 untuk menyalin poster ke R2. Format tambahan: name, poster_url (fallback thumb_url), description, dan episodes.server_data.*.link_embed. Episodes juga boleh berupa array server. Stream URL unik ditambahkan ke kolom kosong atau kolom baru; link lama dan Video ID tidak ditimpa. Respons dibatasi 2 MiB, 20 hasil per API, timeout 7 detik, cache pencarian 120 detik. Hanya HTTPS hostname publik, tanpa redirect.

Parser diuji dengan sampel JSON pengguna. Koneksi hostname produksi tetap perlu diverifikasi menggunakan Result dan pencarian judul setelah konfigurasi.


## Auto Grab di General

Panel Auto Grab ada di bawah Link reporting. Pilih katalog aktif lalu Mulai Auto Grab. Hanya film dengan status No Image yang diproses (aturan yang sama dengan filter All Videos). Satu request memproses satu film; halaman harus tetap terbuka. Tidak ada cron baru. Tombol Berhenti menghentikan setelah request berjalan selesai. Menjalankan ulang akan melewati film yang kini memiliki image; item gagal/dilewati dapat dicoba lagi.

Kode diambil dari judul kode tunggal atau kode dalam kurung siku di awal judul, dibandingkan persis tanpa membedakan huruf besar. Hasil memakai movie_code, atau kode judul bila movie_code kosong. English-Subtitle ditolak, hasil ambigu dilewati. Hanya poster_url yang dipakai untuk upload R2. Stream embed unik ditambahkan tanpa mengubah link sebelumnya. Banner dan link disimpan dalam transaksi; upload baru dibersihkan bila penyimpanan gagal. Proses memakai lock lokal untuk mencegah dua request auto grab bersamaan pada server yang sama. Panel menampilkan total berhasil/dilewati/gagal dan 200 hasil terakhir, tidak menyimpan log permanen ke database.
