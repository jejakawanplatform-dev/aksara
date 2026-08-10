<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import { Table } from '@tiptap/extension-table';
import { TableRow } from '@tiptap/extension-table-row';
import { TableHeader } from '@tiptap/extension-table-header';
import { TableCell } from '@tiptap/extension-table-cell';
import { Link } from '@tiptap/extension-link';
import axios from 'axios';
import { AksaraImage } from './aksara-image';
import { compressImageFile } from '@/lib/image-compress';

const props = defineProps({
    modelValue: { type: String, default: '' },
    uploadUrl: { type: String, default: '' },
    editable: { type: Boolean, default: true },
});

const emit = defineEmits(['update:modelValue']);

const fileInput = ref(null);
const uploading = ref(false);
const uploadError = ref('');
const imageSelected = ref(false);
const showProps = ref(false);
const propsForm = ref({ alt: '', title: '', width: '', align: 'center' });

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
            class: 'aksara-tiptap-prose focus:outline-none min-h-[160px] px-3 py-2',
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

onBeforeUnmount(() => {
    editor.value?.destroy();
});

const canUploadImages = computed(() => !!props.uploadUrl);
const canUseImageActions = computed(() => imageSelected.value && !!editor.value);

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

function pickImage() {
    if (!canUploadImages.value) return;
    uploadError.value = '';
    fileInput.value?.click();
}

async function onFileChange(event) {
    const file = event.target.files?.[0];
    event.target.value = '';
    if (!file || !editor.value) return;

    uploading.value = true;
    uploadError.value = '';

    try {
        const compressed = await compressImageFile(file);
        const dataUrl = await fileToDataUrl(compressed);
        const { data } = await axios.post(props.uploadUrl, {
            dataUrl,
            originalName: file.name || 'ilustrasi.jpg',
        });
        const url = data?.url;
        if (!url) {
            throw new Error('Server tidak mengembalikan URL gambar.');
        }
        editor.value
            .chain()
            .focus()
            .setImage({ src: url, alt: file.name || 'Ilustrasi', align: 'center' })
            .run();
    } catch (err) {
        const msg =
            err?.response?.data?.errors?.dataUrl?.[0] ||
            err?.response?.data?.message ||
            err?.message ||
            'Gagal mengunggah gambar.';
        uploadError.value = msg;
    } finally {
        uploading.value = false;
    }
}

function fileToDataUrl(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(String(reader.result));
        reader.onerror = () => reject(new Error('Gagal membaca file.'));
        reader.readAsDataURL(file);
    });
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
    pickImage();
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
    // Let TipTap handle click; ensure NodeSelection via setNodeSelection if needed
    try {
        const pos = editor.value.view.posAtDOM(container, 0);
        if (typeof pos === 'number') {
            editor.value.chain().setNodeSelection(pos).run();
        }
    } catch (_) {
        // ignore
    }
}
</script>

<template>
    <div class="aksara-tiptap-root overflow-visible rounded-xl border border-aksara-line bg-white" @click="selectImageUnderCursor">
        <div class="flex flex-wrap items-center gap-1 border-b border-aksara-line bg-aksara-mist/40 px-2 py-1.5">
            <button type="button" class="aksara-btn-secondary !px-2 !py-1 text-xs" @click="run((c) => c.toggleBold())">B</button>
            <button type="button" class="aksara-btn-secondary !px-2 !py-1 text-xs" @click="run((c) => c.toggleItalic())">I</button>
            <button type="button" class="aksara-btn-secondary !px-2 !py-1 text-xs" @click="run((c) => c.toggleHeading({ level: 2 }))">H2</button>
            <button type="button" class="aksara-btn-secondary !px-2 !py-1 text-xs" @click="run((c) => c.toggleBulletList())">• List</button>
            <button type="button" class="aksara-btn-secondary !px-2 !py-1 text-xs" @click="run((c) => c.toggleOrderedList())">1. List</button>
            <button type="button" class="aksara-btn-secondary !px-2 !py-1 text-xs" @click="setLink">Link</button>
            <button
                type="button"
                class="aksara-btn-secondary !px-2 !py-1 text-xs"
                :disabled="uploading || !canUploadImages"
                :title="canUploadImages ? 'Unggah gambar' : 'Unggah gambar tidak tersedia'"
                @click="pickImage"
            >
                {{ uploading ? 'Unggah…' : 'Gambar' }}
            </button>
            <span class="mx-1 h-4 w-px bg-aksara-line" />
            <button
                type="button"
                class="aksara-btn-secondary !px-2 !py-1 text-xs"
                :disabled="!canUseImageActions"
                @click="openProps"
            >
                Properti
            </button>
            <button
                type="button"
                class="aksara-btn-secondary !px-2 !py-1 text-xs"
                :disabled="!canUseImageActions || uploading"
                @click="replaceImage"
            >
                Ganti
            </button>
            <button
                type="button"
                class="aksara-btn-secondary !px-2 !py-1 text-xs text-red-600"
                :disabled="!canUseImageActions"
                @click="deleteImage"
            >
                Hapus
            </button>
        </div>

        <p v-if="uploadError" class="border-b border-red-100 bg-red-50 px-3 py-2 text-xs text-red-700">
            {{ uploadError }}
        </p>

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

        <input
            ref="fileInput"
            type="file"
            accept="image/*"
            class="hidden"
            @change="onFileChange"
        />
    </div>
</template>
