# Plan — Design system (Vue SoT)

## Status

| Field | Isi |
|---|---|
| Kode | `17-design-system` |
| Status | aktif |
| Steering | ADR-012, `coding-standards` |
| Terkait | **03** shell/nav · **15** TipTap/prose |

## Ringkasan

Sumber kebenaran visual frontend Inertia+Vue: token warna/tipografi, kontrak `Components/ui`, ikon, pola page, theme light-only.

## Tujuan

Standarisasi UI supaya page domain konsisten; agent/dev tidak menambah palette/library ad-hoc.

## Scope

**In scope**

- Design tokens (CSS var + Tailwind `aksara-*`)
- Typography (sans / display)
- Icons (`Icon.vue`)
- UI kit contract + Modal + Btn variants
- Theme: light SoT; dark ditunda
- Debt migrasi aksen non-token

**Out of scope**

- Storybook / Figma token pipeline
- Dark mode toggle (fase berikutnya)
- TipTap internals → **15**
- App shell collapse/nav → **03**

## Acceptance

- [x] Spec 17 + ADR-012 light-only
- [x] `Btn` primary/secondary/danger (+ size)
- [x] `Modal.vue` reusable
- [x] Alert tones memakai token aksara
- [x] Migrasi sisa modal per-page (Users/References/Settings)
- [x] Dashboard/report bebas ungu ad-hoc
