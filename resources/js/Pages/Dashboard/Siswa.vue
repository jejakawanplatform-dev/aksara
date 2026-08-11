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
import PageHeader from '@/Components/ui/PageHeader.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import IconButton from '@/Components/ui/IconButton.vue';

defineProps({
    userName: { type: String, required: true },
    materials: { type: Array, default: () => [] },
    materialsIndexUrl: { type: String, required: true },
});
</script>

<template>
    <AppLayout title="Dashboard Siswa">
        <template #header>Dashboard Siswa — {{ userName }}</template>

        <div class="space-y-5">
            <PageHeader
                :title="`Halo, ${userName}`"
                description="Materi pembelajaran yang tersedia untuk kelas Anda."
            >
                <template #actions>
                    <IconButton icon="materials" label="Semua materi" :href="materialsIndexUrl" />
                </template>
            </PageHeader>

            <div class="aksara-surface overflow-hidden">
                <div class="border-b border-aksara-line px-4 py-3 sm:px-5">
                    <h3 class="text-base font-semibold text-aksara-ink">Materi Tersedia</h3>
                </div>

                <div
                    v-if="!materials.length"
                    class="aksara-surface-dashed m-4 p-10 text-center sm:m-5"
                >
                    <h3 class="text-lg font-semibold text-aksara-ink">Belum ada materi</h3>
                    <p class="mt-2 text-sm text-aksara-muted">
                        Guru belum menerbitkan materi untuk kelas Anda.
                    </p>
                    <Link
                        :href="materialsIndexUrl"
                        class="mt-4 inline-block text-sm font-semibold text-aksara-teal hover:underline"
                    >
                        Cek daftar materi
                    </Link>
                </div>

                <div v-else class="divide-y divide-aksara-line">
                    <Link
                        v-for="material in materials"
                        :key="material.id"
                        :href="material.url"
                        class="flex items-center justify-between px-4 py-3 transition hover:bg-aksara-mist/40 sm:px-5"
                    >
                        <div>
                            <p class="font-semibold text-aksara-ink">{{ material.title }}</p>
                            <p class="text-sm text-aksara-muted">
                                {{ material.subject }} · Kelas {{ material.grade }}
                            </p>
                        </div>
                        <StatusBadge status="published" />
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
