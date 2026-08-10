# Verification — Materi, TipTap & Co-Pilot

## Lokasi artefak (stack terkini)

| Peran | Path |
|---|---|
| Controllers | `Materials/MaterialController`, `MaterialEditController` |
| Pages | `resources/js/Pages/Materials/{Index,Show,Edit}.vue` |
| TipTap | `resources/js/Components/tiptap/TipTapEditor.vue`, `aksara-image.js` |
| Math | `resources/js/tiptap-math.js` |
| Services | `AiDraftService`, `MaterialImageService`, `MaterialContentHtml` |
| Tests | `MaterialAuthoringTest`, `MaterialAiCopilotTest`, `MaterialContentHtmlTest` |

## Checklist

- [x] Edit + upload `POST materials/{id}/images`
- [x] Co-Pilot `POST materials/{id}/copilot`
- [x] Tests authoring/copilot/html hijau
- [x] `npm run build` (TipTap bundle)

## Perintah

```bash
php artisan test --filter=MaterialAuthoringTest
php artisan test --filter=MaterialAiCopilotTest
php artisan test --filter=MaterialContentHtmlTest
npm run build
```

## Uji manual

| Langkah | Akun | Harapan |
|---|---|---|
| Edit materi | guru | TipTap + Co-Pilot |
| Upload gambar | guru | `/storage/materials/{id}/…` |
| Siswa buka draft | siswa | ditolak |
