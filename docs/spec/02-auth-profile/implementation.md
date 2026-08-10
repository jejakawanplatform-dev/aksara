# Implementation — Auth & profil

## Artefak

| Area | Path |
|---|---|
| Routes | `routes/auth.php`; `profile.*` di `web.php` |
| Controllers | `AuthenticatedSessionController`, `RegisteredUserController`, `PasswordResetLinkController`, `NewPasswordController`, `PasswordController`, verify/confirm controllers, `ProfileController` |
| Pages | `Pages/Auth/{Login,Register,ForgotPassword,ResetPassword,VerifyEmail,ConfirmPassword}.vue`, `Pages/Profile/Edit.vue` |
| Layout | `Layouts/GuestLayout.vue` |
| Tests | `tests/Feature/Auth/*`, `ProfileTest.php` |

## Alur

```text
/login → Auth/Login.vue → session → /dashboard
/profile → Profile/Edit.vue → PATCH/DELETE
```

## Middleware

- `guest` untuk form auth
- `auth` (+ `signed`/`throttle`) untuk verify & profil
