<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, default: 'draft' },
    label: { type: String, default: null },
});

const map = {
    draft: { text: 'Draf', class: 'bg-amber-50 text-aksara-warn ring-amber-200' },
    reviewed: { text: 'Direview', class: 'bg-sky-50 text-aksara-info ring-sky-200' },
    published: { text: 'Diterbitkan', class: 'bg-emerald-50 text-aksara-ok ring-emerald-200' },
    pending: { text: 'Menunggu', class: 'bg-amber-50 text-aksara-warn ring-amber-200' },
    approved: { text: 'Disetujui', class: 'bg-emerald-50 text-aksara-ok ring-emerald-200' },
    archived: { text: 'Diarsipkan', class: 'bg-slate-100 text-slate-600 ring-slate-200' },
};

const meta = computed(() => {
    const key = String(props.status || '').toLowerCase();
    return map[key] || { text: key || 'Status', class: 'bg-slate-100 text-slate-700 ring-slate-200' };
});
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset"
        :class="meta.class"
    >
        {{ label || meta.text }}
    </span>
</template>
