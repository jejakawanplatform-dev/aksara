# Plan — TipTap rich editor (global)

## Status

| Field | Isi |
|---|---|
| Kode | `15-tiptap-editor` |
| Status | selesai |
| Steering | ADR-008/010, `coding-standards`, `api-contract` |
| Media | Spec **16** |

## Ringkasan

Komponen TipTap Vue reusable (`TipTapEditor`) untuk authoring HTML: toolbar standar, mode STEM (`withMath` + KaTeX lazy), integrasi MediaPicker bila prop `media` diisi.

## Tujuan

Editor rapi, konsisten di edit/show prose, dan bisa dipakai di domain mana pun tanpa menggandakan logika materi.

## Scope

**In scope**

- `TipTapEditor.vue`, `aksara-image.js`, prose CSS bersama (`.aksara-prose`)
- Toolbar: undo/redo, format, list, link, table, gambar→picker, properti gambar
- `withMath`: tombol rumus + preview KaTeX lazy; insert `$…$` / `$$…$$`
- Render show: `tiptap-math.js` bila STEM

**Out of scope**

- Storage list/upload/delete → **16**
- Co-Pilot / publish materi → **09**

## Acceptance

- [x] TipTap reusable via props `modelValue`, `withMath`, `media`, `editable`
- [x] Mode tanpa `media` = tanpa tombol gambar (mis. Evaluation)
- [x] STEM: KaTeX tidak di-load kecuali `withMath` / Show STEM
- [x] Prose edit ≈ show (`.aksara-prose`)
