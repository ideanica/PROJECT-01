# Operational Recording Gap Analysis

## Tujuan

Dokumen ini menjadi acuan perubahan backend PROJECT-01 agar pencatatan operasional OceanPaws dapat menggantikan pola pencatatan pada spreadsheet `🌊OCEANPAWS 🐾` tanpa mengorbankan data historis buyer.

Sumber audit utama: spreadsheet OceanPaws dengan pola data Batch/RESI/CODE/STATUS/PAY/DETAILS/LAST CO serta sheet PO yang mencatat NAME/GO/MERCHANDISE/MEMBER/KETERANGAN/QTY/PRICE/DP/PELUNASAN/STATUS/PAYMENT.

## Prinsip arsitektur

### 1. Member adalah identitas bisnis permanen

`Member` menyimpan identitas buyer dan menjadi pemilik seluruh `MemberOrder`. `User` hanya kredensial login yang dapat ditambahkan kemudian.

Konsekuensinya:

- admin boleh mencatat order untuk Member aktif walaupun Member belum punya User;
- registrasi buyer lama tidak boleh membuat Member kedua;
- jika identitas registrasi cocok dengan Member lama yang belum memiliki User, registrasi menjadi account claim dan wajib menunggu persetujuan admin;
- setelah claim disetujui, seluruh history lama langsung muncul karena order tetap menunjuk ke Member yang sama.

State konseptual akun:

- `unlinked`: Member belum mempunyai User;
- `pending_claim`: User sudah dibuat untuk Member tetapi masih menunggu approval;
- `linked`: User aktif dan terhubung ke Member.

### 2. Pembayaran adalah ledger, bukan satu kolom

Satu order dapat memiliki banyak pembayaran. `order_payments` menjadi sumber data nominal DP, cicilan tambahan, dan pelunasan.

Nilai turunan:

- `paid_amount` = total payment berstatus verified;
- `remaining_amount` = `total_amount - paid_amount`, minimum 0;
- payment state: unpaid / partial / paid.

Kolom legacy `member_orders.payment_amount` tetap dipertahankan untuk kompatibilitas dan datanya dibackfill menjadi satu ledger entry saat migration.

### 3. Satu Batch dapat memiliki banyak shipment group

`shipment_groups` mewakili kelompok pengiriman operasional seperti EMS, air cargo, sea cargo, handcarry, atau online handcarry.

Setiap shipment group dapat menyimpan:

- nama/label kelompok;
- metode pengiriman;
- RESI / tracking number;
- shipment code;
- status perjalanan;
- DETAILS URL;
- LAST CO;
- catatan.

`OrderItem` dapat menunjuk ke satu shipment group. Dengan ini item dari satu Batch tidak lagi dipaksa mengikuti hanya satu jalur logistik.

### 4. GO dicatat eksplisit

`member_orders.go_number` menyimpan nomor/label GO atau grouping operasional buyer dalam PO. Nilainya berupa string agar dapat menerima angka maupun label historis.

## Gap dan status implementasi

| Area | Kondisi sebelum perubahan | Target | Prioritas | Status |
| --- | --- | --- | --- | --- |
| Buyer tanpa akun | Ditolak untuk order baru | Member aktif boleh order tanpa User | P0 | Implemented in backend |
| Account claim | Registrasi selalu membuat Member baru | Reuse Member lama dan pending approval | P0 | Implemented in backend |
| Payment nominal | Satu `payment_amount` | Multi-entry payment ledger | P0 | Implemented in backend |
| DP/pelunasan | Status ada, nominal historis tidak lengkap | Derived paid/remaining dari ledger | P0 | Implemented in backend |
| Resi/logistik | Tidak ada entity | Shipment group per Batch | P0 | Implemented in backend |
| Multi shipment per PO | Satu status Batch | Banyak shipment group | P0 | Implemented in backend |
| GO | Tidak ada field | `go_number` pada order | P1 | Implemented in backend |
| Shipment per item | Tidak ada | `order_items.shipment_group_id` | P1 | Implemented in backend |
| Import spreadsheet penuh | Hanya NAMA+BATCH | Import item, payment, shipment, GO | P2 | Deferred |
| Public random tracking code | Sequential `order_code` | Random code terpisah | P2 | Deferred |

