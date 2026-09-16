<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
import { computed, ref, watch } from 'vue';
import Modal from '@/Components/ui/Modal.vue';
import Btn from '@/Components/ui/Btn.vue';
import Icon from '@/Components/ui/Icon.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    modelValue: { type: Boolean, default: null },
    title: { type: String, default: 'Konfirmasi Aksi Massal' },
    description: { type: String, default: '' },
    count: { type: Number, default: 0 },
    itemLabel: { type: String, default: 'item' },
    confirmWord: { type: String, default: '' }, // misal: "HAPUS"
    dangerWord: { type: String, default: '' },  // alias untuk confirmWord
    danger: { type: Boolean, default: true },
    processing: { type: Boolean, default: false },
    loading: { type: Boolean, default: false }, // alias untuk processing
    confirmButtonText: { type: String, default: 'Konfirmasi' },
});

const emit = defineEmits(['close', 'confirm', 'update:modelValue']);

const isOpen = computed(() => (props.modelValue !== null ? props.modelValue : props.open));
const effectiveWord = computed(() => props.confirmWord || props.dangerWord || '');
const isProcessing = computed(() => props.processing || props.loading);

const typedConfirmation = ref('');

watch(
    isOpen,
    (val) => {
        if (val) {
            typedConfirmation.value = '';
        }
    },
);

const isConfirmed = computed(() => {
    if (!effectiveWord.value) return true;
    return typedConfirmation.value.trim().toUpperCase() === effectiveWord.value.trim().toUpperCase();
});

function handleClose() {
    emit('close');
    emit('update:modelValue', false);
}

function handleConfirm() {
    if (!isConfirmed.value || isProcessing.value) return;
    emit('confirm');
}
</script>

<template>
    <Modal :open="isOpen" :title="title" max-width="md" @close="handleClose">
        <div class="space-y-4 text-sm text-aksara-ink">
            <div
                v-if="danger"
                class="flex items-start gap-3 rounded-lg border border-aksara-danger/20 bg-aksara-danger/5 p-3 text-aksara-danger"
            >
                <Icon name="warning" class="mt-0.5 h-5 w-5 shrink-0" />
                <div class="text-xs leading-relaxed">
                    <p class="font-semibold">Tindakan ini permanen!</p>
                    <p class="mt-0.5">
                        Sebanyak <strong>{{ count }} {{ itemLabel }}</strong> akan diproses. Pastikan data yang Anda pilih sudah sesuai.
                    </p>
                </div>
            </div>

            <p v-if="description" class="text-xs text-aksara-muted">
                {{ description }}
            </p>

            <div v-if="effectiveWord" class="space-y-1.5 pt-1">
                <label class="block text-xs font-medium text-aksara-ink">
                    Ketik <span class="rounded bg-aksara-mist px-1.5 py-0.5 font-mono font-bold text-aksara-danger">{{ effectiveWord }}</span> untuk konfirmasi:
                </label>
                <input
                    v-model="typedConfirmation"
                    type="text"
                    class="aksara-input text-sm"
                    :placeholder="`Ketik '${effectiveWord}'`"
                    :disabled="isProcessing"
                    @keydown.enter.prevent="handleConfirm"
                />
            </div>
        </div>

        <template #footer>
            <div class="flex items-center justify-end gap-2">
                <Btn
                    type="button"
                    variant="secondary"
                    size="sm"
                    :disabled="isProcessing"
                    @click="handleClose"
                >
                    Batal
                </Btn>
                <Btn
                    type="button"
                    :variant="danger ? 'danger' : 'primary'"
                    size="sm"
                    :disabled="!isConfirmed || isProcessing"
                    @click="handleConfirm"
                >
                    <Icon v-if="isProcessing" name="spinner" class="h-3.5 w-3.5 animate-spin" />
                    {{ confirmButtonText }}
                </Btn>
            </div>
        </template>
    </Modal>
</template>
