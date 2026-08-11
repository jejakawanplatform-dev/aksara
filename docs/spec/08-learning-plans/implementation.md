# Implementation — Rencana pembelajaran & draf AI

## Artefak

| Area | Path |
|---|---|
| Controller | `app/Http/Controllers/Plans/PlanController.php` |
| Export | `LearningPlanExportController`, `LearningPlanExportImportService` |
| AI | `app/Services/AiDraftService.php` |
| Pages | `Pages/Plans/{Index,Create,Edit,Draft}.vue` |
| UI list | `Pagination`, `IconButton`, `ExportMenu`; ekspor single Excel/Word/PDF |
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

## UI Index (2026-08-11)

- Filter + tabel densitas compact; aksi baris nowrap (slot absensi/kuis/evaluasi sejajar; disabled bila belum terbit).
- Header: `.aksara-toolbar` — `ExportMenu` (Excel/Word/PDF) + impor + Draf AI / Manual.
- Form Create/Edit: aksi kanan (`.aksara-form-actions`).

## Otorisasi

`permission:plans.manage` + kepemilikan `teacher_id` (admin bypass scope).
Preview draf: owner atau admin (`authorizeOwnerOrAdmin`).