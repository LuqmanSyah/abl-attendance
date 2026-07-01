# ABL Attendance Management System

Sistem ini adalah proyek mata kuliah **ABL** berbasis web untuk mengelola dan memonitor kehadiran pegawai/personel lapangan. Fokus utama sistem adalah pencatatan absensi, validasi supervisor, koreksi absensi, dan laporan kehadiran.

Project ini dibangun dari awal menggunakan **Laravel 12** dan **Filament** sebagai admin panel. Sistem tidak menggunakan starter kit seperti `fila-starter`, agar struktur project lebih mudah dipahami dan dikembangkan oleh tim.

---

## 1. Tujuan Project

Tujuan utama project ini adalah membuat sistem kehadiran pegawai yang dapat membantu proses:

* pencatatan absensi masuk dan pulang,
* pendataan pegawai,
* pengelolaan divisi dan jabatan,
* validasi absensi oleh supervisor,
* pengajuan koreksi absensi,
* monitoring kehadiran,
* pembuatan laporan kehadiran.

Project ini bukan HRIS penuh. Fitur seperti payroll, rekrutmen, training, merit system, dan career planning tidak menjadi fokus utama MVP.

---

## 2. Tech Stack

Project menggunakan stack berikut:

```text
Backend / Fullstack : Laravel 12
Admin Panel         : Filament
Database            : MySQL
Frontend Admin      : Filament Panel
Authentication      : Filament Auth
Package Manager     : Composer & NPM
Development Env     : WSL Ubuntu
```

---

## 3. Scope MVP

Fitur utama yang harus dikerjakan pada tahap awal:

### Admin

* Login ke dashboard admin.
* Mengelola data divisi.
* Mengelola data jabatan.
* Mengelola data pegawai.
* Melihat data absensi.
* Melihat laporan kehadiran.

### Pegawai

* Melakukan absensi masuk.
* Melakukan absensi pulang.
* Melihat riwayat absensi pribadi.
* Mengajukan koreksi absensi jika ada kesalahan.

### Supervisor

* Melihat absensi pegawai di bawahnya.
* Memvalidasi absensi pegawai.
* Menyetujui atau menolak koreksi absensi.
* Melihat laporan kehadiran pegawai.

---

## 4. Role User

Sistem memiliki beberapa role utama:

```text
admin
supervisor
employee
```

Penjelasan:

| Role       | Deskripsi                                     |
| ---------- | --------------------------------------------- |
| admin      | Mengelola data master dan seluruh data sistem |
| supervisor | Memvalidasi absensi dan koreksi pegawai       |
| employee   | Melakukan absensi dan mengajukan koreksi      |

---

## 5. Struktur Modul

Modul utama dalam sistem:

```text
User Management
Employee Management
Division Management
Position Management
Attendance Management
Attendance Validation
Attendance Correction
Attendance Report
Dashboard
```

---

## 6. Struktur Database Awal

Tabel utama yang digunakan:

```text
users
employees
divisions
positions
attendance_records
attendance_corrections
```

### Penjelasan Tabel

| Tabel                  | Fungsi                                  |
| ---------------------- | --------------------------------------- |
| users                  | Menyimpan akun login sistem             |
| employees              | Menyimpan data pegawai                  |
| divisions              | Menyimpan data divisi                   |
| positions              | Menyimpan data jabatan                  |
| attendance_records     | Menyimpan data absensi masuk dan pulang |
| attendance_corrections | Menyimpan pengajuan koreksi absensi     |

---

## 7. Relasi Database

Relasi utama:

```text
users 1 - 1 employees
divisions 1 - many employees
positions 1 - many employees
employees 1 - many attendance_records
employees 1 - many attendance_corrections
supervisor employees 1 - many employees
attendance_records 1 - many attendance_corrections
```

---

## 8. Instalasi Project

### 8.1 Clone Repository

```bash
git clone <repository-url>
cd abl-attendance
```

### 8.2 Install Dependency Laravel

```bash
composer install
```

