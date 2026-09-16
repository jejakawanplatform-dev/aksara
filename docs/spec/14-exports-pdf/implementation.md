# Implementation — Sistem Ekspor Dokumen Multi-Format & PDF Resmi (Blade)

## Artefak

| Area | Path | Deskripsi |
|---|---|---|
| Views PDF | `resources/views/exports/{plans-pdf,single-plan-pdf,cp-tp-pdf,atp-pdf,material-pdf,attendance-pdf}.blade.php` | Template cetak A4 (Portrait/Landscape) |
| Partials | `resources/views/exports/partials/{kop,styles,print-button}.blade.php` | Kop surat sekolah, styling print CSS, & tombol print |
| Controllers | `Plans\LearningPlanExportController`, `References\CurriculumExportController`, `Materials\MaterialExportController`, `Attendance\AttendanceExportController` | Endpoint HTTP download berkas |
| Services | `LearningPlanExportImportService`, `CurriculumExportImportService`, `MaterialExportService`, `AttendanceExportService` | Engine parsing data, PhpSpreadsheet, & Word XML |
| UI Component | `resources/js/Components/ui/ExportMenu.vue` | Trigger tombol dropdown popup multi-opsi ekspor |
| Routes | `plans.export`, `plans.export.single`, `references.export.cp-tp`, `references.export.atp`, `materials.export.single`, `attendance.export` | Rute unduh terproteksi permission |
| Tests | `LearningPlanExportImportTest`, `ReferenceExportImportTest`, `MaterialExportTest`, `AttendanceExportTest` | Pest Feature Test (100% lulus) |

## Format Ekspor per Domain

| Domain | Format Didukung | Blade View / Driver | Otorisasi |
|---|---|---|---|
| **Rencana Pembelajaran** | Excel (.xlsx), Word (.docx), PDF (.pdf) | `plans-pdf.blade.php`, `single-plan-pdf.blade.php` | `permission:plans.manage` |
| **Kurikulum (CP/TP/ATP)** | Excel (.xlsx), Word (.docx), PDF (.pdf) | `cp-tp-pdf.blade.php`, `atp-pdf.blade.php` | `permission:references.view` |
| **Materi Pembelajaran** | PDF (.pdf), Word (.docx), Markdown (.md) | `material-pdf.blade.php` (A4 Portrait berkop) | `permission:materials.read\|plans.manage` |
| **Rekapitulasi Presensi** | PDF (.pdf), Excel (.xlsx) | `attendance-pdf.blade.php` (A4 Landscape berkop) | `permission:attendance.summary` |

## Catatan Arsitektur (ADR-014)

- **Blade PDF-Only Exception:** Blade views dilarang digunakan untuk antarmuka web interaktif aplikasi; hanya diizinkan untuk template ekspor cetak PDF.
- **Kop Surat Dinamis:** Membaca profil sekolah dari tabel `system_settings` (`school.name`, `school.npsn`, `school.address`, `school.phone`, `school.headmaster`).
- **Landscape Print:** Khusus berkas presensi (`attendance-pdf`), CSS print diatur ke mode Landscape `@page { size: A4 landscape; margin: 12mm 15mm; }` agar memuat statistik 12-16 kolom tanpa teks terpotong.
- **Tanda Tangan Dinas:** Template presensi dan materi memuat blok tanda tangan Kepala Sekolah dan Guru Pengampu / Wali Kelas yang sinkron dengan data tanggal cetak.
