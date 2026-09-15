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
});
