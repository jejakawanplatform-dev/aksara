# Verifikasi — Tahap 19 (Smoke Testing & QA Suite Enforcer)

## Lokasi Artefak

| Artefak | Lokasi Path |
|---|---|
| Smoke Test | `tests/Feature/Smoke/CriticalJourneySmokeTest.php` |
| PHPStan Config | `phpstan.neon` (Level 9 murni pada `app/`) |
| QA Rules & DoD | `docs/steering/coding-standards.md` |
| QA Test Protocol | `docs/steering/testing-strategy.md` |

## Perintah Verifikasi Otomatis

### 1. Critical Journey Smoke Test
```bash
php artisan test --filter=CriticalJourneySmokeTest
```
**Hasil:** 1 passed (136 assertions, ~1s).

### 2. Full Pest Test Suite
```bash
php artisan test
```
**Hasil:** 206+ passed (>1250 assertions, 100% green).

### 3. Static Analysis PHPStan Level 9
```bash
vendor/bin/phpstan analyse --memory-limit=1G
```
**Hasil:** `[OK] No errors` (Level 9).

### 4. Code Style Formatting
```bash
vendor/bin/pint --test
```
**Hasil:** `PASS` (semua file sesuai PSR-12 / Laravel preset).

### 5. Frontend Unit & Component Tests (Vitest)
```bash
npm run test:unit
```
**Hasil:** 14 passed (100% green).

### 6. End-to-End Testing (Playwright)
```bash
npx playwright test
```
**Hasil:** Playwright E2E test suites configured and operational.

### 7. Frontend Asset Compilation
```bash
npm run build
```
**Hasil:** `✓ built in ~2s` tanpa warning/error.