## Target data model

```text
User (optional login)
  |
  v
Member
  |
  +-- MemberOrder
      |-- go_number
      |-- OrderItem -- optional --> ShipmentGroup
      +-- OrderPayment

Batch
  +-- MemberOrder
  +-- ShipmentGroup
```

## Aturan account claim

Registrasi melakukan pencarian Member berdasarkan exact normalized `username` atau `email`.

- Jika tidak ada kandidat, registrasi membuat Member baru seperti sebelumnya.
- Jika satu Member lama ditemukan dan belum memiliki User, field identitas yang sudah tersimpan tidak boleh bertentangan dengan data registrasi.
- Claim tidak langsung memberi akses login. `registration_pending=true` dan admin harus menyetujui.
- Jika username menunjuk Member A tetapi email menunjuk Member B, registrasi ditolak karena ambigu.
- Jika Member sudah memiliki User, registrasi tidak boleh membuat akun kedua.

Tidak pernah melakukan auto-match hanya dari `display_name`.

## Aturan payment ledger

Tipe awal:

- `dp`
- `installment`
- `settlement`
- `other`

Status:

- `pending`
- `verified`
- `rejected`

Hanya payment `verified` yang dihitung ke `paid_amount`.

Status pembayaran order dapat disinkronkan dari ledger:

- paid = 0 -> `menunggu-dp`
- 0 < paid < total -> `menunggu-pelunasan`
- paid >= total -> `lunas`

Refund tetap menggunakan flow refund order yang sudah ada agar tidak mencampur refund dengan penerimaan kas.

## Aturan shipment group

Satu Batch boleh memiliki nol atau banyak shipment group. Item hanya boleh menunjuk shipment group milik Batch yang sama dengan order-nya.

Method awal bebas-string terkontrol pada UI, dengan rekomendasi nilai:

- `ems`
- `air_cargo`
- `sea_cargo`
- `handcarry`
- `online_handcarry`
- `other`

Status shipment menggunakan `order_statuses` scope `batch`/`all` agar timeline operasional tetap konsisten dengan status yang sudah ada.

## Migration dan compatibility

Migration baru bersifat additive:

- tidak menghapus kolom lama;
- membuat `order_payments`;
- membuat `shipment_groups`;
- menambahkan `member_orders.go_number`;
- menambahkan `order_items.shipment_group_id`;
- membackfill `payment_amount` existing ke ledger jika nilainya lebih dari 0.

Karena kolom legacy tidak langsung dihapus, deployment dapat dilakukan bertahap dan rollback lebih aman.

## Acceptance criteria

1. Admin dapat membuat order untuk Member aktif tanpa User/password.
2. Buyer lama yang kemudian register dapat di-link ke Member yang sama dan order lama tidak berubah `member_id`.
3. Satu order dapat menyimpan lebih dari satu record pembayaran.
4. Sistem dapat menghitung total terbayar dan sisa tagihan dari payment ledger.
5. Satu Batch dapat menyimpan beberapa shipment group dengan resi berbeda.
6. Item dapat ditautkan ke shipment group milik Batch-nya.
7. GO tersimpan sebagai data terstruktur, bukan notes.
8. Data existing tidak hilang saat migration.

## Deferred work

### Full spreadsheet importer

`LegacySpreadsheetImportService` masih perlu fase kedua agar mampu memetakan format spreadsheet lama yang sangat horizontal dan bervariasi. Importer baru harus mempunyai preview, mapping per sheet, duplicate detection, dan idempotency sebelum melakukan write.

### Public tracking secret

`order_code` sequential masih dipertahankan agar kompatibel. Random public tracking code sebaiknya dikerjakan terpisah bersama perubahan URL dan migration link agar tidak memutus link existing.
