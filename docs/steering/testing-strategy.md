# Testing Strategy — Aksara

## Tujuan

Membuktikan bahwa vertical slice aman secara permission/role, alur bisnis benar, dan regresi kritis tertangkap sebelum demo/deploy — pada stack **Inertia + Vue**.

## Tooling

| Tool | Perintah | Fungsi |
|---|---|---|
| Pest / PHPUnit | `php artisan test` | Feature & unit |
| Larastan | `vendor/bin/phpstan analyse` | Type / static analysis |
| Pint | `vendor/bin/pint` | Format PHP |
| Vite | `npm run build` | Bundle Inertia/Vue/TipTap |
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

### 4. Alur bisnis (smoke)

1. Guru buat rencana → `draft`.
2. Generate AI → `ai_generations` `pending`.
3. Approve → plan `reviewed`, material `draft`.
4. Publish → plan + material `published`.
5. Siswa buka materi → `learning_events`.
6. Siswa submit kuis → `quiz_attempts.score`.
7. Guru isi absensi + evaluasi → upsert.
8. Laporan guru / dashboard wali menampilkan ringkasan.
9. Co-Pilot: ceklis gambar kondisional; apply tanpa `<img>` palsu.
10. Upload TipTap → `storage/app/public/materials/{id}/`.

### 5. Frontend (manual / browser)

- Resize & properti gambar TipTap di `/materials/{id}/edit`.
- Co-Pilot apply create/patch/rewrite tanpa hilang seksi.
- Sidebar RBAC + collapse sesuai permission.

Bukan pengganti feature test otorisasi.

## File test utama

| Area | File |
|---|---|
| Dasar / role / seed | `tests/Feature/AksaraTest.php`, `AksaraHardeningTest.php` |
| Auth Breeze | `tests/Feature/Auth/*`, `ProfileTest.php` |
| Materi + Co-Pilot | `MaterialAiCopilotTest.php`, `MaterialAuthoringTest.php` |
| HTML sanitasi | `tests/Unit/MaterialContentHtmlTest.php` |
| AI model | `tests/Unit/AiModelResolutionTest.php` |
| Plans / pipeline | `CreatePlanTpTest.php`, `LearningPipelineTest.php`, `LearningPlanExportImportTest.php` |
| Referensi | `ReferenceCrudTest.php`, `ReferenceExportImportTest.php` |
| Users / RBAC / settings | `UserManagementTest.php`, `RbacMatrixTest.php`, `SystemSettingsTest.php` |
| Oversight | `AdminOversightTest.php` |

## Cara menjalankan lokal

```bash
php artisan test
php artisan test --filter=MaterialAiCopilotTest
vendor/bin/phpstan analyse
vendor/bin/pint --test
npm run build
```

## Definition of done untuk test

1. Test lama tetap hijau.
2. Minimal satu test baru untuk path kritis yang diubah (permission atau alur).
3. Sentuh Vue/CSS → `npm run build` sukses.
4. Catat perintah + hasil di `handover.md` bila perubahan besar.
5. Jangan mengandalkan “berhasil di browser saja” untuk authorization.

## Yang sengaja ditunda

- Load / performance test.
- E2E browser penuh (Dusk/Playwright).
- Contract test terhadap AI provider nyata (cukup mock + validasi schema).
