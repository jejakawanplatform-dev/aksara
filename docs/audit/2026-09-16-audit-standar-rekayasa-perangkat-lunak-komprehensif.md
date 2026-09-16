# Laporan Audit Rekayasa Perangkat Lunak Komprehensif — Aksara
## Evaluasi Standar Pemrograman Terbaik Lintas 5 Layer: FE, BE, DB, Security, & Performa

**Tanggal Audit:** 16 September 2026  
**Auditor:** Antigravity Principal Software Engineer & Security Auditor  
**Cakupan Repositori:** `jejakawanplatform-dev/aksara`  
**Metodologi:** Static Code Analysis (PHPStan L9, Laravel Pint), Dynamic Code Inspection, Query Profiling, Security Threat Modeling, & Bundle Size Analysis.

---

## 📊 1. Executive Summary & Radar Penilaian 5 Pilar

Audit ini mengevaluasi kematangan teknis repositori **Aksara** dari sudut pandang *Software Engineering Best Practices*, *Defensive Security*, *Database Efficiency*, dan *Scalability*. 

```text
               Security (82/100)
                     ▲
                     │
    Performa (84/100)│    Backend (88/100)
             ◄───────┼───────►
                     │
    Frontend (90/100)│    Database (80/100)
                     ▼
```

| Pilar Rekayasa | Skor Kematangan | Status | Temuan Kritis / Catatan Kunci |
| :--- | :---: | :---: | :--- |
| **1. Backend (BE)** | **88 / 100** | 🟢 Sangat Baik | PHPStan Level 9 murni (0 error); 16 berkas perlu penyesuaian Pint; dominan inline validation dibanding FormRequest. |
| **2. Database (DB)** | **80 / 100** | 🟡 Baik | Integritas foreign key & unique composite kuat; terdeteksi N+1 query pada 2 controller rekapitulasi; indeks berulang pada `attendance_records`. |
| **3. Frontend (FE)** | **90 / 100** | 🟢 Sangat Baik | Vue 3 `<script setup>` rapi; token design system konsisten; lifecycle unmount TipTap perlu diperkuat untuk mencegah memory leak. |
| **4. Security** | **82 / 100** | 🟡 Baik | XSS sanitasi & path traversal sangat aman; transmisi `api_key` AI mentah ke frontend props di `/settings`; ketiadaan rate limiter pada endpoint AI. |
| **5. Performa** | **84 / 100** | 🟢 Sangat Baik | Vite bundle build sangat cepat (2.06s); KaTeX di-lazy load; perlu eager loading relasi untuk optimasi paginator 100 baris. |

---

## 🛠️ 2. Pilar 1: Backend Engineering (BE)

### 2.1. Kekuatan Arsitektur
- **Analisis Statis Maksimal (PHPStan Level 9):** Repositori beroperasi pada tingkat ketelitian tipe tertinggi (`level: 9`) dengan 0 error, mencakup *generic collection typing*, strict nullability check, dan explicit return types.
- **Pemisahan Domain Jelas:** Seluruh controller kini telah terkapsulasi rapi di subfolder domain (`Http/Controllers/{Domain}/`), didukung service murni untuk logika berat (`AiDraftService`, `MaterialExportService`, `AttendanceExportService`, `UserExportImportService`).
- **Pemanfaatan Fitur Modern PHP 8.4:** Pemanfaatan string-backed enums (`UserRole`, `PlanStatus`, `AttendanceStatus`, `MaterialStatus`) dan match expressions untuk memetakan status bisnis secara deterministik.

### 2.2. Area Temuan & Peningkatan
- **Temuan BE-1: Pelanggaran Standar Format Kode (Laravel Pint)**
  - *Diagnosa:* Eksekusi `vendor/bin/pint --test` mendeteksi **16 berkas** yang tidak mematuhi standar format PHP (seperti `unary_operator_spaces`, `ordered_imports`, dan `blank_line_before_statement`).
  - *Dampak:* Perbedaan gaya koding minor yang dapat menimbulkan noise diff pada git history.
  - *Solusi:* Jalankan pemformat otomatis `vendor/bin/pint`.
