# Diskusi: Desain & Arsitektur Fitur Bulk Actions pada Data Table

* **Tanggal Inisiasi:** 2026-09-15
* **Inisiator:** Antigravity AI (Pair Programming Assistant) & Developer
* **Status:** `ADOPTED` (Telah diimplementasikan & didokumentasikan di [`docs/spec/18-bulk-actions/`](../spec/18-bulk-actions/))
* **Terkait Dokumen:**
  - `docs/steering/coding-standards.md`
  - `docs/steering/business-rules.md`
  - `docs/spec/17-design-system/`
  - `docs/spec/05-users/`
  - `docs/spec/08-learning-plans/`
  - `docs/spec/09-materials-copilot/`

---

## 1. Konteks & Masalah

Saat ini, seluruh tabel data di Aksara (seperti [Users/Index.vue](file:///c:/Users/jejak/Documents/www/Bimtek/aksara/resources/js/Pages/Users/Index.vue), [Plans/Index.vue](file:///c:/Users/jejak/Documents/www/Bimtek/aksara/resources/js/Pages/Plans/Index.vue), [Materials/Index.vue](file:///c:/Users/jejak/Documents/www/Bimtek/aksara/resources/js/Pages/Materials/Index.vue), dan [References/Index.vue](file:///c:/Users/jejak/Documents/www/Bimtek/aksara/resources/js/Pages/References/Index.vue)) hanya mendukung **aksi baris tunggal** (*single-row action*), seperti:
* Lihat detail (*Show*)
* Edit data (*Edit*)
* Hapus satu data (*Delete*)

### Dampak & Kebutuhan Pengguna:
1. **Inefisiensi Operasional:** Administrator atau guru yang mengelola puluhan hingga ratusan data (contoh: hapus draf materi usang, arsipkan rencana pembelajaran lama, atau bersihkan akun siswa alumni) harus mengklik konfirmasi satu per satu.
2. **Ketiadaan Standar Massal:** Belum ada pola seragam di level komponen UI ([Components/ui/Table.vue](file:///c:/Users/jejak/Documents/www/Bimtek/aksara/resources/js/Components/ui/Table.vue)) untuk menangani pemilihan multi-baris (*checkbox selection*).
3. **Kebutuhan Fitur Aksi Massal Nyata:**
   - **Bulk Delete:** Menghapus banyak data sekaligus dengan aman.
   - **Bulk Status Update:** Mengubah status materi draf menjadi terbit (*publish*) atau arsip (*archive*).
   - **Bulk Export:** Mengekspor hanya baris-baris data yang dipilih ke format PDF / Excel.
   - **Bulk Assign:** Menetapkan rombel/kelas ke daftar siswa terpilih.

---

## 2. Proposal / Usulan Solusi Teknis

### A. Lapisan Frontend (Vue 3 + Inertia)

1. **Composable Reusable: `useBulkSelect.js`**  
   Dibuat di `resources/js/Composables/useBulkSelect.js` agar logika seleksi tidak ditulis ulang di tiap halaman:
   ```javascript
   // state & methods:
   const selectedIds = ref([])
   const isAllSelected = computed(...)
   const toggleSelect(id)
   const toggleSelectAll(items)
   const clearSelection()
   ```

2. **Komponen Contextual Action Bar: `BulkActionBar.vue`**  
   Dibuat di `resources/js/Components/ui/BulkActionBar.vue`:
   * Muncul secara mengambang (*floating bar*) atau di atas tabel ketika `selectedIds.length > 0`.
   * Menampilkan:
     - Badge jumlah item terpilih: `"{n} item dipilih"`
     - Tombol aksi kontekstual (Hapus Terpilih, Ubah Status, Export)
     - Tombol Batal / Kosongkan Seleksi

3. **Integrasi Checkbox pada `Table.vue`:**
   * Kolom pertama `<th>` memiliki checkbox untuk *Select All* pada halaman aktif (dengan status *indeterminate* jika baru sebagian yang dipilih).
   * Kolom pertama `<td>` memiliki checkbox untuk tiap item baris.

---

### B. Lapisan Backend (Laravel Controller & FormRequest)

1. **Endpoint Khusus & Restful:**
   * Contoh pada modul Users:
     - `POST /users/bulk-destroy`
   * Contoh pada modul Materials:
     - `POST /materials/bulk-status`
     - `POST /materials/bulk-destroy`

2. **Validasi Ketat (`FormRequest`):**
   ```php
   public function rules(): array
   {
       return [
           'ids' => ['required', 'array', 'min:1'],
           'ids.*' => ['required', 'integer', 'exists:users,id'],
           'action' => ['sometimes', 'string', 'in:delete,archive,publish'],
       ];
   }
   ```

3. **Guardrails & Integritas Data (`DB::transaction`):**
   * **Self-Protection:** Mencegah Admin menghapus akun yang sedang login saat bulk delete.
   * **Ownership Protection:** Guru hanya dapat melakukan aksi massal pada materi/rencana miliknya sendiri.
   * **Atomicity:** Seluruh operasi dibungkus dalam `DB::transaction()` agar jika 1 baris gagal karena constraint/foreign key, perubahan dibatalkan sepenuhnya.

---

## 3. Kelebihan, Risiko & Mitigasi

| Aspek | Potensi Risiko | Strategi Mitigasi |
| :--- | :--- | :--- |
| **UX & Safety** | Pengguna tidak sengaja menghapus ratusan data penting. | Modal konfirmasi dengan *destructive warning* eksplisit (menampilkan jumlah data yang akan dihapus). |
| **Scope Seleksi** | Bingung apakah checkbox di header memilih data di *seluruh halaman* atau hanya *halaman saat ini*. | **Fase 1:** Batasi seleksi hanya pada data di halaman aktif (*current page*). Tampilkan teks jelas: *"Memilih {n} item di halaman ini"*. |
| **Performa DB** | Query `WHERE IN (...)` dengan ribuan ID membebani memori/database. | Batasi maksimal array seleksi per request (misal: `max:100` ID). |
| **Audit Log** | Perubahan massal sulit dilacak siapa pelakunya. | Catat aktivitas bulk action ke `ai_usage_logs` atau sistem audit activity. |

---

## 4. Tanggapan & Konsensus Tim

* **Inisiator:** Antigravity AI & Developer
* **Status:** `CONSENSUS` (Disepakati 2026-09-15)

---

## 5. Keputusan Bersama (Consensus Outcomes)

Berdasarkan diskusi dan evaluasi best practice industri:

1. **Cakupan Seleksi (Flexible Page-Scope + Cross-Page):**
   * Default: Memilih item pada halaman aktif (*current page*).
   * Banner Ekstensi: Saat seluruh item di halaman aktif terpilih, muncul opsi teks interaktif:  
     *"Semua {n} data di halaman ini dipilih. **Pilih semua {total} data di seluruh halaman?**"*
   * Memberikan fleksibilitas penuh seperti standar Gmail / Shopify.

2. **Keamanan Konfirmasi Hapus (Double Confirmation Guard):**
   * Untuk aksi biasa (misal Ubah Status, Export): Modal konfirmasi standar yang merangkum jumlah data dan dampaknya.
   * Untuk aksi destruktif (**Bulk Delete**): Menggunakan modal *protective confirmation* dengan rincian data yang akan terhapus, peringatan dampak foreign key/relasi, serta **input pengetikan kata konfirmasi `"HAPUS"`** sebelum tombol eksekusi aktif, mencegah insiden salah klik yang fatal.

3. **Integrasi Komponen UI (Contextual Toolbar Replacement):**
   * Tidak menggunakan floating bar bawah agar layar tetap rapi dan tidak menutupi paginasi atau konten bawah.
   * Menggunakan **Contextual Toolbar Swap**:
     - **Default State:** Toolbar menampilkan input pencarian, dropdown filter, dan tombol tambah data.
     - **Active Selection State (`selectedCount > 0`):** Toolbar bertransisi menjadi bilah aksi kontekstual dengan warna aksen lembut (*primary surface tint*). Sisi kiri menampilkan counter seleksi & link select all, sisi kanan menampilkan tombol aksi massal (Hapus, Ubah Status, Batal).

4. **Modul Percontohan (Pilot Modules):**
   * **Modul Pengguna:** [Users/Index.vue](file:///c:/Users/jejak/Documents/www/Bimtek/aksara/resources/js/Pages/Users/Index.vue) (Bulk Delete, Bulk Role/Status).
   * **Modul Materi Pembelajaran:** [Materials/Index.vue](file:///c:/Users/jejak/Documents/www/Bimtek/aksara/resources/js/Pages/Materials/Index.vue) (Bulk Status Publish/Archive, Bulk Delete).

---

## 6. Rencana Tindak Lanjut & Eksekusi (Next Steps)

* [x] Capai konsensus arsitektur & parameter teknis (2026-09-15).
* [x] Buat composable `resources/js/Composables/useBulkSelect.js`.
* [x] Buat komponen UI `resources/js/Components/ui/BulkToolbar.vue`.
* [x] Buat modal protektif `resources/js/Components/ui/BulkConfirmModal.vue` (ketik `"HAPUS"`).
* [x] Implementasikan backend route & controller action pada `UserController.php` (`POST /users/bulk-destroy`).
* [x] Pasang `BulkToolbar` dan checkbox seleksi di `resources/js/Pages/Users/Index.vue`.
* [x] Tulis Feature Test Pest untuk validasi otorisasi & keutuhan data bulk delete (`UserBulkActionTest.php`, 6 passed).
* [x] Dokumentasikan spesifikasi resmi di [`docs/spec/18-bulk-actions/`](../spec/18-bulk-actions/).
* [ ] Replikasikan pola ke `Materials/Index.vue` (tugas lanjutan berikutnya).

