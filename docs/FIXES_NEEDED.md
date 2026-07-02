# Daftar Perbaikan yang Harus Dilakukan

> Dokumen ini mencatat semua kekurangan dan perbaikan yang perlu diselesaikan
> berdasarkan hasil analisis gap antara `PROJECT_CONTEXT.md` dan kondisi kode aktual.
>
> Tanggal Analisis: 2 Juli 2026
> Terakhir Diperbarui: 2 Juli 2026 (setelah investigasi kode aktual)

---

## Legenda Status

| Simbol | Arti |
|--------|------|
| `[ ]` | Belum dikerjakan |
| `[/]` | Sedang dikerjakan |
| `[x]` | Selesai |
| 🔴 | Kritikal — blocker MVP |
| 🟡 | Sedang — perlu sebelum demo |
| 🟢 | Minor — nice to have |

---

## 🔴 KRITIKAL — Blocker MVP

### FIX-01 · Koordinat Kantor Belum Diisi di .env ✅ Sebagian Selesai

**Kondisi Aktual (setelah investigasi):**
`config/attendance.php` sudah ada dengan env key `ATTENDANCE_OFFICE_LATITUDE`, `ATTENDANCE_OFFICE_LONGITUDE`, `ATTENDANCE_OFFICE_RADIUS_METERS`.
`.env.example` sudah ada dengan key yang benar.

**Masalah yang Tersisa:**
Nilai di `.env` aktual masih kosong. `OfficeAttendanceManager::resolveOfficeLocation()` akan throw error saat koordinat null.

**Yang Harus Dilakukan:**
- [x] Buat file `config/attendance.php` ← sudah ada
- [x] Tambahkan variabel ke `.env.example` ← sudah ada
- [ ] Isi nilai koordinat di `.env` (development: Jakarta Pusat `-6.2088`, `106.8456`, radius `100`)
- [ ] Dokumentasikan di `PROJECT_CONTEXT.md` bagian Setup

**Catatan env key yang benar:**
```env
ATTENDANCE_OFFICE_LATITUDE=-6.2088
ATTENDANCE_OFFICE_LONGITUDE=106.8456
ATTENDANCE_OFFICE_RADIUS_METERS=100
```

---

### FIX-02 · Panel Pegawai Belum Punya Riwayat Absensi

**Masalah:**
Panel `/pegawai` hanya punya halaman absensi masuk/pulang (`OfficeAttendance`, `DutyAttendance`).
Tidak ada halaman untuk melihat riwayat absensi sendiri.

**Dampak:** Pegawai tidak bisa memantau kehadirannya sendiri — alur MVP tidak terpenuhi.

**Yang Harus Dilakukan:**
- [ ] Buat Filament Page `AttendanceHistory` di `app/Filament/Employee/Pages/`
- [ ] Tampilkan tabel `attendance_records` milik pegawai yang login
- [ ] Filter by bulan/tanggal dan tipe absensi
- [ ] Kolom: tanggal, tipe, jam masuk, jam pulang, status, verification_status
- [ ] Daftarkan di `EmployeePanelProvider`

---

### FIX-03 · Panel Pegawai Belum Punya Fitur Pengajuan Koreksi

**Masalah:**
Model `AttendanceCorrection` dan migration sudah ada dengan semua field yang diperlukan,
tapi tidak ada halaman Filament di panel pegawai untuk mengajukan koreksi.

**Dampak:** Alur koreksi (Sec. 13 dokumen) tidak bisa berjalan.

**Yang Harus Dilakukan:**
- [ ] Buat Filament Page `RequestCorrection` di `app/Filament/Employee/Pages/`
- [ ] Tabel: tampilkan koreksi yang sudah pernah diajukan (status pending/approved/rejected)
- [ ] Form pengajuan baru: pilih `attendance_record_id`, isi `reason`, opsional `requested_check_in_at` dan `requested_check_out_at`
- [ ] Validasi: record yang dipilih harus milik pegawai yang login
- [ ] Daftarkan di `EmployeePanelProvider`

