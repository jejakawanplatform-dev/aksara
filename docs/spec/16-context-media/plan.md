# Plan — Context-scoped media

## Status

| Field | Isi |
|---|---|
| Kode | `16-context-media` |
| Status | selesai |
| Steering | ADR-008 (extend), `api-contract` |
| UI picker | Spec **15** (`MediaPicker`) |

## Ringkasan

Aset gambar terikat konteks owner (fase 1: `materials/{id}/` di disk `public`). TipTap hanya list/upload/hapus di folder itu — tanpa library user/sekolah atau halaman File Manager.

## Tujuan

Reuse gambar dalam satu materi; isolasi antar materi/user; tetap tepercaya untuk sanitasi `/storage/...`.

## Scope

**In scope**

- `MaterialImageService`: list, store, delete (+ path traversal guard)
- Routes: `GET …/media`, `POST …/images`, `DELETE …/media/{filename}`
- Feature tests isolasi antar material id
- Endpoints di props Edit → TipTap `media`

**Out of scope**

- Tabel DB media, nested folders, non-image
- Library per-user / share lintas materi
- Nav File Manager

## Acceptance

- [x] List hanya file di `materials/{id}/`
- [x] Delete tidak bisa path traversal / materi lain
- [x] Upload tetap menghasilkan `/storage/materials/{id}/…`
