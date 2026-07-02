# Implementation Plan — ABL Attendance Management System

> Rencana implementasi lengkap berdasarkan hasil analisis gap dan kondisi project saat ini.
> Dokumen ini adalah panduan eksekusi tim dari kondisi sekarang hingga MVP siap demo.
>
> Tanggal: 2 Juli 2026
> Versi: 1.1 (diperbarui setelah investigasi kode aktual)

---

## Gambaran Umum

```
Kondisi Saat Ini
├── ✅ Setup project (Laravel 12 + Filament, 3 panel)
├── ✅ Model & migration core (Employee, Position, Division, AttendanceRecord, DutyAssignment, AttendanceCorrection)
├── ✅ Manager absensi (OfficeAttendanceManager, DutyAttendanceManager — termasuk verify())
├── ✅ config/attendance.php — sudah ada, env key: ATTENDANCE_OFFICE_LATITUDE/LONGITUDE/RADIUS_METERS
├── ✅ .env.example — sudah ada dengan key attendance yang benar
├── ✅ Panel Admin — CRUD data master (Division, Position, Employee, User, AttendanceRecord, DutyAssignment, AttendanceCorrection)
├── ✅ Panel Supervisor — DutyAssignmentResource + DutyAttendanceRecordResource (dengan Approve/Reject action)
├── ⚠️  Panel Pegawai — absensi masuk/pulang saja, belum ada riwayat & koreksi
├── ⚠️  Panel Supervisor — belum ada persetujuan koreksi absensi
├── ⚠️  .env — ATTENDANCE_OFFICE_LATITUDE belum diisi (masih kosong)
└── ❌ Laporan kehadiran, testing — belum ada

Target MVP
├── Semua alur absensi berjalan (masuk, pulang, validasi, koreksi)
├── Tiga panel berfungsi sepenuhnya
└── Laporan dasar tersedia
```

---

## FASE 1 — Stabilisasi Core

> **Estimasi:** 0.5 hari kerja
> **Prioritas:** Harus selesai sebelum fase lain dimulai

### 1.1 Set Koordinat Kantor di .env

**Latar Belakang:**
`config/attendance.php` dan `.env.example` sudah ada dengan key yang benar:
```
ATTENDANCE_OFFICE_LATITUDE=
ATTENDANCE_OFFICE_LONGITUDE=
ATTENDANCE_OFFICE_RADIUS_METERS=100
```

Yang belum ada: nilai di `.env` aktual (masih kosong). Jika kosong, `resolveOfficeLocation()` di `OfficeAttendanceManager` akan throw error saat runtime.

**Yang Harus Dilakukan:**

| Aksi | File |
|------|------|
| [UBAH] | `.env` — isi nilai koordinat |

**Nilai default untuk development (Jakarta Pusat):**
```env
ATTENDANCE_OFFICE_LATITUDE=-6.2088
ATTENDANCE_OFFICE_LONGITUDE=106.8456
ATTENDANCE_OFFICE_RADIUS_METERS=100
```

**Verifikasi:** Buka halaman Office Attendance di panel pegawai, pastikan tidak ada error saat submit.

---

### 1.2 Perbaikan Penomoran PROJECT_CONTEXT.md

**File yang diubah:**

| Aksi | File |
|------|------|
| [UBAH] | `docs/PROJECT_CONTEXT.md` |

**Yang dilakukan:**
- Section "9. Command Setup Project dari Nol" (baris 416) → **Section 10**
- Section "8.8 Buat User Admin Filament Manual" dan "8.9 Jalankan Project" → pindah ke bawah Section 8 yang benar (bukan di dalam Section 9)
- Section 10 "Command Pembuatan Model Awal" → **Section 11**
- Section 11 "Command Pembuatan Filament Resource" → **Section 12**
- Section 12 "Alur Absensi" → **Section 13** (dan seterusnya)

**Verifikasi:** Tidak ada dua section dengan nomor yang sama.

---

## FASE 2 — Fitur Panel Pegawai

> **Estimasi:** 2–3 hari kerja
> **Prioritas:** Langsung setelah Fase 1

