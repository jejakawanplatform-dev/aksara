import { Image } from '@tiptap/extension-image';
import { mergeAttributes, ResizableNodeView, getRenderedAttributes } from '@tiptap/core';

function styleResizeHandle(handle, direction) {
    const isTop = direction.includes('top');
    const isBottom = direction.includes('bottom');
    const isLeft = direction.includes('left');
    const isRight = direction.includes('right');

    // Posisi INSET (di dalam gambar) — jangan translate keluar,
    // supaya tidak kepotong overflow-y-auto di <main> layout Aksara.
    Object.assign(handle.style, {
        position: 'absolute',
        width: '18px',
        height: '18px',
        background: '#0f766e',
        border: '2px solid #ffffff',
        borderRadius: '9999px',
        boxShadow: '0 0 0 1px rgba(15, 118, 110, 0.45), 0 2px 6px rgba(0, 0, 0, 0.25)',
        zIndex: '60',
        pointerEvents: 'auto',
        boxSizing: 'border-box',
        top: 'auto',
        right: 'auto',
        bottom: 'auto',
        left: 'auto',
        transform: 'none',
        cursor: 'nwse-resize',
        touchAction: 'none',
    });

    if (isTop && isLeft) {
        handle.style.top = '4px';
        handle.style.left = '4px';
        handle.style.cursor = 'nwse-resize';
    } else if (isTop && isRight) {
        handle.style.top = '4px';
        handle.style.right = '4px';
        handle.style.cursor = 'nesw-resize';
    } else if (isBottom && isLeft) {
        handle.style.bottom = '4px';
        handle.style.left = '4px';
        handle.style.cursor = 'nesw-resize';
    } else if (isBottom && isRight) {
        handle.style.bottom = '4px';
        handle.style.right = '4px';
        handle.style.cursor = 'nwse-resize';
    }
}

/**
 * Image block + resize handles (inline-styled so selalu terlihat).
 */
