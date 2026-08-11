<script setup>
import { computed, nextTick, onBeforeUnmount, ref } from 'vue';
import Icon from '@/Components/ui/Icon.vue';

const props = defineProps({
    icon: { type: String, required: true },
    label: { type: String, required: true },
    href: { type: String, default: null },
    type: { type: String, default: 'button' },
    target: { type: String, default: null },
    danger: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
});

defineEmits(['click']);

const triggerRef = ref(null);
const visible = ref(false);
const coords = ref({ top: 0, left: 0, placement: 'top' });

const tooltipStyle = computed(() => ({
    top: `${coords.value.top}px`,
    left: `${coords.value.left}px`,
    transform:
        coords.value.placement === 'top'
            ? 'translate(-50%, -100%)'
            : 'translate(-50%, 0)',
}));

function updatePosition() {
    const el = triggerRef.value;
    if (!el) return;
    const rect = el.getBoundingClientRect();
    const gap = 8;
    const preferTop = rect.top > 40;
    coords.value = {
        left: rect.left + rect.width / 2,
        top: preferTop ? rect.top - gap : rect.bottom + gap,
        placement: preferTop ? 'top' : 'bottom',
    };
}

function show() {
    if (props.disabled) return;
    visible.value = true;
    nextTick(updatePosition);
}

function hide() {
    visible.value = false;
}

/** Tooltip hanya saat keyboard focus-visible — hindari autofocus/klik mouse menumpuk tip */
function onFocus(event) {
    if (props.disabled) return;
    if (event?.target?.matches?.(':focus-visible')) {
        show();
    }
}

function onScrollOrResize() {
    if (visible.value) updatePosition();
}

if (typeof window !== 'undefined') {
    window.addEventListener('scroll', onScrollOrResize, true);
    window.addEventListener('resize', onScrollOrResize);
}

onBeforeUnmount(() => {
    if (typeof window === 'undefined') return;
    window.removeEventListener('scroll', onScrollOrResize, true);
    window.removeEventListener('resize', onScrollOrResize);
});
</script>

<template>
    <component
        :is="href ? 'a' : 'button'"
        ref="triggerRef"
        :href="href || undefined"
        :type="href ? undefined : type"
        :target="target || undefined"
        :disabled="href ? undefined : disabled"
        :aria-label="label"
        class="aksara-icon-btn"
        :class="{ 'aksara-icon-btn--danger': danger, 'pointer-events-none opacity-40': disabled }"
        @mouseenter="show"
        @mouseleave="hide"
        @focus="onFocus"
        @blur="hide"
        @click="$emit('click', $event)"
    >
        <Icon :name="icon" class="h-4 w-4" />
    </component>

    <Teleport to="body">
        <div
            v-show="visible"
            role="tooltip"
            class="aksara-tooltip-portal"
            :style="tooltipStyle"
        >
            {{ label }}
        </div>
    </Teleport>
</template>
