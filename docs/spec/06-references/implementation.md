# Implementation — Referensi kurikulum & sekolah

## Artefak

| Area | Path |
|---|---|
| Controllers | `References/ReferenceController`, `ReferenceImportController`, `CurriculumExportController` |
| Service | `app/Services/CurriculumExportImportService.php` |
| Page | `resources/js/Pages/References/Index.vue` |
| Models | `AcademicYear`, `Semester`, `SchoolClass`, `Subject`, `CurriculumCp`, `CurriculumTp`, `CurriculumAtpItem` |
| PDF Blade | `resources/views/exports/cp-tp-pdf.blade.php`, `atp-pdf.blade.php` |
| Tests | `ReferenceCrudTest`, `ReferenceExportImportTest` |

## UI (2026-08-11)

- Toolbar tab: `.aksara-toolbar`; CP/ATP: `ExportMenu` + impor + tambah.
- Pagination: Rombel, Mapel, ATP (`subjectOptions` untuk dropdown penuh).
- CP accordion collapsible; aksi elemen CP di toolbar kecil.
- Filter mapel inline `!w-auto` agar tidak menutup tombol.

## Otorisasi

- Group route: `permission:references.view`
- Mutasi sensitif / tab admin: `references.manage` + cek mapel diampu di controller
