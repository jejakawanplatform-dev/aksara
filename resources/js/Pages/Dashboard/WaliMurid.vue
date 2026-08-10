<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';

defineProps({
    parentName: { type: String, required: true },
    childData: { type: Array, default: () => [] },
});

function statusClass(status) {
    if (status === 'Baik') return 'text-green-700';
    if (status === 'Perlu Perhatian') return 'text-yellow-700';
    return 'text-red-700';
}
</script>

<template>
    <AppLayout title="Ringkasan Anak Saya">
        <template #header>Ringkasan Anak Saya</template>

        <div class="mx-auto max-w-5xl space-y-6">
            <Card>
                <div class="mb-2">
                    <h3 class="text-lg font-bold text-aksara-ink">Selamat datang, {{ parentName }}</h3>
                    <p class="text-sm text-aksara-muted">Berikut ringkasan aktivitas belajar anak Anda.</p>
                </div>
            </Card>

            <div
                v-if="!childData.length"
                class="rounded-xl border border-yellow-200 bg-yellow-50 p-6 text-center"
            >
                <p class="text-yellow-700">Belum ada data anak yang terhubung dengan akun Anda.</p>
            </div>

            <Card v-for="data in childData" :key="data.id">
                <h3 class="mb-4 flex items-center gap-2 text-lg font-bold text-aksara-ink">
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-aksara-teal/10 text-sm font-bold text-aksara-teal"
                    >
                        {{ data.initial }}
                    </span>
                    {{ data.name }}
                </h3>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="rounded-lg border border-green-100 bg-green-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-green-600">Kehadiran</p>
                        <p class="mt-1 text-2xl font-bold text-green-700">{{ data.pctHadir }}%</p>
                        <p class="mt-1 text-xs text-green-500">
                            {{ data.hadirCount }}/{{ data.totalAttendance }} pertemuan hadir
                        </p>
                    </div>

                    <div class="rounded-lg border border-blue-100 bg-blue-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Quiz Dikerjakan</p>
                        <p class="mt-1 text-2xl font-bold text-blue-700">{{ data.quizCount }}</p>
                        <p class="mt-1 text-xs text-blue-500">
                            <template v-if="data.avgScore !== null">
                                Rata-rata nilai: <strong>{{ data.avgScore }}</strong>
                            </template>
                            <template v-else>Belum ada quiz</template>
                        </p>
                    </div>

                    <div class="rounded-lg border border-purple-100 bg-purple-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-purple-600">Status</p>
                        <p class="mt-1 text-2xl font-bold" :class="statusClass(data.status)">{{ data.status }}</p>
                        <p class="mt-1 text-xs text-purple-500">Berdasarkan kehadiran & nilai</p>
                    </div>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
