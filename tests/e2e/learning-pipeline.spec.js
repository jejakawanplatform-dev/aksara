import { test, expect } from '@playwright/test';

test.describe('Alur Pipeline Pembelajaran & Asesmen', () => {
    test.beforeEach(async ({ page }) => {
        // Login sebagai Guru (Naya)
        await page.goto('/login');
        await page.fill('#email', 'naya@aksara.test');
        await page.fill('#password', 'password');
        await page.click('button:has-text("Masuk")');
        await expect(page).toHaveURL(/.*dashboard/);
    });

    test('Guru dapat melihat daftar RPP/Modul Ajar (/plans) dan navigasi ke form buat baru', async ({ page }) => {
        await page.goto('/plans');
        await expect(page.locator('body')).toContainText('Rencana Pembelajaran');
        await expect(page.locator('table')).toBeVisible();

        // Navigasi ke form tambah RPP baru
        const createBtn = page.locator('a:has-text("Buat Rencana Baru"), a:has-text("Tambah Rencana"), a[href*="/plans/create"]').first();
        if (await createBtn.isVisible()) {
            await createBtn.click();
            await expect(page).toHaveURL(/.*plans\/create/);
        }
    });

    test('Guru dapat membuka form tambah RPP (/plans/create)', async ({ page }) => {
        await page.goto('/plans/create');
        await expect(page.locator('body')).toContainText('Rencana Pembelajaran');
        await expect(page.locator('select, input').first()).toBeVisible();
    });

    test('Guru dapat mengakses form kuis RPP (/plans/1/quiz)', async ({ page }) => {
        await page.goto('/plans/1/quiz');
        await expect(page.locator('body')).toContainText('Kuis');
    });

    test('Guru dapat mengakses form absensi RPP (/plans/1/attendance)', async ({ page }) => {
        await page.goto('/plans/1/attendance');
        await expect(page.locator('body')).toContainText('Kehadiran');
    });

    test('Guru dapat mengakses form evaluasi & refleksi (/plans/1/evaluation)', async ({ page }) => {
        await page.goto('/plans/1/evaluation');
        await expect(page.locator('body')).toContainText('Evaluasi');
    });

    test('Guru dapat mengakses halaman laporan pembelajaran (/reports/guru)', async ({ page }) => {
        await page.goto('/reports/guru');
        await expect(page.locator('body')).toContainText('Laporan');
    });
});

test.describe('Materi Pembelajaran Siswa', () => {
    test('Siswa dapat membuka dan membaca materi pembelajaran (/materials/1)', async ({ page }) => {
        // Login sebagai Siswa (Adit)
        await page.goto('/login');
        await page.fill('#email', 'adit@aksara.test');
        await page.fill('#password', 'password');
        await page.click('button:has-text("Masuk")');
        await expect(page).toHaveURL(/.*dashboard/);

        // Buka materi
        await page.goto('/materials/1');
        await expect(page.locator('body')).toContainText('Dekomposisi Masalah');
    });
});
