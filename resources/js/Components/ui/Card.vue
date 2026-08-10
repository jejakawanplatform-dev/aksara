<script setup>
defineProps({
    title: { type: String, default: null },
    description: { type: String, default: null },
});
</script>

<template>
    <div class="aksara-card">
        <div v-if="title || $slots.actions" class="aksara-card-header">
            <div>
                <h3 v-if="title" class="font-display text-lg font-semibold text-aksara-ink">{{ title }}</h3>
                <p v-if="description" class="mt-0.5 text-xs text-aksara-muted">{{ description }}</p>
            </div>
            <div v-if="$slots.actions" class="flex items-center gap-2">
                <slot name="actions" />
            </div>
        </div>
        <div class="aksara-card-body">
            <slot />
        </div>
    </div>
</template>
