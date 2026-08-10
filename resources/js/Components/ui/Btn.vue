<script setup>
import { computed } from 'vue';

const props = defineProps({
    type: { type: String, default: 'button' },
    variant: { type: String, default: 'primary' }, // primary | secondary | danger
    size: { type: String, default: 'md' }, // sm | md
    disabled: { type: Boolean, default: false },
    href: { type: String, default: null },
});

const variantClass = computed(() => {
    if (props.variant === 'secondary') return 'aksara-btn-secondary';
    if (props.variant === 'danger') return 'aksara-btn-danger';
    return 'aksara-btn-primary';
});

const sizeClass = computed(() => {
    if (props.size === 'sm') return '!px-3 !py-1.5 text-xs';
    return '';
});
</script>

<template>
    <a
        v-if="href"
        :href="href"
        :class="[variantClass, sizeClass, $attrs.class]"
    >
        <slot />
    </a>
    <button
        v-else
        :type="type"
        :disabled="disabled"
        :class="[variantClass, sizeClass, $attrs.class]"
    >
        <slot />
    </button>
</template>
