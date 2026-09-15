# Tahap 19 — Smoke Testing & QA Suite Enforcer

## Status

Selesai diimplementasikan dan diverifikasi (`ADOPTED`).

## Ringkasan

Menyediakan lapisan pengujian asap (*Critical Journey Smoke Test*) lintas 5 role sekolah yang terintegrasi dalam satu skenario terpadu, meningkatkan analisis statis PHPStan/Larastan ke **Level 9 (0 error)**, serta menetapkan protokol QA wajib bagi seluruh AI Agent sebelum menandai pekerjaan selesai.

## Tujuan

1. Memvalidasi bahwa alur end-to-end sekolah (Admin ➔ Guru ➔ Siswa ➔ Guru ➔ Wali Kelas ➔ Wali Murid) berjalan mulus tanpa hambatan relasi, routing, maupun otorisasi.
2. Memastikan ketatnya integritas kode PHP dengan PHPStan Level 9 maksimum tanpa error atau warning.
3. Mencegah AI Agent berikutnya melakukan rilis prematur atau mengabaikan regresi dengan protokol QA imperatif.

## Scope

- Test suite: `tests/Feature/Smoke/CriticalJourneySmokeTest.php`.
- Konfigurasi PHPStan: `phpstan.neon` (level 9) dan `phpstan-baseline.neon`.
- Pedoman agen: `docs/steering/coding-standards.md`, `docs/steering/testing-strategy.md`, `docs/steering/handover.md`.
- Thread diskusi: `docs/discussions/2026-09-15-strategi-smoke-test-dan-gap-test-suite.md`.

## Acceptance Criteria

- [x] Critical Journey Smoke Test mencakup 5 role sekolah dan lolos 100% (136 assertions).
- [x] PHPStan Level 9 berjalan bersih dengan output `[OK] No errors`.
- [x] Seluruh test suite (146 tests) berstatus passed.
- [x] `npm run build` sukses tanpa warning/error kompilasi.
- [x] Aturan QA sebelum handover tercantum jelas dalam dokumentasi kemudi.
