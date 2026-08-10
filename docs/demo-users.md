# Daftar User Demo Aplikasi Aksara

Berikut adalah daftar pengguna (user) demo yang dihasilkan oleh `DemoDataSeeder` beserta perannya. Data ini dapat digunakan untuk mencoba masuk (login) ke dalam sistem dan mengakses fitur sesuai hak akses masing-masing.

Semua akun menggunakan kata sandi (password) default yang sama yaitu: **`password`**

Semua akun demo `@aksara.test` **sudah terverifikasi email** (siap lewat middleware `verified`). Tidak perlu klik tautan verifikasi.

Jika login mengarah ke halaman “Verify Email”, seed ulang:

```bash
php artisan aksara:seed-demo
# atau hanya verifikasi akun demo yang sudah ada:
php artisan tinker --execute="App\Models\User::where('email','like','%@aksara.test')->update(['email_verified_at'=>now()]);"
```

## 1. Administrator
Akun untuk mengelola seluruh sistem, fitur, pengguna, dan konfigurasi master data.
- **Nama**: Admin Aksara
- **Email**: `admin@aksara.test`

## 2. Guru / Pendidik
Akun untuk mengelola pembelajaran, membuat Rencana Pembelajaran (Modul Ajar), kuis, dan interaksi dengan siswa.
- **Nama**: Ibu Naya
- **Email**: `naya@aksara.test`

## 3. Wali Kelas
Akun khusus guru yang mendapat tugas tambahan sebagai wali kelas.
- **Nama**: Pak Arif
- **Email**: `arif@aksara.test`
- **Tugas**: Wali Kelas VII-A

## 4. Siswa / Peserta Didik
Siswa yang berada di rombongan belajar (rombel) VII-A.
- **Siswa 1**
  - **Nama**: Adit
  - **Email**: `adit@aksara.test`
- **Siswa 2**
  - **Nama**: Bunga
  - **Email**: `bunga@aksara.test`
- **Siswa 3**
  - **Nama**: Citra
  - **Email**: `citra@aksara.test`
- **Siswa 4**
  - **Nama**: Dimas
  - **Email**: `dimas@aksara.test`
- **Siswa 5**
  - **Nama**: Eka
  - **Email**: `eka@aksara.test`

## 5. Wali Murid / Orang Tua
Akun khusus untuk orang tua yang dihubungkan dengan data siswa untuk memantau perkembangan belajar.
- **Orang Tua 1 (Orang Tua Adit)**
  - **Nama**: Ortu Adit
  - **Email**: `ortu.adit@aksara.test`
- **Orang Tua 2 (Orang Tua Bunga)**
  - **Nama**: Ortu Bunga
  - **Email**: `ortu.bunga@aksara.test`

---
*Dokumentasi ini mengikuti `DemoDataSeeder`.*
