# Verification — Sistem Ekspor Dokumen Multi-Format & PDF Resmi (Blade)

## Lokasi artefak (stack terkini)

| Peran | Path |
|---|---|
| Blade PDF | `resources/views/exports/{plans-pdf,single-plan-pdf,cp-tp-pdf,atp-pdf,material-pdf,attendance-pdf}.blade.php` |
| Partials | `resources/views/exports/partials/{kop,styles,print-button}.blade.php` |
| Controllers | `Plans\LearningPlanExportController`, `References\CurriculumExportController`, `Materials\MaterialExportController`, `Attendance\AttendanceExportController` |
| Services | `LearningPlanExportImportService`, `CurriculumExportImportService`, `MaterialExportService`, `AttendanceExportService` |
| Routes | `plans.export*`, `references.export.*`, `materials.export.single`, `attendance.export` |
| Tests | `LearningPlanExportImportTest`, `ReferenceExportImportTest`, `MaterialExportTest`, `AttendanceExportTest` |

## Checklist

- [x] Enam template PDF di `resources/views/exports/`
- [x] Dipicu controller export (bukan page Vue)
- [x] Tests feature export seluruh domain hijau (100% pass)
- [x] Kop surat sekolah dinamis membaca `system_settings` (NPSN/alamat/telp/kepsek)
- [x] Ekspor Excel (.xlsx) rapi dengan styling header Aksara Teal (#0D9488)
- [x] Ekspor Word (.docx) dan Markdown (.md) terformat baik

## Perintah Pengujian

```bash
php artisan test --filter=LearningPlanExportImportTest
php artisan test --filter=ReferenceExportImportTest
php artisan test --filter=MaterialExportTest
php artisan test --filter=AttendanceExportTest
```

## Uji manual

| Langkah | Akun | Harapan |
|---|---|---|
| Export plans PDF/Excel/Word | guru | file terunduh dengan format yang sesuai |
| Export CP/TP & ATP PDF | guru/admin | file terunduh dengan kop surat resmi |
| Export Materi (PDF/Word/MD) | guru/siswa | file terunduh; PDF A4 portrait berkop; Word/MD lengkap |
| Export Rekap Presensi (PDF/Excel) | wali kelas/admin | file terunduh; PDF A4 landscape berkop & tanda tangan; Excel rapi |
