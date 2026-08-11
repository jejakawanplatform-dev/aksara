<script setup>
import { computed, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Field from '@/Components/ui/Field.vue';
import Btn from '@/Components/ui/Btn.vue';
import Icon from '@/Components/ui/Icon.vue';

const props = defineProps({
    plan: { type: Object, required: true },
    form: { type: Object, required: true },
    options: { type: Object, required: true },
    updateUrl: { type: String, required: true },
    indexUrl: { type: String, required: true },
    draftUrl: { type: String, required: true },
});

const editor = useForm({
    academic_year_id: props.form.academic_year_id || '',
    semester_id: props.form.semester_id || '',
    class_id: props.form.class_id || '',
    subject_id: props.form.subject_id || '',
    curriculum_cp_id: props.form.curriculum_cp_id || '',
    curriculum_tp_id: props.form.curriculum_tp_id || '',
    phase: props.form.phase || 'D',
    grade: props.form.grade || 7,
    topic: props.form.topic || '',
    duration_minutes: props.form.duration_minutes || 80,
    learning_objectives: props.form.learning_objectives || '',
    student_needs: props.form.student_needs || '',
    curriculum_reference: props.form.curriculum_reference || '',
    status: props.form.status || 'draft',
});

const filteredSemesters = computed(() =>
    (props.options.semesters || []).filter(
        (s) => !editor.academic_year_id || s.academic_year_id === Number(editor.academic_year_id),
    ),
);

const filteredClasses = computed(() =>
    (props.options.classes || []).filter(
        (c) => !editor.academic_year_id || c.academic_year_id === Number(editor.academic_year_id),
    ),
);

const filteredCps = computed(() =>
    (props.options.cps || []).filter((cp) => !editor.subject_id || cp.subject_id === Number(editor.subject_id)),
);

const filteredTps = computed(() =>
    (props.options.tps || []).filter((tp) => {
        if (editor.curriculum_cp_id) return tp.cp_id === Number(editor.curriculum_cp_id);
        if (!editor.subject_id) return true;
        const cpIds = filteredCps.value.map((c) => c.id);
        return cpIds.includes(tp.cp_id);
    }),
);

watch(
    () => editor.subject_id,
    () => {
        editor.curriculum_cp_id = '';
        editor.curriculum_tp_id = '';
    },
);

watch(
    () => editor.curriculum_cp_id,
    () => {
        editor.curriculum_tp_id = '';
    },
);

function submit() {
    editor
        .transform((data) => ({
            ...data,
            academic_year_id: Number(data.academic_year_id) || null,
            semester_id: Number(data.semester_id) || null,
            class_id: Number(data.class_id) || null,
            subject_id: Number(data.subject_id) || null,
            curriculum_cp_id: data.curriculum_cp_id ? Number(data.curriculum_cp_id) : null,
            curriculum_tp_id: data.curriculum_tp_id ? Number(data.curriculum_tp_id) : null,
            grade: Number(data.grade),
            duration_minutes: Number(data.duration_minutes),
        }))
        .put(props.updateUrl);
}
</script>

<template>
    <AppLayout title="Edit Rencana Pembelajaran">
        <template #header>Edit Rencana Pembelajaran</template>

        <div class="space-y-5">
            <PageHeader
                :title="plan.topic"
                description="Sunting field rencana pembelajaran."
            >
                <template #meta>
                    <Link :href="indexUrl" class="inline-flex items-center gap-1 text-sm text-aksara-muted hover:text-aksara-ink">
                        <Icon name="arrow-left" class="h-3.5 w-3.5" />
                        Rencana Pembelajaran
                    </Link>
                </template>
            </PageHeader>

            <div class="aksara-surface p-4 sm:p-5">
                <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="submit">
                    <Field label="Tahun Ajaran" required :error="editor.errors.academic_year_id">
                        <select v-model="editor.academic_year_id" class="aksara-select">
                            <option v-for="y in options.academicYears" :key="y.id" :value="y.id">{{ y.name }}</option>
                        </select>
                    </Field>
                    <Field label="Semester" required :error="editor.errors.semester_id">
                        <select v-model="editor.semester_id" class="aksara-select">
                            <option v-for="s in filteredSemesters" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </Field>
                    <Field label="Kelas" required :error="editor.errors.class_id">
                        <select v-model="editor.class_id" class="aksara-select">
                            <option v-for="c in filteredClasses" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </Field>
                    <Field label="Mata Pelajaran" required :error="editor.errors.subject_id">
                        <select v-model="editor.subject_id" class="aksara-select">
                            <option v-for="s in options.subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </Field>
                    <Field label="CP" :error="editor.errors.curriculum_cp_id">
                        <select v-model="editor.curriculum_cp_id" class="aksara-select">
                            <option value="">Opsional</option>
                            <option v-for="cp in filteredCps" :key="cp.id" :value="cp.id">{{ cp.label }}</option>
                        </select>
                    </Field>
                    <Field label="TP" :error="editor.errors.curriculum_tp_id">
                        <select v-model="editor.curriculum_tp_id" class="aksara-select">
                            <option value="">Opsional</option>
                            <option v-for="tp in filteredTps" :key="tp.id" :value="tp.id">{{ tp.label }}</option>
                        </select>
                    </Field>
                    <Field label="Fase" required :error="editor.errors.phase">
                        <select v-model="editor.phase" class="aksara-select">
                            <option v-for="p in options.phases" :key="p" :value="p">{{ p }}</option>
                        </select>
                    </Field>
                    <Field label="Kelas (angka)" required :error="editor.errors.grade">
                        <input v-model.number="editor.grade" type="number" min="1" max="12" class="aksara-input" />
                    </Field>
                    <Field label="Topik" required class="md:col-span-2" :error="editor.errors.topic">
                        <input v-model="editor.topic" type="text" class="aksara-input" />
                    </Field>
                    <Field label="Durasi (menit)" required :error="editor.errors.duration_minutes">
                        <input v-model.number="editor.duration_minutes" type="number" min="10" max="480" class="aksara-input" />
                    </Field>
                    <Field label="Status" required :error="editor.errors.status">
                        <select v-model="editor.status" class="aksara-select">
                            <option value="draft">Draf</option>
                            <option value="published">Diterbitkan</option>
                        </select>
                    </Field>
                    <Field label="Kebutuhan Siswa" class="md:col-span-2" :error="editor.errors.student_needs">
                        <input v-model="editor.student_needs" type="text" class="aksara-input" />
                    </Field>
                    <Field label="Tujuan Pembelajaran" required class="md:col-span-2" :error="editor.errors.learning_objectives">
                        <textarea v-model="editor.learning_objectives" rows="3" class="aksara-input" />
                    </Field>
                    <Field label="Referensi Kurikulum" required class="md:col-span-2" :error="editor.errors.curriculum_reference">
                        <textarea v-model="editor.curriculum_reference" rows="2" class="aksara-input" />
                    </Field>

                    <div class="aksara-form-actions md:col-span-2 border-t border-aksara-line pt-4">
                        <Btn :href="indexUrl" variant="secondary">Kembali ke Daftar</Btn>
                        <Btn :href="draftUrl" variant="secondary">Review AI</Btn>
                        <Btn type="submit" :disabled="editor.processing">Simpan</Btn>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
