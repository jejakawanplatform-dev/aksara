# Plan — App shell & navigasi

## Status

| Field | Isi |
|---|---|
| Kode | `03-app-shell-ui` |
| Status | selesai / aktif |
| Steering | `coding-standards` |
| Visual SoT | Spec **17** (design system) |

## Ringkasan

Chrome aplikasi: AppLayout + Sidebar (RBAC groups, collapse) + Topbar (profile menu) + flash; composables `useCan` / `useFlash`.

## Tujuan

Semua page authenticated memakai shell yang konsisten; nav mengikuti permission.

## Scope

**In scope:** Layouts, SidebarNav, edge collapse toggle, profile topbar.  
**Out of scope:** Token/warna/tipografi/Modal kit → **17**.

## Acceptance

- [x] `nav` di-share Inertia dari `SidebarNav`
- [x] Tailwind scan `resources/js/**/*.{js,vue}`
- [x] Collapse + profile menu topbar
