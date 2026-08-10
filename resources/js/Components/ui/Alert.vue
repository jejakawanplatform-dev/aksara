<script setup>
import { computed } from 'vue';

const props = defineProps({
    tone: { type: String, default: 'info' },
    title: { type: String, default: null },
});

const tones = {
    info: 'border-sky-200 bg-sky-50 text-sky-900',
    success: 'border-emerald-200 bg-emerald-50 text-emerald-900',
    ok: 'border-emerald-200 bg-emerald-50 text-emerald-900',
    warning: 'border-amber-200 bg-amber-50 text-amber-950',
    warn: 'border-amber-200 bg-amber-50 text-amber-950',
    danger: 'border-red-200 bg-red-50 text-red-900',
    critical: 'border-red-200 bg-red-50 text-red-900',
    ai: 'border-aksara-line bg-aksara-mist text-aksara-ink',
};

const toneClass = computed(() => tones[props.tone] || tones.info);
</script>

<template>
    <div class="rounded-xl border px-4 py-3 text-sm" :class="toneClass">
        <p v-if="title" class="font-semibold">{{ title }}</p>
        <div :class="{ 'mt-1': !!title }">
            <slot />
        </div>
    </div>
</template>
