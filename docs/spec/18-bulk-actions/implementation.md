# Implementation — Bulk Actions & Selection Toolkit

## Artefak

| Area | Path | Peran |
|---|---|---|
| Routes | `routes/web.php` (`users.bulk-destroy`, `plans.bulk-destroy`, `materials.bulk-destroy`, `references.*.bulk-destroy`) | Endpoint aksi massal |
| Controllers | `Users\UserController`, `Plans\PlanController`, `Materials\MaterialController`, `References\ReferenceController` | Handler validasi, guardrails, & DB::transaction |
| Composable | `resources/js/Composables/useBulkSelect.js` | State management seleksi multi-baris frontend |
| UI Component | `resources/js/Components/ui/BulkToolbar.vue` | Contextual toolbar pengganti filter bar |
| UI Component | `resources/js/Components/ui/BulkConfirmModal.vue` | Modal konfirmasi dengan guard input kata `"HAPUS"` |
| Pages Vue | `Users/Index.vue`, `Plans/Index.vue`, `Materials/Index.vue`, `References/Index.vue` | Integrasi tabel data interaktif |
| Tests | `tests/Feature/{UserBulkActionTest,PlanBulkActionTest,MaterialBulkActionTest,ReferenceBulkActionTest}.php` | Pest Feature Tests pengujian hak akses & guardrails |

## Cakupan Domain & Guardrails

| Modul | Endpoint | Controller Method | Guardrails Khusus |
|---|---|---|---|
| **Users** | `POST /users/bulk-destroy` | `UserController@bulkDestroy` | Self-protection (`Auth::id()`), skip guru ber-RPP & wali kelas ber-rombel |
| **Plans** | `POST /plans/bulk-destroy` | `PlanController@bulkDestroy` | Otorisasi kepemilikan guru (`teacher_id`) / bypass admin |
| **Materials** | `POST /materials/bulk-destroy` | `MaterialController@bulkDestroy` | Otorisasi kepemilikan atau admin |
| **References** | `POST /references/rombels/bulk-destroy` | `ReferenceController@bulkDestroyRombel` | Proteksi rombel yang memiliki anggota siswa aktif |
| **References** | `POST /references/mapel/bulk-destroy` | `ReferenceController@bulkDestroyMapel` | Proteksi mapel yang terhubung dengan RPP atau CP |

## Alur Kerja

```text
Pengguna centang checkbox tabel (per baris / select all header)
       │
       ▼
useBulkSelect memperbarui selectedIds & hasSelection = true
       │
       ▼
Index.vue: Filter bar bertransisi menjadi BulkToolbar
       │
       ▼
Pengguna klik "Hapus terpilih" ➔ BulkConfirmModal terbuka
       │
       ▼
Pengguna ketik "HAPUS" ➔ Tombol konfirmasi aktif ➔ Submit POST .../bulk-destroy
       │
       ▼
Controller Handler:
  1. Validasi array IDs / all_matching
  2. Filter guardrails (self-protection & relasi integritas)
  3. DB::transaction: detach relasi + delete records
  4. Redirect kembali dengan flash feedback rekapitulasi
       │
       ▼
Frontend: clearSelection() + modal tertutup + flash alert tampil
```
