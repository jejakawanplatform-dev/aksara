<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import Btn from '@/Components/ui/Btn.vue';
import Field from '@/Components/ui/Field.vue';
import Modal from '@/Components/ui/Modal.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import IconButton from '@/Components/ui/IconButton.vue';
import ExportMenu from '@/Components/ui/ExportMenu.vue';
import Icon from '@/Components/ui/Icon.vue';

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

const perPage = computed(() => Number(props.filters.per_page) || Number(props.plans.per_page) || 10);

const filterQuery = computed(() => ({
    search: localFilters.search || undefined,
    status: localFilters.status || undefined,
    teacher: localFilters.teacher || undefined,
    subject: localFilters.subject || undefined,
    per_page: perPage.value,
}));

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
                    ...filterQuery.value,
                    page: 1,
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

function materiClass(plan) {
    if (plan.materialPublished) return 'bg-aksara-ok/10 text-aksara-ok';
    if (plan.hasMaterial) return 'bg-aksara-warn/10 text-aksara-warn';
    return 'bg-aksara-info/10 text-aksara-info';
}

function flowClass(active) {
    return active ? 'bg-aksara-ok/10 text-aksara-ok' : 'bg-aksara-mist text-aksara-muted';
}

function exportItems(plan) {
    return [
        { label: 'Excel (.xlsx)', href: plan.urls.exportExcel, icon: 'download' },
        { label: 'Word (.docx)', href: plan.urls.exportWord, icon: 'document' },
        { label: 'PDF', href: plan.urls.exportPdf, icon: 'pdf', target: '_blank' },
    ];
}
</script>

