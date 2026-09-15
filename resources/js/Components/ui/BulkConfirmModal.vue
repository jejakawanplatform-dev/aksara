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
    title: { type: String, default: 'Konfirmasi Aksi Massal' },
    description: { type: String, default: '' },
    count: { type: Number, default: 0 },
    itemLabel: { type: String, default: 'item' },
    confirmWord: { type: String, default: '' }, // misal: "HAPUS"
    danger: { type: Boolean, default: true },
    processing: { type: Boolean, default: false },
    confirmButtonText: { type: String, default: 'Konfirmasi' },
});

const emit = defineEmits(['close', 'confirm']);

const typedConfirmation = ref('');

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            typedConfirmation.value = '';
        }
    },
);

const isConfirmed = computed(() => {
    if (!props.confirmWord) return true;
    return typedConfirmation.value.trim().toUpperCase() === props.confirmWord.trim().toUpperCase();
});

function handleConfirm() {
    if (!isConfirmed.value || props.processing) return;
    emit('confirm');
}
</script>

<template>
    <Modal :open="open" :title="title" max-width="md" @close="emit('close')">
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

            <div v-if="confirmWord" class="space-y-1.5 pt-1">
                <label class="block text-xs font-medium text-aksara-ink">
                    Ketik <span class="rounded bg-aksara-mist px-1.5 py-0.5 font-mono font-bold text-aksara-danger">{{ confirmWord }}</span> untuk konfirmasi:
                </label>
                <input
                    v-model="typedConfirmation"
                    type="text"
                    class="aksara-input text-sm"
                    :placeholder="`Ketik '${confirmWord}'`"
                    :disabled="processing"
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
                    :disabled="processing"
                    @click="emit('close')"
                >
                    Batal
                </Btn>
                <Btn
                    type="button"
                    :variant="danger ? 'danger' : 'primary'"
                    size="sm"
                    :disabled="!isConfirmed || processing"
                    @click="handleConfirm"
                >
                    <Icon v-if="processing" name="spinner" class="h-3.5 w-3.5 animate-spin" />
                    {{ confirmButtonText }}
                </Btn>
            </div>
        </template>
    </Modal>
</template>