### 2.1 Halaman Riwayat Absensi

**File yang dibuat:**

| Aksi | File |
|------|------|
| [BUAT] | `app/Filament/Employee/Pages/AttendanceHistory.php` |
| [UBAH] | `app/Providers/Filament/EmployeePanelProvider.php` |

**Spesifikasi:**
- Extend `Filament\Pages\Page` dengan trait `InteractsWithTable`
- Query: `AttendanceRecord::where('employee_id', auth()->user()->employee->id)`
- Kolom tabel:
  - `attendance_date` — Tanggal
  - `attendance_type` — Tipe (Kantor / Dinas)
  - `check_in_at` — Jam Masuk
  - `check_out_at` — Jam Pulang
  - `status` — Status Absensi (Badge warna)
  - `verification_status` — Status Verifikasi (Badge warna)
- Filter: bulan, tipe absensi
- Navigasi: ikon `heroicon-o-calendar-days`, grup "Absensi"

---

### 2.2 Pengajuan Koreksi Absensi

**File yang dibuat:**

| Aksi | File |
|------|------|
| [BUAT] | `app/Filament/Employee/Pages/RequestCorrection.php` |
| [UBAH] | `app/Providers/Filament/EmployeePanelProvider.php` |

**Spesifikasi:**
- Tampilkan daftar koreksi yang pernah diajukan (tabel atas)
- Form pengajuan baru:
  - Select: pilih `attendance_records` milik sendiri (label: tanggal + status)
  - Textarea: alasan koreksi (`reason`)
  - DateTimePicker: `requested_check_in_at` — opsional
  - DateTimePicker: `requested_check_out_at` — opsional
- Validasi: record yang dipilih harus milik pegawai yang login
- Navigasi: ikon `heroicon-o-pencil-square`, grup "Absensi"

**Field `AttendanceCorrection` yang tersedia (dari migration):**
```
employee_id, attendance_record_id, correction_date, type, reason,
requested_check_in_at, requested_check_out_at, status (default: pending),
reviewed_by, reviewed_at, review_notes
```

---

## FASE 3 — Fitur Panel Supervisor

> **Estimasi:** 1–2 hari kerja
> **Prioritas:** Setelah Fase 2
> **Catatan:** Verifikasi absensi dinas (monitoring + approve/reject) sudah selesai via `DutyAttendanceRecordResource`. Yang tersisa hanya persetujuan koreksi.

### 3.1 Status: Sudah Selesai — Monitoring & Verifikasi Absensi Dinas

`DutyAttendanceRecordResource` di panel Supervisor sudah mengandung:
- Query: hanya bawahan langsung via `superior_id`
- Filter: `verification_status`
- Action Approve: modal konfirmasi + textarea catatan → `DutyAttendanceManager::verify(..., 'approved', ...)`
- Action Reject: modal + required textarea alasan → `DutyAttendanceManager::verify(..., 'rejected', ...)`

Tidak perlu dibuat ulang.

### 3.2 Status: Sudah Selesai — DutyAssignment Supervisor

`DutyAssignmentResource` di panel Supervisor sudah ada untuk mengelola penugasan dinas bawahan.

### 3.3 Persetujuan Koreksi Absensi

**File yang dibuat:**

| Aksi | File |
|------|------|
| [BUAT] | `app/Filament/Supervisor/Pages/ReviewCorrections.php` atau `app/Filament/Supervisor/Resources/AttendanceCorrections/` |
| [UBAH] | `app/Providers/Filament/SupervisorPanelProvider.php` |

**Spesifikasi:**
- Query: koreksi pending dari bawahan langsung
  ```php
  AttendanceCorrection::whereHas('employee', fn($q) =>
      $q->where('superior_id', auth()->user()->employee->id)
  )->where('status', 'pending')
  ```
- Kolom tabel: nama pegawai, tanggal koreksi, alasan, `requested_check_in_at`, `requested_check_out_at`, status
- Aksi:
  - **Setujui** → update `attendance_records` sesuai data koreksi yang diminta, ubah `status` koreksi → `approved`, isi `reviewed_by`, `reviewed_at`
  - **Tolak** → ubah `status` koreksi → `rejected` + isi `review_notes`
