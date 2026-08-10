# Implementation — Design system

## Artefak

| Area | Path |
|---|---|
| Tokens CSS | `resources/css/app.css` (`:root`, `.aksara-*`) |
| Tailwind map | `tailwind.config.js` (`colors.aksara`, fonts) |
| UI kit | `resources/js/Components/ui/*` (+ `PageHeader`, `Modal`) |
| Modal | `resources/js/Components/ui/Modal.vue` |
| Icons | `resources/js/Components/ui/Icon.vue`, `NavIcon.vue` |
| Shell | Spec **03** — `Layouts/*` |
| Editor prose | Spec **15** — `.aksara-prose` |

## Principles

1. **Light-only** (ADR-012). Surface = `paper` / `white` / `mist`.
2. Warna produk hanya **`aksara.*`** (+ state `ok`/`warn`/`danger`/`info`).
3. Komponen baru domain → prefer `Components/ui`; jangan copy modal overlay.
4. Ikon = Heroicons outline path di `Icon.vue` — jangan npm icon set kedua.
5. Blade UI hanya `exports/*` PDF.

## Tokens (semantik)

| Token | Pakai untuk |
|---|---|
| `ink` | teks utama |
| `muted` | meta / hint |
| `teal` / `teal-dark` | brand, CTA primary, link |
| `mist` | surface lembut, hover nav |
| `paper` | background app |
| `line` | border |
| `ok` / `warn` / `danger` / `info` | status & alert |

**Radius:** kontrol `rounded-xl`; card/panel `rounded-2xl`.  
**Shadow:** `shadow-sm` default; popup `shadow-lg` / `shadow-aksara`.

## Typography

| Peran | Font | Contoh |
|---|---|---|
| Brand / judul section | `font-display` (Literata) | Card title, H1–H3 prose |
| UI / body | `font-sans` (Plus Jakarta) | Topbar, form, tabel |
| Meta | sans `text-xs` / `text-[11px]` `text-aksara-muted` | hint, badge label |

## Icons

- Nav: nama di `SidebarNav` harus ada di map `Icon.vue`.
- Tambah ikon = tambah path di map + pakai `<Icon name="…" />`.
- Jangan emoji di chrome produk.

## Components contract

| Komponen | Kapan |
|---|---|
| `Btn` | CTA; `variant`: primary \| secondary \| danger; `size`: sm \| md |
| `Card` | kontainer section page |
| `Field` | label + error di sekitar input |
| `Alert` | callout in-page (`tone`: info/ok/warn/danger/ai) |
| `Flash` | flash Inertia global |
| `EmptyState` | list kosong |
| `StatusBadge` | status enum (draft/published/…) |
| `Table` | tabel sederhana |
| `Modal` | dialog; slot default + `#footer` |
| `PageHeader` | intro page (title/meta/actions) di bawah Topbar |
| `Loading` | spinner inline |

Tombol di TipTap toolbar **bukan** `Btn` — pakai `.aksara-tiptap-tb` (spec 15).

## Theme

Light = SoT. Dark mode nanti: override CSS variables di `.dark` / `prefers-color-scheme` tanpa mengganti komponen.
