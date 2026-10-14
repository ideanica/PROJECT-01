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

## Koreksi responsive mengikuti `interactive tab hero`

Override yang menyusun ulang mobile/tablet pada `d1a4787` dibatalkan. Stylesheet landing,
profil, auth, dan portal serta markup tracking kembali ke baseline `844f3ee` yang
memakai desain `2d59f0c` (`interactive tab hero`). Susunan, posisi, layering, animasi,
navbar, dan grid mengikuti referensi tersebut; tablet tidak diubah menjadi hero dua kolom.

Perubahan ukuran hanya pada judul tracking di layar maksimal 380px agar tidak terpotong,
dan batas penyusutan kolom form admin maksimal 1023px agar nilai Select2 panjang tidak
memaksa kolom keluar viewport. Integrasi backend dan fitur sebelumnya tetap dipertahankan.

Verifikasi: build produksi berhasil; 22 tes akun, approval, dan portal lulus dengan
232 assertion. Perbandingan geometri dan gaya elemen desktop 1440px pada landing,
tracking, profil, dan register sama dengan baseline. Pemeriksaan browser tambahan
menggunakan database fixture lokal, bukan perubahan data produksi.

Browser memeriksa 64 halaman publik pada delapan lebar 320–1023px serta 62 halaman
customer/admin pada 320 dan 820px, tanpa overflow horizontal halaman atau error JavaScript.
Input harga native pada form admin 820px memiliki isi lebih panjang dari area teks;
nilai tetap dapat diedit melalui scroll internal input, tanpa mengubah grid referensi.

## Sinkronisasi remote sebelum push main/backend (15 Oktober 2026)

Perubahan `origin/backend` sampai `298193e` (termasuk frontend admin dari main)
digabungkan dengan perbaikan tampilan lokal. Payment ledger, shipment group, dan
account claim dipertahankan bersama katalog, konfigurasi pembayaran, validasi
kepemilikan item, serta penguncian buyer pada order yang sudah diproses.
Fixture/assertion lama disesuaikan dengan ledger dan account claim yang baru.
Tidak ada perubahan pada CSS landing, auth, profil, atau markup profil dalam merge.

Verifikasi gabungan: 119 tes lulus dengan 945 assertion; build produksi berhasil;
route list berhasil; 31 halaman customer/admin pada browser mobile mengembalikan
respons normal tanpa overflow atau error JavaScript. Migrasi baru diverifikasi
pada SQLite fixture lokal, tanpa menjalankan migrasi pada database aplikasi utama.
