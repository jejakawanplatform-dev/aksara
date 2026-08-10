<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';

defineProps({
    materials: { type: Array, default: () => [] },
    isStudent: { type: Boolean, default: false },
});
</script>

<template>
    <AppLayout title="Materi Pembelajaran">
        <template #header>2. Materi Pembelajaran (Bahan Ajar)</template>

        <div class="space-y-4">
            <PageHeader
                title="Materi Pembelajaran"
                description="Bahan ajar yang terhubung ke rencana pembelajaran."
            />

            <a
                v-for="material in materials"
                :key="material.id"
                :href="material.showUrl"
                class="aksara-panel flex items-center justify-between p-5 transition hover:border-aksara-teal/40 hover:shadow-aksara"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-semibold text-aksara-ink">{{ material.title }}</h3>
                        <StatusBadge v-if="!isStudent" :status="material.status" :label="material.statusLabel" />
                    </div>
                    <p class="mt-0.5 text-sm text-aksara-muted">
                        {{ material.subject }} · Kelas {{ material.className }} · {{ material.durationMinutes }} menit
                    </p>
                </div>
                <span class="flex items-center gap-1 text-sm font-medium text-aksara-teal">
                    {{ isStudent ? 'Baca Materi →' : 'Pratinjau Materi →' }}
                </span>
            </a>

            <EmptyState v-if="!materials.length" title="Belum Ada Bahan Ajar">
                {{
                    isStudent
                        ? 'Materi pembelajaran hanya muncul setelah diterbitkan oleh guru pengampu kelas Anda.'
                        : 'Belum ada bahan ajar yang dibuat. Buat draf Rencana Pembelajaran terlebih dahulu di menu Rencana Pembelajaran.'
                }}
            </EmptyState>
        </div>
    </AppLayout>
</template>
