# Verification — Export PDF (Blade)

## Lokasi artefak (stack terkini)

| Peran | Path |
|---|---|
| Blade PDF | `resources/views/exports/{plans-pdf,single-plan-pdf,cp-tp-pdf,atp-pdf}.blade.php` |
| Partials | `resources/views/exports/partials/{kop,styles,print-button}.blade.php` |
| Controllers | `LearningPlanExportController`, `CurriculumExportController` |
| Services | `LearningPlanExportImportService`, `CurriculumExportImportService` |
| Routes | `plans.export*`, `references.export.*` |
| Tests | `LearningPlanExportImportTest`, `ReferenceExportImportTest` |

## Checklist

- [x] Empat template PDF di `resources/views/exports/`
- [x] Dipicu controller export (bukan page Vue)
- [x] Tests export/import hijau
- [x] Kop sekolah dari `system_settings` (NPSN/alamat/telp/kepsek)

## Perintah

```bash
php artisan test --filter=LearningPlanExportImportTest
php artisan test --filter=ReferenceExportImportTest
```

## Uji manual

| Langkah | Akun | Harapan |
|---|---|---|
| Export plans PDF | guru | file terunduh |
| Export CP/TP PDF | guru/admin | file terunduh |
