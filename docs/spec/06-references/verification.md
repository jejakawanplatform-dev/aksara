# Verification — Referensi kurikulum & sekolah

## Lokasi artefak (stack terkini)

| Peran | Path |
|---|---|
| Controllers | `References/ReferenceController`, `ReferenceImportController`, `CurriculumExportController` |
| Service | `app/Services/CurriculumExportImportService.php` |
| Page | `resources/js/Pages/References/Index.vue` |
| PDF | `resources/views/exports/cp-tp-pdf.blade.php`, `atp-pdf.blade.php` |
| Tests | `ReferenceCrudTest`, `ReferenceExportImportTest`, `ReferenceBulkActionTest`, `CurriculumAuthorizationTest` |

## Checklist

- [x] Hub `/references` (Inertia)
- [x] Import/export terhubung
- [x] Bulk actions Rombel dan Mapel (`references.rombels.bulk-destroy`, `references.mapel.bulk-destroy`)
- [x] Otorisasi ketat & IDOR / re-parenting protection (`CurriculumAuthorizationTest`)
- [x] Tests hijau

## Perintah

```bash
php artisan test --filter=ReferenceCrudTest
php artisan test --filter=ReferenceExportImportTest
php artisan test --filter=ReferenceBulkActionTest
php artisan test --filter=CurriculumAuthorizationTest
```

## Uji manual

| Langkah | Akun | Harapan |
|---|---|---|
| Buka `/references` | guru | mapel diampu editable |
| Export CP/TP PDF | guru/admin | file terunduh |
