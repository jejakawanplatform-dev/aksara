<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import { Table } from '@tiptap/extension-table';
import { TableRow } from '@tiptap/extension-table-row';
import { TableHeader } from '@tiptap/extension-table-header';
import { TableCell } from '@tiptap/extension-table-cell';
import { Link } from '@tiptap/extension-link';
import { AksaraImage } from './aksara-image';
import MediaPicker from './MediaPicker.vue';
import TipTapToolbar from './TipTapToolbar.vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    /** @deprecated gunakan media.uploadUrl */
    uploadUrl: { type: String, default: '' },
    withMath: { type: Boolean, default: false },
    media: {
        type: Object,
        default: null,
        // { listUrl, uploadUrl, deleteUrl }
    },
    editable: { type: Boolean, default: true },
});

const emit = defineEmits(['update:modelValue']);

const imageSelected = ref(false);
const showProps = ref(false);
const showPicker = ref(false);
const showMath = ref(false);
const propsForm = ref({ alt: '', title: '', width: '', align: 'center' });
const mathFormula = ref('');
const mathDisplay = ref(true);
const mathPreviewHtml = ref('');
const mathError = ref('');
const mathReady = ref(false);
let katexModule = null;

const mediaConfig = computed(() => {
    if (props.media && props.media.listUrl && props.media.uploadUrl) {
        return {
            listUrl: props.media.listUrl,
            uploadUrl: props.media.uploadUrl,
            deleteUrl: props.media.deleteUrl || '',
        };
    }
    if (props.uploadUrl) {
        return null;
    }
    return null;
});

const canUseMedia = computed(() => !!mediaConfig.value);
const canUseImageActions = computed(() => imageSelected.value && !!editor.value);

const editor = useEditor({
    content: props.modelValue || '',
    editable: props.editable,
    extensions: [
        StarterKit.configure({
            heading: { levels: [1, 2, 3] },
            link: false,
        }),
        Table.configure({ resizable: true }),
        TableRow,
        TableHeader,
        TableCell,
        Link.configure({ openOnClick: false }),
        AksaraImage,
    ],
    editorProps: {
        attributes: {
            class: 'aksara-prose aksara-tiptap-prose focus:outline-none min-h-[160px] px-3 py-2',
        },
        handleDOMEvents: {
            contextmenu: (view, event) => {
                const node = event.target?.closest?.('[data-aksara-image], [data-resize-container]');
                if (!node || !view.dom.contains(node)) {
                    return false;
                }
                event.preventDefault();
                try {
                    const pos = view.posAtDOM(node, 0);
                    if (typeof pos === 'number' && editor.value) {
                        editor.value.chain().setNodeSelection(pos).run();
                        showProps.value = true;
                    }
                } catch (_) {
                    // ignore
                }
                return true;
            },
        },
    },
    onUpdate: ({ editor: ed }) => {
        emit('update:modelValue', ed.getHTML());
    },
    onSelectionUpdate: ({ editor: ed }) => {
        syncImageSelection(ed);
    },
    onTransaction: ({ editor: ed }) => {
        syncImageSelection(ed);
    },
});

function syncImageSelection(ed) {
    const active = ed.isActive('image');
    imageSelected.value = active;
    if (active) {
        const attrs = ed.getAttributes('image');
        propsForm.value = {
            alt: attrs.alt || '',
            title: attrs.title || '',
            width: attrs.width ? String(attrs.width) : '',
            align: attrs.align || 'center',
        };
    } else {
        showProps.value = false;
    }
}

watch(
    () => props.modelValue,
    (value) => {
        if (!editor.value) return;
        const current = editor.value.getHTML();
        if (value !== current) {
            editor.value.commands.setContent(value || '', { emitUpdate: false });
        }
    },
);

watch(
    () => props.editable,
    (value) => {
        editor.value?.setEditable(value);
    },
);

onBeforeUnmount(() => {
    editor.value?.destroy();
});

function run(cmd) {
    if (!editor.value) return;
    cmd(editor.value.chain().focus()).run();
}

