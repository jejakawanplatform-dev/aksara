# Dokumentasi Teknis & Panduan Arsitektur — Aksara

Selamat datang di pusat dokumentasi teknis dan arsitektur repositori **Aksara**. Berkas ini menjadi sumber acuan utama bagi pengembang perangkat lunak, kontributor, dan AI Agent yang bekerja pada repositori ini.

---

## 🏛️ 1. Tumpukan Teknologi (Technology Stack)

Aksara dibangun dengan pola monolitik modern berbasis **Inertia.js + Vue 3**, menggabungkan kecepatan Single Page Application dengan ketangguhan backend Laravel:

| Lapisan | Teknologi | Versi | Catatan Arsitektur |
|---|---|---|---|
| **Runtime** | PHP | 8.4+ | Enums bertipe, named arguments, match expressions |
| **Framework** | Laravel | 13.x | Routing web murni, form validation, transactional DB |
| **UI Adapter** | Inertia.js | v3.x | Single page application tanpa REST API terpisah |
| **Frontend Framework**| Vue | 3.x | Composition API (`<script setup>`), Single File Components |
| **Styling & CSS** | Tailwind CSS | 3.x | Token kustom `.aksara-*` (ADR-012 Coastal Light Enterprise) |
| **Rich Text Editor** | TipTap | 3.x | `@tiptap/vue-3` + ekstensi KaTeX LaTeX math lazily loaded |
| **Otorisasi & RBAC** | Spatie Permission | 8.x | Matrix perizinan dinamis + kolom `users.role` (ADR-007) |
| **Database** | MySQL | 9.x | Enkapsulasi transaksi, soft-deletes pada entitas pembelajaran |
| **Testing Suite** | PestPHP | 4.x | HTTP feature tests, auth verification, smoke testing |
| **Static Analysis** | Larastan / PHPStan | **Level 9** | Level analisis statis maksimal dengan `phpstan-baseline.neon` |
| **Code Formatter** | Laravel Pint | Terbaru | Standar format kode PHP |
| **Asset Bundler** | Vite | 7.x | Hot Module Replacement (HMR) + bundle split |

---

## 📂 2. Peta Direktori & Struktur Kode

```text
aksara/
├── app/
│   ├── Enums/                 # Enum status (AttendanceStatus, MaterialStatus, UserRole)
│   ├── Http/Controllers/      # Inertia Controllers dikelompokkan per domain
│   │   ├── Attendance/        # Presensi kehadiran siswa & rekap
│   │   ├── Dashboard/         # Dashboard multi-role (Admin, Guru, Siswa, Wali, dll.)
│   │   ├── Evaluation/        # Evaluasi & refleksi mengajar
│   │   ├── Materials/         # Materi pembelajaran & upload media
│   │   ├── Plans/             # Rencana pembelajaran (RPP) & AI generation
│   │   ├── Quiz/              # Pembuatan kuis & submission pengerjaan
│   │   ├── References/        # Data master kurikulum (CP/TP/ATP) & sekolah
│   │   ├── Reports/           # Laporan pembelajaran
│   │   ├── Settings/          # Setelan AI providers & failover priority
│   │   └── Users/             # CRUD akun & bulk destroy
│   ├── Http/Middleware/       # RBAC, Inertia share, profile verification
│   ├── Models/                # Model Eloquent dengan PHPDoc type hints lengkap
│   ├── Services/              # Logika AI & domain murni (AiDraftService, AiProviderClient)
│   └── Support/               # Helper navigasi (SidebarNav) & sanitasi HTML
├── database/
│   ├── migrations/            # Skema tabel database
│   └── seeders/               # DemoDataSeeder, SystemSettingSeeder, AiProviderSeeder
├── resources/
│   ├── js/
│   │   ├── Components/
│   │   │   ├── tiptap/        # TipTapEditor.vue, TipTapToolbar.vue, MediaPicker.vue
│   │   │   └── ui/            # Design System (Btn, Card, Modal, BulkToolbar, dll.)
│   │   ├── Composables/       # useBulkSelect.js, authValidation.js
│   │   ├── Layouts/           # AppLayout.vue (auth) & GuestLayout.vue (landing/auth)
│   │   ├── Pages/             # Halaman antarmuka Inertia per domain
│   │   └── inertia-app.js     # Entry point Vue 3 & createApp
│   └── views/
│       ├── app.blade.php      # Satu-satunya root HTML shell untuk Inertia
│       └── exports/           # Blade templates untuk ekspor dokumen PDF
├── routes/
│   ├── web.php                # Rute utama aplikasi terproteksi middleware RBAC
│   └── auth.php               # Rute Breeze Inertia autentikasi
├── tests/
│   ├── Feature/               # Pest HTTP feature tests per modul
│   │   └── Smoke/             # CriticalJourneySmokeTest.php (alur 5 role terpadu)
│   └── Unit/                  # Pengujian helper & isolasi murni
└── docs/                      # Pusat dokumentasi sistem (steering, spec, discussions)
```

