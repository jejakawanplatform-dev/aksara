<script setup>
import { computed, reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Field from '@/Components/ui/Field.vue';
import Pagination from '@/Components/ui/Pagination.vue';

const props = defineProps({
    classes: { type: Array, default: () => [] },
    plans: { type: Array, default: () => [] },
    summaryData: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    indexUrl: { type: String, required: true },
});

const local = reactive({
    classId: props.filters.classId ? String(props.filters.classId) : '',
    planId: props.filters.planId ? String(props.filters.planId) : '',
});

const perPage = computed(
    () => Number(props.filters.per_page) || Number(props.summaryData.per_page) || 10,
);

const filterQuery = computed(() => ({
    classId: local.classId || undefined,
    planId: local.planId || undefined,
    per_page: perPage.value,
}));

const rows = computed(() => props.summaryData.data ?? []);

watch(
    () => local.classId,
    (value) => {
        local.planId = '';
        router.get(
            props.indexUrl,
            { classId: value || undefined, per_page: perPage.value },
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
                per_page: perPage.value,
            },
            { preserveState: true, replace: true },
        );
    },
);

function pctClass(pct) {
    if (pct >= 80) return 'text-aksara-ok';
    if (pct >= 60) return 'text-aksara-warn';
    return 'text-aksara-danger';
}
</script>

<template>
    <AppLayout title="Rekap Kehadiran Siswa">
        <template #header>Rekap Kehadiran Siswa</template>

        <div class="space-y-5">
            <PageHeader
                title="Rekap Kehadiran Siswa"
                description="Ringkasan kehadiran per siswa berdasarkan kelas dan rencana pembelajaran."
            />

            <div class="aksara-surface p-4 sm:p-5">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <Field label="Kelas" for-id="filter-class">
                        <select id="filter-class" v-model="local.classId" class="aksara-select">
                            <option value="">Pilih kelas…</option>
                            <option v-for="c in classes" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                        </select>
                    </Field>
                    <Field label="Rencana pembelajaran" for-id="filter-plan">
                        <select
                            id="filter-plan"
                            v-model="local.planId"
                            class="aksara-select"
                            :disabled="!local.classId"
                        >
                            <option value="">Semua rencana</option>
                            <option v-for="p in plans" :key="p.id" :value="String(p.id)">{{ p.topic }}</option>
                        </select>
                    </Field>
                </div>
            </div>

            <div v-if="!local.classId" class="aksara-surface-dashed p-10 text-center">
                <h3 class="text-lg font-semibold text-aksara-ink">Pilih kelas</h3>
                <p class="mt-2 text-sm text-aksara-muted">Pilih kelas untuk melihat rekap kehadiran.</p>
            </div>

            <div v-else-if="!rows.length" class="aksara-surface-dashed p-10 text-center">
                <h3 class="text-lg font-semibold text-aksara-ink">Belum ada data</h3>
                <p class="mt-2 text-sm text-aksara-muted">Tidak ada siswa atau data kehadiran untuk filter ini.</p>
            </div>

            <div v-else class="aksara-surface">
                <div class="overflow-x-auto">
                    <table class="aksara-table w-full min-w-[640px]">
                        <thead>
                            <tr>
                                <th class="aksara-th">Nama Siswa</th>
                                <th class="aksara-th text-center text-aksara-ok">Hadir</th>
                                <th class="aksara-th text-center text-aksara-info">Izin</th>
                                <th class="aksara-th text-center text-aksara-warn">Sakit</th>
                                <th class="aksara-th text-center text-aksara-danger">Alpha</th>
                                <th class="aksara-th text-center">% Hadir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in rows"
                                :key="row.studentId"
                                class="hover:bg-aksara-mist/40"
                            >
                                <td class="aksara-td font-medium text-aksara-ink">{{ row.studentName }}</td>
                                <td class="aksara-td text-center">{{ row.hadir }}</td>
                                <td class="aksara-td text-center">{{ row.izin }}</td>
                                <td class="aksara-td text-center">{{ row.sakit }}</td>
                                <td class="aksara-td text-center">{{ row.alpha }}</td>
                                <td class="aksara-td text-center font-bold" :class="pctClass(row.pct)">
                                    {{ row.pct }}%
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-4 pb-4 sm:px-5">
                    <Pagination
                        :paginator="summaryData"
                        :per-page="perPage"
                        :base-url="indexUrl"
                        :query="filterQuery"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
