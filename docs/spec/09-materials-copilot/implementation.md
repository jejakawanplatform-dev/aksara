# Implementation — Materi & Co-Pilot

## Artefak

| Area | Path |
|---|---|
| Controllers | `Materials/MaterialController`, `MaterialEditController` |
| Pages | `Pages/Materials/{Index,Show,Edit}.vue` |
| UI Index | Pagination + filter search/status; IconButton aksi baris |
| Services | `AiDraftService`, `MaterialImageService` (media → spec 16) |
| Support | `MaterialContentHtml`, `MaterialCopilotPatch`, `SubjectContext` |
| Models | `LearningMaterial`, `LearningEvent`, `AiProvider` |
| Editor (global) | Spec **15** — `Components/tiptap/*` |
| Media context | Spec **16** — list/upload/delete `materials/{id}/` |
| Tests | `MaterialAuthoringTest`, `MaterialAiCopilotTest`, `Unit/MaterialContentHtmlTest`, `Unit/MaterialCopilotPatchTest`, `AiModelResolutionTest` |

## UI Edit (2026-08-11)

- Layout split: kolom editor scroll + rail Asisten Aksara full-height (`.aksara-material-edit` / `.aksara-copilot-rail`).
- Asisten tidak ikut scroll konten; toggle di PageHeader (+ `localStorage aksara_copilot_open`).
- Mobile/tablet: drawer dari kanan.
- Lebar editor fleksibel saat menu kiri / Asisten buka-tutup.
- Naming UI: **Asisten Aksara** (bukan "AI Co-Pilot").
- Guru boleh pilih model dari daftar provider aktif (+ rekomendasi/batasan); preferensi `localStorage aksara_asisten_model`.
- Ceklis pengayaan: grid 2 kolom.
- Seksi editor + panel Editor Bahan Ajar: accordion collapsible.
- Saran ilustrasi / Prompt AI Image: **hanya di chat** (`illustrationTips`), tidak di body TipTap; sanitize + `forStudent` membersihkan blok lama.

## Alur

```text
Edit.vue ↔ TipTapEditor (v-model HTML sections) + Co-Pilot sidebar
  POST /materials/{id}/copilot → sanitize → apply create|patch|rewrite
  POST /materials/{id}/publish → status published
  Media (spec 16): GET/POST/DELETE materials/{id}/media|images
```

## Otorisasi

`permission:materials.read|plans.manage` (edit: admin atau `plan.teacher_id`).
