# Verification — App shell & design system

## Lokasi artefak (stack terkini)

| Peran | Path |
|---|---|
| Layouts | `resources/js/Layouts/{AppLayout,Sidebar,Topbar,GuestLayout}.vue` |
| Nav | `app/Support/Navigation/SidebarNav.php` |
| UI kit | `resources/js/Components/ui/*` |
| CSS | `resources/css/app.css` |
| Composables | `resources/js/Composables/useCan.js`, `useFlash.js` |

## Checklist

- [x] `nav` di-share dari `HandleInertiaRequests` → `SidebarNav`
- [x] Tailwind scan mencakup `resources/js/**/*.{js,vue}`
- [x] `npm run build` sukses

## Uji manual

| Langkah | Akun | Harapan |
|---|---|---|
| Login admin | `admin@aksara.test` | menu Users/Access/Settings |
| Login siswa | `adit@aksara.test` | tanpa menu admin |
| Collapse sidebar | guru | icon strip |
