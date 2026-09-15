# Aksara

> Platform manajemen pembelajaran berbasis AI untuk sekolah.  
> Output workshop **Bimtek AI dalam Pembelajaran** — Laravel 13 + Inertia + Vue 3.

---

## Stack

| Layer | Teknologi | Versi |
|---|---|---|
| Runtime | PHP | 8.4 |
| Framework | Laravel | 13.x |
| UI | Inertia.js + Vue 3 + Tailwind | ADR-010 |
| Editor | TipTap (`@tiptap/vue-3`) | 3.x |
| Auth | Laravel Breeze | 2.x |
| RBAC | Spatie Permission | 8.x |
| Database | MySQL | 9.x |
| Testing | PestPHP | 4.x |
| Analysis | Larastan | 3.x |

Referensi perilaku stack UI lama (Livewire/Alpine, read-only): `/home/jejakawan/aksara-ref-livewire`.

---

## Fitur

- **Guru** — Buat rencana pembelajaran, generate CP/TP/ATP via AI, input kehadiran, refleksi evaluasi
- **Siswa** — Akses materi yang diterbitkan, kerjakan quiz interaktif dengan penilaian otomatis
- **Wali Kelas** — Lihat rekap kehadiran per kelas
- **AI Mock Mode** — Generate draf tanpa API key (cocok untuk workshop offline)

---

## Akun Demo

Daftar akun dan kredensial pengujian lokal dipindahkan ke dokumentasi internal:  
👉 **[Lihat Akun Demo & Kredensial](docs/steering/demo-accounts.md)**

---

## Instalasi Lokal

```bash
# 1. Clone & install dependencies
git clone https://github.com/USERNAME/aksara.git
cd aksara
composer install
npm install && npm run build

# 2. Konfigurasi
cp .env.example .env
php artisan key:generate
# Edit .env: isi DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 3. Buat database MySQL
mysql -u root -p -e "CREATE DATABASE aksara_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Migrate + seed data demo
php artisan migrate:fresh --seed
php artisan storage:link

# 5. Jalankan server
php artisan serve
# Buka: http://127.0.0.1:8000
```

---

## Perintah Berguna

```bash
php artisan aksara:seed-demo             # Reset + isi ulang data demo
php artisan test                         # Pest feature/unit suite
php artisan test --filter=CriticalJourneySmokeTest  # Smoke test lintas 5 role
vendor/bin/phpstan analyse --memory-limit=1G        # Static analysis Larastan (Level 9)
vendor/bin/pint --test                   # Pemeriksaan format kode
npm run build                            # Kompilasi aset Vite (Vue 3/Inertia)
```

---

## Dokumentasi

- Log Perubahan & Rilis: [`CHANGELOG.md`](CHANGELOG.md)
- Steering (aturan tetap): `docs/steering/`
- Deploy / env / rollback: `docs/steering/deployment.md`
- Spek bertahap (01–19): `docs/spec/`
- Diskusi antar agent / RFC: `docs/discussions/`
- Handover status: `docs/steering/handover.md`
