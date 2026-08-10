<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';

defineProps({
    reportData: { type: Array, default: () => [] },
    plansCreateUrl: { type: String, required: true },
});
</script>

<template>
    <AppLayout title="Laporan Guru">
        <template #header>4. Laporan Guru</template>

        <Card
            title="Ringkasan Semua Plan"
            description="Kehadiran, quiz, dan evaluasi per rencana pembelajaran Anda."
        >
            <div
                v-if="!reportData.length"
                class="rounded-xl border border-aksara-line bg-aksara-mist/30 p-6 text-center"
            >
                <p class="text-aksara-muted">
                    Belum ada rencana pembelajaran.
                    <a :href="plansCreateUrl" class="text-aksara-teal underline">Buat sekarang →</a>
                </p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-aksara-line text-left text-xs uppercase tracking-wide text-aksara-muted">
                            <th class="pb-3 pr-4">Topik</th>
                            <th class="pb-3 pr-3">Kelas</th>
                            <th class="pb-3 pr-3">Mapel</th>
                            <th class="pb-3 pr-3 text-center">Kehadiran</th>
                            <th class="pb-3 pr-3 text-center">Quiz</th>
                            <th class="pb-3 pr-3 text-center">Rata-rata</th>
                            <th class="pb-3 pr-3 text-center">Evaluasi</th>
                            <th class="pb-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-aksara-line/60">
                        <tr v-for="row in reportData" :key="row.planId" class="hover:bg-aksara-mist/30">
                            <td class="py-3 pr-4 font-medium text-aksara-ink">{{ row.topic }}</td>
                            <td class="py-3 pr-3 text-aksara-muted">{{ row.className || '-' }}</td>
                            <td class="py-3 pr-3 text-aksara-muted">{{ row.subjectName || '-' }}</td>
                            <td class="py-3 pr-3 text-center">
                                <span v-if="row.totalSiswa > 0" class="font-semibold text-green-600">
                                    {{ row.hadirCount }}/{{ row.totalSiswa }}
                                </span>
                                <span v-else class="text-aksara-muted">-</span>
                            </td>
                            <td class="py-3 pr-3 text-center text-blue-600">{{ row.quizCount }}</td>
                            <td class="py-3 pr-3 text-center">
                                <span
                                    v-if="row.avgScore !== null"
                                    class="font-bold"
                                    :class="row.avgScore >= 70 ? 'text-green-600' : 'text-red-600'"
                                >
                                    {{ row.avgScore }}
                                </span>
                                <span v-else class="text-aksara-muted">-</span>
                            </td>
                            <td class="py-3 pr-3 text-center">
                                <span
                                    v-if="row.hasEval"
                                    class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700"
                                >
                                    Selesai
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-700"
                                >
                                    Belum
                                </span>
                            </td>
                            <td class="py-3">
                                <div class="flex gap-2 text-xs">
                                    <a :href="row.attendanceUrl" class="text-aksara-teal hover:underline">Absen</a>
                                    <a :href="row.evaluationUrl" class="text-purple-600 hover:underline">Refleksi</a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>
    </AppLayout>
</template>
