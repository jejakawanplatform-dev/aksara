<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Btn from '@/Components/ui/Btn.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';

defineProps({
    userName: { type: String, required: true },
    roleLabel: { type: String, default: 'Guru' },
    metrics: { type: Object, required: true },
    recentPlans: { type: Array, default: () => [] },
    recentMaterials: { type: Array, default: () => [] },
    classesTaught: { type: Array, default: () => [] },
    urls: { type: Object, required: true },
});
</script>

<template>
    <AppLayout title="Dashboard Guru">
        <template #header>
            <div class="flex w-full items-center justify-between gap-3">
                <span>Dashboard Guru</span>
                <span class="text-xs font-medium text-aksara-muted">Peran: {{ roleLabel }}</span>
            </div>
        </template>

        <div class="space-y-6">
            <div
                class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-aksara-teal to-teal-800 p-6 text-white shadow-md"
            >
                <div class="relative z-10 flex flex-col justify-between gap-6 md:flex-row md:items-center">
                    <div class="max-w-2xl space-y-1.5">
                        <h3 class="font-display text-2xl font-bold tracking-tight">
                            Halo, {{ userName }}
                        </h3>
                        <p class="text-xs leading-relaxed text-white/90">
                            Kelola draf modul ajar, materi, kehadiran, dan evaluasi kelas.
                        </p>
                    </div>
                    <div class="flex shrink-0 flex-wrap items-center gap-2.5">
                        <Link
                            :href="urls.plansCreateAi"
                            class="rounded-xl bg-white px-4 py-2.5 text-xs font-semibold text-aksara-teal shadow-sm hover:bg-aksara-mist"
                        >
                            + Buat Draf AI
                        </Link>
                        <Link
                            :href="urls.plansCreateManual"
                            class="rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-xs font-semibold text-white hover:bg-white/20"
                        >
                            + Buat Manual
                        </Link>
                        <Link
                            :href="urls.reportsGuru"
                            class="rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-xs font-semibold text-white hover:bg-white/20"
                        >
                            Jurnal Guru
                        </Link>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <Card>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-aksara-muted">
                        Total Modul Ajar
                    </p>
                    <p class="mt-2 font-display text-2xl font-bold text-aksara-ink">
                        {{ metrics.totalPlans }}
                    </p>
                    <p class="mt-1 text-[11px]">
                        <span class="font-semibold text-green-700">{{ metrics.publishedPlans }} Terbit</span>
                        <span class="text-aksara-muted"> · </span>
                        <span class="font-medium text-amber-700">{{ metrics.draftPlans }} Draf</span>
                    </p>
                </Card>
                <Card>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-aksara-muted">
                        Materi Terbit
                    </p>
                    <p class="mt-2 font-display text-2xl font-bold text-blue-700">
                        {{ metrics.publishedMaterials }}
                    </p>
                    <p class="mt-1 text-[11px] text-aksara-muted">
                        Dari {{ metrics.totalMaterials }} total materi
                    </p>
                </Card>
                <Card>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-aksara-muted">
                        Jurnal Refleksi
                    </p>
                    <p class="mt-2 font-display text-2xl font-bold text-purple-700">
                        {{ metrics.evaluationsCount }}
                    </p>
                    <p class="mt-1 text-[11px] text-aksara-muted">Catatan evaluasi terisi</p>
                </Card>
                <Card>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-aksara-muted">
                        Kuota AI Hari Ini
                    </p>
                    <p class="mt-2 font-display text-2xl font-bold text-amber-700">
                        {{ metrics.todayAiCount }}
                        <span class="text-xs font-medium text-aksara-muted">/ {{ metrics.dailyLimit }}</span>
                    </p>
                    <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-aksara-mist">
                        <div
                            class="h-1.5 rounded-full bg-amber-500"
                            :style="{ width: `${metrics.aiPercentage}%` }"
                        />
                    </div>
                </Card>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <Card title="Rencana Pembelajaran Terbaru">
                        <template #actions>
                            <Link
                                :href="urls.plansIndex"
                                class="text-xs font-semibold text-aksara-teal hover:underline"
                            >
                                Lihat semua ({{ metrics.totalPlans }})
                            </Link>
                        </template>

                        <div
                            v-if="!recentPlans.length"
                            class="rounded-xl border border-dashed border-aksara-line bg-aksara-mist/30 p-6 text-center"
                        >
                            <p class="text-sm font-medium text-aksara-ink">Belum ada rencana pembelajaran</p>
                            <p class="mt-1 text-xs text-aksara-muted">
                                Mulai buat draf dengan AI atau tulis secara manual.
                            </p>
                            <div class="mt-4 flex justify-center gap-3">
                                <Btn :href="urls.plansCreateAi" class="!text-xs">Buat Draf AI</Btn>
                                <Btn :href="urls.plansCreateManual" variant="secondary" class="!text-xs">
                                    Buat Manual
                                </Btn>
                            </div>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="aksara-table text-xs">
                                <thead>
                                    <tr>
                                        <th class="aksara-th">Topik</th>
                                        <th class="aksara-th">Mapel & Rombel</th>
                                        <th class="aksara-th">Status</th>
                                        <th class="aksara-th text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="plan in recentPlans" :key="plan.id">
                                        <td class="aksara-td">
                                            <div class="font-semibold text-aksara-ink">{{ plan.topic }}</div>
                                            <div class="font-mono text-[11px] text-aksara-muted">
                                                {{ plan.duration_minutes }} menit · Fase {{ plan.phase }}
                                            </div>
                                        </td>
                                        <td class="aksara-td">
                                            <div class="font-medium">{{ plan.subject }}</div>
                                            <div class="text-[11px] text-aksara-muted">{{ plan.classLabel }}</div>
                                        </td>
                                        <td class="aksara-td">
                                            <StatusBadge :status="plan.status" />
                                        </td>
                                        <td class="aksara-td text-right">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <Link
                                                    v-if="plan.status === 'draft'"
                                                    :href="plan.draftUrl"
                                                    class="aksara-btn-secondary !px-2.5 !py-1 text-[11px]"
                                                >
                                                    Review AI
                                                </Link>
                                                <Link
                                                    :href="plan.editUrl"
                                                    class="text-xs font-semibold text-aksara-teal hover:underline"
                                                >
                                                    Edit
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Card>

                    <Card title="Materi Pembelajaran Aktif">
                        <template #actions>
                            <Link
                                :href="urls.materialsIndex"
                                class="text-xs font-semibold text-aksara-teal hover:underline"
                            >
                                Katalog materi
                            </Link>
                        </template>

                        <p v-if="!recentMaterials.length" class="py-2 text-xs text-aksara-muted">
                            Belum ada materi pembelajaran.
                        </p>
                        <div v-else class="grid grid-cols-1 gap-3 md:grid-cols-3">
                            <div
                                v-for="mat in recentMaterials"
                                :key="mat.id"
                                class="flex flex-col justify-between rounded-xl border border-aksara-line bg-aksara-mist/20 p-3.5"
                            >
                                <div class="space-y-1">
                                    <h5 class="line-clamp-2 text-xs font-semibold text-aksara-ink">
                                        {{ mat.title }}
                                    </h5>
                                    <p class="text-[11px] text-aksara-muted">
                                        {{ mat.subject }} · {{ mat.classLabel }}
                                    </p>
                                </div>
                                <Link
                                    :href="mat.url"
                                    class="mt-3 text-xs font-semibold text-aksara-teal hover:underline"
                                >
                                    Baca materi
                                </Link>
                            </div>
                        </div>
                    </Card>
                </div>

                <div class="space-y-6">
                    <Card title="Batas AI Harian">
                        <div class="mb-2 flex items-center justify-between text-xs">
                            <span class="text-aksara-muted">Pemakaian hari ini</span>
                            <span class="font-bold text-amber-700">
                                {{ metrics.todayAiCount }} / {{ metrics.dailyLimit }}
                            </span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-aksara-mist">
                            <div
                                class="h-2 rounded-full bg-amber-500"
                                :style="{ width: `${metrics.aiPercentage}%` }"
                            />
                        </div>
                        <p class="mt-3 text-[11px] leading-relaxed text-aksara-muted">
                            Kuota AI di-reset setiap pukul 00:00 WIB. Modul manual tanpa batas.
                        </p>
                    </Card>

                    <Card title="Kelas & Rombel Diajar">
                        <p v-if="!classesTaught.length" class="text-xs text-aksara-muted">
                            Belum ada kelas rombel terdaftar.
                        </p>
                        <div v-else class="space-y-2">
                            <div
                                v-for="cls in classesTaught"
                                :key="cls.id"
                                class="flex items-center justify-between rounded-xl border border-aksara-line/60 bg-aksara-mist/20 p-2.5 text-xs"
                            >
                                <div class="flex items-center gap-2 font-semibold text-aksara-ink">
                                    <span
                                        class="flex h-7 w-7 items-center justify-center rounded-lg bg-aksara-teal/10 text-xs font-bold text-aksara-teal"
                                    >
                                        {{ cls.grade }}
                                    </span>
                                    <span>{{ cls.label }}</span>
                                </div>
                                <span
                                    class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-700"
                                >
                                    {{ cls.studentsCount }} Siswa
                                </span>
                            </div>
                        </div>
                    </Card>

                    <Card title="Pintasan Fungsi Guru">
                        <div class="space-y-1.5 text-xs">
                            <Link
                                :href="urls.plansIndex"
                                class="flex items-center justify-between rounded-xl p-2 font-medium text-aksara-ink hover:bg-aksara-mist"
                            >
                                Manajemen Modul Ajar
                            </Link>
                            <Link
                                :href="urls.materialsIndex"
                                class="flex items-center justify-between rounded-xl p-2 font-medium text-aksara-ink hover:bg-aksara-mist"
                            >
                                Materi Pembelajaran
                            </Link>
                            <Link
                                :href="urls.reportsGuru"
                                class="flex items-center justify-between rounded-xl p-2 font-medium text-aksara-ink hover:bg-aksara-mist"
                            >
                                Laporan Pembelajaran Guru
                            </Link>
                            <Link
                                :href="urls.evaluationsMonitoring"
                                class="flex items-center justify-between rounded-xl p-2 font-medium text-aksara-ink hover:bg-aksara-mist"
                            >
                                Supervisi Refleksi Evaluasi
                            </Link>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
