# Implementation — Materi, TipTap & Co-Pilot

## Artefak

| Area | Path |
|---|---|
| Controllers | `Materials/MaterialController`, `MaterialEditController` |
| Pages | `Pages/Materials/{Index,Show,Edit}.vue` |
| TipTap | `Components/tiptap/TipTapEditor.vue`, `aksara-image.js` |
| Math | `resources/js/tiptap-math.js` |
| Services | `AiDraftService`, `MaterialImageService` |
| Support | `MaterialContentHtml`, `SubjectContext` |
| Models | `LearningMaterial`, `LearningEvent`, `AiProvider` |
| Tests | `MaterialAuthoringTest`, `MaterialAiCopilotTest`, `Unit/MaterialContentHtmlTest`, `AiModelResolutionTest` |

## Alur media & Co-Pilot

```text
Edit.vue ↔ TipTapEditor (v-model HTML/JSON sections)
  POST /materials/{id}/images → /storage/materials/{id}/…
  POST /materials/{id}/copilot → sanitize → apply create|patch|rewrite
  POST /materials/{id}/publish → status published
```

## Otorisasi

`permission:materials.read|plans.manage` (nav edit biasanya lewat `plans.manage`).
