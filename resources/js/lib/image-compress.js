/**
 * Browser-side image resize + JPEG compression for TipTap material uploads.
 * Keeps payloads under PHP upload_max_filesize (often 2M) before multipart upload.
 */

const DEFAULTS = {
    maxWidth: 1600,
    maxHeight: 1600,
    quality: 0.82,
    /** Soft target after compress; may still upload if canvas cannot get smaller. */
    targetBytes: 1.5 * 1024 * 1024,
    mime: 'image/jpeg',
};

/**
 * @param {File} file
 * @param {Partial<typeof DEFAULTS>} [options]
 * @returns {Promise<File>}
 */
export async function compressImageFile(file, options = {}) {
    const opts = { ...DEFAULTS, ...options };

    if (!file || !file.type.startsWith('image/')) {
        return file;
    }

    // SVG/GIF keep as-is (animation / vectors).
    if (file.type === 'image/svg+xml' || file.type === 'image/gif') {
        return file;
    }

    const source = await loadDrawable(file);
    try {
        const srcW = source.width;
        const srcH = source.height;

        if (
            file.size <= opts.targetBytes
            && file.type === 'image/jpeg'
            && srcW <= opts.maxWidth
            && srcH <= opts.maxHeight
        ) {
            return file;
        }

        const { width, height } = fitWithin(srcW, srcH, opts.maxWidth, opts.maxHeight);
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d', { alpha: false });
        if (!ctx) {
            return file;
        }

        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, width, height);
        ctx.drawImage(source.element, 0, 0, width, height);

        let quality = opts.quality;
        let blob = await canvasToBlob(canvas, opts.mime, quality);

        while (blob && blob.size > opts.targetBytes && quality > 0.55) {
            quality = Math.round((quality - 0.08) * 100) / 100;
            blob = await canvasToBlob(canvas, opts.mime, quality);
        }

        if (blob && blob.size > opts.targetBytes) {
            const scale = Math.sqrt(opts.targetBytes / blob.size) * 0.95;
            const w2 = Math.max(640, Math.round(width * scale));
            const h2 = Math.max(640, Math.round(height * scale));
            canvas.width = w2;
            canvas.height = h2;
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, w2, h2);
            ctx.drawImage(source.element, 0, 0, w2, h2);
            blob = await canvasToBlob(canvas, opts.mime, 0.75);
        }

        if (!blob) {
            return file;
        }

        const base = file.name.replace(/\.[^.]+$/, '') || 'ilustrasi';

        return new File([blob], `${base}.jpg`, {
            type: 'image/jpeg',
            lastModified: Date.now(),
        });
    } finally {
        source.cleanup();
    }
}

/**
 * @param {File} file
 * @returns {Promise<{element: CanvasImageSource, width: number, height: number, cleanup: () => void}>}
 */
async function loadDrawable(file) {
    if (typeof createImageBitmap === 'function') {
        try {
            const bitmap = await createImageBitmap(file);
            return {
                element: bitmap,
                width: bitmap.width,
                height: bitmap.height,
                cleanup: () => {
                    if (typeof bitmap.close === 'function') {
                        bitmap.close();
                    }
                },
            };
        } catch (_) {
            // Fall through to HTMLImageElement.
        }
    }

    const url = URL.createObjectURL(file);
    const img = await loadHtmlImage(url);

    return {
        element: img,
        width: img.naturalWidth,
        height: img.naturalHeight,
        cleanup: () => URL.revokeObjectURL(url),
    };
}

/**
 * @param {string} url
 * @returns {Promise<HTMLImageElement>}
 */
function loadHtmlImage(url) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = () => reject(new Error('Gagal membaca file gambar di browser.'));
        img.src = url;
    });
}

/**
 * @param {number} width
 * @param {number} height
 * @param {number} maxWidth
 * @param {number} maxHeight
 */
function fitWithin(width, height, maxWidth, maxHeight) {
    const ratio = Math.min(maxWidth / width, maxHeight / height, 1);
    return {
        width: Math.max(1, Math.round(width * ratio)),
        height: Math.max(1, Math.round(height * ratio)),
    };
}

/**
 * @param {HTMLCanvasElement} canvas
 * @param {string} mime
 * @param {number} quality
 * @returns {Promise<Blob|null>}
 */
function canvasToBlob(canvas, mime, quality) {
    return new Promise((resolve) => {
        canvas.toBlob((blob) => resolve(blob), mime, quality);
    });
}

/**
 * @param {number} bytes
 */
export function formatBytes(bytes) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}
