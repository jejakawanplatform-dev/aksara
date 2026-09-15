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
* `CONSENSUS` — Telah disepakati bersama solusi terbaiknya.
* `ADOPTED` — Sudah diimplementasikan dan tercatat di `spec/` atau `steering/decision-log.md`.
* `REJECTED` — Usulan ditolak setelah evaluasi risiko/trade-off.

---

## 📝 Format Standar Thread Diskusi

Gunakan [`_template.md`](_template.md) sebagai acuan untuk membuat dokumen diskusi baru.
