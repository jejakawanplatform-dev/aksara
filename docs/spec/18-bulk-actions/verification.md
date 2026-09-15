# Verification — Bulk Actions & Selection Toolkit

## Lokasi Artefak (Stack Terkini)

| Peran | Path |
|---|---|
| Routes | `routes/web.php` |
| Controller | `app/Http/Controllers/Users/UserController.php` |
| Composable | `resources/js/Composables/useBulkSelect.js` |
| Components | `resources/js/Components/ui/BulkToolbar.vue`, `BulkConfirmModal.vue` |
| Page Vue | `resources/js/Pages/Users/Index.vue` |
| Test | `tests/Feature/UserBulkActionTest.php` |

## Checklist Verifikasi

- [x] Route `POST /users/bulk-destroy` terdaftar di `routes/web.php`.
- [x] Composable `useBulkSelect.js` menangani seleksi lokal dan `selectAllMatching`.
- [x] `BulkToolbar.vue` bertransisi menggantikan filter bar saat item dipilih.
- [x] Modal `BulkConfirmModal.vue` memvalidasi pengetikan kata `"HAPUS"`.
- [x] Proteksi akun mandiri (`Auth::id()`) dan proteksi relasi guru/wali kelas berjalan.
- [x] Feature test hijau: `php artisan test --filter=UserBulkActionTest` (6 passed).
- [x] Full test suite hijau: `php artisan test` (145 passed).
- [x] Static analysis bersih: `vendor/bin/phpstan analyse` (0 errors).
- [x] Build frontend sukses: `npm run build`.

## Perintah Uji

```bash
# Uji spesifik fitur bulk action:
php artisan test --filter=UserBulkActionTest

# Uji seluruh test suite:
php artisan test

# Analisis statis:
vendor/bin/phpstan analyse --memory-limit=1G

# Build frontend:
npm run build
```

## Uji Manual

| Langkah | Akun | Harapan |
|---|---|---|
| 1. Buka `/users` | Admin (`admin@aksara.test`) | Tabel menampilkan kolom checkbox di kiri |
| 2. Centang checkbox di baris tertentu | Admin | Filter bar berganti menjadi `BulkToolbar` menampilkan counter item terpilih |
| 3. Centang checkbox header | Admin | Semua baris di halaman aktif terpilih; muncul teks tawaran pilih semua data |
| 4. Klik "Hapus terpilih" | Admin | Muncul modal konfirmasi; tombol konfirmasi nonaktif |
| 5. Ketik "HAPUS" di input konfirmasi | Admin | Tombol konfirmasi aktif; klik untuk eksekusi |
| 6. Selesai eksekusi | Admin | Data terhapus, seleksi di-reset, muncul flash pesan feedback |
| 7. Coba sertakan akun admin sendiri | Admin | Akun admin tidak terhapus; muncul catatan di flash message |
