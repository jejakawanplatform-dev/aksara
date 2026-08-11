<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
import { computed, reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Field from '@/Components/ui/Field.vue';
import Pagination from '@/Components/ui/Pagination.vue';

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

const perPage = computed(
    () => Number(props.filters.per_page) || Number(props.evaluations.per_page) || 10,
);

const filterQuery = computed(() => ({
    search: local.search || undefined,
    teacher: local.teacher || undefined,
    subject: local.subject || undefined,
    per_page: perPage.value,
}));

let timer = null;

watch(
    local,
    () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            router.get(
                props.indexUrl,
                {
                    ...filterQuery.value,
                    page: 1,
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
        <template #header>Monitoring Evaluasi</template>

        <div class="space-y-5">
            <PageHeader
                title="Monitoring Evaluasi & Refleksi Guru"
                description="Rekapitulasi catatan refleksi, kendala pembelajaran, dan rencana tindak lanjut guru di seluruh sekolah."
            />

            <div class="aksara-surface p-4 sm:p-5">
                <div
                    class="grid grid-cols-1 gap-3"
                    :class="isAdmin ? 'md:grid-cols-3' : 'md:grid-cols-2'"
                >
                    <Field label="Pencarian catatan / topik" for-id="eval-search">
                        <input
                            id="eval-search"
                            v-model="local.search"
                            type="search"
                            placeholder="Cari topik atau catatan refleksi…"
                            class="aksara-input"
                        />
                    </Field>

                    <Field v-if="isAdmin" label="Guru" for-id="eval-teacher">
                        <select id="eval-teacher" v-model="local.teacher" class="aksara-select">
                            <option value="">Semua guru</option>
                            <option v-for="t in teachers" :key="t.id" :value="String(t.id)">{{ t.name }}</option>
                        </select>
                    </Field>

                    <Field label="Mapel" for-id="eval-subject">
                        <select id="eval-subject" v-model="local.subject" class="aksara-select">
                            <option value="">Semua mata pelajaran</option>
                            <option v-for="sub in subjects" :key="sub.id" :value="String(sub.id)">{{ sub.name }}</option>
                        </select>
                    </Field>
                </div>
            </div>

            <div
                v-if="!evaluations.data?.length"
                class="aksara-surface-dashed p-10 text-center"
            >
                <h3 class="text-lg font-semibold text-aksara-ink">Belum ada rekap refleksi</h3>
                <p class="mt-2 text-sm text-aksara-muted">
                    Belum ada data evaluasi dan refleksi mengajar yang sesuai kriteria pencarian.
                </p>
            </div>

            <div v-else class="aksara-surface">
                <div class="overflow-x-auto">
                    <table class="aksara-table w-full min-w-[760px]">
                        <thead>
                            <tr>
                                <th class="aksara-th">Rencana & Mapel</th>
                                <th v-if="isAdmin" class="aksara-th">Guru Pengampu</th>
                                <th class="aksara-th">Catatan & Refleksi</th>
                                <th class="aksara-th">Tantangan / Kendala</th>
                                <th class="aksara-th">Rencana Tindak Lanjut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="evalRow in evaluations.data"
                                :key="evalRow.id"
                                class="align-top hover:bg-aksara-mist/40"
                            >
                                <td class="aksara-td min-w-[200px] font-medium text-aksara-ink">
                                    <div class="text-sm font-semibold">{{ evalRow.topic || '—' }}</div>
                                    <div class="mt-0.5 text-xs text-aksara-muted">
                                        {{ evalRow.subjectName || '—' }} · Kelas {{ evalRow.className || '—' }}
                                    </div>
                                    <div class="mt-1 text-[11px] text-aksara-muted">{{ evalRow.createdAt }}</div>
                                </td>
                                <td v-if="isAdmin" class="aksara-td min-w-[140px] text-xs font-medium">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="flex h-6 w-6 items-center justify-center rounded-full bg-aksara-teal/10 text-[10px] font-bold text-aksara-teal"
                                        >
                                            {{ teacherInitial(evalRow.teacherName) }}
                                        </span>
                                        <span>{{ evalRow.teacherName || '—' }}</span>
                                    </div>
                                </td>
                                <td class="aksara-td max-w-xs text-xs leading-relaxed">
                                    <div class="line-clamp-4" v-html="evalRow.notes" />
                                </td>
                                <td class="aksara-td max-w-xs text-xs leading-relaxed text-aksara-warn">
                                    <div class="line-clamp-4" v-html="evalRow.challenges" />
                                </td>
                                <td class="aksara-td max-w-xs text-xs leading-relaxed text-aksara-ok">
                                    <div class="line-clamp-4" v-html="evalRow.nextAction" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-4 pb-4 sm:px-5">
                    <Pagination
                        :paginator="evaluations"
                        :per-page="perPage"
                        :base-url="indexUrl"
                        :query="filterQuery"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
