# ABL Attendance

ABL Attendance adalah aplikasi web sederhana untuk mengelola data pegawai dan kehadiran. Project ini dibuat untuk kebutuhan pembelajaran/mata kuliah ABL dengan fokus pada admin panel, data master, pencatatan absensi, koreksi absensi, dan pengelolaan atasan langsung pegawai.

## Tech Stack

- Laravel 12
- Filament 5
- MySQL
- Vite
- Tailwind CSS

## Fitur Utama

- Login admin melalui Filament panel.
- CRUD divisi, jabatan, pegawai, pengguna, data absensi, dan koreksi absensi.
- Konfigurasi jabatan untuk menentukan apakah pegawai membutuhkan atasan dan apakah jabatan tersebut bisa menjadi atasan.
- Seeder data awal untuk user, divisi, jabatan, dan pegawai.

## Cara Menjalankan Project

Install dependency PHP dan frontend:

```bash
composer install
npm install
```

Siapkan environment:

```bash
cp .env.example .env
php artisan key:generate
```

Jalankan database MySQL dengan Docker Compose:

```bash
docker compose up -d
```

Pastikan konfigurasi database di `.env` sesuai:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=13306
DB_DATABASE=abl_attendance
DB_USERNAME=root
DB_PASSWORD=p455w0rd
```

Jalankan migration dan seeder:

```bash
php artisan migrate --seed
```

Jalankan aplikasi:

```bash
composer run dev
```

Atau jalankan server dan Vite secara terpisah:

```bash
php artisan serve
npm run dev
```

## Akses Aplikasi

- Aplikasi: `http://127.0.0.1:8000`
- Admin panel: `http://127.0.0.1:8000/admin`

Akun admin awal dari seeder:

```text
Email    : admin@example.com
Password : password
```

## Testing

Jalankan test suite:

```bash
php artisan test
```

Format kode PHP dengan Laravel Pint:

```bash
./vendor/bin/pint
```
