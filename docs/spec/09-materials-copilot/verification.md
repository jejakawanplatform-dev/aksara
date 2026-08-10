# Verification — Materi & Co-Pilot

## Lokasi artefak (stack terkini)

| Peran | Path |
|---|---|
| Controllers | `Materials/MaterialController`, `MaterialEditController` |
| Pages | `resources/js/Pages/Materials/{Index,Show,Edit}.vue` |
| Services | `AiDraftService`, `MaterialContentHtml`, `MaterialCopilotPatch` |
| Tests | `MaterialAuthoringTest`, `MaterialAiCopilotTest`, `MaterialContentHtmlTest`, `MaterialCopilotPatchTest` |

## Checklist

- [x] Co-Pilot `POST materials/{id}/copilot`
- [x] Patch apply tidak wipe seksi lain (ADR-009) — unit + feature
- [x] Tests authoring/copilot/html hijau
- [x] Edit wire TipTap (lihat verifikasi 15/16 untuk media)

## Perintah

```bash
php artisan test --filter=MaterialAuthoringTest
php artisan test --filter=MaterialAiCopilotTest
php artisan test --filter=MaterialContentHtmlTest
php artisan test --filter=MaterialCopilotPatchTest
```

## Uji manual

| Langkah | Akun | Harapan |
|---|---|---|
| Edit materi | guru | TipTap + Co-Pilot |
| Siswa buka draft | siswa | ditolak |
