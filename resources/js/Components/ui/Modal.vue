<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
import { onBeforeUnmount, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    maxWidth: { type: String, default: 'lg' }, // sm | md | lg | xl | 2xl
    closeOnBackdrop: { type: Boolean, default: true },
});

const emit = defineEmits(['close']);

const maxWidthClass = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
};

function onBackdrop() {
    if (props.closeOnBackdrop) {
        emit('close');
    }
}

function onKeydown(event) {
    if (event.key === 'Escape' && props.open) {
        emit('close');
    }
}

function clearBodyLock() {
    if (typeof document === 'undefined') return;
    document.body.classList.remove('overflow-hidden');
    window.removeEventListener('keydown', onKeydown);
}

watch(
    () => props.open,
    (isOpen) => {
        if (typeof document === 'undefined') return;
        document.body.classList.toggle('overflow-hidden', isOpen);
        if (isOpen) {
            window.addEventListener('keydown', onKeydown);
        } else {
            window.removeEventListener('keydown', onKeydown);
        }
    },
);

onBeforeUnmount(clearBodyLock);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="aksara-overlay z-[70]"
            role="presentation"
            @click.self="onBackdrop"
        >
            <div
                class="aksara-dialog aksara-modal"
                :class="maxWidthClass[maxWidth] || maxWidthClass.lg"
                role="dialog"
                aria-modal="true"
                :aria-label="title || 'Dialog'"
            >
                <div
                    v-if="title || $slots.header || $slots.close"
                    class="aksara-dialog__header"
                >
                    <div class="min-w-0">
                        <slot name="header">
                            <h3 v-if="title" class="text-base font-semibold text-aksara-ink">
                                {{ title }}
                            </h3>
                            <p v-if="description" class="mt-0.5 text-xs text-aksara-muted">
                                {{ description }}
                            </p>
                        </slot>
                    </div>
                    <button
                        type="button"
                        class="shrink-0 rounded-lg px-2 py-1 text-sm text-aksara-muted transition hover:bg-aksara-mist hover:text-aksara-ink"
                        aria-label="Tutup"
                        @click="emit('close')"
                    >
                        ✕
                    </button>
                </div>

                <div class="aksara-dialog__body">
                    <slot />
                </div>

                <div v-if="$slots.footer" class="aksara-dialog__footer">
                    <slot name="footer" />
                </div>
            </div>
        </div>
    </Teleport>
</template>
