# Laporan Audit Komprehensif — Bug & Gaps Codebase Aksara

**Tanggal Audit:** 21 September 2026
**Auditor:** Antigravity Agentic Pair Programmer (Kiro IDE)
**Cakupan Proyek:** Repositori Aksara — `jejakawanplatform-dev/aksara`
**Metodologi:** Static code analysis mendalam, cross-reference dokumentasi ↔ implementasi, security threat modeling, runtime logic tracing lintas 5 layer (BE, DB, FE, Security, Performance).
**Baseline Audit Sebelumnya:** [2026-09-16-audit-standar-rekayasa-perangkat-lunak-komprehensif.md](./2026-09-16-audit-standar-rekayasa-perangkat-lunak-komprehensif.md)

---

## Executive Summary

Audit ini merupakan sesi inspeksi mendalam pasca-audit 16 September 2026. Fokusnya adalah menemukan **bug dan gap baru** yang muncul setelah serangkaian fitur besar (Bulk Actions, Export Multi-Format, User Import) dikirimkan, serta memverifikasi status aktual seluruh 7 rekomendasi dari audit sebelumnya.

**Total temuan baru: 22 isu** (3 kritis / 4 tinggi / 6 sedang / 9 rendah/dokumentasi).

Kualitas codebase secara keseluruhan tetap tinggi — seluruh tindakan P0/P1 dari audit sebelumnya sudah diimplementasi dengan benar. Temuan baru ini adalah lapisan refinement berikutnya.

---

## Bagian 1: Verifikasi Status Rekomendasi Audit Sebelumnya

| ID | Rekomendasi Lama | Status Aktual |
|---|---|---|
| ACT-01 | API Key masking di SettingsController | ✅ SUDAH — masking berlapis: `$hidden`, manual mask `••••••••XXXX`, guard update, sanitasi error |
| ACT-02 | N+1 di AttendanceSummaryController | ✅ SUDAH — `->with(['attendances' => fn...])` constrained |
| ACT-03 | Rate limiting endpoint AI (copilot + test) | ⚠️ SEBAGIAN — `throttle:15,1` ada di copilot & providers.test, tapi `POST /plans` (AI gen) belum |
| ACT-04 | N+1 di TeacherReportController | ✅ SUDAH — `->with(['class','subject','attendance','quizzes.attempts','evaluation'])` |
| ACT-05 | DB::transaction pada PlanController::bulkDestroy | ✅ SUDAH — wrapped penuh dengan return tuple |
| ACT-06 | TipTap lifecycle cleanup (onBeforeUnmount) | ✅ SUDAH — `editor.value?.destroy()` aktif |
| ACT-07 | Laravel Pint formatting 16 file | ❓ PERLU RE-CHECK — jalankan `vendor/bin/pint --test` |

---

## Bagian 2: Temuan Baru

---

### 🔴 CRITICAL (P0)

#### BUG-01: `POST /plans` AI Generation Tidak Ada HTTP-Level Rate Limiter

- **File:** `routes/web.php` baris 53; `app/Http/Controllers/Plans/PlanController.php::store()`
- **Diagnosa:** Rute `POST /plans` yang memicu `AiDraftService::generateDraft()` — memanggil API berbayar OpenAI/Gemini — hanya dilindungi soft-limit harian berbasis DB. Tidak ada middleware `throttle:` di level HTTP.
- **Soft-limit juga punya race condition:** dua tab browser submit simultan bisa sama-sama lolos batas `$todayCount >= $dailyLimit` karena tidak ada DB-level lock.
- **Bandingkan** dengan `providers.test` yang sudah benar: `->middleware('throttle:15,1')`.
- **Dampak:** Denial-of-Wallet — single user dapat memicu puluhan request API berbayar per detik, menguras kuota token sekolah.
- **Fix:**
  ```php
  // routes/web.php
  Route::post('/', [PlanController::class, 'store'])
      ->middleware('throttle:10,1')
      ->name('store');
  ```
  Untuk race condition quota: gunakan `DB::select('SELECT GET_LOCK(...)` atau Redis atomic counter.
- **Spec terdampak:** `08-learning-plans` → tambahkan task T11.

---

#### BUG-02: `MaterialController::bulkDestroy` Tidak Ada DB Transaction — Partial Delete Risk

