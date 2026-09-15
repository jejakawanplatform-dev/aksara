import { describe, it, expect } from 'vitest';
import { formatBytes } from '@/lib/image-compress.js';

describe('formatBytes helper', () => {
    it('memformat ukuran byte murni (< 1024 B)', () => {
        expect(formatBytes(0)).toBe('0 B');
        expect(formatBytes(512)).toBe('512 B');
        expect(formatBytes(1023)).toBe('1023 B');
    });

    it('memformat ukuran kilobyte (KB)', () => {
        expect(formatBytes(1024)).toBe('1 KB');
        expect(formatBytes(1024 * 50)).toBe('50 KB');
        expect(formatBytes(1024 * 1023)).toBe('1023 KB');
    });

    it('memformat ukuran megabyte (MB)', () => {
        expect(formatBytes(1024 * 1024)).toBe('1.0 MB');
        expect(formatBytes(1024 * 1024 * 2.5)).toBe('2.5 MB');
    });
});
