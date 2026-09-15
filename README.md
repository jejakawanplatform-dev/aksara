# Aksara 🎓

> **Platform Manajemen Pembelajaran Berbantuan AI untuk Ekosistem Sekolah Indonesia.**  
> Dirancang modern, adaptif, dan berpusat pada guru — menggabungkan kemudahan Kurikulum Merdeka dengan kecerdasan asistif AI.

[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Laravel 13](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![Inertia.js v3](https://img.shields.io/badge/Inertia.js-v3-9553E9?style=flat-square&logo=inertia&logoColor=white)](https://inertiajs.com)
[![Vue 3](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=flat-square&logo=vuedotjs&logoColor=white)](https://vuejs.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.x-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-Level_9_Passed-brightgreen?style=flat-square&logo=php)](phpstan.neon)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg?style=flat-square)](LICENSE)

---

## 🌟 Tentang Aksara

**Aksara** lahir sebagai solusi nyata untuk meringankan beban administratif pendidik di Indonesia tanpa mengorbankan kendali dan integritas pedagogis. 

Menggunakan arsitektur monolitik modern berbasis **Laravel 13 + Inertia.js + Vue 3**, Aksara menghadirkan pengalaman pengguna secepat Single Page Application (SPA) dengan keandalan, keamanan, dan kemudahan pengembangan ekosistem Laravel.

Aksara menempatkan AI secara etis: **AI hadir sebagai asisten penyusun draf (*co-pilot*)**, sementara **guru memegang kendali penuh (*human-in-the-loop*)** atas setiap materi yang dipublikasikan ke siswa.

---

## ✨ Fitur-Fitur Unggulan

Aksara mengintegrasikan kebutuhan 5 peran pemangku kepentingan sekolah dalam satu sistem yang harmonis:

```text
┌──────────────┐      ┌──────────────┐      ┌──────────────┐
│  🧑‍🏫 GURU    │      │  🎒 SISWA    │      │  👨‍👩‍👧 WALI    │
│ RPP, AI Draft│ ───► │ Baca Materi, │ ◄─── │ Pantau Nilai │
│ Editor, Kuis │      │ Kuis & Skor  │      │ & Presensi   │
└──────────────┘      └──────────────┘      └──────────────┘
       │                     ▲                     │
       ▼                     │                     ▼
┌──────────────┐             │              ┌──────────────┐
│ 📋 WALI KELAS│ ────────────┘              │ 🛠️ ADMIN     │
│ Rekap Absensi│                            │ RBAC, Users, │
│ & Rombel     │                            │ AI Providers │
└──────────────┘                            └──────────────┘
```

### 🧑‍🏫 1. Ruang Kerja Guru (Pedagogi Cerdas)
* **Penyusun RPP / Modul Ajar Otomatis:** Menyusun rencana pembelajaran berbasis Capaian Pembelajaran (CP), Tujuan Pembelajaran (TP), dan Alur Tujuan Pembelajaran (ATP) dengan bantuan asisten AI terintegrasi.
* **TipTap Rich Text & KaTeX Math Editor:** Editor materi kaya fitur yang mendukung penulisan rumus matematika LaTeX murni, penyisipan tabel terstruktur, format tipografi rapi, serta pengaturan dimensi gambar.
* **AI Material Co-Pilot:** Membantu guru menyempurnakan, memformat, dan memperkaya materi pembelajaran tanpa menghapus struktur tulisan yang sudah ada.
* **Penyimpanan Media Kontekstual:** Manajemen berkas gambar materi mandiri dengan direktori terisolasi (*context-scoped media storage*).
* **Kuis Interaktif & Pembuat Soal:** Pembuatan instan kuis evaluasi berbasis materi dengan kunci jawaban dan bobot skor otomatis.
* **Presensi & Refleksi Mengajar:** Formulir pencatatan absensi siswa harian serta jurnal refleksi kendala dan tindak lanjut evaluasi pembelajaran.

### 🎒 2. Ruang Belajar Siswa (Fokus & Terarah)
* **Antarmuka Membaca Nyaman:** Tampilan baca materi ramah mata, bersih dari distraksi (*distraction-free*), dan responsif di perangkat ponsel maupun komputer.
* **Pelacakan Belajar Otomatis:** Sistem secara otomatis merekam histori akses dan keaktifan siswa saat mempelajari bahan ajar.
* **Pengerjaan Kuis Realtime:** Antarmuka pengerjaan kuis interaktif dengan timer, navigasi soal, dan kalkulasi nilai otomatis saat jawaban dikirimkan.

### 👨‍👩‍👧 3. Portal Wali Murid (Transparansi Pendidikan)
* **Pemantauan Perkembangan Anak:** Dashboard khusus orang tua untuk melihat rekapitulasi kehadiran anak di kelas secara transparan.
* **Riwayat Skor & Capaian Kuis:** Orang tua dapat memantau perolehan nilai latihan dan kuis anak secara berkala tanpa menunggu pembagian rapor fisik.

### 📋 4. Portal Wali Kelas (Pengawasan Rombel)
* **Rekapitulasi Absensi Terpadu:** Melihat ringkasan persentase kehadiran (Hadir, Sakit, Izin, Alpa) seluruh siswa dalam rombel binaan.
* **Monitoring Keterlaksanaan:** Memantau ketercapaian jadwal belajar dan aktivitas kelas binaan secara menyeluruh.

### 🛠️ 5. Kendali Administrator Sekolah (Tata Kelola & Keamanan)
* **Manajemen Pengguna & Bulk Action Toolkit:** Pengelolaan data akun dengan fitur seleksi multi-baris (*cross-page matching*), bilah aksi massal (*bulk toolbar*), dan pengaman ganda konfirmasi hapus (*double-confirmation safety modal*).
* **Matrix Hak Akses Dinamis (RBAC):** Pemetaan izin akses granular (*Spatie Permission*) per role sekolah yang dapat dikonfigurasi langsung dari antarmuka web.
* **Katalog AI & Failover Provider:** Manajemen multi-kredensial AI (OpenAI, Google Gemini, Anthropic, dll.) dengan sistem prioritas *fallback* otomatis dan **AI Mock Mode** untuk simulasi pelatihan tanpa kuota internet berbayar.
* **Ekspor Dokumen Resmi:** Cetak RPP dan laporan pembelajaran ke format PDF standar kop sekolah resmi atau format lembar kerja Excel.

---

## 🎨 Estetika & Design System

Aksara menggunakan tema desain **Coastal Light Enterprise (ADR-012)**:
* **Palet Warna Teduh:** Mengombinasikan nuansa *Ocean Teal* dan *Deep Slate* yang profesional, bersih, dan menenangkan mata saat digunakan berjam-jam oleh guru.
* **Micro-Interactions & Feedback:** Transisi halus, status badge informatif, skeleton loaders, dan dialog konfirmasi interaktif.
* **Pustaka Komponen UI Terstandarisasi:** Seluruh antarmuka dibangun dari komponen modular (`Btn`, `Card`, `Modal`, `Field`, `Table`, `Pagination`, `ExportMenu`, `BulkToolbar`, dll.).

---

## 🚀 Memulai dengan Cepat

Ingin mencoba Aksara di komputer lokal? Ikuti langkah mudah berikut:

```bash
# 1. Clone repositori
git clone https://github.com/jejakawanplatform-dev/aksara.git
cd aksara

# 2. Pasang dependensi
composer install
npm install && npm run build

# 3. Konfigurasi environment
cp .env.example .env
php artisan key:generate

# 4. Migrasi database & isi data demo
php artisan migrate:fresh --seed
php artisan storage:link

# 5. Jalankan aplikasi
php artisan serve
```

Buka peramban Anda di **`http://localhost:8000`**.

> 🔑 **Akun Pengujian Demo:**  
> Daftar lengkap email dan password untuk seluruh role (Admin, Guru, Siswa, Wali Kelas, Wali Murid) tersedia pada panduan:  
> 👉 **[Dokumentasi Akun Demo & Kredensial](docs/steering/demo-accounts.md)**

---

## 📖 Dokumentasi Lengkap

Untuk panduan mendalam seputar arsitektur teknis, aturan coding, dan alur per modul:

* 🏛️ **[Pusat Arsitektur & Panduan Pengembang](docs/README.md)** — Struktur direktori, panduan teknis, stack detail, dan alur QA.
* 📜 **[Log Perubahan & Rilis (CHANGELOG.md)](CHANGELOG.md)** — Riwayat versi dan perubahan fitur dari awal rilis hingga saat ini.
* 🧭 **[Steering Guidelines (docs/steering/)](docs/steering/)** — Aturan bisnis, standar kode, keputusan arsitektur (ADR), dan handover.
* 📋 **[Spesifikasi Modul (docs/spec/)](docs/spec/)** — Dokumentasi teknis 19 modul aplikasi (scaffold, RBAC, RPP, kuis, bulk actions, smoke test, dll.).
* 💬 **[Diskusi Antar Agent & RFC (docs/discussions/)](docs/discussions/)** — Ruang kolaborasi dan telaah usulan fitur baru.

---

## 👨‍💻 Pengembang & Hak Cipta

Aksara dirancang dan dikembangkan dengan penuh dedikasi oleh:

* **Pengembang:** **jejakawan**
* **Situs Web:** [https://jejakawan.com](https://jejakawan.com)
* **Hubungi:** [info@jejakawan.com](mailto:info@jejakawan.com)

---

## 📄 Lisensi

Proyek ini dilisensikan di bawah **[MIT License](LICENSE)** — bebas digunakan, dipelajari, dimodifikasi, dan didistribusikan kembali untuk kemajuan pendidikan Indonesia dengan tetap menyertakan pemberitahuan hak cipta asli.

Lihat berkas [`LICENSE`](LICENSE) dan [`NOTICE`](NOTICE) untuk informasi hukum selengkapnya.
