# Akun Demo & Kredensial Pengujian — Aksara

Dokumen ini memuat daftar akun pengujian bawaan (*default seeder*) yang digunakan untuk kebutuhan workshop, verifikasi fitur, dan pengujian lokal.

> [!WARNING]
> Kredensial di bawah ini **hanya untuk lingkungan lokal / development / demo**.  
> Pada lingkungan **production**, jangan pernah menjalankan seeder demo dan pastikan seluruh password pengguna diubah menggunakan kata sandi yang kuat serta unik.

---

## 👥 Daftar Akun Demo

Seluruh akun demo di bawah di-generate melalui seeder `Database\Seeders\DemoDataSeeder`:

| Role | Nama | Email | Password Default | Cakupan Akses & Fitur |
| :--- | :--- | :--- | :--- | :--- |
| **Administrator** | Admin Aksara | `admin@aksara.test` | `password` | Manajemen pengguna, role & permission matrix, master referensi, AI provider settings. |
| **Guru** | Ibu Naya | `naya@aksara.test` | `password` | Perencanaan pembelajaran (CP/TP/ATP), materi & AI Co-Pilot, kuis, absensi, evaluasi. |
| **Wali Kelas** | Pak Arif | `arif@aksara.test` | `password` | Rekapitulasi absensi kelas, monitoring perkembangan siswa rombel binaan. |
| **Siswa** | Adit | `adit@aksara.test` | `password` | Akses materi pembelajaran terbit, pengerjaan kuis interaktif, histori nilai. |
| **Wali Murid** | Ortu Adit | `ortu.adit@aksara.test` | `password` | Pemantauan kehadiran dan capaian hasil belajar anak. |

---

## 🔄 Reset & Inisialisasi Ulang Akun Demo

Jika data akun demo terhapus, terubah, atau ingin di-reset kembali ke kondisi awal:

```bash
# Opsi 1: Jalankan seeder demo saja (updateOrCreate)
php artisan db:seed --class=DemoDataSeeder

# Opsi 2: Command khusus Aksara
php artisan aksara:seed-demo

# Opsi 3: Reset total database & pasang ulang seeder
php artisan migrate:fresh --seed
```

---

## 🔒 Praktik Keamanan

1. **Email Verifikasi:** Pada lingkungan lokal, akun demo otomatis berstatus `email_verified_at` terisi.
2. **Environment Production:** 
   - Pastikan `APP_ENV=production` dan `APP_DEBUG=false`.
   - Jalankan hanya migrasi inti tanpa `--seed` demo.
   - Buat akun administrator pertama secara aman via terminal / `php artisan tinker`.
