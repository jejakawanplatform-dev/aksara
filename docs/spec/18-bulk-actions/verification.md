# Verification — Bulk Actions & Selection Toolkit

## Lokasi Artefak

| Peran | Path |
|---|---|
| Composable | `resources/js/Composables/useBulkSelect.js` |
| UI Components | `resources/js/Components/ui/{BulkToolbar,BulkConfirmModal}.vue` |
| Controllers | `Users\UserController`, `Plans\PlanController`, `Materials\MaterialController`, `References\ReferenceController` |
| Tests | `tests/Feature/{UserBulkActionTest,PlanBulkActionTest,MaterialBulkActionTest,ReferenceBulkActionTest}.php` |
| Unit Test JS | `tests/Unit/js/useBulkSelect.test.js` |

## Checklist

- [x] Checkbox seleksi tabel berfungsi normal di Users, Plans, Materials, dan References
- [x] Contextual Toolbar menampilkan jumlah item terpilih dan tombol aksi
- [x] Double-confirmation modal mencegah penghapusan tidak disengaja
- [x] Admin terlindungi dari penghapusan akun sendiri
- [x] Integritas relasi terlindungi dan memunculkan feedback informasi yang jelas
- [x] Unit test composable Vitest hijau
- [x] Seluruh Feature Test Pest hijau

## Perintah Pengujian

```bash
# Pengujian Backend (Pest)
php artisan test --filter=UserBulkActionTest
php artisan test --filter=PlanBulkActionTest
php artisan test --filter=MaterialBulkActionTest
php artisan test --filter=ReferenceBulkActionTest

# Pengujian Frontend (Vitest)
npm run test:unit -- tests/Unit/js/useBulkSelect.test.js
```
