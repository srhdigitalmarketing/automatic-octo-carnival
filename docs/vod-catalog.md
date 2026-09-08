# VOD title search

Di API & R2 Storage pilih Add VOD API. Masukkan nama dan hostname saja, misalnya catalog.example, lalu Active dan Save. Maksimal tiga konfigurasi aktif pertama digunakan untuk pencarian. Result menguji respons JSON, bukan ketersediaan setiap video.

Saat admin mengetik Title, server memanggil GET https://HOST/api.php/provide/vod?ac=detail&wd=TITLE. Hasil tampil di area saran Title dan menggantikan pencarian TMDB untuk film selama API VOD aktif. Jika semua konfigurasi VOD paused, pencarian sebelumnya digunakan kembali.

Respons yang didukung: JSON dengan array list, setiap item berisi vod_name, vod_pic, dan vod_content. Pilih hasil untuk mengisi Title, Short Description dan Image URL. Gunakan Grab Image to R2 untuk menyalin poster ke R2. Format tambahan: name, poster_url (fallback thumb_url), description, dan episodes.server_data.*.link_embed. Episodes juga boleh berupa array server. Stream URL unik ditambahkan ke kolom kosong atau kolom baru; link lama dan Video ID tidak ditimpa. Respons dibatasi 2 MiB, 20 hasil per API, timeout 7 detik, cache pencarian 120 detik. Hanya HTTPS hostname publik, tanpa redirect.

Parser diuji dengan sampel JSON pengguna. Koneksi hostname produksi tetap perlu diverifikasi menggunakan Result dan pencarian judul setelah konfigurasi.
