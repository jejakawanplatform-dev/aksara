<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, default: 'draft' },
    label: { type: String, default: null },
});

const map = {
    draft: { text: 'Draf', class: 'bg-aksara-warn/10 text-aksara-warn ring-aksara-warn/25' },
    reviewed: { text: 'Direview', class: 'bg-aksara-info/10 text-aksara-info ring-aksara-info/25' },
    published: { text: 'Diterbitkan', class: 'bg-aksara-ok/10 text-aksara-ok ring-aksara-ok/25' },
    pending: { text: 'Menunggu', class: 'bg-aksara-warn/10 text-aksara-warn ring-aksara-warn/25' },
    approved: { text: 'Disetujui', class: 'bg-aksara-ok/10 text-aksara-ok ring-aksara-ok/25' },
    archived: { text: 'Diarsipkan', class: 'bg-aksara-mist text-aksara-muted ring-aksara-line' },
};

const meta = computed(() => {
    const key = String(props.status || '').toLowerCase();
    return map[key] || { text: key || 'Status', class: 'bg-aksara-mist text-aksara-muted ring-aksara-line' };
});
</script>

<template>
    <span
        class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold leading-none ring-1 ring-inset"
        :class="meta.class"
    >
        {{ label || meta.text }}
    </span>
</template>
