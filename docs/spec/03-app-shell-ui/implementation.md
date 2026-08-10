# Implementation — App shell & design system

## Artefak

| Area | Path |
|---|---|
| Layouts | `resources/js/Layouts/{AppLayout,Sidebar,Topbar,GuestLayout}.vue` |
| Nav | `app/Support/Navigation/SidebarNav.php` |
| UI kit | `resources/js/Components/ui/*` |
| CSS | `resources/css/app.css`, `tailwind.config.js` |
| Composables | `resources/js/Composables/useCan.js`, `useFlash.js` |

## Grup nav

1. UTAMA — dashboard  
2. PEMBELAJARAN — plans / materials / attendance.summary  
3. SUPERVISI & LAPORAN — reports / evaluations.monitoring  
4. MASTER DATA — references  
5. ADMINISTRASI & SISTEM — users / access / settings  

## Catatan

- Collapse sidebar: state Vue + `localStorage`.
- Ikon: Heroicons outline via `Icon.vue`.
