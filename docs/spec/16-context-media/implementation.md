# Implementation — Context-scoped media

## Artefak

| Area | Path |
|---|---|
| Service | `app/Services/MaterialImageService.php` |
| Controller | `Materials/MaterialEditController` (`indexMedia`, `storeImage`, `destroyMedia`) |
| Routes | `materials.media`, `materials.images`, `materials.media.destroy` |
| Disk | `storage/app/public/materials/{id}/` → `/storage/materials/{id}/…` |
| UI | `Components/tiptap/MediaPicker.vue` |
| Tests | `tests/Feature/MaterialMediaTest.php` |

## Alur

```text
MediaPicker
  GET  /materials/{id}/media          → items[{name,url,size,updated_at}]
  POST /materials/{id}/images         → { url }  (dataUrl)
  DELETE /materials/{id}/media/{file} → 204
```

## Isolasi

Filename = basename saja; path wajib di bawah `materials/{material_id}/`.
