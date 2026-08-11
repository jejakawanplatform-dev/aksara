# Implementation — Settings & AI providers

## Artefak

| Area | Path |
|---|---|
| Controller | `app/Http/Controllers/Settings/SettingsController.php` |
| Service | `app/Services/SettingService.php` |
| Catalog | `app/Support/Ai/AiVendorProviderCatalog.php` |
| Models | `SystemSetting`, `AiProvider`, `AiUsageLog` |
| Page | `resources/js/Pages/Settings/Index.vue` |
| Test | `tests/Feature/SystemSettingsTest.php` |

## Otorisasi

`permission:settings.manage`

## Catatan

- Failover runtime di `AiDraftService` mengikuti `priority_order`.
- Mock mode: `AI_MOCK_MODE=true` (env) tetap didukung workshop.
- UI (2026-08-11): tab singkat; usage compact; tabel vendor + prioritas chevron; pagination client-side; guard = select vendor aktif; model per fitur + hint rekomendasi; modal uji koneksi.
- Model per fitur: opsi dropdown **hanya dari vendor `is_active`** (`AiVendorProviderCatalog::modelIdsFromProviders` + filter Vue dari tabel provider).
