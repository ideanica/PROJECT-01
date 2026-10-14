# Audit integrasi backend dan tampilan

Tanggal: 14 Oktober 2026.

## Dasar pemulihan

Referensi tampilan yang ditentukan user adalah commit `2d59f0c` pada `main`,
dengan caption `interactive tab hero`. Pemulihan awal yang menggunakan `backend`
sebagai referensi telah dikoreksi. Stylesheet landing, navbar, login/register, tracking,
dan JavaScript interaksi dikembalikan ke commit tersebut. Fitur integrasi admin/customer
dan perbaikan validasi backend dipertahankan. Indikator password, pencarian group,
animasi hero, dan scroll reveal menggunakan implementasi dari commit referensi.

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

## Perbaikan responsive setelah baseline `844f3ee`

Perubahan layout dibatasi pada lebar di bawah 1024px, mengikuti breakpoint desktop proyek.
Hero memakai tinggi berdasarkan isi pada mobile, dan komposisi teks/ilustrasi berdampingan
pada tablet. Dua lapisan tab berbagi kolom grid sehingga ukurannya tetap mengikuti isi.
Gutter navbar, profil, tracking, form akun, dan portal menggunakan ukuran fluid yang sama.
Judul tracking, tab tagihan, grid kartu, ukuran kontrol, dan keterbacaan form admin disesuaikan.
Menu mobile menutup saat memilih tautan, menyentuh area luar, atau menekan Escape.

Form edit pesanan sebelumnya memiliki kolom grid intrinsik sekitar 664px meskipun layar
hanya 320px. Kolom sekarang memakai `minmax(0, 1fr)` dan anak grid dapat menyusut;
field catatan terukur sekitar 231px pada layar yang sama tanpa kehilangan isinya.
Tidak ada perubahan pada controller, route, model, schema, atau aturan bisnis dalam tahap ini.

Verifikasi tambahan:

- 88 pemeriksaan halaman publik pada 320, 360, 375, 390, 430, 480, 640, 768, 820, 912, dan 1023px.
- 88 pemeriksaan halaman customer setelah login, termasuk profil/edit/detail, riwayat,
  tagihan/EMS, kategori pesanan, katalog, dan detail pesanan melalui signed URL.
- 160 pemeriksaan halaman admin pada delapan lebar mobile/tablet; tidak ditemukan overflow
  atau konten form yang melebar keluar layar setelah perbaikan grid.
- Menu dan hit target hero diuji dengan sentuhan pada delapan kombinasi viewport,
  termasuk landscape 812 × 375px. Tautan tracking tetap bekerja ketika tab hero beranimasi.
- Geometri dan properti visual elemen desktop 1440px pada landing, tracking, profil,
  dan register sama dengan baseline stylesheet sebelum perubahan responsive.
- Build produksi berhasil; 22 tes akun, approval, dan portal lulus dengan 232 assertion.
