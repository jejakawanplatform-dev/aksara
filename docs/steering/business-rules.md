# Business Rules — Aksara

Dokumen ini adalah sumber kebenaran untuk peran, alur proses, dan aturan yang wajib ditegakkan di backend.

## 1. Role dan kemampuan

| Role | Kode | Boleh | Tidak boleh |
|---|---|---|---|
| Administrator | `admin` | CRUD pengguna, matrix hak akses, dashboard sistem, lihat/CRUD referensi, supervisi global rencana & materi pembelajaran (ekspor, hapus massal, pembersihan data yatim) | Mengubah data nilai/kuis siswa di luar wewenang supervisi |
| Guru | `teacher` | CRUD rencana miliknya, generate AI, review/publish, absensi, evaluasi, laporan guru, referensi kurikulum mapel ampuannya (termasuk mapel Informatika untuk kebutuhan Bimtek) | Mengelola akun pengguna / matrix akses |
| Siswa | `student` | Baca materi `published`, kerjakan kuis miliknya, lihat hasilnya sendiri | Membuat/mengedit rencana, melihat draf, absensi global |
| Wali kelas | `homeroom_teacher` | Lihat ringkasan kelas yang diampu | Mengubah rencana/materi/absensi guru mapel |
| Wali murid | `parent` | Baca ringkasan anak yang terhubung di `parent_students` | Melihat data anak lain / mengubah data akademik |

> Otorisasi dicek di backend: middleware `permission` (fitur) + `users.role` untuk identitas dashboard (ADR-003/007). Nav/sidebar Inertia hanya menyembunyikan menu — **tidak cukup** sebagai keamanan. Matrix default bisa diubah admin di `/access` (`Access/Index.vue`).

## 2. Siklus rencana pembelajaran

```text
draft → reviewed → published
```

| Status | Arti | Siapa yang mengubah |
|---|---|---|
| `draft` | Rencana baru / masih diedit; draf AI belum disetujui | Guru pemilik / Admin (supervisi) |
| `reviewed` | Draf AI disetujui; materi terkait dibuat sebagai draf | Guru pemilik / Admin (supervisi) |
| `published` | Rencana + materi siap diakses siswa | Guru pemilik / Admin (supervisi) |

### Aturan terkait

1. Guru hanya berhak mengubah atau menghapus rencana pembelajaran miliknya sendiri (`teacher_id === Auth::id()`). Administrator memiliki wewenang supervisi global (`plans.manage`) untuk pemeliharaan sistem, audit, ekspor, dan pembersihan massal (Spec 08).
2. Otorisasi referensi kurikulum guru (`ReferenceController`): Guru hanya dapat memodifikasi CP/TP/ATP untuk mapel yang diampu via `subject_teachers` atau rencana aktif miliknya, dengan pengecualian khusus mapel Informatika (`INF`) yang terbuka untuk seluruh guru peserta workshop Bimtek (Spec 06).
3. Generate AI hanya dari konteks pembelajaran (fase, kelas, mapel, topik, durasi, tujuan, kebutuhan belajar, referensi kurikulum).
4. Output AI **selalu draf** — wajib direview guru sebelum publish.
5. Label UI: “Draf hasil AI — wajib direview guru”.
6. Publish mengubah status rencana dan materi terkait menjadi `published` (serta mengisi `published_at` pada materi).

## 3. Materi pembelajaran

| Status | Arti |
|---|---|
| `draft` | Belum terlihat siswa |
| `published` | Boleh dibaca siswa |
| `archived` | Tidak aktif (cadangan; UI archive belum wajib) |

### Aturan

1. Siswa hanya boleh membaca materi berstatus `published`.
2. Idealnya materi difilter ke kelas yang diikuti siswa (`class_members`) — ditegakkan di `MaterialController` index/show + dashboard siswa.
3. Membuka materi mencatat `learning_events` (`material_opened` / `material_read`).
4. Konten teks seksi disimpan di JSON `learning_materials.content` (`title`, `sections[].heading/body`, `reflectionQuestion`).
5. File gambar materi (jika ada) disimpan di disk `public` (`materials/{material_id}/`); HTML body hanya boleh mereferensikan `/storage/...` atau `data:image/...` tepercaya (ADR-008).
6. Hasil Asisten Aksara **tidak** boleh memasukkan URL file gambar fiktif ke body seksi. Saran ilustrasi + prompt AI Image tampil **di chat saja** (`illustrationTips`), bukan di konten siswa/export.
7. Materi pembelajaran dapat diekspor ke format cetak PDF resmi (A4 Portrait berkop sekolah), Microsoft Word (.docx), dan Markdown (.md) untuk kebutuhan belajar luring dan arsip kurikulum (ADR-014).

