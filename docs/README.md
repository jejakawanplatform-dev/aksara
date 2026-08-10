# Dokumentasi Proyek Aksara

```text
docs/
├── README.md
├── demo-users.md
├── steering/          ← aturan tetap
└── spec/              ← kemampuan produk (piramida terbalik dari codebase)
    ├── README.md
    ├── 01-scaffold-inertia/ … 14-exports-pdf/
    └── _template/
```

| Folder | Untuk apa |
|---|---|
| [steering/](steering/) | Aturan produk, ADR, handover |
| [spec/](spec/) | Dokumentasi kemampuan **dari kode yang ada** |

**Stack UI:** Laravel 13 + Inertia + Vue 3 + TipTap Vue.

## Urutan baca

1. `steering/product-brief.md` → `business-rules.md` → `handover.md`
2. `steering/coding-standards.md` + `api-contract.md`
3. `spec/README.md` lalu folder kemampuan yang disentuh

## Aturan

- Spek menunjuk path Controller + `Pages/**` stack terkini (lihat tabel di `verification.md` / `implementation.md`).
- UI fitur baru = Inertia page Vue + Controller.
