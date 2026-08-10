# Verification — Context-scoped media

## Lokasi artefak (stack terkini)

| Peran | Path |
|---|---|
| Service | `app/Services/MaterialImageService.php` |
| Controller | `Materials/MaterialEditController` (`indexMedia`, `storeImage`, `destroyMedia`) |
| Routes | `routes/web.php` (`materials.media`, `materials.images`, `materials.media.destroy`) |
| Picker | `resources/js/Components/tiptap/MediaPicker.vue` |
| Tests | `tests/Feature/MaterialMediaTest.php` |

## Checklist

- [x] List / upload / delete hijau
- [x] Isolasi antar material id
- [x] Path traversal / ekstensi ilegal ditolak
- [x] Siswa tidak akses API media; Edit props media lengkap

## Perintah

```bash
php artisan test --filter=MaterialMediaTest
```

## Uji manual

| Langkah | Akun | Harapan |
|---|---|---|
| Picker materi A | guru | Hanya file A |
| Hapus file | guru | Hilang dari disk + list |
| Materi B | guru | Tidak melihat file A |