- Navigasi: ikon `heroicon-o-clipboard-document-check`, grup "Persetujuan"

---

## FASE 4 — Fitur Laporan dan Absensi Non-Hadir

> **Estimasi:** 2–3 hari kerja

### 4.1 Input Absensi Manual (Leave/Sick/Absent)

**File yang dibuat:**

| Aksi | File |
|------|------|
| [UBAH] | Resource `AttendanceRecords` panel Admin — tambah action "Input Tidak Hadir" |
| [UBAH] | Panel Supervisor — tambah action serupa untuk bawahan |

**Spesifikasi:**
- Form: pilih pegawai, tanggal, status (`leave`/`sick`/`absent`), catatan
- Validasi: tidak bisa diinput jika sudah ada `check_in_at` di tanggal tersebut
- Tidak memerlukan GPS

---

### 4.2 Halaman Laporan Kehadiran

**File yang dibuat:**

| Aksi | File |
|------|------|
| [BUAT] | `app/Filament/Admin/Pages/AttendanceReport.php` |
| [BUAT] | `app/Filament/Supervisor/Pages/TeamAttendanceReport.php` |

**Spesifikasi (Admin):**
- Filter: divisi, pegawai, bulan/tahun
- Ringkasan per pegawai: total hadir, telat, tidak hadir, cuti, sakit
- Detail per hari tersedia saat klik nama pegawai
- Tombol Export CSV (opsional)

**Spesifikasi (Supervisor):**
- Sama seperti Admin, tapi dibatasi hanya bawahan langsung

---

## FASE 5 — Dokumentasi dan Pembaruan Kode

> **Estimasi:** 1–2 hari kerja

### 5.1 Update PROJECT_CONTEXT.md

- [ ] Tambahkan penjelasan `DutyAssignment` (tabel, relasi, flow)
- [ ] Tambahkan kebijakan auto-approve absensi kantor di Sec. 12/13
- [ ] Update Sec. 7 (Relasi Database) dengan `duty_assignments`
- [ ] Tandai Sec. 9 & 22 (Sistem Modul) sebagai "Planned — Belum Diimplementasi"
- [ ] Tambahkan catatan bahwa `config/attendance.php` dan `.env.example` sudah ada

### 5.2 Komentar Inline Kode

- [ ] Tambahkan komentar di `OfficeAttendanceManager.php` tentang kebijakan auto-approve
- [ ] Tambahkan docblock ke `DutyAttendanceManager::verify()`
- [ ] Dokumentasikan parameter validasi di `GeoDistance::locationStatus()`

---

## FASE 6 — Testing (Paralel dengan Fase Lain)

> **Estimasi:** 2–3 hari kerja, bisa dikerjakan bersamaan

### 6.1 Factory

| File | Keterangan |
|------|------------|
| `database/factories/AttendanceRecordFactory.php` | Factory absensi dengan semua tipe status |
| `database/factories/DutyAssignmentFactory.php` | Factory penugasan dinas |
| `database/factories/AttendanceCorrectionFactory.php` | Factory koreksi dengan berbagai status |

### 6.2 Feature Tests

| File | Skenario yang Ditest |
|------|---------------------|
| `tests/Feature/OfficeAttendanceTest.php` | Check-in berhasil, check-in duplikat gagal, check-out sebelum check-in gagal, koordinat jauh gagal |
| `tests/Feature/DutyAttendanceTest.php` | Check-in berhasil, penugasan salah pegawai gagal, penugasan expired gagal |
| `tests/Feature/AttendanceCorrectionTest.php` | Pengajuan berhasil, persetujuan update record, penolakan tidak update record |
| `tests/Feature/VerificationTest.php` | Supervisor bisa verif bawahan, tidak bisa verif bukan bawahan |

---

## FASE 7 — Sistem Modul (Opsional, Post-MVP)

> **Estimasi:** 3–5 hari kerja
> **Catatan:** Kerjakan hanya jika ada waktu dan kebutuhan presentasi menuntutnya

