# Verification — Rencana pembelajaran & draf AI

## Lokasi artefak (stack terkini)

| Peran | Path |
|---|---|
| Controller | `app/Http/Controllers/Plans/PlanController.php` |
| Export | `LearningPlanExportController`, `LearningPlanExportImportService` |
| AI | `app/Services/AiDraftService.php` |
| Pages | `resources/js/Pages/Plans/{Index,Create,Edit,Draft}.vue` |
| Models | `LearningPlan`, `AiGeneration`, `AiUsageLog` |
| Tests | `LearningPipelineTest`, `CreatePlanTpTest`, `LearningPlanExportImportTest` |

## Checklist

- [x] Dual-mode create + draft approve/publish
- [x] Scoping guru/admin
- [x] Tests pipeline/export hijau

## Perintah

```bash
php artisan test --filter=LearningPipelineTest
php artisan test --filter=CreatePlanTpTest
php artisan test --filter=LearningPlanExportImportTest
```

## Uji manual

| Langkah | Akun | Harapan |
|---|---|---|
| Buat rencana AI (mock) | guru | draf muncul |
| Approve + publish | guru | materi published |
