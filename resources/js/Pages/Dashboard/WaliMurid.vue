<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';

defineProps({
    parentName: { type: String, required: true },
    childData: { type: Array, default: () => [] },
});

function statusClass(status) {
    if (status === 'Baik') return 'text-aksara-ok';
    if (status === 'Perlu Perhatian') return 'text-aksara-warn';
    return 'text-aksara-danger';
}
</script>

<template>
    <AppLayout title="Ringkasan Anak Saya">
        <template #header>Ringkasan Anak Saya</template>

        <div class="space-y-5">
            <PageHeader
                :title="`Selamat datang, ${parentName}`"
                description="Ringkasan aktivitas belajar anak Anda."
            />

            <div
                v-if="!childData.length"
                class="aksara-surface-dashed p-10 text-center"
            >
                <p class="font-medium text-aksara-ink">Belum ada data anak</p>
                <p class="mt-2 text-sm text-aksara-muted">
                    Belum ada data anak yang terhubung dengan akun Anda.
                </p>
            </div>

            <div
                v-for="data in childData"
                :key="data.id"
                class="aksara-surface overflow-hidden"
            >
                <div class="border-b border-aksara-line px-4 py-3 sm:px-5">
                    <h3 class="flex items-center gap-2 text-base font-semibold text-aksara-ink">
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-aksara-teal/10 text-sm font-bold text-aksara-teal"
                        >
                            {{ data.initial }}
                        </span>
                        {{ data.name }}
                    </h3>
                </div>

                <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-3 sm:p-5">
                    <div class="rounded-lg border border-aksara-line bg-aksara-ok/5 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-aksara-ok">Kehadiran</p>
                        <p class="mt-1 text-2xl font-bold text-aksara-ok">{{ data.pctHadir }}%</p>
                        <p class="mt-1 text-xs text-aksara-muted">
                            {{ data.hadirCount }}/{{ data.totalAttendance }} pertemuan hadir
                        </p>
                    </div>

                    <div class="rounded-lg border border-aksara-line bg-aksara-info/5 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-aksara-info">Quiz Dikerjakan</p>
                        <p class="mt-1 text-2xl font-bold text-aksara-info">{{ data.quizCount }}</p>
                        <p class="mt-1 text-xs text-aksara-muted">
                            <template v-if="data.avgScore !== null">
                                Rata-rata nilai: <strong class="text-aksara-ink">{{ data.avgScore }}</strong>
                            </template>
                            <template v-else>Belum ada quiz</template>
                        </p>
                    </div>

                    <div class="rounded-lg border border-aksara-line bg-aksara-mist/50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-aksara-teal">Status</p>
                        <p class="mt-1 text-2xl font-bold" :class="statusClass(data.status)">{{ data.status }}</p>
                        <p class="mt-1 text-xs text-aksara-muted">Berdasarkan kehadiran & nilai</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
