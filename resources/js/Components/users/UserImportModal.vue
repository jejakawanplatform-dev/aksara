<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/ui/Modal.vue';
import Btn from '@/Components/ui/Btn.vue';
import Icon from '@/Components/ui/Icon.vue';
import Field from '@/Components/ui/Field.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    importUrl: { type: String, required: true },
    templateUrl: { type: String, required: true },
    serverErrors: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'success']);

const fileInput = ref(null);
const isDragging = ref(false);

const form = useForm({
    file: null,
    duplicate_mode: 'skip', // 'skip' | 'update'
    password_mode: 'default', // 'default' | 'generate'
    default_password: 'Aksara2026!',
});

function onFileChange(event) {
    const files = event.target.files;
    if (files && files.length > 0) {
        form.file = files[0];
    }
}

function onDrop(event) {
    isDragging.value = false;
    const files = event.dataTransfer?.files;
    if (files && files.length > 0) {
        const file = files[0];
        const ext = file.name.split('.').pop()?.toLowerCase();
        if (ext === 'xlsx' || ext === 'xls') {
            form.file = file;
        } else {
            alert('Silakan pilih berkas spreadsheet Excel (.xlsx atau .xls).');
        }
    }
}

function removeFile() {
    form.file = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function formatFileSize(bytes) {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function submit() {
    if (!form.file) {
        return;
    }

    form.post(props.importUrl, {
        preserveScroll: true,
        onSuccess: () => {
            removeFile();
            emit('success');
            emit('close');
        },
    });
}

function handleClose() {
    removeFile();
    form.clearErrors();
    emit('close');
}
</script>

<template>
    <Modal
        :open="open"
        title="Impor Data Pengguna dari Excel"
        description="Tambahkan pengguna baru atau perbarui data massal menggunakan berkas spreadsheet (.xlsx)."
        max-width="xl"
        @close="handleClose"
    >
        <div class="space-y-5">
            <!-- Alert error dari server jika ada baris tidak valid -->
            <div
                v-if="serverErrors && serverErrors.length > 0"
                class="rounded-xl border border-aksara-danger/20 bg-aksara-danger/5 p-4 text-sm text-aksara-danger"
            >
                <div class="flex items-center gap-2 font-semibold">
                    <Icon name="information-circle" class="h-4 w-4 shrink-0" />
                    <span>Terdapat kendala pada {{ serverErrors.length }} baris data:</span>
                </div>
                <ul class="mt-2 max-h-40 space-y-1 overflow-y-auto pl-5 text-xs list-disc">
                    <li v-for="(err, idx) in serverErrors" :key="idx">{{ err }}</li>
                </ul>
            </div>

            <!-- Petunjuk & Unduh Template -->
            <div class="flex flex-col gap-3 rounded-xl border border-aksara-line bg-aksara-mist/50 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-0.5">
                    <h4 class="text-sm font-semibold text-aksara-ink">Belum memiliki template impor?</h4>
                    <p class="text-xs text-aksara-muted">
                        Unduh template resmi berformat .xlsx yang telah dilengkapi dropdown role dan referensi rombel.
                    </p>
                </div>
                <a
                    :href="templateUrl"
                    class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-lg border border-aksara-line bg-aksara-surface px-3 py-2 text-xs font-semibold text-aksara-ink shadow-xs transition hover:bg-aksara-mist"
                    download
                >
                    <Icon name="download" class="h-3.5 w-3.5 text-aksara-primary" />
                    Unduh Template (.xlsx)
                </a>
            </div>

            <!-- File Upload Drag & Drop Area -->
            <div>
                <label class="mb-1 block text-xs font-medium text-aksara-ink">
                    Berkas Excel (.xlsx / .xls) <span class="text-aksara-danger">*</span>
                </label>
                <div
                    v-if="!form.file"
                    class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed p-6 text-center transition cursor-pointer"
                    :class="[
                        isDragging
                            ? 'border-aksara-primary bg-aksara-primary/5'
                            : 'border-aksara-line hover:border-aksara-primary/50 hover:bg-aksara-mist/30',
                    ]"
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="onDrop"
                    @click="$refs.fileInput?.click()"
                >
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-aksara-primary/10 text-aksara-primary">
                        <Icon name="upload" class="h-5 w-5" />
                    </div>
                    <p class="mt-2 text-sm font-medium text-aksara-ink">
                        Pilih berkas atau seret ke sini
                    </p>
                    <p class="text-xs text-aksara-muted">Mendukung format .xlsx dan .xls (maksimal 5 MB)</p>
                    <input
                        ref="fileInput"
                        type="file"
                        accept=".xlsx, .xls"
                        class="hidden"
                        @change="onFileChange"
                    />
                </div>

                <!-- Preview File Terpilih -->
                <div
                    v-else
                    class="flex items-center justify-between rounded-xl border border-aksara-primary/30 bg-aksara-primary/5 p-3.5"
                >
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-aksara-primary/10 text-aksara-primary">
                            <Icon name="document" class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-xs font-medium text-aksara-ink">{{ form.file.name }}</p>
                            <p class="text-[11px] text-aksara-muted">{{ formatFileSize(form.file.size) }}</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="rounded-md p-1 text-aksara-muted transition hover:bg-aksara-danger/10 hover:text-aksara-danger"
                        title="Hapus berkas"
                        @click.stop="removeFile"
                    >
                        <Icon name="x-mark" class="h-4 w-4" />
                    </button>
                </div>
                <p v-if="form.errors.file" class="mt-1 text-xs text-aksara-danger">{{ form.errors.file }}</p>
            </div>

            <!-- Opsi Pengaturan Impor -->
            <div class="grid grid-cols-1 gap-4 rounded-xl border border-aksara-line bg-aksara-surface p-4 sm:grid-cols-2">
                <!-- Penanganan Data Duplikat -->
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-aksara-ink">
                        Jika Email Sudah Terdaftar:
                    </label>
                    <div class="space-y-1.5">
                        <label class="flex cursor-pointer items-start gap-2.5 rounded-lg p-2 transition hover:bg-aksara-mist/50">
                            <input
                                v-model="form.duplicate_mode"
                                type="radio"
                                value="skip"
                                class="mt-0.5 text-aksara-primary focus:ring-aksara-primary/30"
                            />
                            <div class="text-xs">
                                <span class="font-medium text-aksara-ink">Lewati (Skip)</span>
                                <p class="text-aksara-muted">Data lama tetap utuh, baris dilewati.</p>
                            </div>
                        </label>
                        <label class="flex cursor-pointer items-start gap-2.5 rounded-lg p-2 transition hover:bg-aksara-mist/50">
                            <input
                                v-model="form.duplicate_mode"
                                type="radio"
                                value="update"
                                class="mt-0.5 text-aksara-primary focus:ring-aksara-primary/30"
                            />
                            <div class="text-xs">
                                <span class="font-medium text-aksara-ink">Perbarui (Update)</span>
                                <p class="text-aksara-muted">Nama, role, dan rombel diperbarui dari Excel.</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Strategi Kata Sandi Akun Baru -->
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-aksara-ink">
                        Kata Sandi Akun Baru:
                    </label>
                    <div class="space-y-1.5">
                        <label class="flex cursor-pointer items-start gap-2.5 rounded-lg p-2 transition hover:bg-aksara-mist/50">
                            <input
                                v-model="form.password_mode"
                                type="radio"
                                value="default"
                                class="mt-0.5 text-aksara-primary focus:ring-aksara-primary/30"
                            />
                            <div class="text-xs">
                                <span class="font-medium text-aksara-ink">Password Seragam</span>
                                <p class="text-aksara-muted">Gunakan satu kata sandi default.</p>
                            </div>
                        </label>
                        <label class="flex cursor-pointer items-start gap-2.5 rounded-lg p-2 transition hover:bg-aksara-mist/50">
                            <input
                                v-model="form.password_mode"
                                type="radio"
                                value="generate"
                                class="mt-0.5 text-aksara-primary focus:ring-aksara-primary/30"
                            />
                            <div class="text-xs">
                                <span class="font-medium text-aksara-ink">Generate Acak</span>
                                <p class="text-aksara-muted">Kredensial dapat diunduh setelah impor.</p>
                            </div>
                        </label>
                    </div>

                    <!-- Input Password Default jika opsi default dipilih -->
                    <div v-if="form.password_mode === 'default'" class="pt-1">
                        <Field label="Password Default" for-id="import-default-pass" :error="form.errors.default_password">
                            <input
                                id="import-default-pass"
                                v-model="form.default_password"
                                type="text"
                                class="aksara-input text-xs"
                                placeholder="Minimal 8 karakter"
                            />
                        </Field>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <Btn type="button" variant="secondary" size="sm" @click="handleClose">
                Batal
            </Btn>
            <Btn
                type="button"
                size="sm"
                class="gap-1.5"
                :disabled="!form.file || form.processing"
                @click="submit"
            >
                <Icon v-if="!form.processing" name="upload" class="h-3.5 w-3.5" />
                <span>{{ form.processing ? 'Memproses Impor...' : 'Mulai Impor' }}</span>
            </Btn>
        </template>
    </Modal>
</template>
