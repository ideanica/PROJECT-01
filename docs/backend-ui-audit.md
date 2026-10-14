# Audit integrasi backend dan tampilan

Tanggal: 14 Oktober 2026.

## Dasar pemulihan

`main` sebelum perbaikan: `2d59f0c`. Versi referensi tampilan: `backend` pada `66990df`.
Riwayat `backend` sudah menjadi ancestor `main`; pemulihan dilakukan pada resource tampilan,
bukan dengan mengganti seluruh aplikasi atau membuang commit fitur baru.
Profil menggunakan sumber yang sama pada kedua branch. Form akun kembali menggunakan
komponen auth bersama. Indikator password, pencarian group, dan animasi scroll dipertahankan.
Animasi scroll tidak lagi membuat konten bergantung pada IntersectionObserver untuk terlihat.

## Hubungan fitur

| Fitur admin | Hasil pada sisi customer |
| --- | --- |
| Customer dan group | Akun login, label group profil, approval sebelum akun pending dapat dipakai |
| Batch dan visibilitas katalog | Katalog publik; arsip dan batch tersembunyi tidak ditampilkan |
| Variasi, harga DP/lunas, deadline | Informasi katalog; produk batch dapat dipilih pada form pesanan admin |
| Master produk | Nama/varian/harga mengisi item pesanan; snapshot item tampil di riwayat dan detail customer |
| Status batch/pesanan/item | Tracking, timeline, daftar pengiriman dan riwayat customer |
| Status pembayaran dan refund | Tagihan, riwayat pembayaran dan kategori refund |
| Metode pembayaran | Petunjuk dan gambar pembayaran pada tagihan serta detail pesanan |
| Pajak/EMS | Hanya tagihan yang diterbitkan tampil pada portal customer |
| Riwayat perubahan status | Timeline user dan audit operasional admin |

Katalog merupakan informasi publik. Pembuatan pesanan tetap dilakukan admin sesuai alur
aplikasi yang tersedia; perubahan ini tidak menambahkan checkout atau konfirmasi pembayaran otomatis.
Data operasional seperti catatan audit internal dan pengelolaan akun tetap berada pada area admin.

## Invariant yang diperbaiki

- Customer pada pesanan yang telah diproses atau direfund tidak dapat diganti.
- ID item yang dikirim saat edit harus milik pesanan tersebut.
- Produk yang dipilih harus terhubung dengan batch yang dipilih.
- Harga awal item memakai harga lunas pada relasi batch-produk, lalu harga default jika belum ada.
- Satu customer memiliki paling banyak satu pesanan dalam sebuah batch; duplikasi ditolak dengan validasi.
- Metode pembayaran user membaca konfigurasi yang sama dengan admin, tanpa salinan konfigurasi baru.
- Variabel runtime yang sudah tersedia melalui `$_SERVER` tidak ditimpa loader `.env`.

Transaksi pembuatan/perubahan pesanan, otorisasi admin, signed URL tracking, ownership bukti
pembayaran/foto, approval, reset password, dan penyimpanan alamat historis tetap menggunakan
implementasi yang sudah ada. Tidak ada migrasi schema baru atau perubahan data asli pada audit ini.

## Verifikasi

- Suite PHPUnit: 113 tes lulus, 920 assertion; database SQLite dalam memori.
- Pengujian khusus harga batch juga lulus setelah fixture membedakan harga batch dan harga default.
- Vite production build, pemeriksaan syntax PHP, route katalog, dan `git diff --check`.
- Browser Edge headless: halaman publik serta halaman admin/customer setelah login pada 360, 768,
  dan 1440px. Tidak ditemukan overflow horizontal atau error JavaScript pada halaman yang diuji.
- Pemeriksaan browser login menggunakan database fixture terpisah; tidak membuat akun pada database asli.
- Interaksi browser juga memverifikasi pencarian group, indikator password, pemilihan produk,
  pengisian harga, penyimpanan perubahan pesanan, dan total tagihan hasil perubahan.
- Semua migrasi pada database yang dikonfigurasi berstatus sudah dijalankan.

Build masih memberikan peringatan optional font fallback dan URL aset publik yang diselesaikan
saat runtime. Gambar halaman publik yang diperiksa berhasil dimuat.
Ini merupakan verifikasi lokal dan audit alur yang tersedia, bukan pengujian deployment produksi,
load test, atau pembuktian bahwa setiap kemungkinan kondisi konkurensi telah tercakup.