<template>
    <AppLayout title="Rencana Pembelajaran">
        <template #header>Rencana Pembelajaran</template>

        <div class="space-y-5">
            <PageHeader
                title="Rencana Pembelajaran"
                :description="
                    isAdmin
                        ? 'Modul ajar seluruh guru di sekolah.'
                        : 'Modul ajar yang Anda kelola.'
                "
            >
                <template #actions>
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <div
                            class="inline-flex items-center gap-1 rounded-xl border border-aksara-line bg-white p-1 shadow-sm"
                            role="group"
                            aria-label="Aksi rencana"
                        >
                            <ExportMenu
                                label="Ekspor daftar"
                                icon="download"
                                :items="[
                                    { label: 'Excel (.xlsx)', href: exportUrls.excel, icon: 'download' },
                                    { label: 'Word (.docx)', href: exportUrls.word, icon: 'document' },
                                    { label: 'PDF', href: exportUrls.pdf, icon: 'pdf', target: '_blank' },
                                ]"
                            />
                            <IconButton icon="upload" label="Impor Excel" @click="showImport = true" />
                            <span class="mx-0.5 h-5 w-px bg-aksara-line" aria-hidden="true" />
                            <Btn :href="createAiUrl" size="sm" class="gap-1.5">
                                <Icon name="sparkles" class="h-3.5 w-3.5" />
                                Draf AI
                            </Btn>
                            <Btn :href="createManualUrl" variant="secondary" size="sm" class="gap-1.5">
                                <Icon name="plus" class="h-3.5 w-3.5" />
                                Manual
                            </Btn>
                        </div>
                    </div>
                </template>
            </PageHeader>

            <div class="aksara-surface p-4 sm:p-5">
                <div
                    class="grid grid-cols-1 gap-3"
                    :class="isAdmin ? 'md:grid-cols-2 xl:grid-cols-4' : 'md:grid-cols-3'"
                >
                    <Field label="Pencarian topik" for-id="search-plans">
                        <input
                            id="search-plans"
                            v-model="localFilters.search"
                            type="search"
                            class="aksara-input"
                            placeholder="Cari topik…"
                        />
                    </Field>
                    <Field v-if="isAdmin" label="Guru" for-id="filter-teacher">
                        <select id="filter-teacher" v-model="localFilters.teacher" class="aksara-select">
                            <option value="">Semua guru</option>
                            <option v-for="t in teachers" :key="t.id" :value="String(t.id)">{{ t.name }}</option>
                        </select>
                    </Field>
                    <Field label="Mapel" for-id="filter-subject">
                        <select id="filter-subject" v-model="localFilters.subject" class="aksara-select">
                            <option value="">Semua</option>
                            <option v-for="s in subjects" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
                        </select>
                    </Field>
                    <Field label="Status" for-id="filter-status">
                        <select id="filter-status" v-model="localFilters.status" class="aksara-select">
                            <option value="">Semua</option>
                            <option value="draft">Draf</option>
                            <option value="reviewed">Direview</option>
                            <option value="published">Diterbitkan</option>
                        </select>
                    </Field>
                </div>
            </div>

            <div v-if="!plans.data?.length" class="aksara-surface-dashed p-10 text-center">
                <h3 class="text-lg font-semibold text-aksara-ink">Belum ada rencana pembelajaran</h3>
                <p class="mt-2 text-sm text-aksara-muted">Tidak ada data yang sesuai filter.</p>
                <div class="mt-4 flex justify-center gap-2">
                    <Btn :href="createAiUrl" size="sm">+ Draf AI</Btn>
                    <Btn :href="createManualUrl" variant="secondary" size="sm">+ Manual</Btn>
                </div>
            </div>

            <div v-else class="aksara-surface overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="aksara-table w-full min-w-[880px]">
                        <thead>
                            <tr>
                                <th class="aksara-th">Topik</th>
                                <th v-if="isAdmin" class="aksara-th">Guru</th>
                                <th class="aksara-th">Mapel / Kelas</th>
                                <th class="aksara-th">Alur</th>
                                <th class="aksara-th w-44 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="plan in plans.data" :key="plan.id" class="hover:bg-aksara-mist/40">
                                <td class="aksara-td">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-medium text-aksara-ink">{{ plan.topic }}</span>
                                        <StatusBadge :status="plan.status" :label="plan.statusLabel" />
                                    </div>
                                    <div class="mt-0.5 text-xs text-aksara-muted">
                                        {{ plan.durationMinutes }} menit · Fase {{ plan.phase }}
                                    </div>
                                </td>
                                <td v-if="isAdmin" class="aksara-td text-sm">{{ plan.teacherName || '—' }}</td>
                                <td class="aksara-td text-sm">
                                    <div>{{ plan.subjectName || '—' }}</div>
                                    <div class="text-xs text-aksara-muted">Kelas {{ plan.className || '—' }}</div>
                                </td>
                                <td class="aksara-td">
                                    <div class="flex flex-nowrap gap-1">
                                        <Link
                                            :href="plan.urls.openMaterial"
                                            method="post"
                                            as="button"
                                            class="rounded-md px-1.5 py-0.5 text-[11px] font-semibold"
                                            :class="materiClass(plan)"
                                            :title="plan.materialPublished ? 'Materi terbit' : plan.hasMaterial ? 'Materi draf' : 'Buat materi'"
                                        >
                                            Materi
                                        </Link>
                                        <span class="rounded-md px-1.5 py-0.5 text-[11px] font-semibold" :class="flowClass(plan.hasQuiz)">
                                            Kuis
                                        </span>
                                        <span
                                            class="rounded-md px-1.5 py-0.5 text-[11px] font-semibold"
                                            :class="flowClass(plan.hasAttendance)"
                                        >
                                            Absensi
                                        </span>
                                        <span
                                            class="rounded-md px-1.5 py-0.5 text-[11px] font-semibold"
                                            :class="flowClass(plan.hasEvaluation)"
                                        >
                                            Refleksi
                                        </span>
                                    </div>
                                </td>
                                <td class="aksara-td">
                                    <div class="inline-flex flex-nowrap items-center justify-end gap-0.5">
                                        <IconButton icon="eye" label="Review" :href="plan.urls.draft" />
                                        <IconButton icon="pencil" label="Edit" :href="plan.urls.edit" />
                                        <ExportMenu
                                            label="Ekspor"
                                            icon="download"
                                            :items="exportItems(plan)"
                                        />
                                        <IconButton
                                            icon="attendance"
                                            label="Absensi"
                                            :href="plan.isPublished ? plan.urls.attendance : null"
                                            :disabled="!plan.isPublished"
                                        />
                                        <IconButton
                                            icon="quiz"
                                            label="Kuis"
                                            :href="plan.isPublished ? plan.urls.quiz : null"
                                            :disabled="!plan.isPublished"
                                        />
                                        <IconButton
                                            icon="evaluation"
                                            label="Evaluasi"
                                            :href="plan.isPublished ? plan.urls.evaluation : null"
                                            :disabled="!plan.isPublished"
                                        />
                                        <IconButton
                                            icon="trash"
                                            label="Hapus"
                                            danger
                                            @click="deletePlan(plan.urls.destroy)"
                                        />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-4 pb-4 sm:px-5">
                    <Pagination
                        :paginator="plans"
                        :per-page="perPage"
                        :base-url="indexUrl"
                        :query="filterQuery"
                    />
                </div>
            </div>
        </div>

        <Modal :open="showImport" title="Impor modul ajar" @close="showImport = false">
            <ul
                v-if="importErrors?.length"
                class="mb-4 space-y-1 rounded-xl border border-aksara-danger/20 bg-aksara-danger/5 p-3 text-xs text-aksara-danger"
            >
                <li v-for="(err, i) in importErrors" :key="i">• {{ err }}</li>
            </ul>

            <p class="text-sm text-aksara-muted">
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
