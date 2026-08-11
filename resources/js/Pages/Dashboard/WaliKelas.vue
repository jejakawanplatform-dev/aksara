<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';

defineProps({
    userName: { type: String, required: true },
    metrics: {
        type: Object,
        default: () => ({
            classesCount: 0,
            studentsCount: 0,
            publishedMaterials: 0,
            pctHadir: null,
            quizAttempts: 0,
        }),
    },
    classes: { type: Array, default: () => [] },
    attendanceSummaryUrl: { type: String, required: true },
});

function pctTone(pct) {
    if (pct === null || pct === undefined) return 'text-aksara-muted';
    if (pct >= 80) return 'text-aksara-ok';
    if (pct >= 60) return 'text-aksara-warn';
    return 'text-aksara-danger';
}
</script>

<template>
    <AppLayout title="Dashboard Wali Kelas">
        <template #header>Dashboard Wali Kelas — {{ userName }}</template>

        <div class="space-y-6">
            <div
                class="rounded-xl border border-aksara-line bg-white p-5 shadow-sm border-l-4 border-l-aksara-teal"
            >
                <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div class="max-w-2xl space-y-1">
                        <h3 class="text-xl font-bold tracking-tight text-aksara-ink">
                            Halo, {{ userName }}
                        </h3>
                        <p class="text-sm leading-relaxed text-aksara-muted">
                            Pantau kehadiran, materi terbit, dan siswa yang perlu perhatian di kelas yang Anda ampu.
                        </p>
                    </div>
                    <Link
                        :href="attendanceSummaryUrl"
                        class="aksara-btn-primary shrink-0 !px-3.5 !py-2 text-xs"
                    >
                        Rekap kehadiran →
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
                <Card>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-aksara-muted">Kelas</p>
                    <p class="mt-2 text-2xl font-bold text-aksara-ink">
                        {{ metrics.classesCount }}
                    </p>
                </Card>
                <Card>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-aksara-muted">Siswa</p>
                    <p class="mt-2 text-2xl font-bold text-aksara-ink">
                        {{ metrics.studentsCount }}
                    </p>
                </Card>
                <Card>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-aksara-muted">Kehadiran</p>
                    <p class="mt-2 text-2xl font-bold" :class="pctTone(metrics.pctHadir)">
                        <template v-if="metrics.pctHadir !== null">{{ metrics.pctHadir }}%</template>
                        <template v-else>—</template>
                    </p>
                </Card>
                <Card>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-aksara-muted">Materi terbit</p>
                    <p class="mt-2 text-2xl font-bold text-aksara-info">
                        {{ metrics.publishedMaterials }}
                    </p>
                </Card>
                <Card>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-aksara-muted">Percobaan kuis</p>
                    <p class="mt-2 text-2xl font-bold text-aksara-teal-dark">
                        {{ metrics.quizAttempts }}
                    </p>
                </Card>
            </div>

            <Card title="Kelas yang diampu">
                <div
                    v-if="!classes.length"
                    class="rounded-xl border border-dashed border-aksara-line bg-aksara-mist/30 p-6 text-center"
                >
                    <p class="font-medium text-aksara-ink">Belum ada kelas</p>
                    <p class="mt-1 text-sm text-aksara-muted">
                        Belum ada kelas yang ditugaskan sebagai wali kelas.
                    </p>
                </div>

                <div v-else class="space-y-6">
                    <div
                        v-for="cls in classes"
                        :key="cls.id"
                        class="border-b border-aksara-line pb-6 last:border-0 last:pb-0"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-aksara-ink">{{ cls.name }}</p>
                                <p class="text-sm text-aksara-muted">
                                    {{ cls.studentsCount }} siswa · grade {{ cls.grade }} ·
                                    {{ cls.plansCount }} rencana · {{ cls.publishedMaterials }} materi terbit
                                </p>
                            </div>
                            <Link
                                :href="cls.attendanceSummaryUrl"
                                class="text-sm font-medium text-aksara-teal hover:underline"
                            >
                                Rekap kelas →
                            </Link>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="rounded-lg bg-aksara-mist/40 px-3 py-2">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-aksara-muted">
                                    % Hadir
                                </p>
                                <p class="mt-0.5 text-lg font-bold" :class="pctTone(cls.pctHadir)">
                                    <template v-if="cls.pctHadir !== null">{{ cls.pctHadir }}%</template>
                                    <template v-else>—</template>
                                </p>
                                <p v-if="cls.totalAttendance" class="text-[11px] text-aksara-muted">
                                    {{ cls.hadirCount }}/{{ cls.totalAttendance }} record
                                </p>
                            </div>
                            <div class="rounded-lg bg-aksara-mist/40 px-3 py-2">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-aksara-muted">
                                    Kuis
                                </p>
                                <p class="mt-0.5 text-lg font-bold text-aksara-ink">{{ cls.quizAttempts }}</p>
                                <p class="text-[11px] text-aksara-muted">
                                    <template v-if="cls.avgQuizScore !== null">
                                        rata-rata {{ cls.avgQuizScore }}
                                    </template>
                                    <template v-else>belum ada</template>
                                </p>
                            </div>
                            <div class="rounded-lg bg-aksara-mist/40 px-3 py-2 sm:col-span-2">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-aksara-muted">
                                    Perlu perhatian
                                </p>
                                <ul v-if="cls.attentionStudents.length" class="mt-1 space-y-0.5">
                                    <li
                                        v-for="s in cls.attentionStudents"
                                        :key="s.id"
                                        class="flex justify-between gap-2 text-sm text-aksara-ink"
                                    >
                                        <span>{{ s.name }}</span>
                                        <span class="font-semibold" :class="pctTone(s.pctHadir)">
                                            {{ s.pctHadir }}%
                                        </span>
                                    </li>
                                </ul>
                                <p v-else class="mt-1 text-sm text-aksara-muted">
                                    Belum ada data kehadiran per siswa.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>

            <p class="text-sm text-aksara-muted">
                Mode wali kelas bersifat baca/rekap. Pembuatan rencana dan publish materi dilakukan oleh guru mapel.
            </p>
        </div>
    </AppLayout>
</template>
