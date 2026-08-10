<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Field from '@/Components/ui/Field.vue';
import Btn from '@/Components/ui/Btn.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';

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
    rombels: { type: Array, default: () => [] },
    subjects: { type: Array, default: () => [] },
    cps: { type: Array, default: () => [] },
    atp: { type: Array, default: () => [] },
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

function navigate(overrides = {}) {
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

        <div class="space-y-6">
            <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">
                Referensi kurikulum untuk bimtek — data CP/TP/ATP Informatika bersifat adaptasi workshop.
            </div>

            <Card title="Referensi Kurikulum" description="Master data akademik, kurikulum, dan rombel.">
                <div class="flex flex-wrap gap-1 overflow-x-auto border-b border-aksara-line text-xs font-semibold">
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

                <!-- Profil -->
                <div v-if="tab === 'profil'" class="mt-6 space-y-4">
                    <h2 class="font-display font-semibold">Profil Sekolah & Branding Institusi</h2>
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
                        <div class="md:col-span-2">
                            <Btn type="submit" :disabled="schoolForm.processing">Simpan profil</Btn>
                        </div>
                    </form>
                </div>

                <!-- Operasional -->
                <div v-else-if="tab === 'operasional'" class="mt-6 space-y-4">
                    <h2 class="font-display font-semibold">Pengaturan Operasional Akademik</h2>
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
                        <div class="md:col-span-3">
                            <Btn type="submit" :disabled="academicForm.processing">Simpan operasional</Btn>
                        </div>
                    </form>
                </div>

                <!-- Tahun -->
                <div v-else-if="tab === 'tahun'" class="mt-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="font-display font-semibold">Tahun Ajaran</h2>
                        <Btn
                            v-if="canManage"
                            type="button"
                            class="!px-3 !py-2 text-xs"
                            @click="openCreate('year', { name: '', code: '', starts_on: '', ends_on: '', is_active: false })"
                        >
                            + Tambah
                        </Btn>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-aksara-mist text-left text-xs uppercase text-aksara-muted">
                                <tr>
                                    <th class="p-3">Nama</th>
                                    <th class="p-3">Kode</th>
                                    <th class="p-3">Status</th>
                                    <th v-if="canManage" class="p-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-aksara-line">
                                <tr v-for="y in years" :key="y.id">
                                    <td class="p-3 font-medium">{{ y.name }}</td>
                                    <td class="p-3 text-aksara-muted">{{ y.code }}</td>
                                    <td class="p-3">
                                        <StatusBadge :status="y.is_active ? 'published' : 'draft'" :label="y.is_active ? 'Aktif' : 'Nonaktif'" />
                                    </td>
                                    <td v-if="canManage" class="space-x-2 p-3 text-right">
                                        <button
                                            type="button"
                                            class="text-xs font-semibold text-aksara-teal"
                                            @click="openEdit('year', y.id, { name: y.name, code: y.code, starts_on: y.starts_on || '', ends_on: y.ends_on || '', is_active: y.is_active })"
                                        >
                                            Edit
                                        </button>
                                        <button type="button" class="text-xs font-semibold text-red-600" @click="deleteEntity('year', y.id)">Hapus</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Semester -->
                <div v-else-if="tab === 'semester'" class="mt-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="font-display font-semibold">Semester</h2>
                        <Btn
                            v-if="canManage"
                            type="button"
                            class="!px-3 !py-2 text-xs"
                            @click="openCreate('semester', { academic_year_id: years[0]?.id || '', name: 'Ganjil', code: 'ganjil', number: 1, starts_on: '', ends_on: '', is_active: false })"
                        >
                            + Tambah
                        </Btn>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-aksara-mist text-left text-xs uppercase text-aksara-muted">
                                <tr>
                                    <th class="p-3">Nama</th>
                                    <th class="p-3">Tahun</th>
                                    <th class="p-3">Status</th>
                                    <th v-if="canManage" class="p-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-aksara-line">
                                <tr v-for="s in semesters" :key="s.id">
                                    <td class="p-3 font-medium">{{ s.name }}</td>
                                    <td class="p-3 text-aksara-muted">{{ s.yearName }}</td>
                                    <td class="p-3">
                                        <StatusBadge :status="s.is_active ? 'published' : 'draft'" :label="s.is_active ? 'Aktif' : 'Nonaktif'" />
                                    </td>
                                    <td v-if="canManage" class="space-x-2 p-3 text-right">
                                        <button v-if="!s.is_active" type="button" class="text-xs font-semibold text-aksara-teal" @click="activateSemester(s.id)">Aktifkan</button>
                                        <button
                                            type="button"
                                            class="text-xs font-semibold"
                                            @click="openEdit('semester', s.id, { academic_year_id: s.academic_year_id, name: s.name, code: s.code, number: s.number, starts_on: s.starts_on || '', ends_on: s.ends_on || '', is_active: s.is_active })"
                                        >
                                            Edit
                                        </button>
                                        <button type="button" class="text-xs font-semibold text-red-600" @click="deleteEntity('semester', s.id)">Hapus</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Rombel -->
                <div v-else-if="tab === 'rombel'" class="mt-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="font-display font-semibold">Rombel</h2>
                        <Btn
                            v-if="canManage"
                            type="button"
                            class="!px-3 !py-2 text-xs"
                            @click="openCreate('rombel', { academic_year_id: years[0]?.id || '', name: '', rombel_code: '', grade: 7, homeroom_teacher_id: '' })"
                        >
                            + Tambah
                        </Btn>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-aksara-mist text-left text-xs uppercase text-aksara-muted">
                                <tr>
                                    <th class="p-3">Nama</th>
                                    <th class="p-3">Kelas</th>
                                    <th class="p-3">Wali</th>
                                    <th class="p-3">Siswa</th>
                                    <th class="p-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-aksara-line">
                                <tr v-for="r in rombels" :key="r.id">
                                    <td class="p-3 font-medium">{{ r.name }}</td>
                                    <td class="p-3">{{ r.grade }}</td>
                                    <td class="p-3 text-aksara-muted">{{ r.homeroomName || '—' }}</td>
                                    <td class="p-3">{{ r.students_count }}</td>
                                    <td class="space-x-2 p-3 text-right">
                                        <button v-if="canManage" type="button" class="text-xs font-semibold text-aksara-teal" @click="openMembers(r.id)">Anggota</button>
                                        <button
                                            v-if="isTeacher"
                                            type="button"
                                            class="text-xs font-semibold"
                                            @click="toggleEnrol(r.id)"
                                        >
                                            {{ teacherEnrolledClassIds.includes(r.id) ? 'Batal enrol' : 'Enrol ajar' }}
                                        </button>
                                        <button
                                            v-if="canManage"
                                            type="button"
                                            class="text-xs font-semibold"
                                            @click="openEdit('rombel', r.id, { academic_year_id: r.academic_year_id, name: r.name, rombel_code: r.rombel_code, grade: r.grade, homeroom_teacher_id: r.homeroom_teacher_id || '' })"
                                        >
                                            Edit
                                        </button>
                                        <button v-if="canManage" type="button" class="text-xs font-semibold text-red-600" @click="deleteEntity('rombel', r.id)">Hapus</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mapel -->
                <div v-else-if="tab === 'mapel'" class="mt-6 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="font-display font-semibold">Mata Pelajaran</h2>
                        <div class="flex gap-2">
                            <select
                                v-if="!canManage"
                                class="aksara-select text-xs"
                                :value="filters.mapelScope"
                                @change="navigate({ mapelScope: $event.target.value })"
                            >
                                <option value="my">Mapel saya</option>
                                <option value="all">Semua</option>
                            </select>
                            <Btn
                                v-if="canManage"
                                type="button"
                                class="!px-3 !py-2 text-xs"
                                @click="openCreate('mapel', { name: '', code: '', phase: 'D', jenjang: 'SMP', description: '' })"
                            >
                                + Tambah
                            </Btn>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-aksara-mist text-left text-xs uppercase text-aksara-muted">
                                <tr>
                                    <th class="p-3">Nama</th>
                                    <th class="p-3">Kode</th>
                                    <th class="p-3">Guru</th>
                                    <th v-if="canManage" class="p-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-aksara-line">
                                <tr v-for="s in subjects" :key="s.id">
                                    <td class="p-3 font-medium">{{ s.name }}</td>
                                    <td class="p-3">{{ s.code }}</td>
                                    <td class="p-3 text-xs text-aksara-muted">{{ (s.teacherNames || []).join(', ') || '—' }}</td>
                                    <td v-if="canManage" class="space-x-2 p-3 text-right">
                                        <button type="button" class="text-xs font-semibold text-aksara-teal" @click="openTeachers(s)">Plotting</button>
                                        <button
                                            type="button"
                                            class="text-xs font-semibold"
                                            @click="openEdit('mapel', s.id, { name: s.name, code: s.code, phase: s.phase, jenjang: s.jenjang, description: s.description || '' })"
                                        >
                                            Edit
                                        </button>
                                        <button type="button" class="text-xs font-semibold text-red-600" @click="deleteEntity('mapel', s.id)">Hapus</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- CP & TP -->
                <div v-else-if="tab === 'cp'" class="mt-6 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="font-display font-semibold">CP & TP</h2>
                        <div class="flex flex-wrap gap-2">
                            <select
                                class="aksara-select text-xs"
                                :value="filters.subjectId"
                                @change="navigate({ subjectId: $event.target.value })"
                            >
                                <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                            <Btn v-if="canManageCurrentSubject" type="button" variant="secondary" class="!px-3 !py-2 text-xs" @click="openImport('cp-tp')">Impor</Btn>
                            <Btn
                                v-if="canManageCurrentSubject"
                                type="button"
                                class="!px-3 !py-2 text-xs"
                                @click="openCreate('cp', { subject_id: filters.subjectId, phase: 'D', element_code: '', element_name: '', statement: '', source_note: '', sequence: 1 })"
                            >
                                + CP
                            </Btn>
                        </div>
                    </div>
                    <div v-for="cp in cps" :key="cp.id" class="rounded-xl border border-aksara-line p-4">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p class="font-semibold text-aksara-ink">{{ cp.element_name }} <span class="text-xs text-aksara-muted">({{ cp.element_code }})</span></p>
                                <p class="mt-1 text-sm text-aksara-muted">{{ cp.statement }}</p>
                            </div>
                            <div v-if="canManageCurrentSubject" class="space-x-2">
                                <button
                                    type="button"
                                    class="text-xs font-semibold text-aksara-teal"
                                    @click="openCreate('tp', { curriculum_cp_id: cp.id, code: '', statement: '', grade: 7, sequence: 1 })"
                                >
                                    + TP
                                </button>
                                <button
                                    type="button"
                                    class="text-xs font-semibold"
                                    @click="openEdit('cp', cp.id, { subject_id: cp.subject_id, phase: cp.phase, element_code: cp.element_code, element_name: cp.element_name, statement: cp.statement, source_note: cp.source_note || '', sequence: cp.sequence })"
                                >
                                    Edit
                                </button>
                                <button type="button" class="text-xs font-semibold text-red-600" @click="deleteEntity('cp', cp.id)">Hapus</button>
                            </div>
                        </div>
                        <ul class="mt-3 space-y-2 border-t border-aksara-line pt-3">
                            <li v-for="tp in cp.tps" :key="tp.id" class="flex justify-between gap-2 text-sm">
                                <span><strong>{{ tp.code }}</strong> — {{ tp.statement }}</span>
                                <span v-if="canManageCurrentSubject" class="shrink-0 space-x-2">
                                    <button
                                        type="button"
                                        class="text-xs font-semibold"
                                        @click="openEdit('tp', tp.id, { curriculum_cp_id: tp.curriculum_cp_id, code: tp.code, statement: tp.statement, grade: tp.grade, sequence: tp.sequence })"
                                    >
                                        Edit
                                    </button>
                                    <button type="button" class="text-xs font-semibold text-red-600" @click="deleteEntity('tp', tp.id)">Hapus</button>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- ATP -->
                <div v-else-if="tab === 'atp'" class="mt-6 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="font-display font-semibold">ATP</h2>
                        <div class="flex flex-wrap gap-2">
                            <select
                                class="aksara-select text-xs"
                                :value="filters.subjectId"
                                @change="navigate({ subjectId: $event.target.value })"
                            >
                                <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                            <select
                                class="aksara-select text-xs"
                                :value="filters.atpGradeFilter ?? ''"
                                @change="navigate({ atpGradeFilter: $event.target.value === '' ? '' : Number($event.target.value) })"
                            >
                                <option value="">Semua kelas</option>
                                <option :value="7">Kelas 7</option>
                                <option :value="8">Kelas 8</option>
                                <option :value="9">Kelas 9</option>
                            </select>
                            <Btn v-if="canManageCurrentSubject" type="button" variant="secondary" class="!px-3 !py-2 text-xs" @click="openImport('atp')">Impor</Btn>
                            <Btn
                                v-if="canManageCurrentSubject"
                                type="button"
                                class="!px-3 !py-2 text-xs"
                                @click="openCreate('atp', { subject_id: filters.subjectId, academic_year_id: years.find(y => y.is_active)?.id || years[0]?.id || '', semester_id: '', curriculum_tp_id: '', grade: filters.atpGradeFilter || 7, sequence: 1, unit_title: '', estimated_meetings: 2 })"
                            >
                                + ATP
                            </Btn>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-aksara-mist text-left text-xs uppercase text-aksara-muted">
                                <tr>
                                    <th class="p-3">Urut</th>
                                    <th class="p-3">TP</th>
                                    <th class="p-3">Unit</th>
                                    <th class="p-3">Kelas</th>
                                    <th v-if="canManageCurrentSubject" class="p-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-aksara-line">
                                <tr v-for="item in atp" :key="item.id">
                                    <td class="p-3">{{ item.sequence }}</td>
                                    <td class="p-3 font-medium">{{ item.tpCode }}</td>
                                    <td class="p-3 text-aksara-muted">{{ item.unit_title || '—' }}</td>
                                    <td class="p-3">{{ item.grade }}</td>
                                    <td v-if="canManageCurrentSubject" class="space-x-2 p-3 text-right">
                                        <button
                                            type="button"
                                            class="text-xs font-semibold"
                                            @click="openEdit('atp', item.id, { subject_id: item.subject_id, academic_year_id: item.academic_year_id, semester_id: item.semester_id || '', curriculum_tp_id: item.curriculum_tp_id, grade: item.grade, sequence: item.sequence, unit_title: item.unit_title || '', estimated_meetings: item.estimated_meetings })"
                                        >
                                            Edit
                                        </button>
                                        <button type="button" class="text-xs font-semibold text-red-600" @click="deleteEntity('atp', item.id)">Hapus</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </Card>
        </div>

        <!-- CRUD modal -->
        <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-aksara-ink/40" @click="closeForm" />
            <div class="relative z-10 max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-aksara-line bg-white p-6 shadow-lg">
                <h3 class="font-display text-lg font-semibold">{{ formTitle }}</h3>
                <form class="mt-4 space-y-3" @submit.prevent="saveEntity">
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
                                <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
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
                                <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
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
                    <div class="flex justify-end gap-2 pt-2">
                        <Btn type="button" variant="secondary" @click="closeForm">Batal</Btn>
                        <Btn type="submit" :disabled="form.processing">Simpan</Btn>
                    </div>
                </form>
            </div>
        </div>

        <!-- Members modal -->
        <div v-if="memberClass" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-aksara-ink/40" @click="closeMembers" />
            <div class="relative z-10 max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl border border-aksara-line bg-white p-6 shadow-lg">
                <div class="flex justify-between">
                    <h3 class="font-display text-lg font-semibold">Anggota — {{ memberClass.name }}</h3>
                    <button type="button" class="text-sm text-aksara-muted" @click="closeMembers">Tutup</button>
                </div>
                <ul class="mt-4 space-y-2 text-sm">
                    <li v-for="s in memberClass.students" :key="s.id" class="flex justify-between">
                        <span>{{ s.name }}</span>
                        <button type="button" class="text-xs text-red-600" @click="detachStudent(s.id)">Lepas</button>
                    </li>
                </ul>
                <div class="mt-4 flex gap-2">
                    <select v-model="attachStudentForm.student_id" class="aksara-select flex-1">
                        <option value="">Pilih siswa</option>
                        <option v-for="s in availableStudents" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
                    </select>
                    <Btn type="button" class="!px-3 !py-2 text-xs" @click="attachStudent">Tambah</Btn>
                </div>
            </div>
        </div>

        <!-- Subject teachers modal -->
        <div v-if="showTeachersModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-aksara-ink/40" @click="showTeachersModal = false" />
            <div class="relative z-10 max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl border border-aksara-line bg-white p-6 shadow-lg">
                <h3 class="font-display text-lg font-semibold">Plotting guru pengampu</h3>
                <div class="mt-4 max-h-64 space-y-2 overflow-y-auto">
                    <label v-for="t in allTeachers" :key="t.id" class="flex items-center gap-2 text-sm">
                        <input v-model="teachersForm.teacher_ids" type="checkbox" :value="String(t.id)" class="rounded" />
                        {{ t.name }}
                    </label>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <Btn type="button" variant="secondary" @click="showTeachersModal = false">Batal</Btn>
                    <Btn type="button" @click="saveTeachers">Simpan</Btn>
                </div>
            </div>
        </div>

        <!-- Import modal -->
        <div v-if="showImport" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-aksara-ink/40" @click="showImport = null" />
            <div class="relative z-10 w-full max-w-md rounded-2xl border border-aksara-line bg-white p-6 shadow-lg">
                <h3 class="font-display text-lg font-semibold">Impor {{ showImport === 'atp' ? 'ATP' : 'CP/TP' }}</h3>
                <form class="mt-4 space-y-3" @submit.prevent="submitImport">
                    <Field label="File" required for-id="import_file">
                        <input id="import_file" type="file" class="aksara-input" @change="importForm.importFile = $event.target.files?.[0] ?? null" />
                    </Field>
                    <div class="flex justify-end gap-2">
                        <Btn type="button" variant="secondary" @click="showImport = null">Batal</Btn>
                        <Btn type="submit" :disabled="importForm.processing">Impor</Btn>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
