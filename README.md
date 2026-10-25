# PO Management API

Backend aplikasi Purchase Order (PO) menggunakan Laravel 13 dan MySQL.

## Fitur fondasi

- Role pengguna: `admin`, `sales`, dan `warehouse`.
- Master data supplier dan produk.
- Pembuatan PO dengan detail item dalam satu transaksi database.
- Pelacakan penerimaan barang per item dan status PO.
- Notifikasi database Laravel.
- Fondasi penyimpanan koneksi Google Calendar per pengguna; token refresh dienkripsi melalui Laravel Crypt.
- Login berbasis session dan endpoint JSON.

## Setup lokal

1. Pastikan PHP 8.3+, Composer, dan MySQL tersedia.
2. Salin konfigurasi contoh jika belum memiliki `.env`:

   ```bash
   copy .env.example .env
   ```

3. Atur `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` sesuai database lokal.
4. Pasang dependency dan buat app key:

   ```bash
   composer install
   php artisan key:generate
   ```

5. Jika ingin membuat admin awal, isi `ADMIN_NAME`, `ADMIN_EMAIL`, dan `ADMIN_PASSWORD` pada `.env` dengan nilai milikmu sendiri, lalu jalankan `php artisan db:seed`. Seeder tidak membuat akun jika variabel tersebut kosong.\n6. Jalankan migration secara aman (tidak menghapus tabel):

   ```bash
   php artisan migrate
   ```

7. Jalankan aplikasi:

   ```bash
   php artisan serve
   ```

Jangan gunakan `migrate:fresh` pada database yang berisi data penting. Seeder tidak menyimpan kredensial contoh secara default. Jangan memakai password produksi yang lemah atau membagikan file `.env`.

## API

Semua endpoint di bawah memakai prefix `/api`. Endpoint selain login dan token CSRF membutuhkan session login.

| Method | Endpoint | Kegunaan |
|---|---|---|
| GET | `/api/csrf-token` | Mendapatkan CSRF token untuk session |
| POST | `/api/login` | Login dengan email dan password |
| GET | `/api/me` | Profil pengguna aktif |
| POST | `/api/logout` | Logout |
| GET | `/api/suppliers` | Daftar supplier |
| POST | `/api/suppliers` | Membuat supplier (admin) |
| GET | `/api/products` | Daftar produk |
| POST | `/api/products` | Membuat produk (admin) |
| GET | `/api/purchase-orders` | Daftar PO |
| POST | `/api/purchase-orders` | Membuat PO dan item |
| GET | `/api/purchase-orders/{id}` | Detail PO |
| POST | `/api/purchase-orders/{id}/receive` | Mencatat penerimaan (admin/gudang) |
| POST | `/api/purchase-orders/{id}/cancel` | Membatalkan PO sesuai izin |
| GET | `/api/notifications` | Daftar notifikasi |
| PATCH | `/api/notifications/{id}/read` | Tandai notifikasi dibaca |
| PATCH | `/api/notifications/read-all` | Tandai semua notifikasi dibaca |
| GET | `/api/google-calendar/status` | Status koneksi kalender pengguna |
| DELETE | `/api/google-calendar/connection` | Putus koneksi kalender |

Untuk request POST/PUT/PATCH/DELETE, gunakan cookie session dan header `X-CSRF-TOKEN` dari endpoint `/api/csrf-token`. Client harus menyimpan cookie session di antara request.

## Status PO

- `OPEN`: belum ada penerimaan.
- `PARTIALLY_RECEIVED`: sebagian barang sudah diterima.
- `RECEIVED`: seluruh barang sudah diterima.
- `CANCELLED`: PO dibatalkan.
- PO terlambat dihitung dari tanggal estimasi dan tidak disimpan sebagai status terpisah.

## Catatan integrasi

Google Calendar belum tersambung ke Google OAuth/API secara end-to-end. Saat ini tersedia model penyimpanan koneksi, status, dan endpoint status/pemutusan koneksi. Untuk sinkronisasi event yang sesungguhnya masih diperlukan Google Cloud project, OAuth consent screen, credential OAuth di konfigurasi lingkungan, callback OAuth, dan proses create/update/delete event.

## Pengujian

Jalankan test suite Laravel setelah dependency terpasang:

```bash
php artisan test
```

Perubahan kode yang di-commit ke GitHub belum dianggap lulus pengujian sampai command ini berhasil dijalankan pada lingkungan project.
