# Plan — Bulk Actions & Selection Toolkit

## Status

| Field | Isi |
|---|---|
| Kode | `18-bulk-actions` |
| Status | selesai |
| Steering | `coding-standards`, `business-rules`, `api-contract`, `17-design-system` |
| Diskusi Asal | `docs/discussions/2026-09-15-fitur-bulk-actions-tabel.md` |

## Ringkasan

Toolkit seleksi multi-baris (*bulk selection*) dan aksi massal kontekstual untuk tabel data di Aksara. Diimplementasikan penuh pada modul percontohan Manajemen Pengguna (`Users/Index.vue`) dan siap direplikasi ke modul data lainnya.

## Tujuan

1. Efisiensi pengelolaan banyak data bagi Administrator dan Guru tanpa harus menghapus/mengubah satu per satu.
2. Standardisasi komponen seleksi frontend (`useBulkSelect`, `BulkToolbar`, `BulkConfirmModal`).
3. Menjamin keamanan operasional: *self-protection* (tidak bisa hapus akun login), integritas relasi data (guru ber-RPP dan wali kelas terikat rombel aman dari penghapusan massal), serta *double-confirmation guard* ketik `"HAPUS"`.

## Scope

**In scope**
- Composable frontend `useBulkSelect.js` (seleksi halaman aktif & opsi lintas halaman / all matching).
- Komponen UI `BulkToolbar.vue` (contextual swap di atas tabel) dan `BulkConfirmModal.vue`.
- Endpoint backend `POST /users/bulk-destroy` dengan transaksi `DB::transaction`.
- Integrasi tabel `Users/Index.vue` lengkap dengan checkbox header indeterminate dan baris.
- Feature Test Pest `UserBulkActionTest.php`.

**Out of scope**
- Background async queue untuk jutaan record (saat ini transaksi sinkron DB cukup untuk skala sekolah).
- Undo / restore recycle bin (karena soft-delete belum diadopsi di schema saat ini).

## Acceptance

- [x] Route `POST /users/bulk-destroy` dengan middleware `permission:users.manage`.
- [x] Checkbox seleksi per baris dan select-all di header tabel.
- [x] Contextual Toolbar menggantikan filter bar saat ada item terpilih.
- [x] Modal protektif dengan kewajiban ketik `"HAPUS"` untuk bulk delete.
- [x] Admin tidak bisa menghapus akun sendiri.
- [x] Guru ber-RPP dan wali kelas aktif otomatis dilindungi / dilewati.
- [x] Feature test hijau (6/6 passed, 23 assertions).
