# Tasks — Bulk Actions & Selection Toolkit

Checklist deliverable tahap 18.

| ID | Task | Prioritas | Status | Catatan |
|---|---|---|---|---|
| T01 | Composable `resources/js/Composables/useBulkSelect.js` | P0 | done | Mendukung page-scoped & selectAllMatching |
| T02 | Komponen `resources/js/Components/ui/BulkToolbar.vue` | P0 | done | Contextual bar pengganti filter biasa |
| T03 | Komponen `resources/js/Components/ui/BulkConfirmModal.vue` | P0 | done | Double confirmation guard ketik "HAPUS" |
| T04 | Route `POST /users/bulk-destroy` | P0 | done | Middleware `permission:users.manage` |
| T05 | Method `UserController::bulkDestroy` | P0 | done | Self-protection & relasi check dalam DB transaction |
| T06 | Integrasi UI `resources/js/Pages/Users/Index.vue` | P0 | done | Checkbox header/row + modal konfirmasi |
| T07 | Feature test `tests/Feature/UserBulkActionTest.php` | P0 | done | 6 test case lengkap (admin, non-admin, self, relasi) |
| T08 | Replikasi ke `Materials/Index.vue` & Controller | P1 | done | `MaterialController@bulkDestroy` & `MaterialBulkActionTest` |
| T09 | Replikasi ke `Plans/Index.vue` & Controller | P1 | done | `PlanController@bulkDestroy` & `PlanBulkActionTest` |
| T10 | Replikasi ke `References/Index.vue` (Rombel & Mapel) | P1 | done | `ReferenceController@bulkDestroy*` & `ReferenceBulkActionTest` |
| T11 | **[HOTFIX]** Bungkus loop delete `MaterialController::bulkDestroy` dalam `DB::transaction` | P0 | done | BUG-02 — Audit 2026-09-21; partial delete risk jika iterasi gagal di tengah |
| T12 | **[HOTFIX]** Bungkus loop delete `ReferenceController::bulkDestroyRombel` dan `bulkDestroyMapel` dalam `DB::transaction` | P0 | done | BUG-03 — Audit 2026-09-21; `detach()` + `delete()` dua operasi terpisah tanpa transaction, state bisa korup |
