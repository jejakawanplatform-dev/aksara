# Implementation — Export PDF (Blade)

## Artefak

| Area | Path |
|---|---|
| Views | `resources/views/exports/{plans-pdf,single-plan-pdf,cp-tp-pdf,atp-pdf}.blade.php` |
| Partials | `resources/views/exports/partials/{kop,styles,print-button}.blade.php` |
| Controllers | `LearningPlanExportController`, `CurriculumExportController` |
| Services | `LearningPlanExportImportService`, `CurriculumExportImportService` |
| Routes | `plans.export`, `plans.export.single`, `references.export.cp-tp`, `references.export.atp` |
| Tests | `LearningPlanExportImportTest`, `ReferenceExportImportTest` |

## Catatan

- Jangan menambah Blade page UI baru; PDF-only exception.
- Permission mengikuti group plans/references.
- Kop sekolah membaca `school.name` / `npsn` / `address` / `phone` / `headmaster` via `setting()`.
- Single plan export: `excel` | `word` | `pdf` (`plans.export.single`); UI baris Plans memakai `ExportMenu`.
- Curriculum export CP/TP & ATP: format sama; UI Refs memakai `ExportMenu` + template URL `__ID__` / `__FMT__`.
