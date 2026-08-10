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
