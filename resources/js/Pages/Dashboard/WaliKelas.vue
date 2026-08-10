<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';

defineProps({
    userName: { type: String, required: true },
    classes: { type: Array, default: () => [] },
    attendanceSummaryUrl: { type: String, required: true },
});
</script>

<template>
    <AppLayout title="Dashboard Wali Kelas">
        <template #header>Dashboard Wali Kelas — {{ userName }}</template>

        <div class="space-y-6">
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
                <div v-else>
                    <div
                        v-for="cls in classes"
                        :key="cls.id"
                        class="flex items-center justify-between border-b border-aksara-line py-3 last:border-0"
                    >
                        <div>
                            <p class="font-medium text-aksara-ink">{{ cls.name }}</p>
                            <p class="text-sm text-aksara-muted">
                                {{ cls.studentsCount }} siswa · grade {{ cls.grade }}
                            </p>
                        </div>
                        <Link
                            :href="attendanceSummaryUrl"
                            class="text-sm text-aksara-teal hover:underline"
                        >
                            Lihat rekap →
                        </Link>
                    </div>
                </div>
            </Card>

            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p class="font-semibold">Mode wali kelas</p>
                <p class="mt-1 text-amber-800/90">
                    Bersifat baca/rekap. Pembuatan rencana dan publish materi dilakukan oleh guru mapel.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
