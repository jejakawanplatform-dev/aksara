# Plan — App shell & design system

## Status

| Field | Isi |
|---|---|
| Kode | `03-app-shell-ui` |
| Status | selesai / aktif |
| Steering | `coding-standards` |

## Ringkasan

Chrome aplikasi: AppLayout + Sidebar (RBAC groups, collapse) + Topbar + flash; UI kit Vue (`Components/ui`) dan token CSS `.aksara-*`.

## Tujuan

Semua page authenticated memakai shell dan komponen visual yang konsisten; nav mengikuti permission.

## Scope

**In scope:** Layouts, SidebarNav, UI kit, CSS tokens, composables `useCan`/`useFlash`.  
**Out of scope:** Modal global reusable (masih in-page bila perlu).

## Acceptance

- [x] `nav` di-share Inertia dari `SidebarNav`
- [x] Tailwind scan `resources/js/**/*.{js,vue}`
- [x] UI kit terpakai di page domain
