# Plan — Materi & Co-Pilot

## Status

| Field | Isi |
|---|---|
| Kode | `09-materials-copilot` |
| Status | selesai / aktif |
| Steering | `business-rules`, ADR-008/009/010, `api-contract` |
| Editor / media | Spec **15** (TipTap) + **16** (context media) |

## Ringkasan

Siswa/guru membaca materi; guru menyunting konten (editor TipTap global), publish, dan Co-Pilot chat (intent create/patch/rewrite) dengan sanitasi HTML.

## Tujuan

Authoring bahan ajar kaya konten tanpa broken image; AI hanya asisten draf di sidebar.

## Scope

**In scope**

- Index / Show / Edit materi (Inertia)
- Persist konten JSON + publish
- Co-Pilot `POST …/copilot` + sanitasi `MaterialContentHtml`
- Wiring TipTap + media endpoints ke Edit (implementasi editor di 15/16)

**Out of scope**

- Implementasi internal TipTap / KaTeX toolbar → **15**
- List/delete/upload storage context → **16**

## Acceptance

- [x] Index/Show/Edit Inertia
- [x] Co-Pilot `POST …/copilot` + apply aman
- [x] Sanitasi HTML (ADR-008)
- [x] Tests authoring/copilot/html hijau
