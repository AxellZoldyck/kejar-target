# Backend — Kejar Target API

Laravel REST API adalah satu-satunya jalur akses data bisnis Kejar Target. API memakai Laravel Sanctum (stateful cookie), UUID, tenant scope berdasarkan user terautentikasi, dan PostgreSQL/Supabase pada runtime.

## Persyaratan

- PHP 8.3+ dengan `pdo_pgsql` untuk PostgreSQL.
- Composer 2.
- PostgreSQL 15+ atau project Supabase.

## Menjalankan lokal

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

Isi koneksi PostgreSQL pada `.env`. Untuk Supabase, gunakan connection string/host pooler yang diberikan Supabase dan set `DB_SSLMODE=require`. Frontend default berada di `http://localhost:3000`.

Health check tersedia pada `GET http://localhost:8000/api/v1/health`.

## Demo data

Seeder hanya berjalan pada environment `local`:

```bash
php artisan db:seed
```

Akun development yang dibuat seeder (password seluruhnya `password`):

- `admin@kejartarget.test`
- `spv@demo.test`
- `sales1@demo.test`
- `sales2@demo.test`

Jangan jalankan demo seeder pada production.

## Test dan formatter

Pada instalasi PHP normal:

```bash
php artisan test
vendor/bin/pint --test
```

Environment workspace Codex ini memiliki modul SQLite tetapi tidak mengaktifkannya secara global. Perintah ekuivalen yang dipakai untuk verifikasi adalah:

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: \
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit
```

Pengujian PostgreSQL tetap diperlukan sebelum deployment untuk memverifikasi perilaku row lock/concurrency terhadap engine production.

## Prinsip keamanan

- Jangan mengirim `company_id` dari frontend sebagai sumber tenant.
- Jangan menyimpan token autentikasi di browser storage; gunakan cookie Sanctum + CSRF.
- Storage bukti aktivitas bersifat private dan hanya dilayani melalui endpoint berotorisasi.
- Payment gateway otomatis belum diaktifkan. Checkout MVP mengembalikan instruksi manual dan tidak pernah menandai pembayaran sukses.