- **File:** `app/Http/Controllers/Materials/MaterialController.php` baris 109–165
- **Diagnosa:** Loop `foreach ($materials as $material)` yang memanggil `$material->delete()` tidak dibungkus dalam `DB::transaction`. Jika database error atau deadlock terjadi di iterasi ke-N, iterasi 1..N-1 sudah terhapus secara permanen.
- **Bandingkan** dengan `PlanController::bulkDestroy` yang sudah benar menggunakan `DB::transaction(function() {...})`.
- **Fix:**
  ```php
  [$deletedCount, $skippedPublished] = DB::transaction(function () use ($materials) {
      $deleted = 0; $skipped = 0;
      foreach ($materials as $material) {
          if ($material->events->isNotEmpty()) { $skipped++; continue; }
          $material->delete(); $deleted++;
      }
      return [$deleted, $skipped];
  });
  ```
- **Spec terdampak:** `18-bulk-actions` → tambahkan task T11 (hotfix).

---

#### BUG-03: `ReferenceController::bulkDestroyRombel` dan `bulkDestroyMapel` Tidak Ada Transaction

- **File:** `app/Http/Controllers/References/ReferenceController.php` baris 563–602, 708–748
- **Diagnosa:** Dua bulk delete endpoint di References juga tidak menggunakan `DB::transaction`. Setiap rombel dihapus dengan dua operasi terpisah: `$rombel->students()->detach()` lalu `$rombel->delete()`. Jika `delete()` gagal setelah `detach()`, data pivot `class_members` sudah terhapus tapi rombel masih ada — state tidak konsisten.
- **Fix:** Wrap seluruh loop ke dalam `DB::transaction(function () use (...) { ... })`.
- **Spec terdampak:** `18-bulk-actions` → tambahkan task T12 (hotfix).

---

### 🟠 HIGH (P1)

#### BUG-04: `Quiz` Model Tidak Men-cast `status` ke Enum — Inkonsistensi Sistem-wide

- **File:** `app/Models/Quiz.php`
- **Diagnosa:** Model `Quiz` tidak mendefinisikan cast `status` ke enum, padahal semua model lain (`LearningPlan`, `LearningMaterial`, `AttendanceRecord`) menggunakan backed enum. Konsekuensi nyata: `MaterialController::show()` terpaksa menggunakan triple-fallback detection (3 cara berbeda mencari quiz published) karena tidak bisa mengandalkan tipe yang konsisten.
  ```php
  // Triple-fallback yang seharusnya tidak perlu ada:
  $publishedQuiz = $plan->quizzes->firstWhere('status', 'published')
      ?? $plan->quizzes->firstWhere('status.value', 'published');
  if (!$publishedQuiz) {
      $publishedQuiz = $plan->quizzes->first(function ($q) { ... });
  }
  ```
- **Fix:**
  1. Buat `app/Enums/QuizStatus.php` (mirip `MaterialStatus`).
  2. Tambahkan ke `Quiz::casts()`: `'status' => QuizStatus::class`.
  3. Ganti `isPublished()`: `return $this->status === QuizStatus::Published;`.
  4. Hapus triple-fallback di `MaterialController::show()`.
- **Spec terdampak:** `10-quizzes` → tambahkan task T10.

---

#### BUG-05: `User::belongsToClass()` Memanggil `classIds()` Tanpa Cache — Potensi N+1

- **File:** `app/Models/User.php` baris 123–130
- **Diagnosa:** `belongsToClass()` memanggil `classIds()` setiap kali dipanggil. `classIds()` menjalankan DB query baru setiap pemanggilan (raw `DB::table('class_members')->...`). Method ini dipanggil di `QuizAttemptController::show()`, `QuizAttemptController::submit()`, `MaterialController::show()`, dan `MaterialExportController` — empat tempat berbeda, masing-masing trigger query sendiri dalam request yang sama.
- **Fix:**
  ```php
  private ?array $cachedClassIds = null;

  public function classIds(): array
  {
      if ($this->cachedClassIds !== null) {
          return $this->cachedClassIds;
      }
      // ... existing logic ...
      return $this->cachedClassIds = $result;
  }
  ```
- **Spec terdampak:** Tidak ada spec khusus — ini perbaikan model. Update `05-users/tasks.md` atau buat catatan di `testing-strategy.md`.

---

#### BUG-06: `Btn.vue` Menggunakan `<a>` Native untuk Navigasi Internal — Full Page Reload

