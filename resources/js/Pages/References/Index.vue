<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Field from '@/Components/ui/Field.vue';
import Btn from '@/Components/ui/Btn.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import Modal from '@/Components/ui/Modal.vue';
import IconButton from '@/Components/ui/IconButton.vue';
import Icon from '@/Components/ui/Icon.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import ExportMenu from '@/Components/ui/ExportMenu.vue';

const props = defineProps({
    pageTitle: { type: String, default: 'Referensi Kurikulum' },
    tab: { type: String, required: true },
    tabs: { type: Array, required: true },
    canManage: { type: Boolean, default: false },
    canManageCurrentSubject: { type: Boolean, default: false },
    filters: { type: Object, required: true },
    school: { type: Object, required: true },
    academic: { type: Object, required: true },
    years: { type: Array, default: () => [] },
    semesters: { type: Array, default: () => [] },
    rombels: { type: Object, default: () => ({ data: [] }) },
    subjects: { type: Object, default: () => ({ data: [] }) },
    subjectOptions: { type: Array, default: () => [] },
    cps: { type: Array, default: () => [] },
    atp: { type: Object, default: () => ({ data: [] }) },
    tpOptions: { type: Array, default: () => [] },
    homeroomCandidates: { type: Array, default: () => [] },
    allTeachers: { type: Array, default: () => [] },
    memberClass: { type: Object, default: null },
    availableStudents: { type: Array, default: () => [] },
    teacherEnrolledClassIds: { type: Array, default: () => [] },
    isTeacher: { type: Boolean, default: false },
    urls: { type: Object, required: true },
});

const visibleTabs = computed(() =>
    props.tabs.filter((t) => !t.adminOnly || props.canManage),
);

const perPage = computed(() => Number(props.filters.per_page) || 10);

const rombelRows = computed(() => props.rombels?.data ?? []);
const subjectRows = computed(() => props.subjects?.data ?? []);
const atpRows = computed(() => props.atp?.data ?? []);

const filterQuery = computed(() => ({
    tab: props.tab,
    subjectId: props.filters.subjectId || undefined,
    atpGradeFilter:
        props.filters.atpGradeFilter === null || props.filters.atpGradeFilter === undefined
            ? undefined
            : props.filters.atpGradeFilter,
    mapelScope: props.filters.mapelScope || undefined,
    membersClassId: props.filters.membersClassId || undefined,
    per_page: perPage.value,
}));

function navigate(overrides = {}) {
    const resetPage =
        overrides.tab !== undefined ||
        overrides.subjectId !== undefined ||
        overrides.atpGradeFilter !== undefined ||
        overrides.mapelScope !== undefined ||
        overrides.page === 1;

    router.get(
        props.urls.index,
        {
            tab: overrides.tab ?? props.tab,
            subjectId: overrides.subjectId ?? props.filters.subjectId ?? undefined,
            atpGradeFilter:
                overrides.atpGradeFilter !== undefined
                    ? overrides.atpGradeFilter
                    : props.filters.atpGradeFilter ?? undefined,
            mapelScope: overrides.mapelScope ?? props.filters.mapelScope ?? undefined,
            membersClassId:
                overrides.membersClassId !== undefined
                    ? overrides.membersClassId
                    : props.filters.membersClassId ?? undefined,
            per_page: overrides.per_page ?? perPage.value,
            page: resetPage ? 1 : overrides.page,
        },
        { preserveState: true, replace: true },
    );
}

function setTab(tab) {
    navigate({ tab, membersClassId: null });
}

function urlReplace(template, map) {
    let url = template;
    Object.entries(map).forEach(([k, v]) => {
        url = url.replace(k, String(v));
    });
    return url;
}

function exportCurriculumItems(kind) {
    const subjectId = props.filters.subjectId;
    if (!subjectId) return [];
    const template = kind === 'atp' ? props.urls.exportAtp : props.urls.exportCpTp;
    return [
        {
            label: 'Excel (.xlsx)',
            href: urlReplace(template, { __ID__: subjectId, __FMT__: 'excel' }),
            icon: 'download',
        },
        {
            label: 'Word (.docx)',
            href: urlReplace(template, { __ID__: subjectId, __FMT__: 'word' }),
            icon: 'document',
        },
        {
            label: 'PDF',
            href: urlReplace(template, { __ID__: subjectId, __FMT__: 'pdf' }),
            icon: 'pdf',
            target: '_blank',
        },
    ];
}

const schoolForm = useForm({ ...props.school });
const academicForm = useForm({ ...props.academic });

watch(() => props.school, (s) => Object.assign(schoolForm, s), { deep: true });
watch(() => props.academic, (a) => Object.assign(academicForm, a), { deep: true });

function saveSchool() {
    schoolForm.post(props.urls.school, { preserveScroll: true });
}
function saveAcademic() {
    academicForm.post(props.urls.academic, { preserveScroll: true });
}

/* Generic CRUD modal */
const showForm = ref(false);
const formEntity = ref('');
const editingId = ref(null);
const form = useForm({});

