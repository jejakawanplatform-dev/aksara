<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';

defineProps({
    userName: { type: String, required: true },
    materials: { type: Array, default: () => [] },
    materialsIndexUrl: { type: String, required: true },
});
</script>

<template>
    <AppLayout title="Dashboard Siswa">
        <template #header>
            <div class="flex w-full items-center justify-between gap-3">
                <span>Dashboard Siswa — {{ userName }}</span>
                <Link :href="materialsIndexUrl" class="text-sm text-aksara-teal hover:underline">
                    Semua materi →
                </Link>
            </div>
        </template>

        <Card title="Materi Tersedia">
            <div
                v-if="!materials.length"
                class="rounded-xl border border-dashed border-aksara-line bg-aksara-mist/30 p-6 text-center"
            >
                <p class="font-medium text-aksara-ink">Belum ada materi</p>
                <p class="mt-1 text-sm text-aksara-muted">
                    Guru belum menerbitkan materi untuk kelas Anda.
                </p>
                <Link
                    :href="materialsIndexUrl"
                    class="mt-4 inline-block text-sm font-semibold text-aksara-teal hover:underline"
                >
                    Cek daftar materi
                </Link>
            </div>
            <div v-else>
                <Link
                    v-for="material in materials"
                    :key="material.id"
                    :href="material.url"
                    class="-mx-2 flex items-center justify-between rounded-lg border-b border-aksara-line px-2 py-3 last:border-0 hover:bg-aksara-mist/50"
                >
                    <div>
                        <p class="font-medium text-aksara-ink">{{ material.title }}</p>
                        <p class="text-sm text-aksara-muted">
                            {{ material.subject }} · Kelas {{ material.grade }}
                        </p>
                    </div>
                    <StatusBadge status="published" />
                </Link>
            </div>
        </Card>
    </AppLayout>
</template>
