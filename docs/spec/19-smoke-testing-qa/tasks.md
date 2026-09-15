# Tasks — Tahap 19 (Smoke Testing & QA Suite Enforcer)

## Selesai

- [x] Diskusi evaluasi kesenjangan test suite (`docs/discussions/2026-09-15-strategi-smoke-test-dan-gap-test-suite.md`).
- [x] Tingkatkan PHPStan ke Level 9 di `phpstan.neon`.
- [x] Generate baseline terisolasi `phpstan-baseline.neon` untuk framework generic Eloquent.
- [x] Buat test feature smoke terpadu di `tests/Feature/Smoke/CriticalJourneySmokeTest.php`.
- [x] Uji alur Admin (dashboard, users, settings, references).
- [x] Uji alur Guru (dashboard, plans, materials edit).
- [x] Uji alur Siswa (dashboard, materials show, learning_events, quiz attempt submit).
- [x] Uji alur Guru lanjutan (absensi kehadiran, evaluasi refleksi).
- [x] Uji alur Wali Kelas (dashboard, attendance summary).
- [x] Uji alur Wali Murid (dashboard, childData pantau nilai kuis & presensi).
- [x] Jalankan full Pest test suite (146 tests passed).
- [x] Jalankan build frontend `npm run build` (sukses tanpa error).
- [x] Update pedoman kemudi `coding-standards.md` dengan Bagian 7 "Quality Assurance (QA) Wajib Sebelum Selesai".
- [x] Update `testing-strategy.md` dan `handover.md` mencatat adopsi QA Level 9 dan Smoke Testing.
- [x] Update status diskusi menjadi `ADOPTED`.

## Debt / Rencana Lanjutan

- [ ] (P3) Tambahkan Browser E2E smoke test menggunakan Playwright untuk interaksi TipTap dan KaTeX.
- [ ] (P3) Tambahkan unit test Vitest untuk composables JavaScript.
