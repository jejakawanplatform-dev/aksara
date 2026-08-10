<script setup>
defineProps({
    label: { type: String, default: null },
    required: { type: Boolean, default: false },
    error: { type: String, default: null },
    hint: { type: String, default: null },
    forId: { type: String, default: null },
});
</script>

<template>
    <div class="space-y-1.5">
        <label v-if="label" :for="forId" class="aksara-label">
            {{ label }}
            <span v-if="required" class="font-bold text-red-500">*</span>
        </label>
        <slot />
        <p v-if="hint && !error" class="text-xs text-aksara-muted">{{ hint }}</p>
        <p v-if="error" class="text-xs font-medium text-red-600">{{ error }}</p>
    </div>
</template>
