# Plan — Settings & AI providers

## Status

| Field | Isi |
|---|---|
| Kode | `07-settings-ai` |
| Status | selesai / aktif |
| Steering | `api-contract`, ADR-002 |

## Ringkasan

`/settings`: pengaturan sistem (`system_settings`), katalog vendor AI (`ai_providers`), prioritas failover, test koneksi, preferensi model per fitur.

## Tujuan

Operasional AI & flag teknis dikelola admin tanpa edit `.env` untuk setiap vendor.

## Acceptance

- [x] CRUD/toggle/priority provider
- [x] `setting()` cached helper
- [x] `SystemSettingsTest` hijau
