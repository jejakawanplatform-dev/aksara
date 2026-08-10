<script setup>
import { onBeforeUnmount, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    maxWidth: { type: String, default: 'lg' }, // sm | md | lg | xl
    closeOnBackdrop: { type: Boolean, default: true },
});

const emit = defineEmits(['close']);

const maxWidthClass = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
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
            class="fixed inset-0 z-[70] flex items-center justify-center bg-aksara-ink/40 p-4"
            role="presentation"
            @click.self="onBackdrop"
        >
            <div
                class="aksara-modal w-full overflow-hidden rounded-2xl border border-aksara-line bg-white shadow-xl"
                :class="maxWidthClass[maxWidth] || maxWidthClass.lg"
                role="dialog"
                aria-modal="true"
                :aria-label="title || 'Dialog'"
            >
                <div
                    v-if="title || $slots.header || $slots.close"
                    class="flex items-start justify-between gap-3 border-b border-aksara-line px-5 py-4"
                >
                    <div class="min-w-0">
                        <slot name="header">
                            <h3 v-if="title" class="font-display text-lg font-semibold text-aksara-ink">
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

                <div class="max-h-[min(70vh,36rem)] overflow-y-auto px-5 py-4">
                    <slot />
                </div>

                <div
                    v-if="$slots.footer"
                    class="flex flex-wrap justify-end gap-2 border-t border-aksara-line bg-aksara-mist/20 px-5 py-3"
                >
                    <slot name="footer" />
                </div>
            </div>
        </div>
    </Teleport>
</template>