### 8.3 Install Dependency Frontend

```bash
npm install
```

### 8.4 Copy File Environment

```bash
cp .env.example .env
```

### 8.5 Generate Application Key

```bash
php artisan key:generate
```

### 8.6 Setup Database

Jalankan MySQL lewat Docker Compose:

```bash
docker compose up -d
```

Konfigurasi `.env` default untuk Docker:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=13306
DB_DATABASE=abl_attendance
DB_USERNAME=root
DB_PASSWORD=p455w0rd
```

Pastikan container database sudah berjalan:

```bash
docker compose ps
```

### 8.7 Jalankan Migration dan Seeder

```bash
php artisan migrate --seed
```

Seeder akan membuat akun admin lokal:

```text
Email    : admin@example.com
Password : password
```

### 8.8 Buat User Admin Filament Manual

```bash
php artisan make:filament-user
```

Gunakan command ini jika tidak memakai seeder atau ingin membuat akun admin tambahan.

### 8.9 Jalankan Project

Terminal 1:

```bash
php artisan serve
```

Terminal 2:

```bash
npm run dev
```

Akses aplikasi:

```text
http://127.0.0.1:8000
```

Akses admin panel Filament:

```text
http://127.0.0.1:8000/admin
```

---

## 9. Command Setup Project dari Nol

Jika project belum dibuat sama sekali, gunakan command berikut:

```bash
composer create-project laravel/laravel:^12.0 abl-attendance

cd abl-attendance

composer require filament/filament:"^5.0"

php artisan filament:install --panels

php artisan migrate --seed

php artisan make:filament-user

