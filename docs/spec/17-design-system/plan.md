# Plan — Design system (Vue SoT)

## Status

| Field | Isi |
|---|---|
| Kode | `17-design-system` |
| Status | selesai |
| Steering | ADR-012, `coding-standards` |
| Terkait | **03** shell/nav · **15** TipTap/prose |

## Ringkasan

Sumber kebenaran visual frontend Inertia+Vue: token warna/tipografi, kontrak `Components/ui`, ikon, pola page, theme **light enterprise** (permanen).

## Tujuan

Standarisasi UI supaya page domain konsisten; agent/dev tidak menambah palette/library ad-hoc.

## Scope

**In scope**

- Design tokens (CSS var + Tailwind `aksara-*`) — slate dingin + brand teal
- Typography (sans UI / display brand)
- Icons (`Icon.vue`)
- UI kit contract + Modal + Btn variants
- Theme: **light-only permanen** (enterprise-education)

**Out of scope**

- Storybook / Figma token pipeline
- Dark mode (tidak digarap)
- TipTap internals → **15**
- App shell collapse/nav → **03**

## Acceptance

- [x] Spec 17 + ADR-012 light-only permanen
- [x] `Btn` primary/secondary/danger (+ size)
- [x] `Modal.vue` reusable
- [x] Alert tones memakai token aksara
- [x] Migrasi sisa modal per-page (Users/References/Settings)
- [x] Dashboard/report bebas ungu ad-hoc
- [x] Token enterprise (slate netral + teal brand); dashboard hero flat
- [x] Pagination / IconButton / ExportMenu / toolbar / densitas tabel (2026-08-11)
