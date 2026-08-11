/**
 * Aksara — platform pembelajaran berbantuan AI.
 *
 * @copyright 2026 jejakawan (https://jejakawan.com)
 * @license   MIT
 *
 * Clone, fork, and modification are permitted under the MIT License.
 * See the LICENSE file in the project root.
 */
/**
 * Validasi ringan sisi klien untuk form auth (selaras aturan Laravel Breeze).
 */

export function isFilled(value) {
    return String(value ?? '').trim().length > 0;
}

export function isValidEmail(value) {
    const email = String(value ?? '').trim();
    if (!email) return false;
    // Cukup ketat untuk UX; server tetap SoT.
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

export function emailError(value, { required = true } = {}) {
    const email = String(value ?? '').trim();
    if (!email) {
        return required ? 'Email wajib diisi.' : null;
    }
    if (!isValidEmail(email)) {
        return 'Format email tidak valid.';
    }
    return null;
}

export function passwordError(value, { required = true, min = 0 } = {}) {
    const password = String(value ?? '');
    if (!password) {
        return required ? 'Password wajib diisi.' : null;
    }
    if (min > 0 && password.length < min) {
        return `Password minimal ${min} karakter.`;
    }
    return null;
}

export function confirmationError(password, confirmation) {
    if (!String(confirmation ?? '')) {
        return 'Konfirmasi password wajib diisi.';
    }
    if (password !== confirmation) {
        return 'Konfirmasi password tidak cocok.';
    }
    return null;
}

export function nameError(value) {
    if (!isFilled(value)) {
        return 'Nama wajib diisi.';
    }
    return null;
}

/** Gabungkan error server Inertia dengan error lokal (setelah field disentuh / submit). */
export function resolveError(serverError, localError, showLocal) {
    return serverError || (showLocal ? localError : null) || null;
}

export function inputClass(hasError) {
    return [
        'aksara-input',
        hasError ? 'aksara-input--error' : '',
    ];
}
