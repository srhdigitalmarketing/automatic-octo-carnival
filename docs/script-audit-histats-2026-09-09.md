# Audit ulang setelah penggantian HiStats — 9 September 2026

## Cakupan

Ketiga remote main (automatic-octo-carnival, idokto, enokto) diverifikasi pada commit 7ba845542e1547e5962e4781c99ae0e5c1baf308. Sumber identik diaudit pada checkout lokal. Tidak ada deploy atau perubahan database produksi.

## Temuan dan perbaikan

- Site.update mengisi custom_footer_codes dari custom_header_codes yang sudah di-encode. Header dan footer kini disimpan independen dengan satu encoding base64.
- Panel Custom Codes tidak dimuat oleh form Site walaupun controller menyimpan field-nya. Panel dihubungkan kembali dan field yang tidak dikirim oleh form lama tidak lagi menimpa nilai tersimpan. Mengirim string kosong secara eksplisit tetap menghapus kode tersebut.
- Nilai kosong panel dikonversi menjadi string untuk helper textarea.
- Hapus public/Thumbs.db dan public/uploads/Thumbs.db, metadata thumbnail Windows yang bukan aset aplikasi. Tambahkan Thumbs.db ke .gitignore.

Kode footer yang sudah rusak di database tidak dapat dipulihkan otomatis tanpa kode asli. Setelah deploy, periksa Custom Codes dan tempel ulang kode resmi bila perlu; hindari memasang HiStats ganda.

## Validasi

- Audit diperluas: 948 pemeriksaan sintaks PHP aplikasi/framework/tes dan JavaScript aplikasi/player/tes, semuanya lulus. Setelah perubahan form terakhir, sintaks file terkait diperiksa ulang.
- Regresi akhir mencakup 49 tes standalone dan 4 tes MySQL pada database sementara; lihat hasil eksekusi audit.
- Tes baru menjalankan controller Site dengan header/footer berbeda, footer saja, pengosongan eksplisit dan field yang tidak dikirim.
- Pemeriksaan literal view tidak menemukan file hilang setelah normalisasi awalan slash; dua kandidat awal merupakan file yang ada di direktori General.
- Pemeriksaan diff whitespace lulus.

## File yang dipertahankan

Compatibility view google_analytics.php tetap kosong dan diperlukan oleh template lama saat deployment/OPcache belum seragam. Tidak menjalankan GA4. Endpoint traffic lama juga dipertahankan untuk tab browser lama. Riwayat migrasi, StreamHgClient yang masih dipakai pengujian kompatibilitas, file vendor/framework, konfigurasi, writable dan uploads tidak dihapus berdasarkan dugaan.

Audit lokal tidak membuktikan semua kondisi produksi bebas error. Kredensial API nyata, izin filesystem server, OPcache, skema database tiap website, dan jaringan provider belum diuji pada server. Tidak ada tabel database yang dihapus atau disarankan untuk dihapus dari audit ini.