function openCreate(entity, defaults = {}) {
    formEntity.value = entity;
    editingId.value = null;
    form.clearErrors();
    Object.keys(form.data()).forEach((k) => delete form[k]);
    Object.assign(form, defaults);
    showForm.value = true;
}

function openEdit(entity, id, data) {
    formEntity.value = entity;
    editingId.value = id;
    form.clearErrors();
    Object.keys(form.data()).forEach((k) => delete form[k]);
    Object.assign(form, data);
    showForm.value = true;
}

function closeForm() {
    showForm.value = false;
    formEntity.value = '';
    editingId.value = null;
}

const entityRoutes = {
    year: { store: 'yearsStore', update: 'yearsUpdate', destroy: 'yearsDestroy', idKey: '__ID__' },
    semester: { store: 'semestersStore', update: 'semestersUpdate', destroy: 'semestersDestroy', idKey: '__ID__' },
    rombel: { store: 'rombelsStore', update: 'rombelsUpdate', destroy: 'rombelsDestroy', idKey: '__ID__' },
    mapel: { store: 'mapelStore', update: 'mapelUpdate', destroy: 'mapelDestroy', idKey: '__ID__' },
    cp: { store: 'cpsStore', update: 'cpsUpdate', destroy: 'cpsDestroy', idKey: '__ID__' },
    tp: { store: 'tpsStore', update: 'tpsUpdate', destroy: 'tpsDestroy', idKey: '__ID__' },
    atp: { store: 'atpStore', update: 'atpUpdate', destroy: 'atpDestroy', idKey: '__ID__' },
};

function saveEntity() {
    const cfg = entityRoutes[formEntity.value];
    if (!cfg) return;
    const opts = { onSuccess: () => closeForm(), preserveScroll: true };
    if (editingId.value) {
        form.put(urlReplace(props.urls[cfg.update], { [cfg.idKey]: editingId.value }), opts);
    } else {
        form.post(props.urls[cfg.store], opts);
    }
}

function deleteEntity(entity, id) {
    if (!window.confirm('Hapus data ini?')) return;
    const cfg = entityRoutes[entity];
    router.delete(urlReplace(props.urls[cfg.destroy], { [cfg.idKey]: id }), { preserveScroll: true });
}

function activateSemester(id) {
    router.post(urlReplace(props.urls.semestersActivate, { __ID__: id }), {}, { preserveScroll: true });
}

function toggleEnrol(id) {
    router.post(urlReplace(props.urls.rombelsEnrol, { __ID__: id }), {}, { preserveScroll: true });
}

function openMembers(id) {
    navigate({ tab: 'rombel', membersClassId: id });
}

function closeMembers() {
    navigate({ tab: 'rombel', membersClassId: null });
}

const attachStudentForm = useForm({ student_id: '' });
function attachStudent() {
    if (!props.memberClass) return;
    attachStudentForm.post(urlReplace(props.urls.rombelsAttachStudent, { __ID__: props.memberClass.id }), {
        onSuccess: () => attachStudentForm.reset(),
        preserveScroll: true,
    });
}
function detachStudent(studentId) {
    if (!props.memberClass) return;
    router.delete(
        urlReplace(props.urls.rombelsDetachStudent, {
            __RID__: props.memberClass.id,
            __SID__: studentId,
        }),
        { preserveScroll: true },
    );
}

const showTeachersModal = ref(false);
const teachersSubjectId = ref(null);
const teachersForm = useForm({ teacher_ids: [] });

function openTeachers(subject) {
    teachersSubjectId.value = subject.id;
    teachersForm.teacher_ids = (subject.teacherIds || []).map(String);
    showTeachersModal.value = true;
}

function saveTeachers() {
    teachersForm
        .transform((data) => ({
            teacher_ids: data.teacher_ids.map((id) => Number(id)),
        }))
        .post(urlReplace(props.urls.mapelTeachers, { __ID__: teachersSubjectId.value }), {
            onSuccess: () => {
                showTeachersModal.value = false;
            },
            preserveScroll: true,
        });
}

const importForm = useForm({ subject_id: '', importFile: null });
const showImport = ref(null); // 'cp-tp' | 'atp' | null

function openImport(kind) {
    importForm.subject_id = String(props.filters.subjectId || '');
    importForm.importFile = null;
    showImport.value = kind;
}

function submitImport() {
    const url = showImport.value === 'atp' ? props.urls.importAtp : props.urls.importCpTp;
    importForm.post(url, {
        forceFormData: true,
        onSuccess: () => {
            showImport.value = null;
        },
    });
}

/** CP accordion — daftar TP collapsible per elemen */
const expandedCpIds = ref([]);

watch(
    () => props.cps.map((c) => c.id).join(','),
    () => {
        const ids = props.cps.map((c) => c.id);
        const kept = expandedCpIds.value.filter((id) => ids.includes(id));
        expandedCpIds.value = kept.length ? kept : ids.slice(0, 1);
    },
    { immediate: true },
);

function isCpExpanded(id) {
    return expandedCpIds.value.includes(id);
}

function toggleCp(id) {
    if (isCpExpanded(id)) {
        expandedCpIds.value = expandedCpIds.value.filter((x) => x !== id);
    } else {
        expandedCpIds.value = [...expandedCpIds.value, id];
    }
}

