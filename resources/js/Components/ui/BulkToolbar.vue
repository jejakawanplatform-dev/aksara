<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
import { computed } from 'vue';
import Icon from '@/Components/ui/Icon.vue';

const props = defineProps({
    selectedCount: { type: Number, required: true },
    totalCount: { type: Number, default: 0 },
    isAllMatching: { type: Boolean, default: false },
    currentPageCount: { type: Number, default: 0 },
    itemLabel: { type: String, default: 'item' },
});

const emit = defineEmits(['clear', 'select-all-matching', 'clear-matching']);

const canSelectAllMatching = computed(() => {
    return props.totalCount > props.currentPageCount && props.selectedCount >= props.currentPageCount;
});
</script>

<template>
    <div
        class="flex flex-col gap-3 rounded-xl border border-aksara-primary/40 bg-aksara-primary/5 p-3.5 shadow-sm transition-all sm:flex-row sm:items-center sm:justify-between"
    >
        <div class="flex flex-wrap items-center gap-2.5 text-sm text-aksara-ink">
            <span class="inline-flex items-center gap-1.5 rounded-md bg-aksara-primary/15 px-2.5 py-1 font-semibold text-aksara-primary">
                <Icon name="check" class="h-3.5 w-3.5" />
                {{ selectedCount }} {{ itemLabel }} dipilih
            </span>

            <span v-if="canSelectAllMatching" class="text-xs text-aksara-muted">
                <template v-if="!isAllMatching">
                    Hanya data di halaman ini.
                    <button
                        type="button"
                        class="ml-1 font-medium text-aksara-primary underline hover:text-aksara-primary-hover"
                        @click="emit('select-all-matching')"
                    >
                        Pilih semua {{ totalCount }} {{ itemLabel }} di seluruh halaman
                    </button>
                </template>
                <template v-else>
                    Seluruh {{ totalCount }} {{ itemLabel }} di semua halaman terpilih.
                    <button
                        type="button"
                        class="ml-1 font-medium text-aksara-primary underline hover:text-aksara-primary-hover"
                        @click="emit('clear-matching')"
                    >
                        Hanya pilih halaman ini
                    </button>
                </template>
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <slot name="actions" />

            <button
                type="button"
                class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-aksara-muted transition hover:bg-aksara-mist hover:text-aksara-ink"
                @click="emit('clear')"
            >
                <Icon name="x" class="h-3.5 w-3.5" />
                Batal
            </button>
        </div>
    </div>
</template>
