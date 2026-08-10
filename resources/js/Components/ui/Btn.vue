<script setup>
defineProps({
    type: { type: String, default: 'button' },
    variant: { type: String, default: 'primary' },
    disabled: { type: Boolean, default: false },
    href: { type: String, default: null },
});
</script>

<template>
    <a
        v-if="href"
        :href="href"
        :class="[
            variant === 'secondary' ? 'aksara-btn-secondary' : 'aksara-btn-primary',
            $attrs.class,
        ]"
    >
        <slot />
    </a>
    <button
        v-else
        :type="type"
        :disabled="disabled"
        :class="[
            variant === 'secondary' ? 'aksara-btn-secondary' : 'aksara-btn-primary',
            $attrs.class,
        ]"
    >
        <slot />
    </button>
</template>
