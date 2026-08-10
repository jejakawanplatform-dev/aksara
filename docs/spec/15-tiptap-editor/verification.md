# Verification — TipTap rich editor

## Lokasi artefak (stack terkini)

| Peran | Path |
|---|---|
| Editor | `resources/js/Components/tiptap/TipTapEditor.vue` |
| Image | `resources/js/Components/tiptap/aksara-image.js` |
| Picker | `resources/js/Components/tiptap/MediaPicker.vue` |
| Math | `resources/js/tiptap-math.js` |
| CSS | `resources/css/app.css` (`.aksara-prose`) |
| Wire | `Pages/Materials/Edit.vue`, `Show.vue`, `Evaluation/Form.vue` |

## Checklist

- [x] Toolbar lengkap + table
- [x] `withMath` → rumus; tanpa math di mode biasa
- [x] Gambar via MediaPicker (bila `media` set)
- [x] Prose rapi; outline hanya saat selected
- [x] KaTeX CSS tidak global di `app.css` (lazy via editor/math module)

## Perintah

```bash
npm run build
php artisan test --filter=MaterialMediaTest
```

## Uji manual

| Langkah | Akun | Harapan |
|---|---|---|
| Edit materi STEM | guru | Tombol Rumus + preview |
| Edit materi non-STEM | guru | Tanpa tombol Rumus |
| Insert gambar | guru | Picker konteks materi |
| Resize / properti | guru | Outline hanya selected |

Regresi otomatis: Edit page assert `isStem` + endpoints media (`MaterialMediaTest`).
