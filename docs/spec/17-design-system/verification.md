# Verification — Design system

## Lokasi artefak (stack terkini)

| Peran | Path |
|---|---|
| Spec | `docs/spec/17-design-system/*` |
| Tokens | `resources/css/app.css`, `tailwind.config.js` |
| UI | `resources/js/Components/ui/{Btn,Modal,Alert,Pagination,IconButton,ExportMenu,…}.vue` |
| ADR | `docs/steering/decision-log.md` (ADR-012) |

## Checklist

- [x] README spec memuat tahap 17
- [x] `Btn` danger/size
- [x] `Modal` terpakai Plans, Profile, Users, References, Settings
- [x] Tidak ada ungu di Evaluation Form / Guru jurnal metric / Reports refleksi link
- [x] Tidak ada overlay `fixed inset-0` ad-hoc di Pages (pakai Modal)
- [x] Theme light-only permanen (tidak ada toggle / class `.dark`)
- [x] Token enterprise coastal tipis + teal (`paper` `#f5fafb`, `mist` `#eef6f7`; bukan glass)
- [x] Overlay/dialog/popover/surface + form/badge/flash memakai token aksara di UI kit & pages
- [x] Dashboard Guru / Wali: hero panel putih + border + aksen kiri teal (bukan gradient)
- [x] `Pagination` + `per_page` di list panjang (Plans/Materials/Users/Summary/Monitoring/Report/Refs)
- [x] Tabel densitas compact; header lebih tinggi dari baris data
- [x] `ExportMenu` di Plans (header + baris) dan Refs CP/ATP
- [x] Ikon `quiz` ≠ `attendance`
- [x] Toolbar aksi `.aksara-toolbar`; form/modal aksi kanan
- [x] References CP accordion collapsible

## Perintah

```bash
npm run build
php artisan test --filter=ReferenceCrudTest
php artisan test --filter=LearningPlanExportImportTest
php artisan test --filter=SystemSettingsTest
```

Hasil build terakhir: **pass**.

## Uji manual

| Langkah | Harapan |
|---|---|
| Shell (sidebar/topbar) | mist coastal, active link inset teal, border-first |
| Plans Index | toolbar card; ekspor = popup; kolom aksi nowrap; absensi/kuis slot sejajar |
| Plans Create | Mode AI/Manual + submit di kanan |
| Referensi CP & TP | toolbar card; ekspor popup; accordion CP; filter mapel tidak menutup tombol |
| Settings Integrasi AI | usage compact; tabel vendor; guard select; model per fitur |
| Profile / modal | footer aksi kanan (Batal → Simpan) |
