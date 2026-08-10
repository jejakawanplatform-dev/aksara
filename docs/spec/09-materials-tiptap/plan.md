# Plan — Materi, TipTap & Co-Pilot

## Status

| Field | Isi |
|---|---|
| Kode | `09-materials-tiptap` |
| Status | selesai / aktif |
| Steering | `business-rules`, ADR-008/009/010, `api-contract` |

## Ringkasan

Siswa/guru membaca materi; guru menyunting dengan TipTap Vue (upload gambar, STEM math opsional), publish, dan Co-Pilot chat (intent create/patch/rewrite) dengan sanitasi HTML.

## Tujuan

Authoring bahan ajar kaya konten tanpa broken image; AI hanya asisten draf di sidebar.

## Acceptance

- [x] Index/Show/Edit Inertia
- [x] TipTap + upload `materials/{id}/images`
- [x] Co-Pilot `POST …/copilot` + apply aman
- [x] Tests authoring/copilot/html hijau
