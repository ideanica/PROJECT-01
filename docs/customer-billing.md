# Pesananku dan Tagihan

## Tanggung jawab halaman

- `/order-history` menyimpan semua pesanan customer, termasuk pesanan lunas, selesai, dan refund. Filter lifecycle: Semua, Aktif, Dikirim, Selesai, Refund. Dikirim tidak masuk Aktif; refund tidak masuk Selesai.
- `/billing` adalah pusat pembayaran dengan dua tab: Tagihan Aktif dan Riwayat Pembayaran. Satu order dapat memiliki dua kewajiban aktif: sisa pembayaran pesanan dan EMS & Pajak. Kartu finansial tidak menampilkan thumbnail, produk, varian, jumlah item, atau shipment.
- Detail order tetap menyimpan item, progress, rincian keuangan, pembayaran terverifikasi, EMS yang sudah diterbitkan, dan penanda refund.

## Sumber perhitungan

`MemberOrder::outstanding_obligations` dipakai oleh billing dan badge profil. `PaymentSchedule` menentukan nominal tahap pembayaran berdasarkan `payment_type`; `payment_settings` menyimpan persentase DP global.

- Pembayaran pesanan aktif jika order tidak direfund, `total_amount > 0`, dan `remaining_amount > 0`.
- `remaining_amount` berasal dari total order dikurangi ledger `OrderPayment` berstatus `verified`, selain tipe `ems`. Pembayaran `pending`, `rejected`, dan kolom legacy `payment_amount` tidak mengurangi tagihan. EMS memiliki saldo terpisah dari pesanan; pembayaran EMS yang terverifikasi dikurangkan dari nominal EMS jika alokasi kemudian berubah.
- EMS aktif jika order tidak direfund, nominal EMS positif, sudah dipublish, dan status `unpaid`. EMS `paid`, nol, null, dan draft tidak dihitung.
- Badge Pesananku menghitung semua order. Badge Tagihan menghitung kewajiban: satu order dengan sisa pembayaran dan EMS unpaid menambah dua.
- Total dan jumlah di bagian atas Tagihan mencakup seluruh kewajiban aktif customer, meski pencarian sedang membatasi kartu yang terlihat. Pengurutan nominal membandingkan nilai tiap kewajiban.

Pilihan `payment_type=dp` tanpa pembayaran terverifikasi ditagihkan sebesar total × persentase DP, dibulatkan dua desimal. Setelah DP diverifikasi, tahap berikutnya adalah seluruh sisa pelunasan. Pilihan `full` ditagihkan sebesar seluruh sisa barang sejak awal. Pesanan lama tanpa pilihan pembayaran tetap menggunakan seluruh sisa ledger dan label status existing. Jatuh tempo order tidak dibuat dari tanggal batch; hanya EMS memiliki tanggal jatuh tempo existing.

Form order admin mewajibkan pilihan DP atau Lunas untuk pesanan baru. DP menyimpan snapshot persentase yang aktif saat order dibuat. Order lama tanpa pembayaran masih dapat dilengkapi skemanya; setelah ada record pembayaran, skema tidak dapat diubah.

## Kompatibilitas rute

| Rute lama | Tujuan |
| --- | --- |
| `/orders` | `/order-history` |
| `/orders/unpaid` | `/billing` |
| `/order-history?filter=unpaid` | `/billing` |
| `/billing/orders`, `/billing/ems` | `/billing` |
| `/billing?tab=history` | Tab Riwayat Pembayaran |
| Billing lama `?tab=paid` atau `?tab=history` | `/billing?tab=history` |
| Billing `?tab=refund` | `/order-history?filter=refund` |

Parameter `q` dan `sort` dipertahankan pada redirect. Tautan detail tetap memakai signed URL dengan `return_to` menuju halaman asal beserta query, dan anchor ke ringkasan pembayaran atau EMS. Validasi signature, kepemilikan order, same-origin return URL, serta otorisasi bukti pembayaran tetap berlaku.

## Data historis

Order tanpa total yang diketahui tidak menghasilkan tagihan pembayaran pesanan; data tersebut tetap terlihat dalam Pesananku. Status pembayaran lama tidak mengalahkan ledger. Status refund tetap menutup semua kewajiban customer dari order itu, tanpa menghapus riwayat keuangannya. Tidak ada migrasi atau perubahan otomatis terhadap pembayaran historis.

Metode pembayaran mengikuti konfigurasi batch dan fallback QRIS existing. Tombol Bayar Sekarang muncul pada kewajiban aktif, termasuk EMS setelah harga barang sudah lunas, dan membuka halaman pembayaran tersendiri. Jika QRIS belum dikonfigurasi, informasi untuk menghubungi admin ditampilkan pada halaman tersebut.

## Pusat pembayaran customer

