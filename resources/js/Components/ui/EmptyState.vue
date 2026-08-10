<script setup>
defineProps({
    title: { type: String, default: 'Belum ada data' },
    actionHref: { type: String, default: null },
    actionLabel: { type: String, default: null },
});
</script>

<template>
    <div class="rounded-2xl border border-dashed border-aksara-line bg-white px-6 py-12 text-center">
        <p class="font-display text-lg text-aksara-ink">{{ title }}</p>
        <div class="mt-2 text-sm text-aksara-muted">
            <slot />
        </div>
        <a
            v-if="actionHref && actionLabel"
            :href="actionHref"
            class="aksara-btn-primary mt-5 inline-flex"
        >
            {{ actionLabel }}
        </a>
    </div>
</template>