**Field `AttendanceCorrection` yang tersedia:**
```
employee_id, attendance_record_id, correction_date, type, reason,
requested_check_in_at, requested_check_out_at, status (default: pending),
reviewed_by, reviewed_at, review_notes
```

---

### FIX-04 · Panel Supervisor — Verifikasi Absensi ✅ Selesai

**Kondisi Aktual (setelah investigasi):**
`DutyAttendanceRecordResource` di panel `/atasan` sudah ada dan lengkap:
- Query sudah filter bawahan langsung via `superior_id`
- Filter `verification_status` tersedia
- Action **Approve** dan **Reject** sudah ada dengan modal konfirmasi dan textarea catatan
- Memanggil `DutyAttendanceManager::verify()` yang sudah terimplementasi

Tidak perlu dikerjakan lagi.

---

### FIX-05 · Panel Supervisor Belum Punya Persetujuan Koreksi Absensi

**Masalah:**
Tidak ada halaman di panel supervisor untuk melihat dan menyetujui/menolak pengajuan
koreksi dari pegawai bawahannya.

**Dampak:** Alur koreksi (Sec. 13) tidak bisa selesai.

**Yang Harus Dilakukan:**
- [ ] Buat Filament Resource untuk `AttendanceCorrection` di `app/Filament/Supervisor/Resources/`
- [ ] Query: hanya koreksi dari bawahan langsung dengan status `pending`
- [ ] Kolom: nama pegawai, tanggal koreksi, alasan, jam diminta (check-in & check-out), status
- [ ] Aksi Setujui: update `attendance_records` sesuai data koreksi, set status → `approved`, isi `reviewed_by` dan `reviewed_at`
- [ ] Aksi Tolak: set status → `rejected`, isi `review_notes`
- [ ] Daftarkan di `SupervisorPanelProvider`

---

## 🟡 SEDANG — Perlu Sebelum Demo

### FIX-06 · `DutyAssignment` Tidak Terdokumentasi di PROJECT_CONTEXT.md

**Masalah:**
Model, migration, dan manager `DutyAssignment` sudah ada di kode, bahkan sudah ada resource
di panel Admin dan Supervisor, tapi tidak dijelaskan di `PROJECT_CONTEXT.md`.

**Yang Harus Dilakukan:**
- [ ] Tambahkan section `DutyAssignment` ke `PROJECT_CONTEXT.md`
- [ ] Jelaskan tujuan, relasi, dan kapan digunakan
- [ ] Update diagram relasi di Sec. 7

---

### FIX-07 · Kebijakan Auto-Approve Absensi Kantor Tidak Terdokumentasi

**Masalah:**
`OfficeAttendanceManager::checkIn()` menyetel `verification_status = 'approved'` langsung,
sementara `DutyAttendanceManager::checkIn()` menyetel `'pending'`.
Kebijakan ini tidak ada di dokumen sehingga berpotensi membingungkan developer lain.

**Yang Harus Dilakukan:**
- [ ] Tambahkan penjelasan kebijakan ini ke `PROJECT_CONTEXT.md` Sec. 12 (Alur Absensi)
- [ ] Beri komentar inline di `OfficeAttendanceManager.php`

---

### FIX-08 · Tidak Ada Mekanisme Absensi Leave/Sick/Absent

**Masalah:**
Dokumen menyebutkan status `leave`, `sick`, `absent` — tapi tidak ada form atau flow
untuk mencatat pegawai yang tidak masuk. Saat ini hanya status `present` yang otomatis
terisi saat check-in.

**Yang Harus Dilakukan:**
- [ ] Buat fitur input manual absensi oleh Admin atau Supervisor
- [ ] Status: `absent`, `leave`, `sick` bisa diinput tanpa GPS
- [ ] Tambahkan validasi agar status ini tidak bisa diisi jika sudah ada check-in