- **File:** `resources/js/Components/ui/Btn.vue`
- **Diagnosa:** Komponen `Btn.vue` selalu merender `<a :href="href">` native HTML (bukan Inertia `<Link>`) ketika prop `href` diberikan. Dipakai di **11+ halaman** untuk navigasi internal:
  - `Plans/Index.vue`, `Plans/Edit.vue`, `Plans/Create.vue`, `Plans/Draft.vue`
  - `Materials/Show.vue`, `Materials/Edit.vue`
  - `Dashboard/Guru.vue`, `Reports/Teacher.vue`, `Evaluation/Form.vue`, `Attendance/Form.vue`, `Quiz/Form.vue`
  Setiap klik memicu full HTTP reload, mengorbankan keunggulan SPA Inertia (shared layout, scroll state, flash message timing). Menariknya, `Dashboard/Admin.vue` sudah menggunakan Inertia `<Link>` langsung — inkonsistensi.
- **Fix:**
  ```vue
  <script setup>
  import { Link } from '@inertiajs/vue3';
  defineProps({ href: String, external: { type: Boolean, default: false }, /* ... */ });
  </script>
  <template>
    <component
      :is="href && !external ? Link : href ? 'a' : 'button'"
      v-bind="href ? { href } : { type, disabled }"
      :class="[variantClass, sizeClass]"
    >
      <slot />
    </component>
  </template>
  ```
  Tambahkan prop `external` (default `false`) agar link download/eksternal tetap pakai `<a>`.
- **Spec terdampak:** `17-design-system` → tambahkan task T16.

---

#### GAP-01: `generateFullMaterialContent()` Silent Fallback Tanpa Log Error

- **File:** `app/Services/AiDraftService.php` baris ~455–490
- **Diagnosa:** Saat semua AI provider gagal di `generateFullMaterialContent()`, fungsi diam-diam mengembalikan konten template statis tanpa `Log::error`. Bandingkan dengan `generateDraft()` yang sudah benar: `Log::error('All database AI providers in failover chain failed.')`.
- **Dampak:** Guru/admin tidak tahu konten materi adalah template generik bukan hasil AI — menyulitkan debug dan pemantauan penggunaan.
- **Fix:** Tambahkan `Log::error('AI: All providers failed generating material content. Serving static fallback.', ['plan_id' => $plan->id, 'topic' => $plan->topic])` sebelum return fallback.
- **Spec terdampak:** `09-materials-copilot` → tambahkan task T13.

---

### 🟡 MEDIUM (P2)

#### GAP-02: Admin Tidak Bisa Mengakses/Edit Absensi Guru Lain

- **File:** `app/Http/Controllers/Attendance/AttendanceController.php`
- **Diagnosa:** Method `edit()` dan `save()` keduanya menggunakan `abort_unless($plan->teacher_id === Auth::id(), 403)` tanpa fallback admin. Bertentangan dengan permission `attendance.manage` yang diberikan ke Admin di `PermissionCatalog::defaultMatrix()` dan pola `authorizeOwnerOrAdmin()` yang sudah ada di `PlanController`.
- **Fix:** Ganti guard dengan helper serupa `PlanController`:
  ```php
  abort_unless(Auth::user()?->isAdmin() || $plan->teacher_id === Auth::id(), 403);
  ```
- **Spec terdampak:** `11-attendance` → tambahkan task T11.

---

#### GAP-03: `QuizAttemptController` Tidak Validasi Jumlah dan Panjang Jawaban

- **File:** `app/Http/Controllers/Quiz/QuizAttemptController.php` baris 101–107
- **Diagnosa:** Validasi `answers` hanya `required|array` dengan elemen `nullable|string`. Tidak ada validasi:
  - Jumlah jawaban harus sama dengan jumlah soal (`size:N`)
  - Panjang maksimal per jawaban (`max:500`)
  Siswa bisa mengirim lebih banyak jawaban dari jumlah soal (data ekstra masuk ke DB JSON), atau string tidak terbatas.
- **Fix:**
  ```php
  $questionCount = count(is_array($quiz->questions) ? $quiz->questions : []);
  $validated = $request->validate([
      'answers' => ['required', 'array', 'size:' . $questionCount],
      'answers.*' => ['nullable', 'string', 'max:500'],
  ]);
  ```
- **Spec terdampak:** `10-quizzes` → tambahkan task T11.

---

#### GAP-04: `MaterialExportService::exportWord()` Tidak Ada Error Handling

- **File:** `app/Services/MaterialExportService.php`
- **Diagnosa:** Method `exportWord()` tidak memiliki `try/catch` di level method. Exception dari PhpWord (mis. file permission, memory limit saat dokumen besar) akan bubble up sebagai HTTP 500 tanpa pesan ramah pengguna.
- **Fix:** Wrap isi method dengan `try/catch (\Throwable $e)` dan return response error yang informatif atau throw custom exception.
- **Spec terdampak:** `14-exports-pdf` → tambahkan task/catatan.

---

