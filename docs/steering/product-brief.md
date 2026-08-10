# Product Brief — Aksara

## Ringkasan

**Aksara** adalah prototipe platform manajemen aktivitas pembelajaran berbantuan AI untuk sekolah. Proyek ini adalah output workshop **Bimtek AI dalam Pembelajaran**.

Fokusnya adalah *vertical slice*: guru merancang pembelajaran dengan bantuan AI → siswa membaca materi & mengerjakan kuis → guru mencatat kehadiran & evaluasi → laporan dapat dilihat sesuai role.

## Masalah yang diselesaikan

Guru membutuhkan cara yang lebih tertata untuk:

1. menyusun administrasi pembelajaran dan materi;
2. menyajikan materi sesuai rencana;
3. mencatat kehadiran dan aktivitas siswa;
4. melakukan penilaian setelah pembelajaran;
5. melihat laporan serta evaluasi pembelajaran.

AI berperan sebagai **asisten draf** (CP/TP/ATP, rencana, materi). Guru tetap mereview, mengedit, dan memutuskan publikasi.

## Pengguna (role)

| Role | Kode | Kebutuhan utama |
|---|---|---|
| Administrator | `admin` | Kelola pengguna, matrix akses, settings AI, oversight |
| Guru | `teacher` | Buat rencana, generate draf AI, publish materi, absensi, evaluasi, laporan |
| Siswa | `student` | Baca materi published, kerjakan kuis |
| Wali kelas | `homeroom_teacher` | Lihat ringkasan kelas yang menjadi tanggung jawabnya |
| Wali murid | `parent` | Lihat ringkasan anak yang terhubung (mode baca) |

## Tujuan demo (MVP)

Setiap kelompok mampu mendemokan alur berikut dengan data fiktif:

```text
Guru membuat rencana → AI membuat draf → guru review dan publikasi materi
→ siswa membaca materi → siswa mengerjakan kuis → guru mencatat hadir
→ guru/wali melihat laporan → guru menulis evaluasi.
```

## Scope (in scope)

- Rencana pembelajaran + AI draft terstruktur (JSON), dual-mode AI/manual.
- Review/edit draf oleh guru, lalu publish materi (TipTap + Co-Pilot).
- Siswa membaca materi berstatus `published`.
- Kuis pilihan ganda + penilaian otomatis.
- Kehadiran per rencana, evaluasi/refleksi guru.
- Referensi kurikulum (CP/TP/ATP), users, RBAC matrix, system settings + AI providers.
- Dashboard/laporan sesuai role.
- Mode `AI_MOCK_MODE` untuk workshop offline tanpa API key.

## Non-scope

- Integrasi Dapodik, SSO sekolah, atau data produksi.
- Penjadwalan kompleks dan kalender akademik.
- Kenaikan kelas, rapor resmi, tanda tangan elektronik.
- Bank soal besar, proctoring, analitik prediktif, notifikasi WhatsApp.
- Multi-tenancy banyak sekolah dan aplikasi mobile native.
- AI yang otomatis menerbitkan dokumen tanpa persetujuan guru.
- REST API publik (lihat ADR-010 — UI = Inertia web session).

## Stack

| Layer | Teknologi |
|---|---|
| Runtime | PHP 8.4 |
| Framework | Laravel 13 |
| UI | Inertia.js + Vue 3 + Tailwind CSS |
| Editor konten | TipTap (`@tiptap/vue-3`) + KaTeX opsional (STEM) |
| Auth | Laravel Breeze (Inertia/Vue) |
| RBAC | Spatie Permission + kolom `users.role` |
| Database | MySQL |
| Testing | Pest / PHPUnit |
| Analysis | Larastan / PHPStan, Laravel Pint |

Keputusan UI: **ADR-010** (Inertia + Vue sebagai stack resmi).

## Kriteria sukses

1. Alur vertical slice dapat didemokan end-to-end di UI Inertia.
2. Authorization permission + role ditegakkan di backend.
3. Output AI selalu berstatus draf hingga guru publish.
4. Tidak ada data pribadi siswa nyata di prompt, repo, atau lingkungan demo.
5. Dokumentasi di `docs/steering/` cukup untuk agent/developer lain melanjutkan.

## Sumber terkait

- `docs/steering/business-rules.md` — aturan proses dan otorisasi
- `docs/steering/database-schema.md` — model data
- `docs/steering/api-contract.md` — route dan kontrak AI
- `docs/steering/coding-standards.md` — konvensi kode & struktur Vue
- `docs/steering/handover.md` — status kerja terkini
- `docs/demo-users.md` — akun demo