---

### FIX-09 · Penomoran Section di PROJECT_CONTEXT.md Salah

**Masalah:**
Ada **dua Section 9** di dokumen:
- Baris 223: "Catatan Desain: Dashboard dan Modul Tugas per Jabatan"
- Baris 416: "Command Setup Project dari Nol"

Juga ada subsection "8.8" dan "8.9" yang muncul di dalam konten Section 9 (baris 380–413).

**Yang Harus Dilakukan:**
- [ ] Pindahkan subsection "8.8" dan "8.9" ke bawah Section 8 yang benar
- [ ] Renomor Section 9 "Command Setup" → Section 10
- [ ] Section 10 "Command Pembuatan Model Awal" → Section 11
- [ ] Section 11 dan seterusnya maju satu nomor

---

### FIX-10 · Tidak Ada Laporan Kehadiran

**Masalah:**
Target output project menyebutkan "Admin/supervisor dapat melihat laporan kehadiran"
(Sec. 21 poin 7), tapi tidak ada halaman laporan sama sekali.

**Yang Harus Dilakukan:**
- [ ] Buat halaman `AttendanceReport` di panel Admin
- [ ] Filter: per divisi, per bulan, per pegawai
- [ ] Tampilkan: total hadir, telat, tidak hadir, cuti
- [ ] Opsional: export ke CSV/PDF
- [ ] Buat `TeamAttendanceReport` di panel Supervisor (hanya bawahan sendiri)

---

## 🟢 MINOR — Nice to Have

### FIX-11 · Tidak Ada Factory untuk Model Utama

**Masalah:**
Tidak ada `AttendanceRecordFactory`, `DutyAssignmentFactory`, `AttendanceCorrectionFactory`.
Testing akan sangat sulit tanpa factory.

**Yang Harus Dilakukan:**
- [ ] Buat factory untuk `AttendanceRecord`
- [ ] Buat factory untuk `DutyAssignment`
- [ ] Buat factory untuk `AttendanceCorrection`

---

### FIX-12 · Tidak Ada Feature Test

**Masalah:**
Folder `tests/Feature/` dan `tests/Unit/` kosong. Alur absensi dan koreksi
belum punya coverage test sama sekali.

**Yang Harus Dilakukan:**
- [ ] Buat `OfficeAttendanceTest` — alur check-in dan check-out kantor
- [ ] Buat `DutyAttendanceTest` — alur check-in dan check-out dinas
- [ ] Buat `AttendanceCorrectionTest` — alur pengajuan dan persetujuan koreksi
- [ ] Buat `VerificationTest` — supervisor bisa verif bawahan, tidak bisa verif bukan bawahan

---

### FIX-13 · Sistem Modul (Fitur Lanjutan)

**Masalah:**
Sec. 9 dan Sec. 22 di dokumen mendeskripsikan sistem modul dinamis berbasis jabatan,
tapi belum ada implementasinya sama sekali. Ini bukan blocker MVP tapi penting
untuk demo fleksibilitas sistem.

**Yang Harus Dilakukan:**
- [ ] Buat migration `modules` dan `module_position`
- [ ] Buat Model `Module` dengan relasi `BelongsToMany Position`
- [ ] Buat Filament Resource `Module` di panel Admin
- [ ] Tambahkan checklist modul di form Jabatan
- [ ] Implementasi `hasModule()` di `User` model
- [ ] Terapkan `shouldRegisterNavigation()` dan `canAccess()` di resource
- [ ] Dashboard dinamis berbasis modul jabatan

---

## Ringkasan Jumlah Perbaikan

| Prioritas | Total | Selesai | Tersisa |
|-----------|-------|---------|---------|
| 🔴 Kritikal | 5 item | 1 (FIX-04) | 4 item |
| 🟡 Sedang | 5 item | 0 | 5 item |
| 🟢 Minor | 3 item | 0 | 3 item |
| **Total** | **13 item** | **1** | **12 item** |