export const AksaraImage = Image.extend({
    name: 'image',
    draggable: false,

    addOptions() {
        return {
            ...this.parent?.(),
            inline: false,
            allowBase64: true,
            HTMLAttributes: {
                class: 'aksara-editor-image',
            },
            resize: {
                enabled: true,
                directions: ['bottom-right', 'bottom-left', 'top-right', 'top-left'],
                minWidth: 80,
                minHeight: 80,
                alwaysPreserveAspectRatio: true,
            },
        };
    },

    addAttributes() {
        return {
            ...this.parent?.(),
            width: {
                default: null,
                parseHTML: (element) => {
                    const w = element.getAttribute('width') || element.style.width;
                    if (!w) return null;
                    const n = parseInt(String(w), 10);
                    return Number.isFinite(n) ? n : null;
                },
                renderHTML: (attributes) => {
                    if (!attributes.width) return {};
                    return { width: String(attributes.width) };
                },
            },
            height: {
                default: null,
                parseHTML: (element) => {
                    const h = element.getAttribute('height') || element.style.height;
                    if (!h) return null;
                    const n = parseInt(String(h), 10);
                    return Number.isFinite(n) ? n : null;
                },
                renderHTML: (attributes) => {
                    if (!attributes.height) return {};
                    return { height: String(attributes.height) };
                },
            },
            align: {
                default: 'center',
                parseHTML: (element) => element.getAttribute('data-align') || 'center',
                renderHTML: (attributes) => ({
                    'data-align': attributes.align || 'center',
                }),
            },
        };
    },

    renderHTML({ HTMLAttributes }) {
        const align = HTMLAttributes['data-align'] || 'center';
        const width = HTMLAttributes.width;
        const styleParts = ['max-width:100%', 'height:auto'];

        if (width) {
            styleParts.push(`width:${width}px`);
        }

        if (align === 'left') {
            styleParts.push('display:block', 'margin:0.75rem auto 0.75rem 0');
        } else if (align === 'right') {
            styleParts.push('display:block', 'margin:0.75rem 0 0.75rem auto');
        } else {
            styleParts.push('display:block', 'margin:0.75rem auto');
        }

        return [
            'img',
            mergeAttributes(this.options.HTMLAttributes, HTMLAttributes, {
                style: styleParts.join(';'),
            }),
        ];
    },

    addNodeView() {
        if (!this.options.resize?.enabled || typeof document === 'undefined') {
            return null;
        }

        const { directions, minWidth, minHeight, alwaysPreserveAspectRatio } = this.options.resize;
        const resizeManagedAttributes = new Set(['src', 'width', 'height']);

        return ({ node, getPos, HTMLAttributes, editor }) => {
            const el = document.createElement('img');
            el.draggable = false;
            el.className = 'aksara-editor-image';

            const mergedAttributes = mergeAttributes(this.options.HTMLAttributes, HTMLAttributes);
            Object.entries(mergedAttributes).forEach(([key, value]) => {
                if (value == null) return;
                if (['src', 'width', 'height'].includes(key)) return;
                el.setAttribute(key, value);
            });

            const syncImageSource = (src) => {
                if (typeof src === 'string' && src !== '') {
                    if (el.getAttribute('src') !== src) {
                        el.src = src;
                    }
                    return;
                }
                el.removeAttribute('src');
                el.src = '';
            };

            syncImageSource(HTMLAttributes.src);

            let previousHTMLAttributes = { ...HTMLAttributes };

            const onUpdate = (updatedNode) => {
                if (updatedNode.type !== node.type) {
                    return false;
                }

                const extensionAttributes = editor.extensionManager.attributes.filter(
                    (attribute) => attribute.type === updatedNode.type.name
                );
                const newHTMLAttributes = getRenderedAttributes(updatedNode, extensionAttributes);

                Object.keys(previousHTMLAttributes).forEach((key) => {
                    if (!resizeManagedAttributes.has(key) && !(key in newHTMLAttributes)) {
                        el.removeAttribute(key);
                    }
                });

                Object.entries(newHTMLAttributes).forEach(([key, value]) => {
                    if (resizeManagedAttributes.has(key)) return;
                    if (value != null) el.setAttribute(key, value);
                    else el.removeAttribute(key);
                });

                syncImageSource(newHTMLAttributes.src);
                previousHTMLAttributes = newHTMLAttributes;

                const align = updatedNode.attrs.align || 'center';
                el.setAttribute('data-align', align);
                if (updatedNode.attrs.width) {
                    el.style.width = `${updatedNode.attrs.width}px`;
                    el.style.height = 'auto';
                }
                if (updatedNode.attrs.height) {
                    el.style.height = `${updatedNode.attrs.height}px`;
                }

                const container = el.closest?.('[data-resize-container]');
                if (container) {
                    container.setAttribute('data-align', align);
                }

                return true;
            };

            const nodeView = new ResizableNodeView({
                element: el,
                editor,
                node,
                getPos,
                onResize: (width, height) => {
                    el.style.width = `${width}px`;
                    el.style.height = `${height}px`;
                },
                onCommit: (width, height) => {
                    const pos = getPos();
                    if (pos === undefined) return;
                    editor
                        .chain()
                        .setNodeSelection(pos)
                        .updateAttributes('image', { width: Math.round(width), height: Math.round(height) })
                        .run();
                },
                onUpdate,
                options: {
                    directions,
                    min: { width: minWidth, height: minHeight },
                    preserveAspectRatio: alwaysPreserveAspectRatio === true,
                    className: {
                        container: 'aksara-image-resize-container',
                        wrapper: 'aksara-image-resize-wrapper',
                        handle: 'aksara-image-resize-handle',
                        resizing: 'is-resizing',
                    },
                    createCustomHandle: (direction) => {
                        const handle = document.createElement('div');
                        handle.dataset.resizeHandle = direction;
                        handle.className = 'aksara-image-resize-handle';
                        styleResizeHandle(handle, direction);
                        return handle;
                    },
                },
            });

            const dom = nodeView.dom;
            dom.setAttribute('data-align', node.attrs.align || 'center');
            dom.setAttribute('data-aksara-image', '1');
            // Jangan tunggu onload — gambar cached sering tidak fire onload.
            dom.style.visibility = 'visible';
            dom.style.pointerEvents = 'auto';
            dom.style.overflow = 'visible';
            // Outline selalu — bukti NodeView aktif + mudah diklik
            dom.style.outline = '2px dashed rgba(15, 118, 110, 0.35)';
            dom.style.outlineOffset = '4px';
            dom.style.borderRadius = '0.5rem';

            if (node.attrs.width) {
                el.style.width = `${node.attrs.width}px`;
                el.style.height = 'auto';
            }
            if (node.attrs.height) {
                el.style.height = `${node.attrs.height}px`;
            }

            // Jika natural size sudah ada (cache), pastikan handle tetap terlihat.
            if (el.complete) {
                dom.style.visibility = 'visible';
                dom.style.pointerEvents = 'auto';
            } else {
                el.addEventListener('load', () => {
                    dom.style.visibility = 'visible';
                    dom.style.pointerEvents = 'auto';
                });
                el.addEventListener('error', () => {
                    dom.style.visibility = 'visible';
                    dom.style.pointerEvents = 'auto';
                });
            }

            return nodeView;
        };
    },
});
