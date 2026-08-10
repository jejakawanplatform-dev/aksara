<script setup>
import { reactive, ref, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import Btn from '@/Components/ui/Btn.vue';
import Field from '@/Components/ui/Field.vue';
import Modal from '@/Components/ui/Modal.vue';

const props = defineProps({
    plans: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    teachers: { type: Array, default: () => [] },
    subjects: { type: Array, default: () => [] },
    isAdmin: { type: Boolean, default: false },
    importTemplateUrl: { type: String, required: true },
    importUrl: { type: String, required: true },
    indexUrl: { type: String, required: true },
    createAiUrl: { type: String, required: true },
    createManualUrl: { type: String, required: true },
    exportUrls: { type: Object, required: true },
    importErrors: { type: Array, default: () => [] },
});

const localFilters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    teacher: props.filters.teacher || '',
    subject: props.filters.subject || '',
});

const showImport = ref(false);
const importForm = useForm({
    importFile: null,
});

let filterTimer = null;

watch(
    localFilters,
    () => {
        clearTimeout(filterTimer);
        filterTimer = setTimeout(() => {
            router.get(
                props.indexUrl,
                {
                    search: localFilters.search || undefined,
                    status: localFilters.status || undefined,
                    teacher: localFilters.teacher || undefined,
                    subject: localFilters.subject || undefined,
                },
                { preserveState: true, replace: true },
            );
        }, 300);
    },
    { deep: true },
);

function onImportFile(e) {
    importForm.importFile = e.target.files?.[0] ?? null;
}

function submitImport() {
    importForm.post(props.importUrl, {
        forceFormData: true,
        onSuccess: () => {
            showImport.value = false;
            importForm.reset();
        },
    });
}

function deletePlan(url) {
    if (!window.confirm('Yakin ingin menghapus rencana pembelajaran ini?')) return;
    router.delete(url);
}
</script>