### 7.1 Database

```php
// Migration: create_modules_table
Schema::create('modules', function (Blueprint $table) {
    $table->id();
    $table->string('key')->unique();
    $table->string('name');
    $table->string('description')->nullable();
    $table->string('navigation_label')->nullable();
    $table->string('navigation_group')->nullable();
    $table->integer('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

// Migration: create_module_position_table
Schema::create('module_position', function (Blueprint $table) {
    $table->foreignId('module_id')->constrained()->cascadeOnDelete();
    $table->foreignId('position_id')->constrained()->cascadeOnDelete();
    $table->primary(['module_id', 'position_id']);
});
```

### 7.2 Model & Helper

```php
// Position.php — tambahkan relasi
public function modules(): BelongsToMany
{
    return $this->belongsToMany(Module::class);
}

// User.php — tambahkan helper
public function hasModule(string $key): bool
{
    return $this->employee?->position
        ?->modules()
        ->where('key', $key)
        ->where('is_active', true)
        ->exists() ?? false;
}
```

### 7.3 Integrasi Filament

```php
// Di setiap Resource/Page yang perlu dibatasi
public static function shouldRegisterNavigation(): bool
{
    return auth()->user()?->hasModule('key_modul') ?? false;
}

public static function canAccess(): bool
{
    return auth()->user()?->hasModule('key_modul') ?? false;
}
```

---

## Ringkasan Timeline

| Fase | Nama | Estimasi | Prioritas |
|------|------|----------|-----------|
| 1 | Stabilisasi Core | 0.5 hari | 🔴 Harus pertama |
| 2 | Fitur Panel Pegawai | 2–3 hari | 🔴 Segera |
| 3 | Fitur Panel Supervisor (sisa) | 1–2 hari | 🔴 Segera |
| 4 | Laporan & Absensi Non-Hadir | 2–3 hari | 🟡 Penting |
| 5 | Dokumentasi | 1–2 hari | 🟡 Penting |
| 6 | Testing | 2–3 hari | 🟡 Paralel |
| 7 | Sistem Modul | 3–5 hari | 🟢 Opsional |
| **Total** | | **11–19 hari** | |

---

## Urutan Pengerjaan yang Disarankan

```
Hari 1:
  Pagi  : Fase 1 (isi .env, fix dokumen)
  Siang : mulai Fase 2

Hari 2-3 : Fase 2 (panel pegawai: riwayat + koreksi)

Hari 4   : Fase 3 (persetujuan koreksi supervisor)

Hari 5-7 : Fase 4 (laporan + input non-hadir)

Hari 8-9 : Fase 5 (dokumentasi)

Paralel  : Fase 6 (testing — bisa dikerjakan bersamaan Fase 3-5)

Opsional : Fase 7 (sistem modul)
```

---

## Catatan Penting untuk Tim

> **Fase 3 lebih singkat dari perkiraan awal.**
> Verifikasi absensi dinas (monitoring bawahan + Approve/Reject) sudah selesai via `DutyAttendanceRecordResource`.
> Yang tersisa di Fase 3 hanya persetujuan koreksi absensi.

> **Jangan langsung loncat ke Fase 7.**
> Sistem modul adalah fitur kompleks yang tidak berguna jika fitur inti di Fase 1–3 belum berjalan.
> Selesaikan alur dasar terlebih dahulu, baru tambahkan fitur lanjutan.

> **Koordinasikan pengerjaan panel.**
> Fase 2 (panel pegawai) dan Fase 3 (panel supervisor) bisa dikerjakan paralel oleh dua orang berbeda,
> tapi pastikan field `AttendanceCorrection` sudah dikonfirmasi dulu sebelum masing-masing
> membuat halaman di panel masing-masing. Field yang tersedia: `employee_id`, `attendance_record_id`,
> `correction_date`, `type`, `reason`, `requested_check_in_at`, `requested_check_out_at`,
> `status`, `reviewed_by`, `reviewed_at`, `review_notes`.

> **Selalu jalankan `php artisan test` sebelum push.**
> Gunakan branch terpisah per fitur untuk menghindari konflik.
