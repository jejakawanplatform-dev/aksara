<script setup>
import { reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';

const props = defineProps({
    classes: { type: Array, default: () => [] },
    plans: { type: Array, default: () => [] },
    summaryData: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    indexUrl: { type: String, required: true },
});

const local = reactive({
    classId: props.filters.classId ? String(props.filters.classId) : '',
    planId: props.filters.planId ? String(props.filters.planId) : '',
});

watch(
    () => local.classId,
    (value) => {
        local.planId = '';
        router.get(
            props.indexUrl,
            { classId: value || undefined },
            { preserveState: true, replace: true },
        );
    },
);

watch(
    () => local.planId,
    (value) => {
        if (!local.classId) {
            return;
        }
        router.get(
            props.indexUrl,
            {
                classId: local.classId,
                planId: value || undefined,
            },
            { preserveState: true, replace: true },
        );
    },
);

function pctClass(pct) {
    if (pct >= 80) return 'text-green-600';
    if (pct >= 60) return 'text-yellow-600';
    return 'text-red-600';
}
</script>

<template>
    <AppLayout title="Rekap Kehadiran Siswa">
        <template #header>Rekap Kehadiran Siswa</template>

        <Card>
            <div class="mb-4 flex flex-wrap gap-3">
                <select id="filter-class" v-model="local.classId" class="aksara-select">
                    <option value="">-- Pilih Kelas --</option>
                    <option v-for="c in classes" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                </select>
                <select
                    id="filter-plan"
                    v-model="local.planId"
                    class="aksara-select"
                    :disabled="!local.classId"
                >
                    <option value="">Semua rencana</option>
                    <option v-for="p in plans" :key="p.id" :value="String(p.id)">{{ p.topic }}</option>
                </select>
            </div>

            <p v-if="!summaryData.length" class="py-8 text-center text-sm text-aksara-muted">
                Pilih kelas untuk melihat rekap kehadiran.
            </p>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-aksara-line text-left text-xs uppercase tracking-wide text-aksara-muted">
                            <th class="pb-3 pr-4">Nama Siswa</th>
                            <th class="pb-3 pr-3 text-center text-green-600">Hadir</th>
                            <th class="pb-3 pr-3 text-center text-blue-600">Izin</th>
                            <th class="pb-3 pr-3 text-center text-yellow-600">Sakit</th>
                            <th class="pb-3 pr-3 text-center text-red-600">Alpha</th>
                            <th class="pb-3 text-center">% Hadir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-aksara-line/60">
                        <tr
                            v-for="row in summaryData"
                            :key="row.studentId"
                            class="hover:bg-aksara-mist/30"
                        >
                            <td class="py-3 pr-4 font-medium text-aksara-ink">{{ row.studentName }}</td>
                            <td class="py-3 pr-3 text-center">{{ row.hadir }}</td>
                            <td class="py-3 pr-3 text-center">{{ row.izin }}</td>
                            <td class="py-3 pr-3 text-center">{{ row.sakit }}</td>
                            <td class="py-3 pr-3 text-center">{{ row.alpha }}</td>
                            <td class="py-3 text-center font-bold" :class="pctClass(row.pct)">
                                {{ row.pct }}%
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </AppLayout>
</template>
