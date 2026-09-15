# Implementation — Bulk Actions & Selection Toolkit

## Artefak

| Area | Path | Peran |
|---|---|---|
| Routes | `routes/web.php` (`users.bulk-destroy`) | Endpoint aksi massal pengguna |
| Controller | `app/Http/Controllers/Users/UserController.php` (`bulkDestroy`) | Handler validasi, guardrails, & transaksi |
| Composable | `resources/js/Composables/useBulkSelect.js` | State management seleksi tabel multi-baris |
| UI Component | `resources/js/Components/ui/BulkToolbar.vue` | Contextual toolbar pengganti filter bar |
| UI Component | `resources/js/Components/ui/BulkConfirmModal.vue` | Modal konfirmasi dengan guard input kata |
| Page Vue | `resources/js/Pages/Users/Index.vue` | Integrasi tabel pengguna percontohan |
| Test | `tests/Feature/UserBulkActionTest.php` | Feature test Pest pengujian hak akses & penghapusan |

## Alur Utama

```text
Pengguna centang checkbox tabel (per baris / select all header)
       │
       ▼
useBulkSelect memperbarui selectedIds & hasSelection = true
       │
       ▼
Users/Index.vue: Filter bar bertransisi menjadi BulkToolbar
       │
       ▼
Pengguna klik "Hapus terpilih" ➔ BulkConfirmModal terbuka
       │
       ▼
Pengguna ketik "HAPUS" ➔ Tombol konfirmasi aktif ➔ Submit POST /users/bulk-destroy
       │
       ▼
UserController::bulkDestroy():
  1. Validasi array IDs / all_matching
  2. Filter self-protection (Auth::id() dilewati)
  3. Filter relasi aktif (Guru ber-RPP & Wali kelas ber-rombel dilewati)
  4. DB::transaction: detach relasi + delete record
  5. Redirect ke users.index dengan flash feedback ringkasan
       │
       ▼
Frontend: clearSelection() + modal tertutup + flash alert tampil
```

## Otorisasi & Guardrails

- **Middleware:** `auth`, `permission:users.manage`
- **Self-Protection:** `Auth::id()` otomatis dilewati agar admin tidak mengunci dirinya sendiri.
- **Relational Integrity:** Guru dengan rencana pembelajaran aktif dan wali kelas dengan rombel binaan otomatis dilindungi dan dilaporkan dalam pesan flash.
