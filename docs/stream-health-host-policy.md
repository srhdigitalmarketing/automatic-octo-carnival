# Pemeriksaan hanya untuk host API terdaftar

Domain stream dicocokkan persis dengan `embed_domains` pada provider aktif `upnshare`, `custom_http`, atau `vod_catalog` di API & R2 Storage. Host R2 penyimpanan, domain yang hanya muncul pada URL API, serta `api_id` lama bukan dasar pencocokan.

- Cron dan pemeriksaan langsung melewati domain tanpa konfigurasi aktif. Tidak ada fallback HTTP atau DNS ke domain tersebut, dan konfigurasi environment lama tidak mengaktifkannya kembali.
- Player dan daftar server tetap menyertakan link tanpa API, walaupun ada flag otomatis `is_broken`, `provider_status`, atau `last_error` lama. Status pada formulir adalah Active. Flag kesehatan lama dipulihkan saat link dipilih atau masuk batch cron; laporan pengguna dan URL tetap dipertahankan.
- Kegagalan player untuk host tanpa API tidak menulis flag broken global. Mekanisme mencoba link berikutnya untuk permintaan pengguna tetap tersedia.
- Host API aktif tetap mengikuti hasil pemeriksaan provider, prioritas, serta pengecualian link terhapus/error/processing.
- Cron tetap memajukan penanda rotasi job agar host yang dilewati tidak menghalangi antrean. Ini tidak menandainya Healthy dan tidak menghapus laporan pengguna.
- Player tidak menunggu pemeriksaan jaringan. Daftar konfigurasi host dibaca satu kali per request.

Tidak ada perubahan URL video, isi movies, atau migrasi database. Sesudah kode diperbarui, link tanpa API yang sebelumnya tersaring oleh flag lama dapat dipilih kembali. Ini memperbaiki penyaringan internal; bukan jaminan video pada situs pihak ketiga tersedia.

## Aktifkan semua link tanpa API yang masih berstatus Broken

Setelah update kode, jalankan dari root website:

```bash
php spark streams:activate-unregistered
```

Perintah memproses bertahap 250 baris, mengosongkan flag/timestamp pemeriksaan lama dan mengatur is_broken=0 hanya untuk stream tanpa host API aktif. Tidak melakukan HTTP/DNS, tidak menghapus laporan, dan tidak mengubah URL, prioritas atau konten. Jika konfigurasi API tidak dapat dibaca, aktivasi dibatalkan. Bisa dijalankan ulang. Tidak diperlukan migrasi tabel.
