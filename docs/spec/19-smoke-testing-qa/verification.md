# Verifikasi — Tahap 19 (Smoke Testing & QA Suite Enforcer)

## Lokasi Artefak

| Artefak | Lokasi Path |
|---|---|
| Smoke Test | `tests/Feature/Smoke/CriticalJourneySmokeTest.php` |
| PHPStan Config | `phpstan.neon` |
| PHPStan Baseline | `phpstan-baseline.neon` |
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
**Hasil:** 146 passed (1090 assertions, 100% green).

### 3. Static Analysis PHPStan Level 9
```bash
vendor/bin/phpstan analyse --memory-limit=1G
```
**Hasil:** `[OK] No errors` (Level 9).

### 4. Code Style Formatting
```bash
vendor/bin/pint --test
```

### 5. Frontend Asset Compilation
```bash
npm run build
```
**Hasil:** `✓ built in ~2s` tanpa warning/error.