const formTitle = computed(() => {
    const labels = {
        year: 'Tahun Ajaran',
        semester: 'Semester',
        rombel: 'Rombel',
        mapel: 'Mata Pelajaran',
        cp: 'Capaian Pembelajaran',
        tp: 'Tujuan Pembelajaran',
        atp: 'Item ATP',
    };
    return `${editingId.value ? 'Edit' : 'Tambah'} ${labels[formEntity.value] || ''}`;
});
</script>

<template>
    <AppLayout :title="pageTitle">
        <template #header>{{ pageTitle }}</template>

        <div class="space-y-5">
            <PageHeader
                :title="pageTitle"
                description="Master data akademik, kurikulum, dan rombel."
            />

            <div class="rounded-xl border border-aksara-info/25 bg-aksara-info/5 p-4 text-sm text-aksara-ink">
                Referensi kurikulum untuk bimtek — data CP/TP/ATP Informatika bersifat adaptasi workshop.
            </div>

            <div class="aksara-surface overflow-hidden">
                <div class="flex flex-wrap gap-1 overflow-x-auto border-b border-aksara-line px-4 pt-2 text-xs font-semibold sm:px-5">
                    <button
                        v-for="t in visibleTabs"
                        :key="t.key"
                        type="button"
                        class="border-b-2 px-3 py-2.5 transition"
                        :class="tab === t.key ? 'border-aksara-teal text-aksara-teal' : 'border-transparent text-aksara-muted'"
                        @click="setTab(t.key)"
                    >
                        {{ t.label }}
                    </button>
                </div>

                <div class="p-4 sm:p-5">
                <!-- Profil -->
                <div v-if="tab === 'profil'" class="space-y-4">
                    <h3 class="text-base font-semibold text-aksara-ink">Profil Sekolah & Branding Institusi</h3>
                    <form class="grid grid-cols-1 gap-3 md:grid-cols-2" @submit.prevent="saveSchool">
                        <Field label="Nama Sekolah" for-id="school_name">
                            <input id="school_name" v-model="schoolForm.name" class="aksara-input" />
                        </Field>
                        <Field label="NPSN" for-id="school_npsn">
                            <input id="school_npsn" v-model="schoolForm.npsn" class="aksara-input" />
                        </Field>
                        <Field label="Alamat" for-id="school_address" class="md:col-span-2">
                            <textarea id="school_address" v-model="schoolForm.address" class="aksara-input" rows="2" />
                        </Field>
                        <Field label="Kepala Sekolah" for-id="school_headmaster">
                            <input id="school_headmaster" v-model="schoolForm.headmaster" class="aksara-input" />
                        </Field>
                        <Field label="Telepon" for-id="school_phone">
                            <input id="school_phone" v-model="schoolForm.phone" class="aksara-input" />
                        </Field>
                        <div class="aksara-form-actions md:col-span-2">
                            <Btn type="submit" :disabled="schoolForm.processing">Simpan profil</Btn>
                        </div>
                    </form>
                </div>

                <!-- Operasional -->
                <div v-else-if="tab === 'operasional'" class="space-y-4">
                    <h3 class="text-base font-semibold text-aksara-ink">Pengaturan Operasional Akademik</h3>
                    <form class="grid grid-cols-1 gap-3 md:grid-cols-3" @submit.prevent="saveAcademic">
                        <Field label="Nilai kelulusan KKM" for-id="pass_score">
                            <input id="pass_score" v-model.number="academicForm.passing_score" type="number" class="aksara-input" />
                        </Field>
                        <Field label="Batas percobaan kuis" for-id="quiz_limit">
                            <input id="quiz_limit" v-model.number="academicForm.quiz_attempt_limit" type="number" class="aksara-input" />
                        </Field>
                        <Field label="Toleransi absensi (menit)" for-id="att_tol">
                            <input id="att_tol" v-model.number="academicForm.attendance_tolerance_minutes" type="number" class="aksara-input" />
                        </Field>
                        <div class="aksara-form-actions md:col-span-3">
                            <Btn type="submit" :disabled="academicForm.processing">Simpan operasional</Btn>
                        </div>
                    </form>
                </div>

                <!-- Tahun -->
                <div v-else-if="tab === 'tahun'" class="space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-aksara-ink">Tahun Ajaran</h3>
                        <div v-if="canManage" class="aksara-toolbar">
                            <Btn
                                type="button"
                                size="sm"
                                class="gap-1.5"
                                @click="openCreate('year', { name: '', code: '', starts_on: '', ends_on: '', is_active: false })"
                            >
                                <Icon name="plus" class="h-3.5 w-3.5" />
                                Tambah
                            </Btn>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="aksara-table w-full min-w-[480px]">
                            <thead>
                                <tr>
                                    <th class="aksara-th">Nama</th>
                                    <th class="aksara-th">Kode</th>
                                    <th class="aksara-th">Status</th>
                                    <th v-if="canManage" class="aksara-th w-28 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="y in years" :key="y.id" class="hover:bg-aksara-mist/40">
                                    <td class="aksara-td font-medium">{{ y.name }}</td>
                                    <td class="aksara-td text-aksara-muted">{{ y.code }}</td>
                                    <td class="aksara-td">
                                        <StatusBadge :status="y.is_active ? 'published' : 'draft'" :label="y.is_active ? 'Aktif' : 'Nonaktif'" />
                                    </td>
                                    <td v-if="canManage" class="aksara-td">
                                        <div class="flex items-center justify-end gap-0.5">
                                            <IconButton
                                                icon="pencil"
                                                label="Edit"
                                                @click="openEdit('year', y.id, { name: y.name, code: y.code, starts_on: y.starts_on || '', ends_on: y.ends_on || '', is_active: y.is_active })"
                                            />
                                            <IconButton icon="trash" label="Hapus" danger @click="deleteEntity('year', y.id)" />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Semester -->
                <div v-else-if="tab === 'semester'" class="space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-aksara-ink">Semester</h3>
                        <div v-if="canManage" class="aksara-toolbar">
                            <Btn
                                type="button"
                                size="sm"
                                class="gap-1.5"
                                @click="openCreate('semester', { academic_year_id: years[0]?.id || '', name: 'Ganjil', code: 'ganjil', number: 1, starts_on: '', ends_on: '', is_active: false })"
                            >
                                <Icon name="plus" class="h-3.5 w-3.5" />
                                Tambah
                            </Btn>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="aksara-table w-full min-w-[480px]">
                            <thead>
                                <tr>
                                    <th class="aksara-th">Nama</th>
                                    <th class="aksara-th">Tahun</th>
                                    <th class="aksara-th">Status</th>
                                    <th v-if="canManage" class="aksara-th w-28 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="s in semesters" :key="s.id" class="hover:bg-aksara-mist/40">
                                    <td class="aksara-td font-medium">{{ s.name }}</td>
                                    <td class="aksara-td text-aksara-muted">{{ s.yearName }}</td>
                                    <td class="aksara-td">
                                        <StatusBadge :status="s.is_active ? 'published' : 'draft'" :label="s.is_active ? 'Aktif' : 'Nonaktif'" />
                                    </td>
                                    <td v-if="canManage" class="aksara-td">
                                        <div class="flex flex-nowrap items-center justify-end gap-0.5">
                                            <IconButton
                                                v-if="!s.is_active"
                                                icon="check"
                                                label="Aktifkan"
                                                @click="activateSemester(s.id)"
                                            />
                                            <IconButton
                                                icon="pencil"
                                                label="Edit"
                                                @click="openEdit('semester', s.id, { academic_year_id: s.academic_year_id, name: s.name, code: s.code, number: s.number, starts_on: s.starts_on || '', ends_on: s.ends_on || '', is_active: s.is_active })"
                                            />
                                            <IconButton icon="trash" label="Hapus" danger @click="deleteEntity('semester', s.id)" />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Rombel -->
                <div v-else-if="tab === 'rombel'" class="space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-aksara-ink">Rombel</h3>
                        <div v-if="canManage" class="aksara-toolbar">
                            <Btn
                                type="button"
                                size="sm"
                                class="gap-1.5"
                                @click="openCreate('rombel', { academic_year_id: years[0]?.id || '', name: '', rombel_code: '', grade: 7, homeroom_teacher_id: '' })"
                            >
                                <Icon name="plus" class="h-3.5 w-3.5" />
                                Tambah
                            </Btn>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="aksara-table w-full min-w-[560px]">
                            <thead>
                                <tr>
                                    <th class="aksara-th">Nama</th>
                                    <th class="aksara-th">Kelas</th>
                                    <th class="aksara-th">Wali</th>
                                    <th class="aksara-th">Siswa</th>
                                    <th class="aksara-th w-32 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="r in rombelRows" :key="r.id" class="hover:bg-aksara-mist/40">
                                    <td class="aksara-td font-medium">{{ r.name }}</td>
                                    <td class="aksara-td">{{ r.grade }}</td>
                                    <td class="aksara-td text-aksara-muted">{{ r.homeroomName || '—' }}</td>
                                    <td class="aksara-td">{{ r.students_count }}</td>
                                    <td class="aksara-td">
                                        <div class="flex flex-nowrap items-center justify-end gap-0.5">
                                            <IconButton
                                                v-if="canManage"
                                                icon="users"
                                                label="Anggota"
                                                @click="openMembers(r.id)"
                                            />
                                            <IconButton
                                                v-if="isTeacher"
                                                :icon="teacherEnrolledClassIds.includes(r.id) ? 'x-mark' : 'check'"
                                                :label="teacherEnrolledClassIds.includes(r.id) ? 'Batal enrol' : 'Enrol ajar'"
                                                @click="toggleEnrol(r.id)"
                                            />
                                            <template v-if="canManage">
                                                <IconButton
                                                    icon="pencil"
                                                    label="Edit"
                                                    @click="openEdit('rombel', r.id, { academic_year_id: r.academic_year_id, name: r.name, rombel_code: r.rombel_code, grade: r.grade, homeroom_teacher_id: r.homeroom_teacher_id || '' })"
                                                />
                                                <IconButton icon="trash" label="Hapus" danger @click="deleteEntity('rombel', r.id)" />
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination
                        :paginator="rombels"
                        :per-page="perPage"
                        :base-url="urls.index"
                        :query="filterQuery"
                    />
                </div>

                <!-- Mapel -->
                <div v-else-if="tab === 'mapel'" class="space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-base font-semibold text-aksara-ink">Mata Pelajaran</h3>
                        <div class="aksara-toolbar">
                            <select
                                v-if="!canManage"
                                class="aksara-select !w-auto min-w-[9rem] !border-0 !shadow-none text-xs"
                                :value="filters.mapelScope"
                                @change="navigate({ mapelScope: $event.target.value })"
                            >
                                <option value="my">Mapel saya</option>
                                <option value="all">Semua</option>
                            </select>
                            <Btn
                                v-if="canManage"
                                type="button"
                                size="sm"
                                class="gap-1.5"
                                @click="openCreate('mapel', { name: '', code: '', phase: 'D', jenjang: 'SMP', description: '' })"
                            >
                                <Icon name="plus" class="h-3.5 w-3.5" />
                                Tambah
                            </Btn>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="aksara-table w-full min-w-[480px]">
                            <thead>
                                <tr>
                                    <th class="aksara-th">Nama</th>
                                    <th class="aksara-th">Kode</th>
                                    <th class="aksara-th">Guru</th>
                                    <th v-if="canManage" class="aksara-th w-28 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="s in subjectRows" :key="s.id" class="hover:bg-aksara-mist/40">
                                    <td class="aksara-td font-medium">{{ s.name }}</td>
                                    <td class="aksara-td">{{ s.code }}</td>
                                    <td class="aksara-td text-xs text-aksara-muted">{{ (s.teacherNames || []).join(', ') || '—' }}</td>
                                    <td v-if="canManage" class="aksara-td">
                                        <div class="flex flex-nowrap items-center justify-end gap-0.5">
                                            <IconButton icon="users" label="Plotting guru" @click="openTeachers(s)" />
                                            <IconButton
                                                icon="pencil"
                                                label="Edit"
                                                @click="openEdit('mapel', s.id, { name: s.name, code: s.code, phase: s.phase, jenjang: s.jenjang, description: s.description || '' })"
                                            />
                                            <IconButton icon="trash" label="Hapus" danger @click="deleteEntity('mapel', s.id)" />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination
                        :paginator="subjects"
                        :per-page="perPage"
                        :base-url="urls.index"
                        :query="filterQuery"
                    />
                </div>

                <!-- CP & TP -->
                <div v-else-if="tab === 'cp'" class="space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex min-w-0 flex-wrap items-center gap-3">
                            <h3 class="text-base font-semibold text-aksara-ink">CP & TP</h3>
                            <select
                                class="aksara-select !w-auto min-w-[11rem] max-w-[16rem] text-xs"
                                :value="filters.subjectId"
                                @change="navigate({ subjectId: $event.target.value })"
                            >
                                <option v-for="s in subjectOptions" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                        <div v-if="canManageCurrentSubject" class="aksara-toolbar">
                            <ExportMenu
                                label="Ekspor CP/TP"
                                icon="download"
                                :items="exportCurriculumItems('cp')"
                            />
                            <IconButton icon="upload" label="Impor" @click="openImport('cp-tp')" />
                            <span class="mx-0.5 h-5 w-px bg-aksara-line" aria-hidden="true" />
                            <Btn
                                type="button"
                                size="sm"
                                class="gap-1.5"
                                @click="openCreate('cp', { subject_id: filters.subjectId, phase: 'D', element_code: '', element_name: '', statement: '', source_note: '', sequence: 1 })"
                            >
                                <Icon name="plus" class="h-3.5 w-3.5" />
                                CP
                            </Btn>
                        </div>
                    </div>
                    <div
                        v-for="cp in cps"
                        :key="cp.id"
                        class="rounded-xl border bg-white transition-colors"
                        :class="isCpExpanded(cp.id) ? 'border-aksara-teal/35' : 'border-aksara-line'"
                    >
                        <div class="flex items-start gap-1 p-3 sm:p-4">
                            <button
                                type="button"
                                class="aksara-icon-btn mt-0.5"
                                :aria-expanded="isCpExpanded(cp.id)"
                                :aria-label="isCpExpanded(cp.id) ? 'Tutup TP' : 'Buka TP'"
                                @click="toggleCp(cp.id)"
                            >
                                <Icon
                                    :name="isCpExpanded(cp.id) ? 'chevron-down' : 'chevron-right'"
                                    class="h-4 w-4"
                                />
                            </button>
                            <button
                                type="button"
                                class="min-w-0 flex-1 rounded-md px-1 py-0.5 text-left outline-none focus-visible:ring-2 focus-visible:ring-aksara-teal/30 focus-visible:ring-offset-1"
                                :aria-expanded="isCpExpanded(cp.id)"
                                @click="toggleCp(cp.id)"
                            >
                                <p class="font-semibold text-aksara-ink">
                                    {{ cp.element_name }}
                                    <span class="text-xs font-medium text-aksara-muted">({{ cp.element_code }})</span>
                                </p>
                                <p class="mt-1 text-sm leading-relaxed text-aksara-muted">{{ cp.statement }}</p>
                                <p v-if="!isCpExpanded(cp.id)" class="mt-1.5 text-xs text-aksara-muted">
                                    {{ (cp.tps || []).length }} TP
                                </p>
                            </button>
                            <div
                                v-if="canManageCurrentSubject"
                                class="aksara-toolbar"
                                @click.stop
                            >
                                <IconButton
                                    icon="plus"
                                    label="Tambah TP"
                                    @click="openCreate('tp', { curriculum_cp_id: cp.id, code: '', statement: '', grade: 7, sequence: 1 })"
                                />
                                <IconButton
                                    icon="pencil"
                                    label="Edit CP"
                                    @click="openEdit('cp', cp.id, { subject_id: cp.subject_id, phase: cp.phase, element_code: cp.element_code, element_name: cp.element_name, statement: cp.statement, source_note: cp.source_note || '', sequence: cp.sequence })"
                                />
                                <IconButton icon="trash" label="Hapus CP" danger @click="deleteEntity('cp', cp.id)" />
                            </div>
                        </div>
                        <ul
                            v-show="isCpExpanded(cp.id)"
                            class="space-y-2 border-t border-aksara-line px-3 pb-3 pt-3 sm:px-4 sm:pb-4"
                        >
                            <li
                                v-for="tp in cp.tps"
                                :key="tp.id"
                                class="flex items-start gap-3 rounded-lg px-1 py-1 text-sm"
                            >
                                <span class="min-w-0 flex-1 text-aksara-ink"><strong>{{ tp.code }}</strong> — <span class="text-aksara-muted">{{ tp.statement }}</span></span>
                                <span v-if="canManageCurrentSubject" class="flex shrink-0 flex-nowrap items-center gap-0.5">
                                    <IconButton
                                        icon="pencil"
                                        label="Edit TP"
                                        @click="openEdit('tp', tp.id, { curriculum_cp_id: tp.curriculum_cp_id, code: tp.code, statement: tp.statement, grade: tp.grade, sequence: tp.sequence })"
                                    />
                                    <IconButton icon="trash" label="Hapus TP" danger @click="deleteEntity('tp', tp.id)" />
                                </span>
                            </li>
                            <li v-if="!(cp.tps || []).length" class="px-1 text-sm text-aksara-muted">
                                Belum ada TP pada elemen ini.
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- ATP -->
                <div v-else-if="tab === 'atp'" class="space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex min-w-0 flex-wrap items-center gap-3">
                            <h3 class="text-base font-semibold text-aksara-ink">ATP</h3>
                            <select
                                class="aksara-select !w-auto min-w-[11rem] max-w-[16rem] text-xs"
                                :value="filters.subjectId"
                                @change="navigate({ subjectId: $event.target.value })"
                            >
                                <option v-for="s in subjectOptions" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                            <select
                                class="aksara-select !w-auto min-w-[8rem] text-xs"
                                :value="filters.atpGradeFilter ?? ''"
                                @change="navigate({ atpGradeFilter: $event.target.value === '' ? '' : Number($event.target.value) })"
                            >
                                <option value="">Semua kelas</option>
                                <option :value="7">Kelas 7</option>
                                <option :value="8">Kelas 8</option>
                                <option :value="9">Kelas 9</option>
                            </select>
                        </div>
                        <div v-if="canManageCurrentSubject" class="aksara-toolbar">
                            <ExportMenu
                                label="Ekspor ATP"
                                icon="download"
                                :items="exportCurriculumItems('atp')"
                            />
                            <IconButton icon="upload" label="Impor" @click="openImport('atp')" />
                            <span class="mx-0.5 h-5 w-px bg-aksara-line" aria-hidden="true" />
                            <Btn
                                type="button"
                                size="sm"
                                class="gap-1.5"
                                @click="openCreate('atp', { subject_id: filters.subjectId, academic_year_id: years.find(y => y.is_active)?.id || years[0]?.id || '', semester_id: '', curriculum_tp_id: '', grade: filters.atpGradeFilter || 7, sequence: 1, unit_title: '', estimated_meetings: 2 })"
                            >
                                <Icon name="plus" class="h-3.5 w-3.5" />
                                ATP
                            </Btn>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="aksara-table w-full min-w-[560px]">
                            <thead>
                                <tr>
                                    <th class="aksara-th">Urut</th>
                                    <th class="aksara-th">TP</th>
                                    <th class="aksara-th">Unit</th>
                                    <th class="aksara-th">Kelas</th>
                                    <th v-if="canManageCurrentSubject" class="aksara-th w-28 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in atpRows" :key="item.id" class="hover:bg-aksara-mist/40">
                                    <td class="aksara-td">{{ item.sequence }}</td>
                                    <td class="aksara-td font-medium">{{ item.tpCode }}</td>
                                    <td class="aksara-td text-aksara-muted">{{ item.unit_title || '—' }}</td>
                                    <td class="aksara-td">{{ item.grade }}</td>
                                    <td v-if="canManageCurrentSubject" class="aksara-td">
                                        <div class="flex items-center justify-end gap-0.5">
                                            <IconButton
                                                icon="pencil"
                                                label="Edit"
                                                @click="openEdit('atp', item.id, { subject_id: item.subject_id, academic_year_id: item.academic_year_id, semester_id: item.semester_id || '', curriculum_tp_id: item.curriculum_tp_id, grade: item.grade, sequence: item.sequence, unit_title: item.unit_title || '', estimated_meetings: item.estimated_meetings })"
                                            />
                                            <IconButton icon="trash" label="Hapus" danger @click="deleteEntity('atp', item.id)" />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination
                        :paginator="atp"
                        :per-page="perPage"
                        :base-url="urls.index"
                        :query="filterQuery"
                    />
                </div>
                </div>
            </div>
        </div>

        <Modal :open="showForm" :title="formTitle" @close="closeForm">
            <form id="refs-entity-form" class="space-y-3" @submit.prevent="saveEntity">
                <template v-if="formEntity === 'year'">
                    <Field label="Nama Tahun Ajaran" required for-id="year_name"><input id="year_name" v-model="form.name" class="aksara-input" /></Field>
                    <Field label="Kode" required for-id="year_code"><input id="year_code" v-model="form.code" class="aksara-input" /></Field>
                    <Field label="Mulai" for-id="year_start"><input id="year_start" v-model="form.starts_on" type="date" class="aksara-input" /></Field>
                    <Field label="Selesai" for-id="year_end"><input id="year_end" v-model="form.ends_on" type="date" class="aksara-input" /></Field>
                    <label class="flex items-center gap-2 text-sm"><input v-model="form.is_active" type="checkbox" class="rounded" /> Set Sebagai Tahun Ajaran Aktif</label>
                </template>
                <template v-else-if="formEntity === 'semester'">
                    <Field label="Tahun Ajaran" required for-id="sem_year">
                        <select id="sem_year" v-model="form.academic_year_id" class="aksara-select">
                            <option v-for="y in years" :key="y.id" :value="y.id">{{ y.name }}</option>
                        </select>
                    </Field>
                    <Field label="Nama" required for-id="sem_name"><input id="sem_name" v-model="form.name" class="aksara-input" /></Field>
                    <Field label="Kode" required for-id="sem_code"><input id="sem_code" v-model="form.code" class="aksara-input" /></Field>
                    <Field label="Nomor" required for-id="sem_num"><input id="sem_num" v-model.number="form.number" type="number" class="aksara-input" /></Field>
                    <label class="flex items-center gap-2 text-sm"><input v-model="form.is_active" type="checkbox" class="rounded" /> Aktif</label>
                </template>
                <template v-else-if="formEntity === 'rombel'">
                    <Field label="Tahun Ajaran" required for-id="rom_year">
                        <select id="rom_year" v-model="form.academic_year_id" class="aksara-select">
                            <option v-for="y in years" :key="y.id" :value="y.id">{{ y.name }}</option>
                        </select>
                    </Field>
                    <Field label="Nama" required for-id="rom_name"><input id="rom_name" v-model="form.name" class="aksara-input" /></Field>
                    <Field label="Kode" for-id="rom_code"><input id="rom_code" v-model="form.rombel_code" class="aksara-input" /></Field>
                    <Field label="Grade" required for-id="rom_grade"><input id="rom_grade" v-model.number="form.grade" type="number" class="aksara-input" /></Field>
                    <Field label="Wali kelas" for-id="rom_home">
                        <select id="rom_home" v-model="form.homeroom_teacher_id" class="aksara-select">
                            <option value="">—</option>
                            <option v-for="t in homeroomCandidates" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </Field>
                </template>
                <template v-else-if="formEntity === 'mapel'">
                    <Field label="Nama" required for-id="map_name"><input id="map_name" v-model="form.name" class="aksara-input" /></Field>
                    <Field label="Kode" required for-id="map_code"><input id="map_code" v-model="form.code" class="aksara-input" /></Field>
                    <Field label="Fase" required for-id="map_phase"><input id="map_phase" v-model="form.phase" class="aksara-input" /></Field>
                    <Field label="Jenjang" required for-id="map_jenjang"><input id="map_jenjang" v-model="form.jenjang" class="aksara-input" /></Field>
                    <Field label="Deskripsi" for-id="map_desc"><textarea id="map_desc" v-model="form.description" class="aksara-input" rows="2" /></Field>
                </template>
                <template v-else-if="formEntity === 'cp'">
                    <Field label="Mapel" required for-id="cp_sub">
                        <select id="cp_sub" v-model="form.subject_id" class="aksara-select">
                            <option v-for="s in subjectOptions" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </Field>
                    <Field label="Fase" required for-id="cp_phase"><input id="cp_phase" v-model="form.phase" class="aksara-input" /></Field>
                    <Field label="Kode elemen" required for-id="cp_ecode"><input id="cp_ecode" v-model="form.element_code" class="aksara-input" /></Field>
                    <Field label="Nama elemen" required for-id="cp_ename"><input id="cp_ename" v-model="form.element_name" class="aksara-input" /></Field>
                    <Field label="Pernyataan" required for-id="cp_stmt"><textarea id="cp_stmt" v-model="form.statement" class="aksara-input" rows="3" /></Field>
                    <Field label="Urutan" required for-id="cp_seq"><input id="cp_seq" v-model.number="form.sequence" type="number" class="aksara-input" /></Field>
                </template>
                <template v-else-if="formEntity === 'tp'">
                    <Field label="Kode TP" required for-id="tp_code"><input id="tp_code" v-model="form.code" class="aksara-input" /></Field>
                    <Field label="Pernyataan" required for-id="tp_stmt"><textarea id="tp_stmt" v-model="form.statement" class="aksara-input" rows="3" /></Field>
                    <Field label="Kelas" for-id="tp_grade"><input id="tp_grade" v-model.number="form.grade" type="number" class="aksara-input" /></Field>
                    <Field label="Urutan" required for-id="tp_seq"><input id="tp_seq" v-model.number="form.sequence" type="number" class="aksara-input" /></Field>
                </template>
                <template v-else-if="formEntity === 'atp'">
                    <Field label="Mapel" required for-id="atp_sub">
                        <select id="atp_sub" v-model="form.subject_id" class="aksara-select">
                            <option v-for="s in subjectOptions" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </Field>
                    <Field label="Tahun Ajaran" required for-id="atp_year">
                        <select id="atp_year" v-model="form.academic_year_id" class="aksara-select">
                            <option v-for="y in years" :key="y.id" :value="y.id">{{ y.name }}</option>
                        </select>
                    </Field>
                    <Field label="TP" required for-id="atp_tp">
                        <select id="atp_tp" v-model="form.curriculum_tp_id" class="aksara-select">
                            <option v-for="tp in tpOptions" :key="tp.id" :value="tp.id">{{ tp.code }}</option>
                        </select>
                    </Field>
                    <Field label="Judul unit" for-id="atp_unit"><input id="atp_unit" v-model="form.unit_title" class="aksara-input" /></Field>
                    <Field label="Kelas" required for-id="atp_grade"><input id="atp_grade" v-model.number="form.grade" type="number" class="aksara-input" /></Field>
                    <Field label="Urutan" required for-id="atp_seq"><input id="atp_seq" v-model.number="form.sequence" type="number" class="aksara-input" /></Field>
                </template>
            </form>
            <template #footer>
                <Btn type="button" variant="secondary" size="sm" @click="closeForm">Batal</Btn>
                <Btn type="submit" form="refs-entity-form" size="sm" :disabled="form.processing">Simpan</Btn>
            </template>
        </Modal>

        <Modal
            :open="!!memberClass"
            :title="memberClass ? `Anggota — ${memberClass.name}` : 'Anggota'"
            max-width="md"
            @close="closeMembers"
        >
            <template v-if="memberClass">
                <ul class="space-y-2 text-sm">
                    <li v-for="s in memberClass.students" :key="s.id" class="flex justify-between">
                        <span>{{ s.name }}</span>
                        <button type="button" class="text-xs text-aksara-danger" @click="detachStudent(s.id)">Lepas</button>
                    </li>
                </ul>
                <div class="mt-4 flex gap-2">
                    <select v-model="attachStudentForm.student_id" class="aksara-select flex-1">
                        <option value="">Pilih siswa</option>
                        <option v-for="s in availableStudents" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
                    </select>
                    <Btn type="button" size="sm" @click="attachStudent">Tambah</Btn>
                </div>
            </template>
        </Modal>

        <Modal
            :open="showTeachersModal"
            title="Plotting guru pengampu"
            max-width="md"
            @close="showTeachersModal = false"
        >
            <div class="max-h-64 space-y-2 overflow-y-auto">
                <label v-for="t in allTeachers" :key="t.id" class="flex items-center gap-2 text-sm">
                    <input v-model="teachersForm.teacher_ids" type="checkbox" :value="String(t.id)" class="rounded" />
                    {{ t.name }}
                </label>
            </div>
            <template #footer>
                <Btn type="button" variant="secondary" size="sm" @click="showTeachersModal = false">Batal</Btn>
                <Btn type="button" size="sm" @click="saveTeachers">Simpan</Btn>
            </template>
        </Modal>

        <Modal
            :open="!!showImport"
            :title="showImport === 'atp' ? 'Impor ATP' : 'Impor CP/TP'"
            max-width="md"
            @close="showImport = null"
        >
            <form id="refs-import-form" class="space-y-3" @submit.prevent="submitImport">
                <Field label="File" required for-id="import_file">
                    <input id="import_file" type="file" class="aksara-input" @change="importForm.importFile = $event.target.files?.[0] ?? null" />
                </Field>
            </form>
            <template #footer>
                <Btn type="button" variant="secondary" size="sm" @click="showImport = null">Batal</Btn>
                <Btn type="submit" form="refs-import-form" size="sm" :disabled="importForm.processing">Impor</Btn>
            </template>
        </Modal>
    </AppLayout>
</template>