php artisan serve
```

---

## 10. Command Pembuatan Model Awal

Gunakan command berikut untuk membuat model dan migration:

```bash
php artisan make:model Division -m
php artisan make:model Position -m
php artisan make:model Employee -m
php artisan make:model AttendanceRecord -m
php artisan make:model AttendanceCorrection -m
```

Setelah migration diisi, jalankan:

```bash
php artisan migrate
```

---

## 11. Command Pembuatan Filament Resource

Setelah model dan migration selesai, buat resource Filament:

```bash
php artisan make:filament-resource Division --generate
php artisan make:filament-resource Position --generate
php artisan make:filament-resource Employee --generate
php artisan make:filament-resource AttendanceRecord --generate
php artisan make:filament-resource AttendanceCorrection --generate
```

Resource digunakan untuk membuat halaman CRUD di panel admin.

---

## 12. Alur Absensi

Alur dasar absensi:

```text
1. Pegawai login ke sistem.
2. Pegawai melakukan absensi masuk.
3. Sistem menyimpan jam masuk, tanggal, status, lokasi, dan foto jika diperlukan.
4. Pegawai melakukan absensi pulang.
5. Sistem menyimpan jam pulang.
6. Supervisor mengecek data absensi.
7. Supervisor melakukan validasi absensi.
8. Data absensi masuk ke laporan kehadiran.
```

---

## 13. Alur Koreksi Absensi

Alur koreksi absensi:

```text
1. Pegawai melihat riwayat absensi.
2. Jika ada kesalahan, pegawai mengajukan koreksi.
3. Pegawai mengisi alasan koreksi.
4. Supervisor menerima pengajuan koreksi.
5. Supervisor menyetujui atau menolak koreksi.
6. Jika disetujui, data absensi diperbarui.
7. Status koreksi disimpan sebagai arsip.
```

---

## 14. Status Absensi

Status absensi yang digunakan:

```text
present
late
absent
leave
sick
pending
approved
rejected
```

Penjelasan:

| Status   | Arti              |
| -------- | ----------------- |
| present  | Hadir             |
| late     | Terlambat         |
| absent   | Tidak hadir       |
| leave    | Cuti/izin         |
| sick     | Sakit             |
| pending  | Menunggu validasi |
| approved | Disetujui         |
| rejected | Ditolak           |

---

## 15. Rekomendasi Urutan Pengerjaan

Urutan pengerjaan project:

```text
1. Setup Laravel 12
2. Install Filament
3. Setup database
4. Buat user admin
5. Buat migration divisi
6. Buat migration jabatan
7. Buat migration pegawai
8. Buat migration absensi
9. Buat migration koreksi absensi
10. Buat Filament Resource untuk data master
11. Buat fitur absensi
12. Buat fitur validasi supervisor
13. Buat fitur koreksi absensi
14. Buat dashboard laporan
15. Testing
16. Dokumentasi
```

---

## 16. Pembagian Tugas Tim

Rekomendasi pembagian tugas untuk tim:

| Anggota   | Tugas                                      |
| --------- | ------------------------------------------ |
| Anggota 1 | Setup project, environment, dan repository |
| Anggota 2 | Modul user dan role                        |
| Anggota 3 | Modul divisi dan jabatan                   |
| Anggota 4 | Modul pegawai                              |
| Anggota 5 | Modul absensi                              |
| Anggota 6 | Modul validasi supervisor                  |
| Anggota 7 | Modul koreksi absensi dan laporan          |
| Anggota 8 | Testing, dokumentasi, dan presentasi       |

---

## 17. Aturan Pengembangan

Beberapa aturan coding yang perlu diikuti:

* Gunakan nama tabel dalam bentuk plural.
* Gunakan nama model dalam bentuk singular.
* Gunakan migration untuk semua perubahan database.
* Jangan mengubah database manual tanpa migration.
* Gunakan Eloquent Relationship.
* Gunakan Filament Resource untuk CRUD admin.
* Gunakan validasi form pada setiap input.
* Gunakan enum/string status yang konsisten.
* Jangan memasukkan fitur di luar scope MVP tanpa diskusi.

---

## 18. Naming Convention

Contoh penamaan:

```text
Model              : Employee
Migration          : create_employees_table
Controller         : EmployeeController
Filament Resource  : EmployeeResource
Table              : employees
Foreign Key        : employee_id
```

---

## 19. Batasan Project

Fitur yang tidak termasuk MVP:

```text
Payroll
Rekrutmen
Training
Merit system
Career recommendation
Inventory
Multi-company
Microservice
Mobile app native
```

Fitur tersebut boleh dijadikan pengembangan lanjutan, tetapi tidak menjadi fokus utama project saat ini.

---

## 20. Catatan untuk AI Agent

AI Agent harus mengikuti konteks berikut:

```text
Project ini adalah sistem monitoring kehadiran pegawai untuk mata kuliah ABL.

Gunakan Laravel 12 dan Filament.

Jangan gunakan fila-starter.

Jangan buat arsitektur microservice.

Jangan menambahkan fitur HRIS besar seperti payroll, training, merit system, atau career recommendation kecuali diminta.

Fokus utama adalah CRUD data pegawai, divisi, jabatan, absensi, validasi supervisor, koreksi absensi, dan laporan.

Utamakan implementasi yang sederhana, realistis, dan mudah dipresentasikan.

Gunakan struktur Laravel standar.

Gunakan Filament Resource untuk CRUD.

Gunakan migration untuk semua struktur database.

Gunakan relasi Eloquent yang jelas.

Jangan membuat struktur terlalu kompleks.

Jika ada pilihan implementasi, pilih yang paling mudah dipahami oleh mahasiswa dan paling cepat selesai.
```

---

## 21. Target Output Project

Target akhir project:

```text
1. Aplikasi Laravel berjalan normal.
2. Admin dapat login ke Filament panel.
3. Admin dapat mengelola pegawai, divisi, dan jabatan.
4. Pegawai dapat memiliki data absensi.
5. Supervisor dapat memvalidasi absensi.
6. Pegawai dapat mengajukan koreksi absensi.
7. Admin/supervisor dapat melihat laporan kehadiran.
8. Project memiliki dokumentasi yang jelas.
```

---

## 22. Lisensi

Project ini dibuat untuk kebutuhan pembelajaran dan tugas mata kuliah ABL.
