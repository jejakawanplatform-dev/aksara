<script setup>
import { computed } from 'vue';

const props = defineProps({
    tone: { type: String, default: 'info' },
    title: { type: String, default: null },
});

/** Prefer aksara semantic tokens (spec 17) — avoid ad-hoc purple/sky palettes */
const tones = {
    info: 'border-aksara-info/25 bg-aksara-info/5 text-aksara-ink',
    success: 'border-aksara-ok/25 bg-aksara-ok/5 text-aksara-ink',
    ok: 'border-aksara-ok/25 bg-aksara-ok/5 text-aksara-ink',
    warning: 'border-aksara-warn/30 bg-aksara-warn/5 text-aksara-ink',
    warn: 'border-aksara-warn/30 bg-aksara-warn/5 text-aksara-ink',
    danger: 'border-aksara-danger/25 bg-aksara-danger/5 text-aksara-ink',
    critical: 'border-aksara-danger/25 bg-aksara-danger/5 text-aksara-ink',
    ai: 'border-aksara-line bg-aksara-mist text-aksara-ink',
};

const toneClass = computed(() => tones[props.tone] || tones.info);
</script>

<template>
    <div class="rounded-xl border px-4 py-3 text-sm" :class="toneClass">
        <p v-if="title" class="font-semibold text-aksara-ink">{{ title }}</p>
        <div :class="{ 'mt-1': !!title }" class="text-aksara-ink/90">
            <slot />
        </div>
    </div>
</template>
