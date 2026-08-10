# Verification — Design system

## Lokasi artefak (stack terkini)

| Peran | Path |
|---|---|
| Spec | `docs/spec/17-design-system/*` |
| Tokens | `resources/css/app.css`, `tailwind.config.js` |
| UI | `resources/js/Components/ui/{Btn,Modal,Alert,Card,Field,…}.vue` |
| ADR | `docs/steering/decision-log.md` (ADR-012) |

## Checklist

- [x] README spec memuat tahap 17
- [x] `Btn` danger/size
- [x] `Modal` terpakai Plans, Profile, Users, References, Settings
- [x] Tidak ada ungu di Evaluation Form / Guru jurnal metric / Reports refleksi link
- [x] Tidak ada overlay `fixed inset-0` ad-hoc di Pages (pakai Modal)

## Perintah

```bash
npm run build
```

## Uji manual

| Langkah | Harapan |
|---|---|
| Topbar profile + shell | token teal/mist |
| Plans → Import modal | overlay `aksara-ink/40`, Modal kit |
| Profile → Hapus akun | Modal + Btn danger |
| Evaluation Form callout | Alert tone `ai` / mist, bukan ungu |