function setLink() {
    const previous = editor.value?.getAttributes('link').href;
    const url = window.prompt('URL tautan', previous || 'https://');
    if (url === null) return;
    if (url === '') {
        run((c) => c.unsetLink());
        return;
    }
    run((c) => c.extendMarkRange('link').setLink({ href: url }));
}

function insertTable() {
    run((c) =>
        c.insertTable({
            rows: 3,
            cols: 3,
            withHeaderRow: true,
        }),
    );
}

function openPicker() {
    if (!canUseMedia.value) return;
    showPicker.value = true;
}

function onMediaSelect({ url, name }) {
    if (!editor.value || !url) return;
    editor.value
        .chain()
        .focus()
        .setImage({ src: url, alt: name || 'Ilustrasi', align: 'center' })
        .run();
    showPicker.value = false;
}

function openProps() {
    if (!canUseImageActions.value) return;
    showProps.value = true;
}

function applyProps() {
    if (!editor.value || !imageSelected.value) return;
    const width = propsForm.value.width ? parseInt(propsForm.value.width, 10) : null;
    editor.value
        .chain()
        .focus()
        .updateAttributes('image', {
            alt: propsForm.value.alt || null,
            title: propsForm.value.title || null,
            width: Number.isFinite(width) ? width : null,
            align: propsForm.value.align || 'center',
        })
        .run();
    showProps.value = false;
}

function replaceImage() {
    if (!canUseImageActions.value) return;
    openPicker();
}

function deleteImage() {
    if (!editor.value || !imageSelected.value) return;
    editor.value.chain().focus().deleteSelection().run();
    showProps.value = false;
}

function selectImageUnderCursor(event) {
    if (!editor.value) return;
    const target = event.target;
    const container = target?.closest?.('[data-aksara-image], [data-resize-container]');
    if (!container || !editor.value.view.dom.contains(container)) {
        return;
    }
    try {
        const pos = editor.value.view.posAtDOM(container, 0);
        if (typeof pos === 'number') {
            editor.value.chain().setNodeSelection(pos).run();
        }
    } catch (_) {
        // ignore
    }
}

async function ensureKatex() {
    if (katexModule) {
        mathReady.value = true;
        return katexModule;
    }
    await import('katex/dist/katex.min.css');
    katexModule = await import('katex');
    mathReady.value = true;
    return katexModule;
}

async function openMath() {
    if (!props.withMath) return;
    mathFormula.value = '';
    mathDisplay.value = true;
    mathPreviewHtml.value = '';
    mathError.value = '';
    showMath.value = true;
    try {
        await ensureKatex();
    } catch (err) {
        mathError.value = err?.message || 'Gagal memuat KaTeX.';
    }
}

async function refreshMathPreview() {
    const raw = mathFormula.value.trim();
    if (!raw) {
        mathPreviewHtml.value = '';
        mathError.value = '';
        return;
    }
    try {
        const katex = await ensureKatex();
        mathPreviewHtml.value = katex.default.renderToString(raw, {
            displayMode: mathDisplay.value,
            throwOnError: false,
        });
        mathError.value = '';
    } catch (err) {
        mathError.value = err?.message || 'Preview gagal.';
        mathPreviewHtml.value = '';
    }
}

watch([mathFormula, mathDisplay], () => {
    if (showMath.value) {
        refreshMathPreview();
    }
});

function insertMath() {
    const raw = mathFormula.value.trim();
    if (!raw || !editor.value) return;
    const token = mathDisplay.value ? `$$${raw}$$` : `$${raw}$`;
    editor.value.chain().focus().insertContent(token).run();
    showMath.value = false;
}
</script>