---

## ⚙️ 3. Panduan Instalasi & Pengembangan Lokal

### Prasyarat:
* PHP >= 8.4 dengan ekstensi `pdo_mysql`, `mbstring`, `intl`, `fileinfo`.
* Composer >= 2.x
* Node.js >= 20.x & npm
* MySQL Server >= 8.0 atau 9.x

### Langkah Instalasi:

```bash
# 1. Pasang dependensi backend & frontend
composer install
npm install

# 2. Setup berkas konfigurasi environment
cp .env.example .env
php artisan key:generate

# 3. Sesuaikan konfigurasi database di .env:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=aksara_db
# DB_USERNAME=root
# DB_PASSWORD=

# 4. Jalankan migrasi dan seed data demo lengkap
php artisan migrate:fresh --seed

# 5. Buat tautan symlink berkas penyimpanan publik
php artisan storage:link

# 6. Kompilasi aset frontend
npm run build
# Atau jalankan dev server HMR:
# npm run dev

# 7. Jalankan server lokal
php artisan serve
```

---

## 🛡️ 4. Gerbang Pengujian (Quality Assurance Protocol)

Sesuai aturan baku di [`docs/steering/coding-standards.md`](steering/coding-standards.md), setiap perubahan kode **wajib** melalui gerbang QA berikut sebelum dinyatakan selesai atau di-commit:

```bash
# 1. Full Pest Test Suite (100% wajib lolos / passed)
php artisan test

# 2. Critical Journey Smoke Test Lintas 5 Role
php artisan test --filter=CriticalJourneySmokeTest

# 3. Static Analysis Larastan / PHPStan Level 9 (Wajib 0 Error)
vendor/bin/phpstan analyse --memory-limit=1G

# 4. Verifikasi Format Kode PHP
vendor/bin/pint --test

# 5. Kompilasi Aset Frontend Tanpa Error / Warning
npm run build
```

---

## 🗺️ 5. Peta Navigasi Dokumentasi Internal

Dokumentasi Aksara terbagi secara modular ke dalam 4 folder terarah di bawah `docs/`:

```text
docs/
├── README.md                  # Halaman yang sedang Anda baca (Technical Hub)
├── steering/                  # Aturan kemudi permanen & tata kelola proyek
├── spec/                      # Spesifikasi teknis bertahap (Tahap 01 s/d 19)
├── discussions/               # Forum kolaborasi, ide, dan RFC antar agent/human
└── audit/                     # Laporan audit keselarasan kode & dokumentasi
```

