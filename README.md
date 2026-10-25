# OceanPaws Order

Sistem pengelolaan pesanan Kpop merchandise berdasarkan customer, batch pembelian, produk, dan status progress.

## Alur utama

- Customer dapat mengecek satu pesanan tanpa login melalui kode pesanan.
- Customer dapat mencari username yang didaftarkan admin untuk melihat seluruh riwayat pembeliannya tanpa login.
- Status pesanan mengikuti status batch secara default. Admin dapat memberi override pada satu pesanan atau satu item jika kondisinya berbeda.
- Admin mengelola nama, email, telepon, alamat customer, katalog produk, batch, pesanan, status, dan log perubahan status.
- Produk aktif muncul pada dropdown form pesanan dan otomatis mengisi nama, varian, serta harga awal.

Alur status awal mengikuti pola spreadsheet operasional OceanPaws: `Ordered → Arrived Warehouse → Flight/Sea to Indonesia → Arrived Admin → Siap Distribusi → Selesai`.

## Menjalankan project

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Konfigurasi bawaan menggunakan SQLite. Pastikan ekstensi `pdo_sqlite` dan `sqlite3` aktif, lalu buat file `database/database.sqlite` sebelum migrasi jika belum tersedia.
Seeder lokal membuat akun admin `admin@oceanpaws.test` dan satu akun customer untuk verifikasi alur pada `customer.demo@oceanpaws.test`, keduanya dengan password `password`. Nama customer dan data katalog tampil seperti data operasional biasa. Ganti kredensial sebelum membagikan environment. Seeder rutin `migrate --seed` hanya menyiapkan status dan admin awal; data skenario bisnis dijalankan secara eksplisit dengan `php artisan db:seed --class=DemoBusinessSeeder`. Seeder production memerlukan `INITIAL_ADMIN_NAME`, `INITIAL_ADMIN_EMAIL`, `INITIAL_ADMIN_PASSWORD`, dan `OCEANPAWS_DEMO_CUSTOMER_PASSWORD`.

## Data aplikasi

Seeder `DemoBusinessSeeder` menyiapkan akun customer, group, produk, batch katalog, pesanan lunas/belum lunas/DP, transaksi terverifikasi/menunggu/ditolak, tagihan EMS, shipment, dan histori status yang saling terhubung. Jalankan pada database pengembangan atau pengujian yang memang ditujukan untuk skenario seeder. Seeder memakai konfigurasi metode pembayaran aktif yang sudah tersedia; ia tidak membuat QRIS atau nomor resi palsu. Unggah QRIS asli dari admin sebelum menerima pembayaran customer.

## Login dan registrasi customer

- `/login`: email dan password; tautan **Daftar terlebih dahulu** membuka `/register`.
- Registrasi memerlukan nama, username unik, email unik, pilihan group aktif atau “Belum memilih”, password minimal 8 karakter, dan konfirmasi password. Registrasi tidak menggabungkan data buyer lama otomatis.
- `/admin/login`: diarahkan ke form login yang sama. Login menggunakan email dan password.
- Admin mengelola **Customer / Buyer** (identitas, akun login, detail dan riwayat pesanan) serta **Group** (label, daftar customer, aktif/nonaktif).
- **Kelola Status** memiliki tiga tab: Status Tracking, Status Pembayaran, dan Label Group. Scope lama tetap tersimpan untuk menjaga relasi batch, pesanan, item, dan histori; status tracking baru dapat dipakai lintas perjalanan pesanan. Group disimpan terpisah dari status pesanan.

### Reset password

- Customer dan admin memakai **Lupa password?** pada `/login`, lalu memasukkan email akun.
- Tautan email berlaku 60 menit dan sekali pakai. Password baru mengikuti validasi password kuat; reset tidak mengubah role, group, atau persetujuan registrasi.
- Setelah reset, login kembali dengan password baru. Sesi lama dan token "ingat sesi" tidak lagi berlaku.
- Pengiriman menggunakan mailer Laravel tanpa worker antrean. Atur `APP_URL` ke URL aplikasi yang benar, `MAIL_MAILER=smtp`, serta `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_SCHEME`, dan `MAIL_FROM_ADDRESS` sesuai layanan email. Simpan kredensial hanya di environment.
- Dengan `MAIL_MAILER=log`, email hanya masuk ke log lokal dan tidak dikirim ke inbox. Jangan gunakan mailer ini untuk reset password di production.

### Memperbarui instalasi existing

```bash
git switch backend
git pull origin backend
composer install
php artisan migrate
php artisan optimize:clear
npm ci
npm run build
```

Setelah migrasi, admin perlu membuat minimal satu group aktif agar registrasi tersedia. Akun LINE lama tetap memiliki buyer dan riwayat yang sama, tetapi login LINE dinonaktifkan. Buka **Customer / Buyer → Edit Pelanggan**, periksa username/email, isi password baru dan konfirmasinya, lalu simpan. Jangan membuat buyer baru untuk akun lama. Akun admin existing tetap bisa login dengan email; username fallback yang dibuat migrasi adalah `admin-ID` jika tidak memiliki username buyer yang tersedia.

Group nonaktif tidak dapat dipilih pada registrasi; membership customer lama dipertahankan. Buyer nonaktif tidak dapat login atau melanjutkan sesi yang sudah ada. Tidak ada penghapusan histori atau reset database dalam migrasi ini.

