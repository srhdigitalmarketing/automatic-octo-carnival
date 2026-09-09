# HiStats

Buka Settings → Site → HiStats. Tempel Counter Code versi async yang lengkap dari akun HiStats, pilih cakupan, lalu aktifkan dan simpan. Default kosong dan nonaktif. Embed / player adalah cakupan awal; opsi publik mencakup halaman tema Pirate dan homepage Storage. Admin tidak dihitung.

Pilih hidden tracker di HiStats untuk statistik tanpa counter visual, atau pilih counter visual untuk ditampilkan pada halaman. Kode resmi disisipkan utuh di akhir halaman, termasuk konfigurasi counter/noscript; loader async berasal dari kode tersebut. Tidak ada polling, cron, atau penulisan statistik pengunjung ke database aplikasi. Script pihak ketiga tetap memiliki biaya jaringan dan CPU, sehingga tidak ada jaminan tanpa overhead.

Kolom ini adalah kode eksekusi untuk administrator tepercaya, sama seperti custom code. Pemeriksaan format mengharuskan loader HiStats async dan menolak document.write, bukan sanitasi JavaScript umum. Hanya tempel kode resmi dari akun sendiri. Jangan pasang lagi lewat custom header/footer. Jika memakai iframe, pilih opsi frame yang sesuai ketika membuat kode di HiStats.

Integrasi GA4 bawaan telah dihapus. Entri ga4_* lama di settings tidak dibaca atau dijalankan; tidak perlu menghapus tabel atau data konten. Kode Google yang pernah ditempel manual di custom header/footer harus dihapus manual. HiStats memakai tiga entri baru dalam settings saat disimpan; tidak memerlukan migrasi database.

Belum ada kode akun yang dipasang. Uji Realtime di HiStats setelah deployment dan aktivasi; ad blocker dapat mencegah tracking. Pengujian otomatis menggunakan respons tiruan tanpa mengirim kunjungan ke HiStats.

Sumber: [HiStats](https://www.histats.com/) dan [pilihan counter](https://www.histats.com/?act=6).

## Error setelah mengganti GA4

Jika log menyebut `Invalid file: partials/google_analytics.php` dari `themes/pirate/embed.php`, server masih menjalankan template lama sementara partial sudah terhapus. Penyebab dapat berupa upload/pull yang tidak lengkap atau kode lama di OPcache.

Update seluruh kode, bukan hanya menghapus partial. Jalankan `git pull --ff-only origin main` dari folder website setelah perbaikan tersedia di main. Jika Git menolak karena perubahan lokal, jangan gunakan reset/clean paksa. Periksa perubahan lokal dahulu. Di aaPanel, restart layanan PHP yang digunakan website untuk memuat ulang OPcache PHP-FPM; reset melalui PHP CLI tidak membersihkan OPcache PHP-FPM.

File `app/Views/partials/google_analytics.php` sekarang hanya compatibility view kosong untuk pemanggilan lama selama deployment. Pertahankan file ini: tidak memuat Google, tidak membaca settings dan tidak mengakses database. Template terbaru hanya memanggil HiStats. Tidak ada tabel atau konten yang perlu dihapus untuk error ini.

Setelah update/restart, buka player dan periksa waktu entri log terbaru. Log lama tetap tersimpan dan bukan bukti bahwa error masih terjadi.