## 4. Generasi AI

| Status review | Arti |
|---|---|
| `pending` | Baru digenerate, menunggu review |
| `approved` | Guru menyetujui draf |
| `rejected` | Ditolak (skema siap; aksi UI boleh ditambah) |

### Aturan keamanan AI

1. Backend saja yang memanggil AI API. API key tidak boleh ke browser.
2. Jangan kirim identitas siswa, NIS, nilai individual, atau data pribadi ke AI.
3. Validasi schema output di backend sebelum disimpan.
4. Simpan jejak minimal: waktu, model, ringkasan input, output, status, pembuat.
5. Timeout + error handling + fallback (`AI_MOCK_MODE=true` untuk workshop).
6. Jangan mengklaim hasil AI sebagai dokumen kurikulum resmi.
7. Capability image generation ditandai di katalog vendor (`supports_image_generation`). Ceklis UI Generate Gambar AI hanya jika `AiProvider::hasConfiguredImageGeneration()`.
8. Untuk provider teks-only: ceklis Link Gambar Ilustrasi boleh menyertakan tautan pencarian Unsplash/Wikimedia + prompt siap salin; dilarang `<img>` hotlink file.

## 5. Kehadiran

1. Satu record unik per pasangan `(plan_id, student_id)`.
2. Status: `present` | `excused` | `sick` | `absent`.
3. Guru pemilik rencana yang mengisi/mengubah.
4. Upsert diperbolehkan (updateOrCreate).
5. Peringatan Dini Kehadiran (*Early Warning Indicator*): Siswa dengan persentase kehadiran di bawah 75% (`< 75%`) otomatis mendapatkan penanda visual untuk intervensi dini wali kelas dan guru.
6. Rekapitulasi Presensi Multi-Format: Rekapitulasi kehadiran kelas dapat diekspor ke format cetak PDF resmi (Landscape A4 dengan Kop Surat Sekolah dan blok tanda tangan) serta spreadsheet Excel (.xlsx) dengan kalkulasi otomatis (ADR-014).

## 6. Kuis

1. Status kuis: `draft` | `published`.
2. Siswa hanya mengerjakan kuis `published` yang relevan dengan kelas/rencananya.
3. Satu percobaan per siswa per kuis (unique `quiz_id` + `student_id`).
4. Penilaian otomatis dari `correct_answer`; skor 0–100.
5. Passing score default helper: 70 (boleh disesuaikan kemudian).

## 7. Evaluasi guru

1. Satu evaluasi unik per `(plan_id, teacher_id)`.
2. Field wajib: `notes` (min. 20 karakter), `challenges` & `next_action` (min. 10).
3. Hanya guru pemilik rencana.

## 8. Laporan

| Pemirsa | Isi yang boleh dilihat |
|---|---|
| Guru | Ringkasan rencana miliknya: kehadiran, kuis, status evaluasi |
| Wali kelas | Ringkasan kelas yang diampu |
| Wali murid | Ringkasan anak terhubung saja (mode baca) |

## 9. Aksi Massal & Tata Kelola Data Pengguna

1. **Guardrail Proteksi Diri:** Administrator tidak dapat menghapus akunnya sendiri melalui fitur `bulkDestroy` (`Auth::id()` otomatis dilewati).
2. **Perlindungan Integritas Relasional:** Akun guru yang memiliki rencana pembelajaran aktif dan wali kelas yang memiliki rombel binaan aktif dilindungi dari penghapusan massal, dan sistem memberikan rekap peringatan eksplisit.
3. **Pipa Impor Excel:** Impor pengguna memvalidasi format kolom template wajib (`nama`, `email`, `role`). Duplikasi ditangani via opsi `skip` atau `update`. Kredensial acak sementara hanya tersimpan dalam session flash dan dapat diunduh sekali (ADR-015).

## 10. Data demo & privasi

1. Gunakan data fiktif (seeder `DemoDataSeeder` / `php artisan aksara:seed-demo`).
2. Jangan commit `.env` atau API key.
3. Jangan memasukkan data pribadi nyata ke prompt, repository, atau lingkungan demo.

## 11. Aturan yang belum sepenuhnya ditegakkan (debt)

Catatan untuk agent/developer:

- Policy Laravel per-model **opsional** (P3 / deferred) — otorisasi fitur lewat Spatie permission + kepemilikan data. Matrix di `/access`. Lihat ADR-003/007.

Lihat juga `docs/steering/decision-log.md` dan `docs/steering/handover.md`.