Header menampilkan total outstanding dan jumlah kewajiban aktif. Angka ini tetap sama pada kedua tab dan tidak dibatasi pencarian. Tab aktif memakai kartu finansial dengan nominal dominan, jenis kewajiban, referensi batch/order, status, jatuh tempo jika tersedia, dan tombol Bayar Sekarang. Tombol mempertahankan desain pink/mint, ikon dompet, dan panah, tetapi sekarang berupa tautan ke `/billing/orders/{memberOrder}/payment/{billType}` (order/ems), bukan disclosure. Halaman ini memerlukan login customer pemilik pesanan. Tautan Lihat detail pesanan tetap memakai signed URL.

Riwayat Pembayaran mengambil setiap record `OrderPayment` milik customer, bukan order yang sudah lunas. Hanya nominal positif dengan status `verified` dan tipe pembayaran customer yang dikenal (`dp`, `installment`, `settlement`, `full_payment`, `other`, `ems`) yang ditampilkan. Dua pembayaran dalam satu order menghasilkan dua baris. Tipe refund atau penyesuaian yang tidak dikenal tidak dianggap sebagai pembayaran masuk.

Urutan transaksi selalu terbaru berdasarkan `paid_at`, dengan fallback `created_at` untuk record lama. ID menjadi pembeda urutan jika tanggal sama. Transaksi dikelompokkan per bulan/tahun. Jika kedua tanggal kosong, record tetap ditampilkan pada bagian Tanggal belum tersedia setelah transaksi bertanggal. Pencarian memakai referensi order/batch atau jenis pembayaran, tanpa membutuhkan data produk. Pembayaran asli pada order yang kemudian direfund tetap terlihat dengan penanda Pesanan direfund; ini tidak menambahkan transaksi refund sebagai pembayaran positif.

Pembayaran EMS baru dicatat sebagai `OrderPayment` bertipe `ems` dan tampil pada riwayat setelah diverifikasi. Bukti yang diunggah admin untuk pembayaran di luar alur customer juga masuk sebagai transaksi `pending`; tidak ada jalur yang langsung menandai EMS atau pembayaran pesanan sebagai lunas. Status EMS `paid` historis tetap dihormati tanpa membuat ledger atau tanggal pembayaran sintetis. Rincian EMS yang sudah lunas tetap tersedia pada detail pesanan.

## Pengiriman bukti dan verifikasi admin

Halaman pembayaran memuat QRIS, harga keseluruhan, DP, total terverifikasi, sisa keseluruhan, nominal yang harus dibayar, petunjuk, serta status pending/alasan penolakan. Customer hanya memilih file bukti; tidak ada input tanggal atau catatan. `paid_at` dicatat otomatis memakai waktu server saat bukti baru disimpan, bukan waktu verifikasi admin. Tanggal/catatan yang disisipkan ke request diabaikan. Pengiriman ulang dengan UUID yang sama tidak mengubah timestamp asli. Nominal tidak dapat diedit: backend menghitung ulang dan menolak nominal tidak cocok atau tampilan yang kedaluwarsa. EMS harus sebesar sisa tagihan EMS. File JPG/PNG/WebP/PDF maksimal 4 MB disimpan di disk `local` yang privat; akses bukti memerlukan pemilik pesanan atau admin.

Pengiriman berhasil kembali ke halaman pembayaran terkait. Validasi yang gagal kembali ke form dengan pesan kesalahan; customer perlu memilih ulang file. Halaman untuk tagihan yang sudah selesai/refund menampilkan informasi tanpa form pengiriman baru. Catatan verifikasi/penolakan admin dan catatan historis tetap dipertahankan.

Di menu Transaksi, admin dapat mengatur DP 1–99,99% (nilai awal 50%). Persentase disimpan pada pesanan saat bukti DP pertama dikirim, termasuk jika bukti kemudian ditolak. Perubahan pengaturan hanya memengaruhi DP yang belum memiliki snapshot; transaksi pending dan historis tidak dihitung ulang. DP historis yang sudah terverifikasi langsung berlanjut ke sisa pelunasan. Katalog menampilkan harga DP berdasarkan persentase global yang sama. Migrasi: `2026_10_23_000002_add_payment_dp_settings.php`. `PaymentScheduleTest` memeriksa nominal DP/lunas, penolakan manipulasi, konfigurasi admin, snapshot, dan pembulatan.

Pengiriman selalu membuat status `pending`. Hanya satu bukti pending diperbolehkan per pesanan dan jenis kewajiban. UUID pengiriman mencegah duplikasi saat permintaan diulang, termasuk setelah diverifikasi. Lock batch lalu pesanan menyelaraskan pengiriman/verifikasi dengan perubahan alokasi EMS. Kegagalan transaksi menghapus file yang baru disimpan.

