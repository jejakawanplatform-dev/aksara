# Plan — Bulk Actions & Selection Toolkit

## Status

| Field | Isi |
|---|---|
| Kode | `18-bulk-actions` |
| Status | selesai |
| Steering | `coding-standards`, `business-rules`, `api-contract`, `17-design-system`, ADR-015 |
| Diskusi Asal | `docs/discussions/2026-09-15-fitur-bulk-actions-tabel.md` |

## Ringkasan

Toolkit seleksi multi-baris (*bulk selection*) dan aksi massal kontekstual untuk tabel data di Aksara. Diimplementasikan penuh di 4 modul utama data sekolah: **Manajemen Pengguna** (`Users/Index.vue`), **Rencana Pembelajaran** (`Plans/Index.vue`), **Materi Pembelajaran** (`Materials/Index.vue`), dan **Referensi Akademik: Rombel & Mapel** (`References/Index.vue`).

## Tujuan

1. Efisiensi pengelolaan data massal bagi Administrator dan Guru tanpa harus menghapus satu per satu.
2. Standardisasi komponen seleksi frontend (`useBulkSelect`, `BulkToolbar`, `BulkConfirmModal`).
3. Menjamin keamanan operasional: *self-protection* (tidak bisa hapus akun login sendiri), integritas relasi data (guru ber-RPP dan wali kelas terikat rombel aman dari penghapusan massal), serta *double-confirmation guard* ketik `"HAPUS"`.

## Scope

**In scope**
- Composable frontend `useBulkSelect.js` (seleksi halaman aktif & opsi lintas halaman / all matching).
- Komponen UI `BulkToolbar.vue` (contextual swap di atas tabel) dan `BulkConfirmModal.vue`.
- Endpoints backend penghapusan massal dengan transaksi `DB::transaction`:
  - `POST /users/bulk-destroy`
  - `POST /plans/bulk-destroy`
  - `POST /materials/bulk-destroy`
  - `POST /references/rombels/bulk-destroy`
  - `POST /references/mapel/bulk-destroy`
- Integrasi tabel `Users/Index.vue`, `Plans/Index.vue`, `Materials/Index.vue`, dan `References/Index.vue`.
- Feature Tests Pest: `UserBulkActionTest`, `PlanBulkActionTest`, `MaterialBulkActionTest`, `ReferenceBulkActionTest`.

**Out of scope**
- Background async queue untuk jutaan record (transaksi sinkron DB cukup untuk volume sekolah).
- Undo / restore recycle bin.

## Acceptance

- [x] Checkbox seleksi per baris dan select-all di header tabel pada 4 modul utama
- [x] Contextual Toolbar menggantikan filter bar saat ada item terpilih
- [x] Modal protektif dengan kewajiban ketik `"HAPUS"` untuk konfirmasi bulk delete
- [x] Admin tidak bisa menghapus akun sendiri
- [x] Guru ber-RPP dan wali kelas aktif otomatis dilindungi / dilewati
- [x] Seluruh 4 feature test bulk actions hijau (100% passed)
