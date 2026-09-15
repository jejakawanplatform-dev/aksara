import { test, expect } from '@playwright/test';

test.describe('Administrasi & Pengawasan Sistem', () => {
    test.beforeEach(async ({ page }) => {
        // Login sebagai Admin
        await page.goto('/login');
        await page.fill('#email', 'admin@aksara.test');
        await page.fill('#password', 'password');
        await page.click('button:has-text("Masuk")');
        await expect(page).toHaveURL(/.*dashboard/);
    });

    test('Admin dapat membuka halaman Manajemen Pengguna (/users)', async ({ page }) => {
        await page.goto('/users');
        await expect(page.locator('body')).toContainText('Manajemen Pengguna');
        await expect(page.locator('table')).toBeVisible();
        await expect(page.locator('tbody tr')).not.toHaveCount(0);
    });

    test('Admin dapat membuka halaman Matrix Hak Akses (/access)', async ({ page }) => {
        await page.goto('/access');
        await expect(page.locator('body')).toContainText('Matrix Hak Akses');
        await expect(page.locator('table')).toBeVisible();
    });

    test('Admin dapat membuka halaman Pengaturan Sistem & AI (/settings)', async ({ page }) => {
        await page.goto('/settings');
        await expect(page.locator('body')).toContainText('Pengaturan Sistem');
        await expect(page.locator('body')).toContainText('Integrasi AI');
    });

    test('Admin dapat membuka halaman Referensi Kurikulum & Sekolah (/references)', async ({ page }) => {
        await page.goto('/references');
        await expect(page.locator('body')).toContainText('Referensi');
    });
});
