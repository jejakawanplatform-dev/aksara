import { test, expect } from '@playwright/test';

test.describe('Bulk Actions & Selection Toolkit E2E', () => {
    test.beforeEach(async ({ page }) => {
        // Login sebagai Admin
        await page.goto('/login');
        await page.fill('#email', 'admin@aksara.test');
        await page.fill('#password', 'password');
        await page.click('button:has-text("Masuk")');
        await expect(page).toHaveURL(/.*dashboard/);
    });

    test('Memilih checkbox pengguna memicu BulkToolbar dan membuka BulkConfirmModal dengan double confirmation', async ({ page }) => {
        await page.goto('/users');

        // Pastikan tabel pengguna termuat
        await expect(page.locator('body')).toContainText('Manajemen Pengguna');

        // Dapatkan checkbox di baris data pertama tabel
        const rowCheckbox = page.locator('tbody tr input[type="checkbox"]').first();
        await expect(rowCheckbox).toBeVisible({ timeout: 10000 });

        // Centang checkbox baris pertama
        await rowCheckbox.check();

        // BulkToolbar harus muncul menggantikan toolbar pencarian
        const bulkToolbar = page.locator('div[role="toolbar"][aria-label="Aksi massal"]');
        await expect(bulkToolbar).toBeVisible();
        await expect(bulkToolbar).toContainText('dipilih');

        // Klik tombol Hapus pada BulkToolbar
        const deleteButton = bulkToolbar.locator('button:has-text("Hapus terpilih")');
        await expect(deleteButton).toBeVisible();
        await deleteButton.click();

        // Modal konfirmasi harus terbuka
        const modal = page.locator('.aksara-modal').filter({ hasText: 'Hapus Pengguna Terpilih' });
        await expect(modal).toBeVisible();

        // Tombol konfirmasi merah harus berstatus disabled sebelum mengetik "HAPUS"
        const confirmBtn = modal.locator('button:has-text("Ya, Hapus Sekarang")');
        await expect(confirmBtn).toBeDisabled();

        // Ketik kata konfirmasi "HAPUS"
        const confirmInput = modal.locator('input[type="text"]');
        await confirmInput.fill('HAPUS');

        // Tombol konfirmasi sekarang harus aktif (enabled)
        await expect(confirmBtn).toBeEnabled();

        // Klik tombol Batal demi keamanan data pengujian
        await modal.locator('button:has-text("Batal")').click();
        await expect(modal).not.toBeVisible();

        // Batalkan seleksi dari BulkToolbar
        await bulkToolbar.locator('button:has-text("Batal")').click();
        await expect(bulkToolbar).not.toBeVisible();
    });
});
