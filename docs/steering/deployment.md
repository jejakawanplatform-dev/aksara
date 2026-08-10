# Deployment — Aksara

Panduan deploy tanpa secret. Rahasia hanya lewat env platform / GitHub Secrets.

## Lingkungan

| Env | Tujuan | Catatan |
|---|---|---|
| `local` | Dev / workshop | `AI_MOCK_MODE=true` OK |
| `testing` | Pest / CI | MySQL service di GitHub Actions |
| `production` | Railway (workflow deploy) | Mock AI off kecuali sengaja |

## Variabel production (tanpa nilai secret)

Wajib diisi di platform host (bukan di git):

| Key | Fungsi |
|---|---|
| `APP_KEY` | Enkripsi Laravel |
| `APP_URL` | URL publik HTTPS |
| `APP_ENV=production` | |
| `APP_DEBUG=false` | |
| `DB_*` | MySQL production |
| `AI_API_KEY` / key di `ai_providers` | Hanya backend |
| `AI_MOCK_MODE` | `false` di prod nyata; `true` hanya demo offline |
| Session/cache/queue | Sesuaikan driver host (`database`/`redis`) |

Opsional: `MAIL_*`, `LOG_CHANNEL`, `FILESYSTEM_DISK=public` + storage link.

Jangan commit `.env`. Contoh lokal: `.env.example` (`AI_MOCK_MODE=true`).

## CI

Workflow: `.github/workflows/ci.yml`

1. PHP 8.4 + MySQL 8.4
2. `composer install` → `migrate:fresh --seed` → `php artisan test`
3. Larastan (`vendor/bin/phpstan analyse`)

Branch: `push`/`PR` ke `main` (dan `develop` untuk test).

## Deploy (Railway)

Workflow: `.github/workflows/deploy.yml`

- Trigger: CI sukses di `main`
- Secret: `RAILWAY_TOKEN`
- Langkah: `railway up` lalu `migrate --force`

Pastikan service Railway punya env production di atas + volume/disk untuk `storage/app/public` bila upload media dipakai.

## Smoke setelah deploy

1. Login `naya@aksara.test` (atau akun prod) → `/dashboard`
2. Buka materi edit → Co-Pilot kirim pesan singkat (mock/key sesuai env)
3. Export PDF satu rencana → kop sekolah tampil
4. `php artisan storage:link` sudah jalan di image/host

## Rollback singkat

1. Di Railway: redeploy deployment sebelumnya (UI Deployments → Redeploy).
2. Jika migrasi sudah jalan dan tidak kompatibel mundur: siapkan migrasi reverse atau restore DB snapshot host sebelum migrate.
3. Jangan `migrate:fresh` di production.
4. Setelah rollback: cek `APP_KEY`/env tidak berubah tak sengaja; jalankan smoke login + satu halaman guru.

## AI & keamanan

- API key hanya env / baris `ai_providers` (terenkripsi/tersimpan DB admin).
- Browser tidak boleh memegang key.
- Workshop: biarkan `AI_MOCK_MODE=true` agar generate tanpa kuota.
