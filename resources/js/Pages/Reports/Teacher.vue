<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import Btn from '@/Components/ui/Btn.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import IconButton from '@/Components/ui/IconButton.vue';

const props = defineProps({
    reportData: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    indexUrl: { type: String, required: true },
    plansCreateUrl: { type: String, required: true },
});

const perPage = computed(
    () => Number(props.filters.per_page) || Number(props.reportData.per_page) || 10,
);

const filterQuery = computed(() => ({
    per_page: perPage.value,
}));
</script>

<template>
    <AppLayout title="Laporan Guru">
        <template #header>Laporan Guru</template>

        <div class="space-y-5">
            <PageHeader
                title="Laporan Guru"
                description="Kehadiran, kuis, dan evaluasi per rencana pembelajaran Anda."
            />

            <div v-if="!reportData.data?.length" class="aksara-surface-dashed p-10 text-center">
                <h3 class="text-lg font-semibold text-aksara-ink">Belum ada rencana</h3>
                <p class="mt-2 text-sm text-aksara-muted">
                    Belum ada rencana pembelajaran untuk ditampilkan.
                </p>
                <div class="mt-4">
                    <Btn :href="plansCreateUrl" size="sm">Buat rencana</Btn>
                </div>
            </div>

            <div v-else class="aksara-surface">
                <div class="overflow-x-auto">
                    <table class="aksara-table w-full min-w-[720px]">
                        <thead>
                            <tr>
                                <th class="aksara-th">Topik</th>
                                <th class="aksara-th">Kelas</th>
                                <th class="aksara-th">Mapel</th>
                                <th class="aksara-th text-center">Kehadiran</th>
                                <th class="aksara-th text-center">Quiz</th>
                                <th class="aksara-th text-center">Rata-rata</th>
                                <th class="aksara-th text-center">Evaluasi</th>
                                <th class="aksara-th w-28 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in reportData.data" :key="row.planId" class="hover:bg-aksara-mist/40">
                                <td class="aksara-td font-medium text-aksara-ink">{{ row.topic }}</td>
                                <td class="aksara-td text-sm text-aksara-muted">{{ row.className || '—' }}</td>
                                <td class="aksara-td text-sm text-aksara-muted">{{ row.subjectName || '—' }}</td>
                                <td class="aksara-td text-center">
                                    <span v-if="row.totalSiswa > 0" class="font-semibold text-aksara-ok">
                                        {{ row.hadirCount }}/{{ row.totalSiswa }}
                                    </span>
                                    <span v-else class="text-aksara-muted">—</span>
                                </td>
                                <td class="aksara-td text-center text-aksara-info">{{ row.quizCount }}</td>
                                <td class="aksara-td text-center">
                                    <span
                                        v-if="row.avgScore !== null"
                                        class="font-bold"
                                        :class="row.avgScore >= 70 ? 'text-aksara-ok' : 'text-aksara-danger'"
                                    >
                                        {{ row.avgScore }}
                                    </span>
                                    <span v-else class="text-aksara-muted">—</span>
                                </td>
                                <td class="aksara-td text-center">
                                    <StatusBadge
                                        :status="row.hasEval ? 'published' : 'draft'"
                                        :label="row.hasEval ? 'Selesai' : 'Belum'"
                                    />
                                </td>
                                <td class="aksara-td">
                                    <div class="flex flex-wrap items-center justify-end gap-0.5">
                                        <IconButton icon="attendance" label="Absensi" :href="row.attendanceUrl" />
                                        <IconButton icon="evaluation" label="Refleksi" :href="row.evaluationUrl" />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-4 pb-4 sm:px-5">
                    <Pagination
                        :paginator="reportData"
                        :per-page="perPage"
                        :base-url="indexUrl"
                        :query="filterQuery"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
