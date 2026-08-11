<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
/**
 * Icon-only TipTap toolbar — looks like a real editor chrome, not form pills.
 */
defineProps({
    editor: { type: Object, default: null },
    withMath: { type: Boolean, default: false },
    canUseMedia: { type: Boolean, default: false },
    canUseImageActions: { type: Boolean, default: false },
});

const emit = defineEmits([
    'undo',
    'redo',
    'bold',
    'italic',
    'strike',
    'h2',
    'h3',
    'bullet',
    'ordered',
    'quote',
    'link',
    'table',
    'image',
    'math',
    'image-props',
    'image-replace',
    'image-delete',
]);

function active(editor, name, attrs) {
    if (!editor) return false;
    return attrs ? editor.isActive(name, attrs) : editor.isActive(name);
}
</script>

<template>
    <div class="aksara-tiptap-toolbar" role="toolbar" aria-label="Format teks">
        <div class="aksara-tiptap-toolbar__group">
            <button type="button" class="aksara-tiptap-tb" title="Undo (Ctrl+Z)" @click="emit('undo')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 14l-4-4 4-4" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 10h8a5 5 0 010 10h-3" />
                </svg>
            </button>
            <button type="button" class="aksara-tiptap-tb" title="Redo (Ctrl+Y)" @click="emit('redo')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 14l4-4-4-4" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 10h-8a5 5 0 000 10h3" />
                </svg>
            </button>
        </div>

        <span class="aksara-tiptap-toolbar__sep" aria-hidden="true" />

        <div class="aksara-tiptap-toolbar__group">
            <button
                type="button"
                class="aksara-tiptap-tb"
                :class="{ 'is-active': active(editor, 'bold') }"
                title="Bold"
                @click="emit('bold')"
            >
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M7 5h5.5a3.5 3.5 0 010 7H7V5zm0 7h6.5a3.5 3.5 0 010 7H7v-7z" />
                </svg>
            </button>
            <button
                type="button"
                class="aksara-tiptap-tb"
                :class="{ 'is-active': active(editor, 'italic') }"
                title="Italic"
                @click="emit('italic')"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M12 5h6M8 19h6M14 5l-4 14" />
                </svg>
            </button>
            <button
                type="button"
                class="aksara-tiptap-tb"
                :class="{ 'is-active': active(editor, 'strike') }"
                title="Strikethrough"
                @click="emit('strike')"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M5 12h14M8.5 7.5C9.2 6.2 10.5 5.5 12 5.5c2.2 0 3.5 1.2 3.5 2.8 0 .7-.3 1.3-.8 1.8M7.2 14.2c.5 1.8 2 2.8 4.3 2.8 2.3 0 4-1.2 4.5-3" />
                </svg>
            </button>
        </div>

        <span class="aksara-tiptap-toolbar__sep" aria-hidden="true" />

        <div class="aksara-tiptap-toolbar__group">
            <button
                type="button"
                class="aksara-tiptap-tb aksara-tiptap-tb--label"
                :class="{ 'is-active': active(editor, 'heading', { level: 2 }) }"
                title="Heading 2"
                @click="emit('h2')"
            >
                <span>H2</span>
            </button>
            <button
                type="button"
                class="aksara-tiptap-tb aksara-tiptap-tb--label"
                :class="{ 'is-active': active(editor, 'heading', { level: 3 }) }"
                title="Heading 3"
                @click="emit('h3')"
            >
                <span>H3</span>
            </button>
        </div>

        <span class="aksara-tiptap-toolbar__sep" aria-hidden="true" />

        <div class="aksara-tiptap-toolbar__group">
            <button
                type="button"
                class="aksara-tiptap-tb"
                :class="{ 'is-active': active(editor, 'bulletList') }"
                title="Bullet list"
                @click="emit('bullet')"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M9 6h11M9 12h11M9 18h11" />
                    <circle cx="5" cy="6" r="1.2" fill="currentColor" stroke="none" />
                    <circle cx="5" cy="12" r="1.2" fill="currentColor" stroke="none" />
                    <circle cx="5" cy="18" r="1.2" fill="currentColor" stroke="none" />
                </svg>
            </button>
            <button
                type="button"
                class="aksara-tiptap-tb"
                :class="{ 'is-active': active(editor, 'orderedList') }"
                title="Numbered list"
                @click="emit('ordered')"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M10 6h10M10 12h10M10 18h10" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5h2v3.5H4m0 5.5h3m-3 0h1.5v.01M4 18h3" />
                </svg>
            </button>
            <button
                type="button"
                class="aksara-tiptap-tb"
                :class="{ 'is-active': active(editor, 'blockquote') }"
                title="Blockquote"
                @click="emit('quote')"
            >
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M7.2 18c-1.9 0-3.2-1.4-3.2-3.3 0-2.4 1.7-4.7 4.8-6.8l.7 1.1C7.3 10.5 6.4 12 6.4 13.2c.3-.2.8-.3 1.3-.3 1.4 0 2.5 1 2.5 2.4S9.1 18 7.2 18zm9 0c-1.9 0-3.2-1.4-3.2-3.3 0-2.4 1.7-4.7 4.8-6.8l.7 1.1c-2.2 1.5-3.1 3-3.1 4.2.3-.2.8-.3 1.3-.3 1.4 0 2.5 1 2.5 2.4s-1.2 2.7-3 2.7z" />
                </svg>
            </button>
        </div>

        <span class="aksara-tiptap-toolbar__sep" aria-hidden="true" />

        <div class="aksara-tiptap-toolbar__group">
            <button
                type="button"
                class="aksara-tiptap-tb"
                :class="{ 'is-active': active(editor, 'link') }"
                title="Tautan"
                @click="emit('link')"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 13a5 5 0 007.54.54l1.46-1.46a5 5 0 00-7.07-7.07L10.5 6.5" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 11a5 5 0 00-7.54-.54L5 12a5 5 0 007.07 7.07l1.43-1.43" />
                </svg>
            </button>
            <button
                type="button"
                class="aksara-tiptap-tb"
                :class="{ 'is-active': active(editor, 'table') }"
                title="Sisipkan tabel"
                @click="emit('table')"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="3.5" y="4.5" width="17" height="15" rx="1.5" />
                    <path d="M3.5 9.5h17M3.5 14.5h17M9.5 4.5v15M14.5 4.5v15" />
                </svg>
            </button>
            <button
                v-if="canUseMedia"
                type="button"
                class="aksara-tiptap-tb"
                title="Gambar"
                @click="emit('image')"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="3.5" y="4.5" width="17" height="15" rx="2" />
                    <circle cx="9" cy="10" r="1.5" fill="currentColor" stroke="none" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.5 16.5l4.5-4.5 3 3 4-5 5.5 6.5" />
                </svg>
            </button>
            <button
                v-if="withMath"
                type="button"
                class="aksara-tiptap-tb aksara-tiptap-tb--label"
                title="Rumus KaTeX"
                @click="emit('math')"
            >
                <span class="font-serif italic">∑</span>
            </button>
        </div>

        <template v-if="canUseImageActions">
            <span class="aksara-tiptap-toolbar__sep" aria-hidden="true" />
            <div class="aksara-tiptap-toolbar__group">
                <button type="button" class="aksara-tiptap-tb" title="Properti gambar" @click="emit('image-props')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.5a3.5 3.5 0 100-7 3.5 3.5 0 000 7z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.4 15a1.7 1.7 0 00.3 1.8l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-1.8-.3 1.7 1.7 0 00-1 1.5V21a2 2 0 11-4 0v-.1a1.7 1.7 0 00-1.1-1.5 1.7 1.7 0 00-1.8.3l-.1.1a2 2 0 11-2.8-2.8l.1-.1a1.7 1.7 0 00.3-1.8 1.7 1.7 0 00-1.5-1H3a2 2 0 110-4h.1a1.7 1.7 0 001.5-1.1 1.7 1.7 0 00-.3-1.8l-.1-.1a2 2 0 112.8-2.8l.1.1a1.7 1.7 0 001.8.3H9a1.7 1.7 0 001-1.5V3a2 2 0 114 0v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.8-.3l.1-.1a2 2 0 112.8 2.8l-.1.1a1.7 1.7 0 00-.3 1.8V9c.3.6.9 1 1.6 1H21a2 2 0 110 4h-.1a1.7 1.7 0 00-1.5 1z" />
                    </svg>
                </button>
                <button type="button" class="aksara-tiptap-tb" title="Ganti gambar" @click="emit('image-replace')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h3l1.5-2h7L17 7h3v12H4V7z" />
                        <circle cx="12" cy="13" r="3" />
                    </svg>
                </button>
                <button
                    type="button"
                    class="aksara-tiptap-tb aksara-tiptap-tb--danger"
                    title="Hapus gambar"
                    @click="emit('image-delete')"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M9 8V6h6v2m-7 3v7m4-7v7m4-7v7M7 8l1 12h8l1-12" />
                    </svg>
                </button>
            </div>
        </template>
    </div>
</template>
