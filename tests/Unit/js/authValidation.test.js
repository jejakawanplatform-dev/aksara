import { describe, it, expect } from 'vitest';
import {
    isFilled,
    isValidEmail,
    emailError,
    passwordError,
    confirmationError,
    nameError,
    resolveError,
} from '@/Composables/authValidation.js';

describe('authValidation helpers', () => {
    it('memvalidasi field terisi (isFilled)', () => {
        expect(isFilled('')).toBe(false);
        expect(isFilled('   ')).toBe(false);
        expect(isFilled(null)).toBe(false);
        expect(isFilled('Naya')).toBe(true);
    });

    it('memvalidasi format email (isValidEmail & emailError)', () => {
        expect(isValidEmail('admin@aksara.test')).toBe(true);
        expect(isValidEmail('bukan-email')).toBe(false);
        expect(isValidEmail('nama@domain')).toBe(false);

        expect(emailError('')).toBe('Email wajib diisi.');
        expect(emailError('salahformat')).toBe('Format email tidak valid.');
        expect(emailError('guru@aksara.test')).toBeNull();
    });

    it('memvalidasi panjang password minimal (passwordError)', () => {
        expect(passwordError('')).toBe('Password wajib diisi.');
        expect(passwordError('12345', { min: 8 })).toBe('Password minimal 8 karakter.');
        expect(passwordError('password123', { min: 8 })).toBeNull();
    });

    it('memvalidasi konfirmasi kecocokan password (confirmationError)', () => {
        expect(confirmationError('rahasia', '')).toBe('Konfirmasi password wajib diisi.');
        expect(confirmationError('rahasia', 'beda')).toBe('Konfirmasi password tidak cocok.');
        expect(confirmationError('rahasia', 'rahasia')).toBeNull();
    });

    it('mengutamakan error server di atas error lokal (resolveError)', () => {
        expect(resolveError('Error dari backend', 'Error lokal', true)).toBe('Error dari backend');
        expect(resolveError(null, 'Error lokal', true)).toBe('Error lokal');
        expect(resolveError(null, 'Error lokal', false)).toBeNull();
    });
});
