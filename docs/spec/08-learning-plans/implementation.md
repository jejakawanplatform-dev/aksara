# Implementation — Rencana pembelajaran & draf AI

## Artefak

| Area | Path |
|---|---|
| Controller | `app/Http/Controllers/Plans/PlanController.php` |
| Export | `LearningPlanExportController`, `LearningPlanExportImportService` |
| AI | `app/Services/AiDraftService.php` |
| Pages | `Pages/Plans/{Index,Create,Edit,Draft}.vue` |
| Models | `LearningPlan`, `AiGeneration`, `AiUsageLog`, `LearningMaterial` |
| Enum | `PlanStatus` (`draft`/`reviewed`/`published`) |
| Trait | `app/Traits/ScopesTeacherOrAdmin.php` |
| Tests | `LearningPipelineTest`, `CreatePlanTpTest`, `LearningPlanExportImportTest` |

## Alur

```text
Create (AI|manual) → draft plan (+ ai_generations pending)
  → Draft approve → reviewed + material draft
  → Draft publish → plan+material published
```

## Otorisasi

`permission:plans.manage` + kepemilikan `teacher_id` (admin bypass scope).
