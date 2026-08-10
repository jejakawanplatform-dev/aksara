# Verification — Settings & AI providers

## Lokasi artefak (stack terkini)

| Peran | Path |
|---|---|
| Controller | `app/Http/Controllers/Settings/SettingsController.php` |
| Service | `app/Services/SettingService.php` |
| Catalog | `app/Support/Ai/AiVendorProviderCatalog.php` |
| Models | `SystemSetting`, `AiProvider`, `AiUsageLog` |
| Page | `resources/js/Pages/Settings/Index.vue` |
| Test | `tests/Feature/SystemSettingsTest.php` |

## Checklist

- [x] `permission:settings.manage`
- [x] Providers CRUD/test/priority
- [x] `SystemSettingsTest` hijau

## Perintah

```bash
php artisan test --filter=SystemSettingsTest
```

## Uji manual

| Langkah | Akun | Harapan |
|---|---|---|
| GET `/settings` | admin | form + tabel provider |
