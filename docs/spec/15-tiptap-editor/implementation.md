# Implementation — TipTap rich editor

## Artefak

| Area | Path |
|---|---|
| Editor | `resources/js/Components/tiptap/TipTapEditor.vue` |
| Image node | `resources/js/Components/tiptap/aksara-image.js` |
| Picker | `resources/js/Components/tiptap/MediaPicker.vue` |
| Math helpers | `resources/js/tiptap-math.js` |
| CSS | `resources/css/app.css` (`.aksara-prose`, `.ProseMirror`) |
| Consumers | `Pages/Materials/Edit.vue`, `Pages/Materials/Show.vue`, `Pages/Evaluation/Form.vue` |

## Kontrak props

```js
{
  modelValue: String,
  withMath: Boolean,   // STEM toolbar + lazy KaTeX
  media: null | { listUrl, uploadUrl, deleteUrl }, // deleteUrl = base …/media
  editable: Boolean,
}
```

## Alur

```text
TipTapEditor
  ├─ toolbar → commands / MediaPicker / MathModal
  └─ EditorContent (.aksara-prose)
Show (STEM) → renderKaTeXInElement(tiptap-math.js)
```
