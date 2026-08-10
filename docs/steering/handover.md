# Handover — Aksara

Dokumen status kerja terkini agar agent/developer berikutnya bisa melanjutkan tanpa mengandalkan chat.

## Stack runtime (wajib)

| Layer | Pilihan |
|---|---|
| Backend | Laravel 13, PHP 8.4, MySQL |
| UI | **Inertia.js + Vue 3 + Tailwind** (ADR-010) |
| Editor | TipTap Vue (`resources/js/Components/tiptap`) |
| Auth | Breeze Inertia (`Pages/Auth/*`) |
| RBAC | Spatie Permission + `users.role` |
| Entry JS | `resources/js/inertia-app.js` |
| Root HTML | `resources/views/app.blade.php` |
| PDF | Blade `resources/views/exports/*` |

Jangan menambah stack UI di luar Inertia + Vue tanpa ADR baru.

## Status

### Selesai

- Domain + UI Inertia/Vue sesuai `docs/spec/01–14` (scaffold → exports)
- Design system Vue: `Components/ui/*`; TipTap: `Components/tiptap/*`

### Perlu penguatan

- Smoke browser TipTap image resize/properti + Co-Pilot apply setelah deploy
- Perluas assertInertia pada feature test kritis
- PHPStan: banyak noise typing Eloquent di controller (utang terpisah)

### Backlog / non-scope

- REST API publik / mobile, Dapodik / SSO
- Bank soal besar, proctoring, notifikasi, multi-tenant
- Auto-generate image bytes dari Co-Pilot (butuh API image berbayar)

## Cara menjalankan

```bash
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
# DB_* dan AI_MOCK_MODE=true
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve   # atau vhost lokal
```

Akun demo: lihat `docs/demo-users.md` (contoh guru `naya@aksara.test` / `password`).

Seed berisi rencana/materi/kuis **Informatika — Dekomposisi Masalah** (published), TA **2025/2026**, semester **Ganjil**, rombel **VII-A / VIII-A / IX-A**.

## Perubahan terakhir

- Spek `01–14`: verification memakai tabel **Lokasi artefak (stack terkini)** — path konkret, tanpa checklist generik “ada di disk”.

## Keputusan yang wajib dipatuhi

1. Output AI selalu draf; publish hanya oleh guru (ADR-001).
2. API key AI hanya di backend / `ai_providers` (ADR-002).
3. Tidak mengirim data pribadi siswa ke AI.
4. Otorisasi permission + kepemilikan di backend; `users.role` untuk identitas (ADR-003/007).
5. Perubahan skema lewat migration; keputusan besar di `decision-log.md`.
6. Teks CP/TP/ATP seed = adaptasi workshop — verifikasi resmi sebelum produksi.
7. Jangan CRUD role bebas — matrix permission pada role tetap (ADR-007).
8. TipTap: KaTeX hanya jika mode STEM (`with-math`).
9. Scoping data: `ScopesTeacherOrAdmin`.
10. Failover AI: `ai_providers.priority_order`.
11. Materi: tidak ada `<img>` fiktif dari AI teks; file hanya storage tepercaya (ADR-008).
12. Ceklis Generate Gambar AI hanya jika provider image terkonfigurasi.
13. Co-Pilot tunggal; patch tidak wipe seksi tak terkait (ADR-009).
14. UI = Inertia + Vue; domain PHP dipertahankan (ADR-010).
15. Preferensi model: `system_settings` (`ai.model_*`).

## Test

```bash
php artisan test          # terakhir: 114 passed (2026-08-10)
npm run build
php artisan storage:link
```

## Known issue

1. FormRequest scaffold masih `authorize(): false` — jangan di-wire mentah.
2. Simpan kuis plan masih cenderung `updateOrCreate` by title — edit by id lebih baik nanti.
3. DB lama tanpa reseed provider: `php artisan db:seed --class=AiProviderSeeder`.

## Dokumen baca dulu

| File | Isi |
|---|---|
| `product-brief.md` | produk & stack |
| `business-rules.md` | aturan proses |
| `coding-standards.md` | struktur & konvensi Vue |
| `api-contract.md` | route / AI |
| `database-schema.md` | skema |
| `testing-strategy.md` | cara uji |
| `decision-log.md` | ADR |
| `docs/spec/18-inertia-vue-migration/` | cutover UI |

## Langkah berikutnya

1. Fitur produk berikutnya sesuai spek baru di `docs/spec/`.
2. (Opsional) rapikan PHPStan Eloquent typing; tambah assertInertia.