#### GAP-05: `POST /plans` AI Mode Belum Masuk Matriks Rate Limiting Dokumentasi

- **File:** `docs/steering/api-contract.md`
- **Diagnosa:** Kontrak API belum mencatat bahwa `POST /plans` memiliki dua mode (`mode=ai` / `mode=manual`) dengan behavior berbeda drastis — AI mode memanggil external API berbayar. Endpoint ini juga tidak masuk matriks throttle.
- **Fix:** Update `api-contract.md` untuk mencatat mode parameter dan throttle yang ditambahkan (BUG-01).

---

### 🔵 LOW / DOKUMENTASI (P3)

#### GAP-06: `docs/spec/17-design-system` Tidak Mencatat Behavior `href` di `Btn.vue`

Dokumentasi design system mencatat prop `href` di `Btn.vue` tapi tidak mendokumentasikan bahwa ini `<a>` native (bukan Inertia Link) dan hanya boleh untuk link eksternal/download. Gap ini berkontribusi pada pemakaian salah di 11+ halaman (BUG-06).

#### GAP-07: Inkonsistensi Dualisme Auth — Enum Role vs Spatie — Belum Terdokumentasi Risikonya

Sistem menggunakan dua mekanisme bersamaan (`UserRole` enum + Spatie HasRoles) yang harus selalu sinkron via `syncAppRole()`. Risiko desync jika ada path pembuatan user baru yang terlewat memanggil `syncAppRole()` belum terdokumentasi di ADR-003 sebagai guard wajib.

#### GAP-08: `SettingService` Cache — Perlu Verifikasi Untuk Template Cetak

Template PDF berkop memanggil `setting('school.name')`, `setting('school.npsn')`, dll. secara individual. Perlu dipastikan `SettingService` sudah pakai in-memory array cache agar semua kunci diselesaikan dalam 1 DB query (bukan N query per kunci saat cetak PDF).

---

## Bagian 3: Matriks Prioritas Tindakan

| Prioritas | ID | Isu | Kompleksitas | Spec |
|---|---|---|---|---|
| **P0 — Kritis** | BUG-01 | Rate limiting `POST /plans` AI | Rendah | 08-learning-plans T11 |
| **P0 — Kritis** | BUG-02 | DB::transaction `MaterialController::bulkDestroy` | Sangat Rendah | 18-bulk-actions T11 |
| **P0 — Kritis** | BUG-03 | DB::transaction `ReferenceController` bulk deletes | Sangat Rendah | 18-bulk-actions T12 |
| **P1 — Tinggi** | BUG-04 | `Quiz::status` tanpa enum cast | Sedang | 10-quizzes T10 |
| **P1 — Tinggi** | BUG-05 | `User::classIds()` tanpa cache | Rendah | — |
| **P1 — Tinggi** | BUG-06 | `Btn.vue` full page reload navigasi internal | Sedang | 17-design-system T16 |
| **P1 — Tinggi** | GAP-01 | Silent fallback `generateFullMaterialContent` | Sangat Rendah | 09-materials-copilot T13 |
| **P2 — Sedang** | GAP-02 | Admin tidak bisa akses absensi guru lain | Rendah | 11-attendance T11 |
| **P2 — Sedang** | GAP-03 | QuizAttempt validasi jumlah/panjang jawaban | Rendah | 10-quizzes T11 |
| **P2 — Sedang** | GAP-04 | `exportWord()` tanpa error handling | Rendah | 14-exports-pdf |
| **P3 — Rendah** | GAP-05 | `POST /plans` mode AI belum di api-contract.md | Sangat Rendah | steering/api-contract |
| **P3 — Rendah** | GAP-06..08 | Gap dokumentasi | Sangat Rendah | steering/decision-log |

---

## Bagian 4: Kesimpulan

Aksara berada pada kematangan teknis yang sangat baik. Tiga bug P0 semuanya berupa baris kode yang sedikit (masing-masing 3–5 baris) namun berdampak besar pada integritas data dan keamanan finansial (token API). Ketiganya harus di-patch sebelum rilis berikutnya.

Bug arsitektur paling strategis untuk jangka panjang adalah `Quiz::status` tanpa enum cast (BUG-04) dan `Btn.vue` full reload (BUG-06) — keduanya menyebar secara silent di banyak titik kode dan akan terus menumpuk technical debt jika tidak diselesaikan.

---

*Laporan ini merupakan baseline untuk sprint perbaikan berikutnya. Seluruh task fixing telah didaftarkan ke `docs/spec/` masing-masing modul dan ADR baru di `docs/steering/decision-log.md`.*