<template>
    <AppLayout title="Rencana Pembelajaran">
        <template #header>1. Rencana Pembelajaran (Modul Ajar)</template>

        <Card
            title="Rencana Pembelajaran (Modul Ajar)"
            :description="
                isAdmin
                    ? 'Daftar seluruh Rencana Pembelajaran buatan semua guru di sekolah.'
                    : 'Daftar seluruh Rencana Pembelajaran buatan Anda.'
            "
        >
            <template #actions>
                <div class="flex flex-wrap items-center gap-2">
                    <a :href="exportUrls.excel" class="aksara-btn-secondary !px-3 !py-2 text-xs">Ekspor Excel</a>
                    <a :href="exportUrls.word" class="aksara-btn-secondary !px-3 !py-2 text-xs">Ekspor Word</a>
                    <a :href="exportUrls.pdf" target="_blank" class="aksara-btn-secondary !px-3 !py-2 text-xs">Cetak PDF</a>
                    <button type="button" class="aksara-btn-secondary !px-3 !py-2 text-xs" @click="showImport = true">
                        Impor Excel
                    </button>
                    <Btn :href="createAiUrl" class="!px-3 !py-2 text-xs">+ Buat Draf AI</Btn>
                    <Btn :href="createManualUrl" variant="secondary" class="!px-3 !py-2 text-xs">+ Buat Manual</Btn>
                </div>
            </template>

            <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4">
                <Field label="Pencarian Topik" for-id="search-plans">
                    <input
                        id="search-plans"
                        v-model="localFilters.search"
                        type="text"
                        class="aksara-input"
                        placeholder="Cari topik pembelajaran..."
                    />
                </Field>
                <Field v-if="isAdmin" label="Filter Guru" for-id="filter-teacher">
                    <select id="filter-teacher" v-model="localFilters.teacher" class="aksara-select">
                        <option value="">Semua Guru</option>
                        <option v-for="t in teachers" :key="t.id" :value="String(t.id)">{{ t.name }}</option>
                    </select>
                </Field>
                <Field label="Filter Mapel" for-id="filter-subject">
                    <select id="filter-subject" v-model="localFilters.subject" class="aksara-select">
                        <option value="">Semua Mata Pelajaran</option>
                        <option v-for="s in subjects" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
                    </select>
                </Field>
                <Field label="Filter Status" for-id="filter-status">
                    <select id="filter-status" v-model="localFilters.status" class="aksara-select">
                        <option value="">Semua Status</option>
                        <option value="draft">Draf</option>
                        <option value="reviewed">Direview</option>
                        <option value="published">Diterbitkan</option>
                    </select>
                </Field>
            </div>

            <div
                v-if="!plans.data?.length"
                class="rounded-2xl border border-dashed border-aksara-line bg-white p-10 text-center"
            >
                <h3 class="font-display text-lg font-semibold text-aksara-ink">Belum Ada Rencana Pembelajaran</h3>
                <p class="mt-2 text-sm text-aksara-muted">
                    Tidak ada data yang sesuai dengan kriteria filter.
                </p>
                <div class="mt-4 flex justify-center gap-2">
                    <Btn :href="createAiUrl" class="text-xs">+ Buat via Draf AI</Btn>
                    <Btn :href="createManualUrl" variant="secondary" class="text-xs">+ Buat Manual</Btn>
                </div>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="aksara-table w-full min-w-[720px]">
                    <thead>
                        <tr>
                            <th class="aksara-th">Topik Pembelajaran</th>
                            <th v-if="isAdmin" class="aksara-th">Guru Pengampu</th>
                            <th class="aksara-th">Mapel / Kelas</th>
                            <th class="aksara-th">Alur</th>
                            <th class="aksara-th text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="plan in plans.data" :key="plan.id" class="hover:bg-aksara-mist/30">
                            <td class="aksara-td">
                                <div class="font-semibold text-aksara-ink">{{ plan.topic }}</div>
                                <div class="mt-0.5 text-xs text-aksara-muted">
                                    {{ plan.durationMinutes }} menit · Fase {{ plan.phase }}
                                </div>
                                <div class="mt-1">
                                    <StatusBadge :status="plan.status" :label="plan.statusLabel" />
                                </div>
                            </td>
                            <td v-if="isAdmin" class="aksara-td text-xs">{{ plan.teacherName || '-' }}</td>
                            <td class="aksara-td text-xs">
                                <div>{{ plan.subjectName || '-' }}</div>
                                <div class="text-aksara-muted">Kelas {{ plan.className || '-' }}</div>
                            </td>
                            <td class="aksara-td">
                                <div class="flex flex-wrap gap-1 text-[11px]">
                                    <Link
                                        :href="plan.urls.openMaterial"
                                        method="post"
                                        as="button"
                                        class="rounded px-1.5 py-0.5 font-medium"
                                        :class="
                                            plan.materialPublished
                                                ? 'bg-emerald-100 text-emerald-800'
                                                : plan.hasMaterial
                                                  ? 'bg-amber-100 text-amber-800'
                                                  : 'bg-blue-100 text-blue-800'
                                        "
                                    >
                                        Materi
                                    </Link>
                                    <span
                                        class="rounded px-1.5 py-0.5 font-medium"
                                        :class="plan.hasQuiz ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500'"
                                    >
                                        Kuis
                                    </span>
                                    <span
                                        class="rounded px-1.5 py-0.5 font-medium"
                                        :class="
                                            plan.hasAttendance ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500'
                                        "
                                    >
                                        Absensi
                                    </span>
                                    <span
                                        class="rounded px-1.5 py-0.5 font-medium"
                                        :class="
                                            plan.hasEvaluation ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500'
                                        "
                                    >
                                        Refleksi
                                    </span>
                                </div>
                            </td>
                            <td class="aksara-td text-right">
                                <div class="flex flex-wrap items-center justify-end gap-1.5">
                                    <a :href="plan.urls.draft" class="aksara-btn-secondary !px-2.5 !py-1 text-xs">Review →</a>
                                    <a :href="plan.urls.edit" class="aksara-btn-secondary !px-2.5 !py-1 text-xs">Edit</a>
                                    <a :href="plan.urls.exportWord" class="text-xs font-bold text-blue-600">DOCX</a>
                                    <a :href="plan.urls.exportPdf" target="_blank" class="text-xs font-bold text-red-600">PDF</a>
                                    <template v-if="plan.isPublished">
                                        <a :href="plan.urls.attendance" class="text-xs text-aksara-muted hover:text-aksara-ink">Absensi</a>
                                        <a :href="plan.urls.quiz" class="text-xs text-aksara-muted hover:text-aksara-ink">Kuis</a>
                                        <a :href="plan.urls.evaluation" class="text-xs text-aksara-muted hover:text-aksara-ink">Evaluasi</a>
                                    </template>
                                    <button
                                        type="button"
                                        class="text-xs text-red-500 hover:text-red-700"
                                        @click="deletePlan(plan.urls.destroy)"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="plans.links?.length" class="mt-4 flex flex-wrap gap-2">
                    <Link
                        v-for="(link, i) in plans.links"
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

        <Modal
            :open="showImport"
            title="Import Modul Ajar"
            @close="showImport = false"
        >
            <ul v-if="importErrors?.length" class="mb-4 space-y-1 rounded-xl bg-aksara-danger/5 p-3 text-xs text-aksara-danger">
                <li v-for="(err, i) in importErrors" :key="i">• {{ err }}</li>
            </ul>

            <p class="text-xs text-aksara-muted">
                Unduh
                <a :href="importTemplateUrl" class="font-semibold text-aksara-teal hover:underline">template Excel</a>
                lalu unggah berkas .xlsx / .xls / .csv (maks. 5MB).
            </p>

            <Field class="mt-4" label="Berkas" :error="importForm.errors.importFile">
                <input type="file" accept=".xlsx,.xls,.csv" class="aksara-input" @change="onImportFile" />
            </Field>

            <template #footer>
                <Btn variant="secondary" size="sm" @click="showImport = false">Batal</Btn>
                <Btn size="sm" :disabled="importForm.processing" @click="submitImport">Impor</Btn>
            </template>
        </Modal>
    </AppLayout>
</template>
