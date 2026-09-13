# Aktifkan / nonaktifkan cek file host

1. Buka **API & R2 Storage**.
2. Pada akun host yang ingin diatur, ubah sakelar di kolom **Cek file host**. Sakelar yang sama tersedia di halaman **Edit**.
3. Tunggu pesan pengaturan berhasil disimpan. Perubahan langsung disimpan tanpa menekan tombol simpan akun API. Tanpa JavaScript, gunakan **Simpan cek file**.

Pengaturan berlaku untuk semua Embed hostnames dalam satu akun API. Untuk mengatur domain secara terpisah, gunakan akun konfigurasi terpisah tanpa hostname tumpang tindih.

| Kondisi | Perilaku |
| --- | --- |
| Cek file aktif | Pemeriksaan admin, cron, laporan kegagalan player dan Bulk Fix dapat memakai host ini selama akun API Active. |
| Cek file nonaktif | Server status/Cek file disembunyikan pada form stream. Admin, cron dan laporan timeout otomatis melewati host ini; Bulk Fix tidak memprosesnya. Link tetap tersedia untuk playback. |
| API Paused | Preferensi sakelar tersimpan, tetapi tidak dijalankan sampai akun API Active kembali. |

ServerDotHost default **nonaktif** untuk menghindari link normal ditandai Broken akibat pemeriksaan HTTP. UPNShare, Custom hostname dan VOD API default **aktif**. Jika diaktifkan, ServerDotHost menggunakan pemeriksaan HTTP terbatas seperti Custom hostname, bukan verifikasi pemutaran video di dalam iframe. Cloudflare R2 tidak memiliki sakelar karena digunakan untuk penyimpanan banner.

Menonaktifkan cek file tidak menonaktifkan akun API. Koneksi/kredensial API, pencarian katalog, Auto Grab, upload R2, dan URL stream tetap tersedia. Kolom **Koneksi API** memeriksa koneksi akun, terpisah dari pengecekan file.

Status Broken lama untuk host nonaktif diabaikan saat memilih stream; flag lama dibersihkan ketika stream dipilih player atau diproses cron health. URL, judul, prioritas dan laporan pengunjung dipertahankan. Form yang sudah terbuka perlu dimuat ulang. Untuk membersihkan flag lama sekaligus secara opsional:

```bash
php spark streams:activate-unregistered
```

Perintah ini mencakup semua host yang tidak diperiksa, tanpa menghubungi URL stream. Menyalakan kembali cek file tidak mengembalikan flag Broken lama yang sudah dibersihkan; status mengikuti pemeriksaan berikutnya.

## Penyimpanan dan pembaruan

Tidak perlu SQL atau migrasi database. Pengaturan disimpan sebagai `host_file_check_<id API>` pada tabel `settings` yang sudah ada. Akun API, kredensial, konfigurasi URL/database dan isi konten tidak diubah saat sakelar disimpan. Pembacaan pengaturan memakai cache selama satu request, tanpa permintaan jaringan tambahan. Hasil pemeriksaan yang masih berjalan diabaikan jika sakelar sudah dinonaktifkan sebelum hasilnya disimpan.

Jika penyimpanan gagal atau respons tidak diterima, muat ulang halaman untuk memastikan status terakhir di server sebelum mencoba lagi. Situs yang memakai kode versi lama perlu menerima pembaruan ini terlebih dahulu.