- **Temuan BE-2: Dominasi Inline Validation vs Dedicated FormRequest**
  - *Diagnosa:* Dari puluhan controller mutasi, hanya terdapat 2 berkas FormRequest di `app/Http/Requests/` (`ProfileUpdateRequest` dan `UserImportRequest`). Controller kompleks seperti `PlanController`, `UserController`, dan `QuizAttemptController` memuat validasi inline puluhan baris di dalam body method.
  - *Rekomendasi:* Ekstrak payload besar (seperti RPP creation dan quiz submission) ke dedicated FormRequest untuk meningkatkan *single responsibility* dan keterujian unit.
- **Temuan BE-3: Logging Kontekstual pada Failover Provider AI**
  - *Diagnosa:* Penanganan error koneksi provider eksternal pada `AiDraftService` dominan melempar exception atau fallback instan tanpa pencatatan log terstruktur (`Log::warning(...)` dengan metadata durasi timeout dan HTTP response code).

---

## 🗄️ 3. Pilar 2: Database Architecture & Schema Integrity (DB)

### 3.1. Kekuatan Arsitektur
- **Relational Constraints Kokoh:** Integritas referensial ditegakkan dengan foreign keys dan `cascadeOnDelete()` yang tepat pada relasi parent-child (`school_classes -> users`, `learning_plans -> school_classes`).
- **Composite Unique Constraints:** Aturan bisnis terlindungi di level skema MySQL:
  - `quizzes` + `students` (`quiz_attempts` unik per kuis per siswa).
  - `learning_plans` + `students` (`attendance_records` unik per pertemuan).
  - `learning_plans` + `teachers` (`teacher_evaluations` unik per refleksi).

