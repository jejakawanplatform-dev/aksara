# Diskusi & Konsensus Antar Agent (docs/discussions)

Folder ini adalah ruang kolaborasi asynchronous antar AI Agent (Gemini, Claude, GPT, Cursor, dll.) serta developer manusia untuk membahas ide arsitektur, trade-off teknis, RFC (*Request for Comments*), maupun evaluasi sebelum sebuah keputusan resmi dimasukkan ke `steering/decision-log.md` atau `spec/`.

---

## 🎯 Tujuan

1. **Catatan Jejak Berpikir (Reasoning Trail):** Menyimpan konteks mengapa suatu pendekatan diusulkan dan apa tanggapan agent lain.
2. **Mencegah Debat Berulang:** Menjadi referensi permanen jika di masa depan ada agent lain yang ingin mengubah hal serupa.
3. **Penyelarasan Multiverse/Multi-Agent:** Ketika proyek dikerjakan bergantian oleh berbagai LLM / Agent IDE, folder ini menjadi tempat serah-terima argumen dan penyelarasan solusi.

---

## 📂 Struktur File

Gunakan format penamaan file berbasis tanggal dan topik:

```text
docs/discussions/
├── README.md
├── _template.md
└── YYYY-MM-DD-nama-topik-diskusi.md
```

*Contoh:*
* `2026-09-15-optimasi-query-kuis-siswa.md`
* `2026-09-20-arsitektur-realtime-notifikasi.md`

---

## 🏷️ Status Thread

Setiap dokumen diskusi harus memiliki status di bagian header:
* `DRAFT` — Masih disusun oleh agent/human inisiator.
* `OPEN` — Terbuka untuk tanggapan, tinjauan, dan kritik dari agent/human lain.
* `CONSENSUS` — Telah disepakati bersama solusi dan parameter teknisnya.
* `ADOPTED` — Pekerjaan telah selesai diimplementasikan, **terdokumentasi di `docs/spec/`**, dan dicatat di `docs/steering/handover.md`.
* `REJECTED` — Usulan ditolak setelah evaluasi risiko/trade-off.

---

## 🔄 Alur Wajib Agen: Dari Diskusi ke Dokumentasi Spek & Handover

Ketika sebuah inisiatif fitur/perubahan yang dibahas di folder ini selesai dieksekusi, **setiap agen/developer WAJIB menjalankan langkah-langkah penutupan berikut**:

1. **Dokumentasikan ke `docs/spec/`:**
   * Pekerjaan yang telah selesai **wajib dicatat tahapannya di `docs/spec/`** (sebagai folder tahap baru, misal `docs/spec/18-bulk-actions/`, atau memperbarui spek modul terkait).
   * Format dokumentasi wajib mengikuti standar 4 berkas:
     - `plan.md` (tujuan, scope, acceptance)
     - `tasks.md` (checklist done/debt)
     - `implementation.md` (lokasi controller, pages, components, & alur)
     - `verification.md` (perintah test Pest & verifikasi manual)
2. **Perbarui Status di `docs/steering/handover.md`:**
   * Tambahkan kemampuan yang baru selesai ke bagian `## Status` ➔ `### Selesai` di [handover.md](../steering/handover.md) dengan tautan ke folder spek baru.
   * Catat hasil verifikasi test (`php artisan test`).
3. **Tutup Thread Diskusi:**
   * Ubah status thread diskusi terkait dari `CONSENSUS` menjadi **`ADOPTED`**.

---

## 📝 Format Standar Thread Diskusi

Gunakan [`_template.md`](_template.md) sebagai acuan untuk membuat dokumen diskusi baru.

---

## 📌 Daftar Thread Diskusi

| Tanggal | Topik Diskusi | Status | Inisiator |
| :--- | :--- | :---: | :--- |
| 2026-09-15 | [Desain & Arsitektur Fitur Bulk Actions pada Data Table](2026-09-15-fitur-bulk-actions-tabel.md) | `ADOPTED` | Antigravity AI & Developer |

