# Implementation — Materi & Co-Pilot

## Artefak

| Area | Path |
|---|---|
| Controllers | `Materials/MaterialController`, `MaterialEditController` |
| Pages | `Pages/Materials/{Index,Show,Edit}.vue` |
| Services | `AiDraftService`, `MaterialImageService` (media → spec 16) |
| Support | `MaterialContentHtml`, `MaterialCopilotPatch`, `SubjectContext` |
| Models | `LearningMaterial`, `LearningEvent`, `AiProvider` |
| Editor (global) | Spec **15** — `Components/tiptap/*` |
| Media context | Spec **16** — list/upload/delete `materials/{id}/` |
| Tests | `MaterialAuthoringTest`, `MaterialAiCopilotTest`, `Unit/MaterialContentHtmlTest`, `Unit/MaterialCopilotPatchTest`, `AiModelResolutionTest` |

## Alur

```text
Edit.vue ↔ TipTapEditor (v-model HTML sections) + Co-Pilot sidebar
  POST /materials/{id}/copilot → sanitize → apply create|patch|rewrite
  POST /materials/{id}/publish → status published
  Media (spec 16): GET/POST/DELETE materials/{id}/media|images
```

## Otorisasi

`permission:materials.read|plans.manage` (edit: admin atau `plan.teacher_id`).