<template>
    <div class="aksara-tiptap-root overflow-visible rounded-xl border border-aksara-line bg-white shadow-sm" @click="selectImageUnderCursor">
        <TipTapToolbar
            :editor="editor"
            :with-math="withMath"
            :can-use-media="canUseMedia"
            :can-use-image-actions="canUseImageActions"
            @undo="run((c) => c.undo())"
            @redo="run((c) => c.redo())"
            @bold="run((c) => c.toggleBold())"
            @italic="run((c) => c.toggleItalic())"
            @strike="run((c) => c.toggleStrike())"
            @h2="run((c) => c.toggleHeading({ level: 2 }))"
            @h3="run((c) => c.toggleHeading({ level: 3 }))"
            @bullet="run((c) => c.toggleBulletList())"
            @ordered="run((c) => c.toggleOrderedList())"
            @quote="run((c) => c.toggleBlockquote())"
            @link="setLink"
            @table="insertTable"
            @image="openPicker"
            @math="openMath"
            @image-props="openProps"
            @image-replace="replaceImage"
            @image-delete="deleteImage"
        />

        <div
            v-if="showProps && imageSelected"
            class="space-y-3 border-b border-aksara-line bg-aksara-mist/20 p-3 text-sm"
        >
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="block text-xs font-semibold text-aksara-muted">
                    Alt
                    <input v-model="propsForm.alt" type="text" class="aksara-input mt-1" />
                </label>
                <label class="block text-xs font-semibold text-aksara-muted">
                    Title
                    <input v-model="propsForm.title" type="text" class="aksara-input mt-1" />
                </label>
                <label class="block text-xs font-semibold text-aksara-muted">
                    Lebar (px)
                    <input v-model="propsForm.width" type="number" min="80" class="aksara-input mt-1" />
                </label>
                <label class="block text-xs font-semibold text-aksara-muted">
                    Perataan
                    <select v-model="propsForm.align" class="aksara-input mt-1">
                        <option value="left">Kiri</option>
                        <option value="center">Tengah</option>
                        <option value="right">Kanan</option>
                    </select>
                </label>
            </div>
            <div class="flex gap-2">
                <button type="button" class="aksara-btn-primary !px-3 !py-1.5 text-xs" @click="applyProps">
                    Terapkan
                </button>
                <button type="button" class="aksara-btn-secondary !px-3 !py-1.5 text-xs" @click="showProps = false">
                    Batal
                </button>
            </div>
        </div>

        <EditorContent :editor="editor" class="overflow-visible" />

        <MediaPicker
            v-if="mediaConfig"
            :open="showPicker"
            :list-url="mediaConfig.listUrl"
            :upload-url="mediaConfig.uploadUrl"
            :delete-url="mediaConfig.deleteUrl"
            @close="showPicker = false"
            @select="onMediaSelect"
        />

        <Teleport to="body">
            <div
                v-if="showMath"
                class="aksara-overlay z-[80]"
                @click.self="showMath = false"
            >
                <div class="aksara-dialog max-w-lg p-4">
                    <h3 class="text-base font-semibold text-aksara-ink">Sisipkan rumus</h3>
                    <p class="mt-1 text-xs text-aksara-muted">KaTeX — contoh: <code>E = mc^2</code> atau <code>\frac{a}{b}</code></p>
                    <label class="mt-3 block text-xs font-semibold text-aksara-muted">
                        Formula
                        <textarea
                            v-model="mathFormula"
                            rows="3"
                            class="aksara-input mt-1 font-mono text-sm"
                            placeholder="x = \frac{-b \pm \sqrt{b^2-4ac}}{2a}"
                        />
                    </label>
                    <label class="mt-2 flex items-center gap-2 text-xs text-aksara-ink">
                        <input v-model="mathDisplay" type="checkbox" class="rounded border-aksara-line text-aksara-teal focus:ring-aksara-teal" />
                        Mode blok ($$…$$)
                    </label>
                    <div class="mt-3 min-h-[3rem] rounded-lg border border-aksara-line bg-aksara-mist/40 px-3 py-2 text-sm">
                        <p v-if="mathError" class="text-xs text-aksara-danger">{{ mathError }}</p>
                        <div v-else-if="mathPreviewHtml" class="overflow-x-auto" v-html="mathPreviewHtml" />
                        <p v-else class="text-xs text-aksara-muted">Preview muncul di sini…</p>
                    </div>
                    <div class="mt-3 flex justify-end gap-2">
                        <button type="button" class="aksara-btn-secondary !px-3 !py-1.5 text-xs" @click="showMath = false">
                            Batal
                        </button>
                        <button
                            type="button"
                            class="aksara-btn-primary !px-3 !py-1.5 text-xs"
                            :disabled="!mathFormula.trim()"
                            @click="insertMath"
                        >
                            Sisipkan
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