### 3.2. Area Temuan & Peningkatan
- **Temuan DB-1: Masalah N+1 Query pada `AttendanceSummaryController` (Tinggi)**
  - *Diagnosa:* Pada berkas [`AttendanceSummaryController.php`](../../app/Http/Controllers/Attendance/AttendanceSummaryController.php#L81-L85), di dalam closure paginasi `->through(function ($student) ...)`:
    ```php
    $records = $student->attendances()
        ->whereIn('plan_id', $planIdsForSummary)
        ->get();
    ```
    Untuk setiap siswa di halaman (misal 50 atau 100 siswa per halaman), query database terpisah dieksekusi berulang kali (50–100 query per request).
  - *Solusi:* Eager load relasi attendance langsung pada query awal:
    ```php
    $class->students()
        ->with(['attendances' => fn($q) => $q->whereIn('plan_id', $planIdsForSummary)])
        ->paginate($perPage)...
    ```
    Dan baca koleksi yang telah di-load via `$student->attendances` (in-memory collection filter) tanpa pemanggilan method relasi `->attendances()`.
- **Temuan DB-2: Masalah N+1 Query pada `TeacherReportController` (Sedang)**
  - *Diagnosa:* Pada berkas [`TeacherReportController.php`](../../app/Http/Controllers/Reports/TeacherReportController.php#L51-L53):
    ```php
    $evaluation = TeacherEvaluation::where('plan_id', $plan->id)
        ->where('teacher_id', $teacher->id)
        ->first();
    ```
    Dieksekusi di dalam perulangan row paginasi RPP guru.
  - *Solusi:* Tambahkan eager loading relasi `evaluation` pada query builder utama RPP (`->with(['class', 'subject', 'attendance', 'quizzes.attempts', 'evaluation'])`).
- **Temuan DB-3: Indeks Redundan pada Tabel `attendance_records` (Rendah)**
  - *Diagnosa:* Pada migrasi `create_activity_tables.php`:
    ```php
    $table->unique(['plan_id', 'student_id']);
    $table->index('plan_id'); // REDUNDAN
    ```
    Dalam mesin penyimpanan InnoDB MySQL, indeks komposit `(plan_id, student_id)` secara otomatis melayani pencarian yang menyaring kolom `plan_id` (aturan *leftmost prefix*). Indeks kedua `index('plan_id')` membuang memori buffer pool dan memperlambat operasi insert massal.
- **Temuan DB-4: Inkonsistensi Transaksional pada Batch Mutator (Sedang)**
  - *Diagnosa:* Pada `PlanController::bulkDestroy`, loop penghapusan `$plan->delete()` tidak dibungkus dalam blok `DB::transaction(...)`, berbeda dengan `UserController::bulkDestroy` yang sudah terproteksi penuh secara transaksional.

---

## 🎨 4. Pilar 3: Frontend Architecture & Component Standards (FE)

### 4.1. Kekuatan Arsitektur
- **Vue 3 Composition API Murni:** Seluruh komponen menggunakan `<script setup>` dengan reaktivitas modern (`ref`, `computed`, `reactive`, `watch`).
- **Design System SoT (Spec 17):** Bebas dari duplikasi styling ad-hoc. Komponen UI (`Btn`, `Card`, `Modal`, `StatusBadge`, `Pagination`, `ExportMenu`, `BulkToolbar`, `BulkConfirmModal`) memiliki antarmuka prop dan slot yang terstandarisasi.
- **Optimasi Bundle KaTeX STEM:** Komponen [`TipTapEditor.vue`](../../resources/js/Components/tiptap/TipTapEditor.vue) melakukan *dynamic import* asinkron untuk modul KaTeX (`await import('katex')`) hanya ketika pengguna membuka dialog rumus matematika, sehingga ukuran bundle editor untuk materi non-STEM tetap sangat ringan.

### 4.2. Area Temuan & Peningkatan
- **Temuan FE-1: Pembersihan Siklus Hidup TipTap (Lifecycle Memory Leak Prevention)**
  - *Diagnosa:* Pada [`TipTapEditor.vue`](../../resources/js/Components/tiptap/TipTapEditor.vue#L9), `onBeforeUnmount` diimpor dari Vue namun tidak pernah dipanggil untuk menghancurkan instance editor ProseMirror:
    ```javascript
    onBeforeUnmount(() => {
        editor.value?.destroy();
    });
    ```
  - *Dampak:* Saat berpindah-pindah antar halaman Single Page Application (SPA), instance ProseMirror dan event listener DOM internalnya dapat tertinggal di memori peramban (*detached DOM memory leak*).
- **Temuan FE-2: Navigasi Internal Tag `<a>` vs Inertia `<Link>` pada Komponen `Btn`**
  - *Diagnosa:* Komponen [`Btn.vue`](../../resources/js/Components/ui/Btn.vue#L32-L38) merender tag HTML standar `<a :href="href">` saat menerima prop `href`.
  - *Dampak:* Jika digunakan untuk tautan rute internal aplikasi tanpa sengaja, peramban akan melakukan hard reload halaman penuh, mengorbankan kecepatan transisi Inertia SPA.
  - *Rekomendasi:* Dukung penggunaan Inertia `Link` secara bersyarat untuk URL internal non-ekspor.
- **Temuan FE-3: Aksesibilitas Formulir (A11y)**
  - *Diagnosa:* Sebagian besar input formulir berada di dalam komponen [`Field.vue`](../../resources/js/Components/ui/Field.vue), namun belum semua elemen input mengikat `id` eksplisit yang terhubung dengan atribut `for` pada tag `<label>`.

---

## 🔒 5. Pilar 4: Security & Data Privacy

### 5.1. Kekuatan Arsitektur
- **Sanitasi HTML Tingkat Tinggi:** [`MaterialContentHtml.php`](../../app/Support/MaterialContentHtml.php) menerapkan *DOMDocument parser* ketat:
  - Seluruh elemen script, iframe, object, dan handler inline event (`onload`, `onerror`, dll.) dihapus otomatis.
  - Atribut `src` pada gambar divalidasi ketat: hanya mengizinkan schema `data:image/...` atau path storage tepercaya `/storage/materials/...`.
- **Isolasi Berkas Context-Scoped:** `MaterialImageService` membatasi akses direktori hanya pada folder milik ID materi aktif (`materials/{material_id}/`), menggunakan `basename()` untuk mencegah eksploitasi *Path Traversal* (`../`).
- **Otorisasi Multi-Layer:** Akses data tidak hanya dicek lewat middleware Spatie Permission, melainkan diverifikasi ulang kepemilikan datanya pada controller (`$plan->teacher_id === Auth::id()`).

### 5.2. Area Temuan & Peningkatan
- **Temuan SEC-1: Transmisi Raw API Key ke Browser pada Pengaturan AI (Tinggi)**
  - *Diagnosa:* Pada [`SettingsController.php`](../../app/Http/Controllers/Settings/SettingsController.php#L50):
    ```php
    'api_key' => $p->api_key,
    ```
    Nilai mentah API Key provider (OpenAI, Gemini, dll.) dikirim langsung ke browser melalui Inertia Page Props.
  - *Risiko Keamanan:* Meskipun halaman `/settings` dilindungi permission `settings.manage` (hanya Admin), kunci rahasia tetap tersimpan di memori JavaScript client, riwayat DOM, dan dapat terekspos jika ada celah ekstensi peramban atau XSS di sesi admin. Hal ini bertentangan dengan prinsip **ADR-002** (*"API key tidak pernah diekspos ke klien/peramban"*).
  - *Rekomendasi Solusi:* Sensor nilai API Key saat dikirim ke frontend:
    ```php
    'api_key' => $p->api_key ? '••••••••' . substr($p->api_key, -4) : null,
    'has_api_key' => !empty($p->api_key),
    ```
    Dan hanya perbarui kolom `api_key` di backend jika input dari user tidak kosong dan bukan string mask sensor.
- **Temuan SEC-2: Ketiadaan Rate Limiter pada Endpoint Beban Berat AI (Sedang)**
  - *Diagnosa:* Rute `POST /materials/{material}/copilot` dan `POST /settings/providers/test` belum dilengkapi middleware `throttle`.
  - *Risiko:* Pengguna terautentikasi dapat melakukan spamming request (baik sengaja maupun akibat multi-click), yang berpotensi menghabiskan kuota token berbayar sekolah (*Denial of Wallet*) atau menyebabkan IP server di-ban oleh vendor AI.
  - *Rekomendasi Solusi:* Tambahkan limit khusus, misal `middleware(['throttle:15,1'])` (maksimal 15 interaksi per menit per user).
- **Temuan SEC-3: Model `AiProvider` Tidak Menyembunyikan `$hidden` (Sedang)**
  - *Diagnosa:* Model [`AiProvider.php`](../../app/Models/AiProvider.php) tidak mendefinisikan `protected $hidden = ['api_key'];`. Jika sewaktu-waktu model ini di-serialize langsung (`json_encode` atau `toArray()`), API Key akan ikut tercurah secara terbuka.

---

## ⚡ 6. Pilar 5: Performance & Scalability (Performa)

### 6.1. Kekuatan Arsitektur
- **Asset Bundling Vite & Tree Shaking:** Kompilasi aset frontend menghasilkan pemisahan vendor chunk yang sangat efisien:
  - `inertia-app`: 236 kB (gzip: 81 kB).
  - `katex`: 258 kB (gzip: 77 kB) terpisah rapi dan hanya dimuat on-demand.
  - Waktu kompilasi hanya ~2 detik.
- **Pagination Terstandarisasi:** Seluruh tabel panjang (RPP, Materi, Pengguna, Presensi, Monitoring, Laporan, Referensi) menggunakan paginasi server-side dengan batas opsional 10, 25, 50, 100 baris.

### 6.2. Area Temuan & Peningkatan
- **Temuan PERF-1: Eliminasi Duplikasi Query Hitung pada `AttendanceSummaryController`**
  - *Diagnosa:* Dalam menghitung statistik kehadiran per siswa:
    ```php
    $hadir = $records->where('status', AttendanceStatus::Present)->count();
    $izin = $records->where('status', AttendanceStatus::Excused)->count();
    $sakit = $records->where('status', AttendanceStatus::Sick)->count();
    $alpha = $records->where('status', AttendanceStatus::Absent)->count();
    ```
    Koleksi di-loop 4 kali untuk setiap status.
  - *Solusi:* Gunakan `$records->countBy(fn($r) => $r->status->value)` untuk melakukan klasifikasi agregat dalam 1 kali iterasi O(N).
- **Temuan PERF-2: Caching Konfigurasi Identitas Sekolah**
  - *Diagnosa:* Pemanggilan `setting('school.name')`, `setting('school.npsn')`, dll. pada template cetak PDF berkop dipanggil per-kunci secara individual.
  - *Solusi:* Pastikan `SettingService` melakukan caching in-memory array `system_settings` sehingga seluruh kunci kop surat diselesaikan dalam 1 hit cache/query tunggal.

---

## 📋 7. Matriks Rekomendasi Tindakan (Action Plan)

| ID | Pilar | Prioritas | Temuan & Aksi Remediasi | Kompleksitas | Dampak Positif |
| :---: | :---: | :---: | :--- | :---: | :--- |
| **ACT-01** | Security | **P0 (Tinggi)** | **Masking API Key AI:** Sensor nilai `api_key` pada `SettingsController` sebelum dikirim ke frontend Inertia; tambahkan `$hidden = ['api_key']` pada model `AiProvider`. | Rendah | Menghilangkan risiko bocornya kredensial LLM sekolah ke browser. |
| **ACT-02** | Database | **P0 (Tinggi)** | **Eager Loading Attendance Summary:** Hilangkan query N+1 pada `AttendanceSummaryController` dengan eager loading `attendances` di query utama siswa. | Rendah | Mengurangi query dari ~51 query menjadi 2 query per request pada tabel presensi. |
| **ACT-03** | Security | **P1 (Sedang)** | **Rate Limiting Endpoint AI:** Pasang middleware `throttle:15,1` pada rute `copilot` dan `providers.test`. | Rendah | Melindungi token kuota AI dari eksploitasi spam / multi-submit. |
| **ACT-04** | Database | **P1 (Sedang)** | **Eager Loading Teacher Report:** Tambahkan eager loading `evaluation` pada `TeacherReportController` untuk mengeliminasi query N+1 per baris RPP. | Rendah | Waktu respons laporan guru menjadi instan. |
| **ACT-05** | Database | **P1 (Sedang)** | **Atomisitas Transaksional:** Bungkus operasi penghapusan massal di `PlanController::bulkDestroy` ke dalam `DB::transaction(...)`. | Rendah | Mencegah state parsial/korup jika terjadi kegagalan sistem saat bulk delete. |
| **ACT-06** | Frontend | **P1 (Sedang)** | **TipTap Lifecycle Cleanup:** Tambahkan pemanggilan eksplisit `editor.value?.destroy()` pada hook `onBeforeUnmount` di `TipTapEditor.vue`. | Sangat Rendah | Mencegah memory leak ProseMirror pada navigasi SPA berulang. |
| **ACT-07** | Backend | **P2 (Rendah)** | **Pembersihan Kode Pint:** Jalankan `vendor/bin/pint` untuk menstandarkan format 16 berkas PHP yang terdeteksi tidak seragam. | Sangat Rendah | Menjamin 100% kepatuhan gaya kode tim Aksara. |
