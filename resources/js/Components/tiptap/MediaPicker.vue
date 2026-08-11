<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
import { onMounted, ref, watch } from 'vue';
import axios from 'axios';
import { compressImageFile } from '@/lib/image-compress';

const props = defineProps({
    listUrl: { type: String, required: true },
    uploadUrl: { type: String, required: true },
    deleteUrl: { type: String, default: '' },
    open: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'select']);

const items = ref([]);
const loading = ref(false);
const uploading = ref(false);
const error = ref('');
const fileInput = ref(null);
const deletingName = ref('');

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            loadItems();
        }
    },
);

onMounted(() => {
    if (props.open) {
        loadItems();
    }
});

async function loadItems() {
    loading.value = true;
    error.value = '';
    try {
        const { data } = await axios.get(props.listUrl);
        items.value = Array.isArray(data?.items) ? data.items : [];
    } catch (err) {
        error.value =
            err?.response?.data?.message || err?.message || 'Gagal memuat media konteks.';
        items.value = [];
    } finally {
        loading.value = false;
    }
}

function pickUpload() {
    error.value = '';
    fileInput.value?.click();
}

function fileToDataUrl(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(String(reader.result));
        reader.onerror = () => reject(new Error('Gagal membaca file.'));
        reader.readAsDataURL(file);
    });
}

async function onFileChange(event) {
    const file = event.target.files?.[0];
    event.target.value = '';
    if (!file) return;

    uploading.value = true;
    error.value = '';
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
        await loadItems();
        emit('select', { url, name: file.name || 'Ilustrasi' });
    } catch (err) {
        error.value =
            err?.response?.data?.errors?.dataUrl?.[0] ||
            err?.response?.data?.message ||
            err?.message ||
            'Gagal mengunggah gambar.';
    } finally {
        uploading.value = false;
    }
}

function selectItem(item) {
    emit('select', { url: item.url, name: item.name });
}

async function removeItem(item) {
    if (!props.deleteUrl) return;
    if (!window.confirm(`Hapus file "${item.name}" dari server?`)) return;

    deletingName.value = item.name;
    error.value = '';
    try {
        const url = `${props.deleteUrl.replace(/\/$/, '')}/${encodeURIComponent(item.name)}`;
        await axios.delete(url);
        items.value = items.value.filter((i) => i.name !== item.name);
    } catch (err) {
        error.value =
            err?.response?.data?.errors?.filename?.[0] ||
            err?.response?.data?.message ||
            err?.message ||
            'Gagal menghapus file.';
    } finally {
        deletingName.value = '';
    }
}

function formatSize(bytes) {
    if (!Number.isFinite(bytes) || bytes < 0) return '';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function onBackdrop(event) {
    if (event.target === event.currentTarget) {
        emit('close');
    }
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="aksara-overlay z-[80]"
            @click="onBackdrop"
        >
            <div
                class="aksara-dialog flex max-h-[min(90vh,640px)] max-w-2xl flex-col"
                role="dialog"
                aria-modal="true"
                aria-label="Pilih media konteks"
            >
                <div class="flex items-center justify-between border-b border-aksara-line px-4 py-3">
                    <div>
                        <h2 class="text-base font-semibold text-aksara-ink">Media konteks</h2>
                        <p class="text-xs text-aksara-muted">Hanya file di folder materi ini.</p>
                    </div>
                    <button type="button" class="aksara-btn-secondary !px-2 !py-1 text-xs" @click="emit('close')">
                        Tutup
                    </button>
                </div>

                <div class="flex flex-wrap items-center gap-2 border-b border-aksara-line bg-aksara-mist/40 px-4 py-2">
                    <button
                        type="button"
                        class="aksara-btn-primary !px-3 !py-1.5 text-xs"
                        :disabled="uploading"
                        @click="pickUpload"
                    >
                        {{ uploading ? 'Mengunggah…' : 'Unggah baru' }}
                    </button>
                    <button
                        type="button"
                        class="aksara-btn-secondary !px-3 !py-1.5 text-xs"
                        :disabled="loading || uploading"
                        @click="loadItems"
                    >
                        Muat ulang
                    </button>
                </div>

                <p v-if="error" class="border-b border-aksara-danger/20 bg-aksara-danger/5 px-4 py-2 text-xs text-aksara-danger">
                    {{ error }}
                </p>

                <div class="min-h-0 flex-1 overflow-y-auto p-4">
                    <p v-if="loading" class="text-sm text-aksara-muted">Memuat…</p>
                    <p v-else-if="items.length === 0" class="text-sm text-aksara-muted">
                        Belum ada gambar di konteks ini. Unggah untuk memulai.
                    </p>
                    <ul v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <li
                            v-for="item in items"
                            :key="item.name"
                            class="group relative overflow-hidden rounded-xl border border-aksara-line bg-aksara-mist/20"
                        >
                            <button
                                type="button"
                                class="block w-full text-left"
                                @click="selectItem(item)"
                            >
                                <img
                                    :src="item.url"
                                    :alt="item.name"
                                    class="aspect-square w-full object-cover"
                                    loading="lazy"
                                />
                                <span class="block truncate px-2 py-1.5 text-[11px] text-aksara-muted">
                                    {{ item.name }}
                                    <template v-if="item.size"> · {{ formatSize(item.size) }}</template>
                                </span>
                            </button>
                            <button
                                v-if="deleteUrl"
                                type="button"
                                class="absolute right-1.5 top-1.5 rounded-md bg-white/90 px-1.5 py-0.5 text-[10px] font-semibold text-aksara-danger shadow-sm opacity-0 transition group-hover:opacity-100"
                                :disabled="deletingName === item.name"
                                @click.stop="removeItem(item)"
                            >
                                {{ deletingName === item.name ? '…' : 'Hapus' }}
                            </button>
                        </li>
                    </ul>
                </div>

                <input
                    ref="fileInput"
                    type="file"
                    accept="image/*"
                    class="hidden"
                    @change="onFileChange"
                />
            </div>
        </div>
    </Teleport>
</template>
