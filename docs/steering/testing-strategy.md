# Testing Strategy — Aksara

## Tujuan

Membuktikan bahwa vertical slice aman secara permission/role, alur bisnis benar, dan regresi kritis tertangkap sebelum demo/deploy — pada stack **Inertia + Vue**.

## Tooling

| Tool | Perintah | Fungsi |
|---|---|---|
| Pest / PHPUnit | `php artisan test` | Feature & unit (100% wajib pass) |
| Pest Smoke Test | `php artisan test --filter=CriticalJourneySmokeTest` | Alur kritis lintas 5 role sekolah |
| Larastan | `vendor/bin/phpstan analyse --memory-limit=1G` | Type / static analysis **Level 9 (0 error)** |
| Pint | `vendor/bin/pint --test` | Format PHP |
| Vite | `npm run build` | Bundle Inertia/Vue/TipTap (0 error & warning) |
| CI | `.github/workflows/ci.yml` | PHP 8.4 + MySQL |

## Lapisan pengujian

### 1. Unit / helper

- Enum `UserRole`, helper `User`, sanitasi `MaterialContentHtml`.
- Resolve model AI (`AiModelResolutionTest`).
- Parsing/validasi output AI bila diekstrak ke method murni.

### 2. Schema & seed

- Tabel inti ada; `users.role` ada.
- Akun demo (`admin@`, `naya@`, …) tersimpan setelah seed.

### 3. Feature / otorisasi (prioritas tinggi)

| Kasus | Harapan |
|---|---|
| Guest akses `/plans` | Redirect login |
| Siswa akses `/plans` | 403 |
| Guru akses `/plans` | 200 + Inertia `Plans/Index` (atau setara) |
| Siswa akses materi `draft` | Ditolak / tidak tampil |
| Siswa akses materi `published` | 200 |
| Guru A mengubah rencana Guru B | Ditolak |
| Wali murid melihat anak lain | Tidak ada datanya |
| Quiz attempt kedua | Gagal / dicegah |
| AI mock mode | Generate tanpa API key |
| Matrix `/access` | Hanya `access.manage` |

Untuk respons Inertia, utamakan assert status + permission; boleh `assertInertia(fn …)` bila paket/helpers tersedia di suite.

### 4. Smoke Test Terpadu (Critical Journey Smoke Test)

Pengujian alur kritis bersambung dalam satu eksekusi terpadu (`tests/Feature/Smoke/CriticalJourneySmokeTest.php`):
1. **Admin**: Mengakses Dashboard Admin, Manajemen Pengguna (`/users`), Pengaturan AI System (`/settings`), dan Referensi Kurikulum (`/references`).
2. **Guru**: Mengakses Dashboard Guru, daftar RPP (`/plans`), dan membuka Editor TipTap materi pembelajaran (`/materials/{id}/edit`).
3. **Siswa**: Mengakses Dashboard Siswa, membaca materi (`/materials/{id}`), mencatat `learning_events`, dan mengerjakan kuis interaktif hingga submit skor (`quiz_attempts`).
4. **Guru**: Mengisi absensi kehadiran siswa (`attendance_records`) dan menyimpan evaluasi & refleksi pembelajaran (`teacher_evaluations`).
5. **Wali Kelas**: Mengakses Dashboard Wali Kelas dan membuka rekap kehadiran kelas (`/attendance/summary`).
6. **Wali Murid**: Mengakses Dashboard Wali Murid dan memantau capaian kehadiran dan kuis anak secara transparan.

### 5. Frontend (manual / browser)

- Resize & properti gambar TipTap di `/materials/{id}/edit`.
- Co-Pilot apply create/patch/rewrite tanpa hilang seksi.
- Sidebar RBAC + collapse sesuai permission.

Bukan pengganti feature test otorisasi.

## File test utama

| Area | File |
|---|---|
| Smoke Test Terpadu | `tests/Feature/Smoke/CriticalJourneySmokeTest.php` |
| Dasar / role / seed | `tests/Feature/AksaraTest.php`, `AksaraHardeningTest.php` |
| Auth Breeze | `tests/Feature/Auth/*`, `ProfileTest.php` |
| Materi + Co-Pilot | `MaterialAiCopilotTest.php`, `MaterialAuthoringTest.php` |
| HTML sanitasi | `tests/Unit/MaterialContentHtmlTest.php` |
| AI model | `tests/Unit/AiModelResolutionTest.php` |
| Plans / pipeline | `CreatePlanTpTest.php`, `LearningPipelineTest.php`, `LearningPlanExportImportTest.php` |
| Referensi | `ReferenceCrudTest.php`, `ReferenceExportImportTest.php` |
| Users / RBAC / settings | `UserManagementTest.php`, `RbacMatrixTest.php`, `SystemSettingsTest.php` |
| Oversight | `AdminOversightTest.php` |
| Absensi & Evaluasi | `AttendanceEvaluationInertiaTest.php` |

## Cara menjalankan lokal (QA Gate Wajib)

```bash
# 1. Full Pest Test Suite
php artisan test

# 2. Critical Journey Smoke Test
php artisan test --filter=CriticalJourneySmokeTest

# 3. Static Analysis PHPStan Level 9
vendor/bin/phpstan analyse --memory-limit=1G

# 4. Code Formatting Check
vendor/bin/pint --test

# 5. Frontend Assets Compilation
npm run build
```

## Definition of done untuk test & agen AI

1. Test lama tetap hijau (100% passed).
2. Minimal satu test baru untuk path kritis yang diubah (permission atau alur).
3. Smoke test alur 5 role tetap lulus.
4. PHPStan tetap di **Level 9 dengan 0 errors / 0 warnings**.
5. `npm run build` sukses tanpa error/warning jika menyentuh frontend.
6. Catat perintah + hasil di `handover.md` bila perubahan besar.
7. Dilarang mengandalkan “berhasil di browser saja” untuk authorization.

## Yang sengaja ditunda

- Load / performance test.
- E2E browser penuh (Dusk/Playwright).
- Contract test terhadap AI provider nyata (cukup mock + validasi schema).
