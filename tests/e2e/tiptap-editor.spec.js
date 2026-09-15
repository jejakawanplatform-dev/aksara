import { test, expect } from '@playwright/test';

test.describe('TipTap Editor & MediaPicker Integration', () => {
    test.beforeEach(async ({ page }) => {
        // Login sebagai Guru
        await page.goto('/login');
        await page.fill('#email', 'naya@aksara.test');
        await page.fill('#password', 'password');
        await page.click('button:has-text("Masuk")');
        await expect(page).toHaveURL(/.*dashboard/);
    });

    test('Editor TipTap memuat konten materi, toolbar aktif, dan dapat menerima input teks', async ({ page }) => {
        await page.goto('/materials/1/edit');

        // Pastikan halaman edit materi berhasil dimuat
        await expect(page.locator('body')).toContainText('Edit Materi');

        // Verifikasi keberadaan komponen TipTap Editor
        const editor = page.locator('.tiptap.ProseMirror').first();
        await expect(editor).toBeVisible({ timeout: 10000 });

        // Verifikasi keberadaan toolbar TipTap
        const toolbar = page.locator('.aksara-tiptap-toolbar').first();
        await expect(toolbar).toBeVisible();

        // Verifikasi tombol-tombol esensial toolbar
        await expect(toolbar.locator('button[title="Bold"]')).toBeVisible();
        await expect(toolbar.locator('button[title="Italic"]')).toBeVisible();
        await expect(toolbar.locator('button[title="Gambar"]')).toBeVisible();

        // Interaksi pengetikan teks pada editor
        await editor.click();
        await editor.pressSequentially(' — Pengujian Playwright E2E');
        await expect(editor).toContainText('Pengujian Playwright E2E');
    });

    test('Tombol Gambar membuka dialog MediaPicker', async ({ page }) => {
        await page.goto('/materials/1/edit');

        const toolbar = page.locator('.aksara-tiptap-toolbar').first();
        await expect(toolbar).toBeVisible({ timeout: 10000 });

        // Klik tombol gambar di toolbar
        await toolbar.locator('button[title="Gambar"]').click();

        // Modal MediaPicker harus muncul (dialog aria-label="Pilih media konteks")
        const mediaModal = page.locator('div[role="dialog"][aria-label="Pilih media konteks"]');
        await expect(mediaModal).toBeVisible();
        await expect(mediaModal).toContainText('Media konteks');

        // Verifikasi adanya tombol Tutup
        await mediaModal.locator('button:has-text("Tutup")').click();
        await expect(mediaModal).not.toBeVisible();
    });
});