### 🧭 5.1. Steering Directory (`docs/steering/`)
Aturan permanen yang tidak boleh dilanggar tanpa Architecture Decision Record (ADR):
* [**`product-brief.md`**](steering/product-brief.md): Visi produk, profil pengguna, dan ringkasan batasan sistem.
* [**`business-rules.md`**](steering/business-rules.md): Aturan bisnis inti alur RPP, materi, otorisasi, dan AI.
* [**`coding-standards.md`**](steering/coding-standards.md): Standar penamaan, pola Vue/Inertia, DoD, dan **Protokol QA Wajib**.
* [**`decision-log.md`**](steering/decision-log.md): Buku catatan keputusan arsitektur (**ADR-001 s/d ADR-016**).
* [**`demo-accounts.md`**](steering/demo-accounts.md): Kredensial dan daftar akun demo untuk seluruh peran.
* [**`handover.md`**](steering/handover.md): Status operasional dan catatan handoff antar developer / agent.
* [**`testing-strategy.md`**](steering/testing-strategy.md): Lapisan strategi pengujian dari unit, feature, hingga smoke test.
* [**`file-header.md`**](steering/file-header.md): Standar header lisensi MIT pada setiap berkas sumber baru.

### 📋 5.2. Specification Directory (`docs/spec/`)
Dokumentasi granular 19 tahap kemampuan produk (masing-masing memuat `plan.md`, `tasks.md`, `implementation.md`, dan `verification.md`):
* [**Daftar Lengkap Spek 01 s/d 19**](spec/README.md)
* *Sorotan Utama:*
  * [Tahap 14: Sistem Ekspor Multi-Format & PDF Resmi](spec/14-exports-pdf/)
  * [Tahap 15: TipTap Rich Editor & KaTeX](spec/15-tiptap-editor/)
  * [Tahap 16: Context-Scoped Media](spec/16-context-media/)
  * [Tahap 17: Design System Vue SoT](spec/17-design-system/)
  * [Tahap 18: Bulk Actions & Selection Toolkit](spec/18-bulk-actions/)
  * [Tahap 19: Smoke Testing & QA Suite Enforcer](spec/19-smoke-testing-qa/)

### 💬 5.3. Discussions & RFC Directory (`docs/discussions/`)
Forum terbuka untuk merumuskan trade-off teknis sebelum keputusan diresmikan:
* [**Pedoman Diskusi & Daftar Thread**](discussions/README.md)
* [2026-09-15: Fitur Bulk Actions pada Data Table](discussions/2026-09-15-fitur-bulk-actions-tabel.md) (`ADOPTED`)
* [2026-09-15: Evaluasi Kesenjangan Test Suite & Smoke Test](discussions/2026-09-15-strategi-smoke-test-dan-gap-test-suite.md) (`ADOPTED`)
* [2026-09-16: Fitur Export / Import Data Pengguna Menggunakan Excel](discussions/2026-09-16-fitur-export-import-data-pengguna-excel.md) (`ADOPTED`)
* [2026-09-16: Fitur Download / Ekspor Materi Pembelajaran](discussions/2026-09-16-fitur-download-dan-ekspor-materi-pembelajaran.md) (`ADOPTED`)
* [2026-09-16: Analisis, Evaluasi & Penyempurnaan Fitur Daftar Hadir](discussions/2026-09-16-analisis-dan-penyempurnaan-fitur-daftar-hadir.md) (`ADOPTED`)

---

## 📌 6. Prinsip Etis AI & Batasan Keamanan

1. **AI Output Is Always a Draft:** Seluruh teks yang dihasilkan model AI berstatus `draft` dan memerlukan persetujuan eksplisit guru sebelum terbit (ADR-001).
2. **Backend-Only AI Execution:** API key provider AI tidak pernah diekspos ke klien/peramban; semua panggilan melalui backend service terisolasi (ADR-002).
3. **Perlindungan Data Pribadi Siswa:** Nama lengkap, identitas personal, atau data sensitif siswa dilarang keras dikirim ke prompt model AI eksternal.
4. **Resiliensi & Failover:** Katalog AI mendukung failover otomatis ke provider alternatif bila provider utama mengalami gangguan kuota atau rate-limit.