## Pengujian

```bash
php artisan test
npm run build
```

## Deployment ke Vercel

Project sudah dilengkapi dengan `vercel.json`, entrypoint `api/index.php`, dan konfigurasi filesystem serverless. Runtime yang digunakan adalah `vercel-php@0.8.0` (PHP 8.4) agar sesuai dengan dependency Laravel dan Symfony pada `composer.lock`.

### 1. Siapkan database production

SQLite lokal tidak cocok untuk Vercel karena filesystem function tidak persisten. Gunakan database PostgreSQL atau MySQL eksternal, misalnya Neon, Supabase, atau layanan database lain yang dapat diakses dari Vercel.

Untuk PostgreSQL, siapkan nilai berikut:

```text
DB_CONNECTION=pgsql
DB_URL=postgresql://USER:PASSWORD@HOST:5432/DATABASE?sslmode=require
```

Jika tetap menggunakan MySQL/phpMyAdmin, database MySQL harus berada pada
hosting eksternal yang memiliki alamat publik. Contoh konfigurasi Vercel:

```text
DB_CONNECTION=mysql
DB_URL=mysql://USER:PASSWORD@HOST:PORT/DATABASE
```

Untuk Railway, gunakan URL koneksi publik/TCP Proxy sebagai `DB_URL`. Entry
point juga mengenali `MYSQL_PUBLIC_URL` dan `MYSQL_URL`, tetapi hostname private
seperti `*.railway.internal` tidak dapat diakses dari Vercel.

`127.0.0.1` dan `localhost` tidak dapat dipakai dari Vercel. phpMyAdmin hanya
antarmuka untuk mengelola MySQL, bukan server database. Jalankan phpMyAdmin pada
hosting/container terpisah dan arahkan `PMA_HOST` serta `PMA_PORT` ke MySQL
online yang sama. Jangan menaruh kredensial database di repository.

### 2. Tambahkan Environment Variables di Vercel

Tambahkan minimal variabel berikut untuk Production, Preview, dan Development sesuai kebutuhan:

```text
APP_NAME=OceanPaws Order
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://nama-project.vercel.app
DB_CONNECTION=pgsql
DB_URL=postgresql://...
LOG_CHANNEL=stderr
CACHE_STORE=array
SESSION_DRIVER=cookie
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=sync
```

Buat `APP_KEY` dari terminal lokal:

```bash
php artisan key:generate --show
```

Jangan menyimpan `APP_KEY`, URL database, atau password di `vercel.json` maupun repository.

### 3. Jalankan migrasi database production

Jalankan migrasi dari komputer lokal menggunakan URL database direct/non-pooling. Contoh PowerShell:

```powershell
$env:APP_ENV='production'
$env:DB_CONNECTION='pgsql'
$env:DB_URL='postgresql://USER:PASSWORD@HOST:5432/DATABASE?sslmode=require'
php artisan migrate --force
php artisan db:seed --force
```

Sebelum menjalankan seeder pada deployment, isi `INITIAL_ADMIN_NAME`, `INITIAL_ADMIN_EMAIL`, dan `INITIAL_ADMIN_PASSWORD`. Seeder admin hanya membuat akun pertama dan tidak menambah admin kedua jika sudah ada. `DemoBusinessSeeder` memerlukan `OCEANPAWS_DEMO_CUSTOMER_PASSWORD`; jalankan hanya pada database yang memang disiapkan untuk skenario seeder, bukan database operasional pelanggan. Jangan gunakan dump SQL lama di `database/schema` untuk instalasi baru; dump itu berasal dari skema aplikasi versi terdahulu dan tidak mengikuti migrasi terkini.

### 4. Hubungkan repository ke Vercel

Import repository GitHub pada dashboard Vercel dengan pengaturan:

- Framework Preset: `Other`
- Root Directory: `./`
- Build dan Output Directory: biarkan mengikuti `vercel.json`
- Install Command: biarkan otomatis

Setelah environment variables tersimpan, jalankan deploy. Setiap push berikutnya ke branch production akan membuat deployment baru secara otomatis.

### Catatan serverless

- Asset Vite dibangun saat deployment dan dilayani melalui entrypoint serverless.
- View, cache, dan file sementara Laravel diarahkan ke `/tmp`.
- Session menggunakan cookie terenkripsi sehingga login admin tidak bergantung pada filesystem function.
- Upload file permanen harus memakai object storage seperti S3; `/tmp` tidak persisten.
- Migrasi tidak dijalankan otomatis saat build untuk menghindari perubahan database dari Preview Deployment.


### Persetujuan registrasi tanpa group

Registrasi “Belum memilih” tersimpan sebagai permintaan tertunda. Akun belum dapat login atau dipilih untuk pesanan baru. Admin membuka **Customer dan Group → Permintaan registrasi → Detail**, memilih label group aktif, lalu menekan **Tentukan group & setujui akun**. Edit customer biasa tidak menyetujui permintaan. Registrasi dengan group aktif langsung diterima.

Jumlah anggota pada tab Customer dan Label Group menghitung seluruh record customer/buyer yang tergabung, termasuk yang nonaktif. Permintaan tanpa group tidak dihitung dalam group mana pun. Kelola Status hanya memuat tracking dan pembayaran; label dibuat melalui **Customer dan Group → Label Group**. Jalankan `php artisan migrate` setelah memperbarui kode.
