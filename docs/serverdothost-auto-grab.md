# Auto Grab Stream Links — ServerDotHost

1. Di **API & R2 Storage**, tambahkan/aktifkan ServerDotHost. Isi token dengan izin `videos:read` dan domain embed yang dipakai, misalnya `bobaplayer.com`. Gunakan fitur cek koneksi untuk memastikan token diterima.
2. Buka **Settings → General → Auto Grab Stream Links (ServerDotHost)**.
3. Pilih akun API lalu klik **Mulai Grab Stream Links**. Biarkan halaman terbuka. Tombol **Berhenti** menghentikan proses setelah permintaan saat ini selesai.

Proses memeriksa Movies dan Episodes yang sudah ada, termasuk video yang sudah memiliki poster atau link lain. Judul harus sama persis setelah normalisasi spasi dan huruf besar/kecil. Tanda baca, nomor, dan keterangan subtitle tetap diperhitungkan. Tidak memakai pencocokan sebagian atau menebak URL dari UUID.

Satu hasil judul yang unik harus berstatus `processing_status=ready`. Detail video diambil lagi untuk mendapatkan `embed_url` terbaru. Video yang belum diunggah/tidak ditemukan, masih diproses, diblokir, tanpa embed yang diizinkan, atau memiliki beberapa hasil judul sama akan dilewati. `visibility=private` tidak otomatis ditolak jika API memang menyediakan link berbagi embed. Halaman daftar yang belum menyediakan embed dapat dilengkapi dari endpoint detail.

Pencarian berjalan satu halaman per permintaan, maksimal 20 halaman per judul. Jika lebih, video dilewati karena hasil unik belum dapat dipastikan. Daftar yang berubah jumlah halamannya, error autentikasi, batas permintaan atau gangguan API menghentikan proses; tidak dilanjutkan dengan ribuan permintaan gagal.

Hanya stream link baru yang ditambahkan (prioritas awal 100 jika kolom tersedia). Link yang sama pada video yang sama dilewati, termasuk status/report lama. Tidak mengganti judul, poster, konten, URL atau prioritas link lama, dan tidak menghapus laporan. Tidak membuat video baru atau tabel baru; tidak memerlukan R2. Perubahan judul/penghapusan lokal selama proses menyebabkan hasil tersebut dilewati.

Batch memakai snapshot ID video saat dimulai, session owner, cache 2 jam dan lock proses. Tidak menyimpan token API dalam job atau mengirimkannya ke browser. Panggilan API dibatasi satu per permintaan admin, dengan jeda 300 ms antarlangkah. Tidak ada proses tambahan pada halaman player/pengunjung atau cron otomatis. Jika halaman ditutup, jalankan lagi; pengecekan duplikat mencegah link yang sudah tersimpan ditambahkan ulang.