Menu admin `/admin/transactions` menampilkan semua catatan pembayaran, pending di atas, pagination, ringkasan global, serta pencarian customer/pesanan/batch dan filter status/jenis/tanggal kirim. Bukti dan tindakan review berada di halaman detail tersendiri. Verifikasi memeriksa saldo/jadwal terkini, refund, dan keberadaan bukti sebelum mengubah status menjadi `verified`; penolakan memerlukan alasan. Reviewer, waktu, dan catatan disimpan. Keputusan final tidak dapat diulang. Bukti customer tidak dapat diubah/dihapus melalui CRUD pencatatan manual. Pencatatan admin untuk pembayaran di luar alur customer wajib menyertakan bukti dan tetap menunggu review pada menu Transaksi.

DP terverifikasi mengubah tahap menjadi `menunggu-pelunasan`; pelunasan mengubahnya menjadi `lunas`. EMS terverifikasi mengubah status EMS menjadi `paid` tanpa mengurangi saldo barang. Bukti pending tidak mengurangi tagihan atau badge. Customer dapat melihat alasan penolakan dan mengirim bukti baru.

Migrasi tambahan: `2026_10_23_000001_add_customer_payment_review.php`. Migrasi menambah metadata pengiriman/review tanpa mengubah pembayaran historis. `PaymentVerificationTest` menguji alur customer/admin, status dan saldo, keamanan bukti, duplikasi, validasi, filter, dan penolakan.

Badge profil tetap memakai `MemberOrder::outstanding_obligations`, sehingga membuka riwayat atau memiliki banyak transaksi tidak menambah badge. Customer lain, guest, dan admin tidak mendapat data transaksi pribadi customer melalui halaman ini.

## Verifikasi

`OutstandingBillingTest` mencakup kewajiban gabungan, ledger dan status yang tidak sinkron, penyelesaian kewajiban, badge profil, lifecycle, redirect, pencarian/pengurutan nominal, QRIS, history, refund, ownership, signature kedaluwarsa/tampered, dan bukti pembayaran. Tes portal, navigasi, alokasi EMS, serta pencatatan pembayaran existing diperbarui mengikuti rute utama baru.

`BillingFinancialCenterTest` menambahkan pemeriksaan kartu tanpa produk, transaksi terpisah per pembayaran, urutan/grouping tanggal, fallback tanggal, pengecualian pending/rejected/nonpositif serta tampilan refund keluar, keamanan data customer, signed return URL dari tab riwayat, empty state kedua tab, konsistensi summary/badge, QRIS, dan ketiadaan transaksi EMS sintetis.

Refund dana kini hanya dapat dicatat dari payment ledger admin. Sistem membatasi nominal pada seluruh penerimaan positif yang sudah terverifikasi dan belum dikembalikan (termasuk EMS), meminta bukti transfer, menahan refund selama ada transaksi pending, lalu menyimpan transaksi keluar dan menutup status pesanan secara atomik. Form edit pesanan tidak dapat mengubah status menjadi refund secara langsung; transaksi verified juga tidak dapat dihapus. Riwayat billing menampilkan refund sebagai Pengembalian Dana dengan nominal keluar.

## Berkas implementasi

- `app/Models/MemberOrder.php`: perhitungan kewajiban aktif bersama.
- `app/Models/OrderPayment.php`: label jenis transaksi pembayaran customer.
- `app/Http/Controllers/CustomerPortalController.php`: billing gabungan, redirect legacy, dan filter lifecycle.
- `app/Http/Controllers/ProfileController.php`: badge dan agregasi EMS.
- `routes/web.php`: rute utama billing dan kompatibilitas rute lama.
- `resources/views/profile/show.blade.php`: entry Tagihan menuju rute utama.
- `resources/views/portal/billing-list.blade.php`: daftar kewajiban aktif.
- `resources/views/portal/order-history.blade.php` dan `order-list.blade.php`: filter dan daftar lifecycle pesanan.
- `resources/views/tracking/order.blade.php`: rincian refund dan metode pembayaran EMS aktif.
- `resources/views/components/order-payment-method.blade.php`: tombol navigasi pembayaran mengikuti kewajiban aktif.
- `resources/views/portal/payment.blade.php`: halaman QRIS, rincian nominal, unggah bukti, dan status transaksi customer.
- `resources/css/portal-neo-brutalism.css`: billing satu kolom utama tanpa sidebar kategori.
- `tests/Feature/OutstandingBillingTest.php`: regresi aturan baru.
- `tests/Feature/BillingPortalTest.php`, `OrderNavigationTest.php`, `CustomerPortalTest.php`, `OperationalRecordingTest.php`, dan `BatchEmsTaxTest.php`: penyesuaian kontrak billing/navigasi existing.
