# Implementation — Design system

## Artefak

| Area | Path |
|---|---|
| Tokens CSS | `resources/css/app.css` (`:root`, `.aksara-*`) |
| Tailwind map | `tailwind.config.js` (`colors.aksara`, fonts) |
| UI kit | `resources/js/Components/ui/*` |
| Icons | `resources/js/Components/ui/Icon.vue`, `NavIcon.vue` |
| Shell | Spec **03** — `Layouts/*` |
| Editor prose | Spec **15** — `.aksara-prose` |

## Principles

1. **Light-only permanen** (ADR-012). Surface = `paper` / `white` / `mist` (**coastal tipis** + teal).
2. Warna produk hanya **`aksara.*`** (+ state `ok`/`warn`/`danger`/`info`). Brand CTA = teal.
3. Komponen baru domain → prefer `Components/ui`; jangan copy modal overlay.
4. Ikon = Heroicons outline path di `Icon.vue` — jangan npm icon set kedua.
5. Blade UI hanya `exports/*` PDF.
6. Tipografi UI = `font-sans` (Plus Jakarta); `font-display` (Literata) hanya wordmark / judul hero singkat.
7. **Bukan glassmorphism** — depth lewat border + wash matte, bukan blur/frosted.
8. **Aksi form/modal** rata kanan (`.aksara-form-actions` / `.aksara-dialog__footer`).
9. **Toolbar aksi** di header tab/section pakai `.aksara-toolbar` (card tipis), bukan ikon mengambang.

## Tokens (semantik)

| Token | Pakai untuk |
|---|---|
| `ink` | teks utama |
| `muted` | meta / hint |
| `teal` / `teal-dark` | brand, CTA primary, link |
| `mist` | surface lembut / sea-mist hover |
| `paper` | background app (airy coastal) |
| `line` | border |
| `sky` | aksen wash opsional (bukan CTA) |
| `ok` / `warn` / `danger` / `info` | status & alert |

**Radius:** kontrol form/btn `rounded-lg`; card/panel `rounded-xl`.  
**Shadow:** `shadow-sm` default; popup `shadow-md` (hindari multi-layer berat).

## Typography

| Peran | Font | Contoh |
|---|---|---|
| Brand / judul hero singkat | `font-display` (Literata) | Wordmark sidebar, hero landing |
| UI / body / section | `font-sans` (Plus Jakarta) | Topbar, form, tabel, PageHeader |
| Meta | sans `text-xs` / `text-[11px]` `text-aksara-muted` | hint, badge label |

## Icons

- Nav: nama di `SidebarNav` harus ada di map `Icon.vue`.
- Tambah ikon = tambah path di map + pakai `<Icon name="…" />`.
- Jangan emoji di chrome produk.
- **Bedakan makna:** `attendance` = clipboard + centang; `quiz` = clipboard + daftar opsi (bukan tanda tanya/help).

## Components contract

| Komponen | Kapan |
|---|---|
| `Btn` | CTA; `variant`: primary \| secondary \| danger; `size`: sm \| md |
| `Card` | kontainer section page (jarang di hero) |
| `Field` | label + hint/error di sekitar input |
| `Alert` | callout in-page (`tone`: info/ok/warn/danger/ai) |
| `Flash` | flash Inertia global |
| `EmptyState` | list kosong |
| `StatusBadge` | status enum (draft/published/…) — compact |
| `Table` | tabel sederhana (legacy); prefer `.aksara-table` |
| `Modal` | dialog; slot default + `#footer` (aksi kanan) |
| `PageHeader` | intro page (title/meta/actions) di bawah Topbar |
| `Loading` | spinner inline |
| `Pagination` | paginator Laravel + `per_page` 10/25/50/100 |
| `IconButton` | aksi ikon; tooltip hover / keyboard focus-visible saja |
| `ExportMenu` | satu tombol → popup Excel/Word/PDF |
| `PasswordInput` | auth password + toggle |

### Overlay / dialog / surface / toolbar

| Class | Pakai |
|---|---|
| `.aksara-overlay` | backdrop modal/picker |
| `.aksara-dialog` (+ `__header`/`__body`/`__footer`) | panel dialog; footer `justify-end` |
| `.aksara-popover` | menu popper (profil, stripbar) |
| `.aksara-export-menu` | popup ekspor (Teleport) |
| `.aksara-surface` / `-dashed` / `-soft` | panel page |
| `.aksara-toolbar` | grup aksi header tab/section (border + shadow-sm) |
| `.aksara-form-actions` | bar aksi form — selalu kanan |
| `.aksara-table` / `-th` / `-td` | tabel compact: header `py-3` + border-b-2; data `py-1.5` |

Form: `.aksara-input` / `.aksara-select` / `.aksara-textarea` (+ `.aksara-input--error`).  
Select di toolbar inline: override `!w-auto` (default select `w-full` memaksa wrap).

Tombol di TipTap toolbar **bukan** `Btn` — pakai `.aksara-tiptap-tb` (spec 15).

## Pagination (list panjang)

Pakai `Pagination.vue` + backend `paginate($perPage)->withQueryString()` untuk:

Plans, Materials, Users, Attendance Summary, Evaluation Monitoring, Teacher Report, References (**Rombel / Mapel / ATP**).

Dropdown mapel di CP/ATP memakai `subjectOptions` penuh (tidak ikut paginator tabel).

**Tidak dipaginasi:** Tahun/Semester (sedikit), Access matrix, Settings providers (client slice jika banyak), Attendance Form (satu rombel penuh), CP accordion.

## Theme

**Light enterprise + coastal tipis = SoT permanen.** Tidak ada dark mode, toggle, `.dark`, atau glassmorphism penuh.
Brand teal dipertahankan; netral digeser ke sea-mist (`paper`/`mist`/`line`); shell boleh wash radial matte sangat lembut. Surface tetap border-first.
