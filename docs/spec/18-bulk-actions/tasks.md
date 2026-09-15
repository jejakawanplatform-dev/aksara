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
| T08 | Replikasi ke `Materials/Index.vue` (bulk publish/archive) | P2 | todo | Dapat dilanjutkan sebagai sub-tahap berikutnya |
