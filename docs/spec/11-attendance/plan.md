# Plan — Kehadiran

## Status

| Field | Isi |
|---|---|
| Kode | `11-attendance` |
| Status | selesai / aktif |
| Steering | `business-rules`, ADR-014 |
| Diskusi Asal | `docs/discussions/2026-09-16-analisis-dan-penyempurnaan-fitur-daftar-hadir.md` |

## Ringkasan

Guru mengisi absensi per rencana pembelajaran; ringkasan kehadiran tersedia untuk role dengan `attendance.summary` (Wali Kelas / Guru / Admin). Dilengkapi fitur ekspor multi-format (PDF & Excel), peringatan dini kehadiran `< 75%`, dan antarmuka presensi cepat.

## Tujuan

1. Satu record unik per `(plan_id, student_id)` dengan status `present`, `excused`, `sick`, `absent`.
2. Menyediakan dokumen rekapitulasi siap cetak resmi berkop sekolah dan format Excel untuk laporan dinas pendidikan.
3. Mempercepat pengisian absensi guru melalui fitur *Tandai Semua Hadir* dan counter waktu nyata.

## Acceptance

- [x] Form isi + save upsert
- [x] Halaman summary rekapitulasi
- [x] Permission terpisah `attendance.manage` vs `attendance.summary`
- [x] Ekspor rekapitulasi kehadiran PDF resmi (A4 Landscape berkop & tanda tangan) dan Excel (.xlsx)
- [x] Tombol pintas "Tandai Semua Hadir", "Reset", dan Live Counter status di Form Presensi
- [x] Peringatan dini kehadiran (*early warning badge*) untuk rasio kehadiran `< 75%`
- [x] Test suite `AttendanceExportTest` dan `AttendanceSummaryTest` hijau
- [x] Admin dapat mengakses dan mengedit absensi milik semua guru (bukan hanya owner) — GAP-02
