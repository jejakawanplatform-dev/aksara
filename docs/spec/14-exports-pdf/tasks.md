# Tasks — Sistem Ekspor Dokumen Multi-Format & PDF Resmi (Blade)

| ID | Task | Prioritas | Status | Catatan |
|---|---|---|---|---|
| T01 | Blade `plans-pdf`, `single-plan-pdf` | P0 | done | Ekspor RPP massal dan satuan |
| T02 | Blade `cp-tp-pdf`, `atp-pdf` | P0 | done | Ekspor dokumen kurikulum |
| T03 | Wire `LearningPlanExportController` | P0 | done | Rute `plans.export*` |
| T04 | Wire `CurriculumExportController` | P0 | done | Rute `references.export.*` |
| T05 | Excel/Word via PhpSpreadsheet/PhpWord | P0 | done | Engine service domain |
| T06 | Tests export/import RPP & Kurikulum | P1 | done | `LearningPlanExportImportTest`, `ReferenceExportImportTest` |
| T07 | Partial kop surat sekolah dinamis | P2 | done | `exports/partials/{kop,styles,print-button}.blade.php` |
| T08 | Blade `material-pdf` & MaterialExportService | P1 | done | Ekspor Materi (PDF, Word, Markdown) |
| T09 | Wire `MaterialExportController` & test | P1 | done | `MaterialExportTest` (11 tests passed) |
| T10 | Blade `attendance-pdf` (A4 Landscape) & AttendanceExportService | P1 | done | Ekspor Rekap Kehadiran (PDF, Excel) |
| T11 | Wire `AttendanceExportController` & test | P1 | done | `AttendanceExportTest` (10 tests passed) |
| T12 | Integrasi UI `ExportMenu.vue` | P1 | done | Komponen popup dropdown multi-format |
