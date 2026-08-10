<script setup>
import { reactive, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';

const props = defineProps({
    evaluations: { type: Object, required: true },
    teachers: { type: Array, default: () => [] },
    subjects: { type: Array, default: () => [] },
    isAdmin: { type: Boolean, default: false },
    filters: { type: Object, default: () => ({}) },
    indexUrl: { type: String, required: true },
});

const local = reactive({
    search: props.filters.search || '',
    teacher: props.filters.teacher || '',
    subject: props.filters.subject || '',
});

let timer = null;

watch(
    local,
    () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            router.get(
                props.indexUrl,
                {
                    search: local.search || undefined,
                    teacher: local.teacher || undefined,
                    subject: local.subject || undefined,
                },
                { preserveState: true, replace: true },
            );
        }, 300);
    },
    { deep: true },
);

function teacherInitial(name) {
    return (name || 'G').charAt(0).toUpperCase();
}
</script>

<template>
    <AppLayout title="Monitoring Evaluasi & Refleksi Guru">
        <template #header>Monitoring Evaluasi & Refleksi Guru</template>

        <Card
            title="Supervisi Evaluasi & Refleksi Mengajar Guru"
            description="Monitoring rekapitulasi catatan refleksi, kendala pembelajaran, dan rencana tindak lanjut guru di seluruh sekolah."
        >
            <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div>
                    <label for="eval-search" class="aksara-label text-xs">Pencarian Catatan / Kendala / Topik</label>
                    <input
                        id="eval-search"
                        v-model="local.search"
                        type="text"
                        placeholder="Cari topik atau catatan refleksi..."
                        class="aksara-input"
                    />
                </div>

                <div v-if="isAdmin">
                    <label for="eval-teacher" class="aksara-label text-xs">Filter Guru</label>
                    <select id="eval-teacher" v-model="local.teacher" class="aksara-select">
                        <option value="">Semua Guru</option>
                        <option v-for="t in teachers" :key="t.id" :value="String(t.id)">{{ t.name }}</option>
                    </select>
                </div>

                <div>
                    <label for="eval-subject" class="aksara-label text-xs">Filter Mapel</label>
                    <select id="eval-subject" v-model="local.subject" class="aksara-select">
                        <option value="">Semua Mata Pelajaran</option>
                        <option v-for="sub in subjects" :key="sub.id" :value="String(sub.id)">{{ sub.name }}</option>
                    </select>
                </div>
            </div>

            <div
                v-if="!evaluations.data?.length"
                class="rounded-2xl border border-dashed border-aksara-line bg-white p-10 text-center"
            >
                <h3 class="font-display text-lg font-semibold text-aksara-ink">Belum Ada Rekap Refleksi</h3>
                <p class="mt-2 text-sm text-aksara-muted">
                    Belum ada data evaluasi dan refleksi mengajar yang sesuai kriteria pencarian.
                </p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="aksara-table w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-aksara-line bg-aksara-mist/40 text-xs font-semibold text-aksara-muted">
                            <th class="aksara-th p-2.5">Rencana & Mapel</th>
                            <th v-if="isAdmin" class="aksara-th p-2.5">Guru Pengampu</th>
                            <th class="aksara-th p-2.5">Catatan & Refleksi Pembelajaran</th>
                            <th class="aksara-th p-2.5">Tantangan / Kendala</th>
                            <th class="aksara-th p-2.5">Rencana Tindak Lanjut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-aksara-line/60">
                        <tr
                            v-for="evalRow in evaluations.data"
                            :key="evalRow.id"
                            class="align-top transition hover:bg-aksara-mist/30"
                        >
                            <td class="aksara-td min-w-[200px] p-2.5 font-medium text-aksara-ink">
                                <div class="text-sm font-semibold">{{ evalRow.topic || '-' }}</div>
                                <div class="mt-0.5 text-xs text-aksara-muted">
                                    {{ evalRow.subjectName || '-' }} · Kelas {{ evalRow.className || '-' }}
                                </div>
                                <div class="mt-1 text-[11px] text-aksara-muted">{{ evalRow.createdAt }}</div>
                            </td>
                            <td v-if="isAdmin" class="aksara-td min-w-[140px] p-2.5 text-xs font-medium">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="flex h-6 w-6 items-center justify-center rounded-full bg-aksara-teal/10 text-[10px] font-bold text-aksara-teal"
                                    >
                                        {{ teacherInitial(evalRow.teacherName) }}
                                    </span>
                                    <span>{{ evalRow.teacherName || '-' }}</span>
                                </div>
                            </td>
                            <td class="aksara-td max-w-xs p-2.5 text-xs leading-relaxed">
                                <div class="line-clamp-4" v-html="evalRow.notes" />
                            </td>
                            <td class="aksara-td max-w-xs p-2.5 text-xs leading-relaxed text-amber-900">
                                <div class="line-clamp-4" v-html="evalRow.challenges" />
                            </td>
                            <td class="aksara-td max-w-xs p-2.5 text-xs leading-relaxed text-emerald-900">
                                <div class="line-clamp-4" v-html="evalRow.nextAction" />
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="evaluations.links?.length" class="mt-4 flex flex-wrap gap-2">
                    <Link
                        v-for="(link, i) in evaluations.links"
                        :key="i"
                        :href="link.url || '#'"
                        class="rounded-lg border border-aksara-line px-3 py-1 text-xs"
                        :class="link.active ? 'bg-aksara-teal text-white' : 'bg-white text-aksara-ink'"
                        :preserve-scroll="true"
                        v-html="link.label"
                    />
                </div>
            </div>
        </Card>
    </AppLayout>
</template>
