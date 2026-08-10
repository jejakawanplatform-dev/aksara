# Plan — Rencana pembelajaran & draf AI

## Status

| Field | Isi |
|---|---|
| Kode | `08-learning-plans` |
| Status | selesai / aktif |
| Steering | `business-rules`, ADR-001/002, `api-contract` |

## Ringkasan

Pipeline rencana: daftar/filter, buat dual-mode (AI/manual), edit, review draf AI (approve/publish), import/export, buka materi terkait. Scoping guru vs admin.

## Tujuan

Guru menyusun modul ajar dengan bantuan AI yang selalu berstatus draf sampai dipublish.

## Acceptance

- [x] CRUD + draft approve/publish
- [x] Generate AI via `AiDraftService` (+ mock/failover)
- [x] Import/export plan
- [x] Tests pipeline / create / export hijau
