import { test, expect } from '@playwright/test';

test.describe('Autentikasi & Navigasi Role', () => {
    test('Pengguna admin dapat login dan melihat Dashboard Admin', async ({ page }) => {
        await page.goto('/login');

        // Isi form login
        await page.fill('#email', 'admin@aksara.test');
        await page.fill('#password', 'password');
        await page.click('button:has-text("Masuk")');

        // Harus diarahkan ke /dashboard
        await expect(page).toHaveURL(/.*dashboard/);
        await expect(page.locator('body')).toContainText('Dashboard Administrator');
    });

    test('Guru dapat login dan melihat Dashboard Guru', async ({ page }) => {
        await page.goto('/login');

        await page.fill('#email', 'naya@aksara.test');
        await page.fill('#password', 'password');
        await page.click('button:has-text("Masuk")');

        await expect(page).toHaveURL(/.*dashboard/);
        await expect(page.locator('body')).toContainText('Naya');
    });

    test('Siswa dapat login dan melihat Dashboard Siswa', async ({ page }) => {
        await page.goto('/login');

        await page.fill('#email', 'adit@aksara.test');
        await page.fill('#password', 'password');
        await page.click('button:has-text("Masuk")');

        await expect(page).toHaveURL(/.*dashboard/);
        await expect(page.locator('body')).toContainText('Adit');
    });
    test('Pengunjung dapat melihat halaman landing (Welcome)', async ({ page }) => {
        await page.goto('/');
        await expect(page).toHaveTitle(/Aksara/);
        await expect(page.locator('body')).toContainText('Aksara');
        await expect(page.locator('a:has-text("Masuk")').first()).toBeVisible();
    });

    test('Wali Kelas dapat login dan melihat Dashboard Wali Kelas', async ({ page }) => {
        await page.goto('/login');

        await page.fill('#email', 'arif@aksara.test');
        await page.fill('#password', 'password');
        await page.click('button:has-text("Masuk")');

        await expect(page).toHaveURL(/.*dashboard/);
        await expect(page.locator('body')).toContainText('Arif');
    });

    test('Wali Murid dapat login dan melihat Dashboard Wali Murid dengan data anak', async ({ page }) => {
        await page.goto('/login');

        await page.fill('#email', 'ortu.adit@aksara.test');
        await page.fill('#password', 'password');
        await page.click('button:has-text("Masuk")');

        await expect(page).toHaveURL(/.*dashboard/);
        await expect(page.locator('body')).toContainText('Wali Murid');
    });

    test('Pengguna terotentikasi dapat membuka halaman Profil (/profile)', async ({ page }) => {
        await page.goto('/login');
        await page.fill('#email', 'admin@aksara.test');
        await page.fill('#password', 'password');
        await page.click('button:has-text("Masuk")');
        await expect(page).toHaveURL(/.*dashboard/);

        await page.goto('/profile');
        await expect(page.locator('body')).toContainText('Profil');
        await expect(page.locator('#email')).toHaveValue('admin@aksara.test');
        await expect(page.locator('#name')).toBeVisible();
    });
});
