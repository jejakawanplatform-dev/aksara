<script setup>
import { computed, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Field from '@/Components/ui/Field.vue';
import Btn from '@/Components/ui/Btn.vue';
import Icon from '@/Components/ui/Icon.vue';

const props = defineProps({
    mode: { type: String, default: 'ai' },
    defaults: { type: Object, required: true },
    options: { type: Object, required: true },
    dailyAiQuota: { type: Object, required: true },
    storeUrl: { type: String, required: true },
    indexUrl: { type: String, required: true },
});

const form = useForm({
    mode: props.mode === 'manual' ? 'manual' : 'ai',
    academic_year_id: props.defaults.academic_year_id || '',
    semester_id: props.defaults.semester_id || '',
    class_id: props.defaults.class_id || '',
    subject_id: props.defaults.subject_id || '',
    curriculum_cp_id: props.defaults.curriculum_cp_id || '',
    curriculum_tp_id: props.defaults.curriculum_tp_id || '',
    phase: props.defaults.phase || 'D',
    grade: props.defaults.grade || 7,
    topic: props.defaults.topic || '',
    duration_minutes: props.defaults.duration_minutes || 80,
    learning_objectives: props.defaults.learning_objectives || '',
    student_needs: props.defaults.student_needs || '',
    curriculum_reference: props.defaults.curriculum_reference || '',
});

const filteredSemesters = computed(() =>
    (props.options.semesters || []).filter(
        (s) => !form.academic_year_id || s.academic_year_id === Number(form.academic_year_id),
    ),
);

const filteredClasses = computed(() =>
    (props.options.classes || []).filter(
        (c) => !form.academic_year_id || c.academic_year_id === Number(form.academic_year_id),
    ),
);

const filteredCps = computed(() =>
    (props.options.cps || []).filter((cp) => !form.subject_id || cp.subject_id === Number(form.subject_id)),
);

const filteredTps = computed(() =>
    (props.options.tps || []).filter((tp) => {
        if (form.curriculum_cp_id) return tp.cp_id === Number(form.curriculum_cp_id);
        if (!form.subject_id) return true;
        const cpIds = filteredCps.value.map((c) => c.id);
        return cpIds.includes(tp.cp_id);
    }).filter((tp) => !form.grade || tp.grade == null || tp.grade === Number(form.grade)),
);

watch(
    () => form.academic_year_id,
    () => {
        const firstSem = filteredSemesters.value[0];
        form.semester_id = firstSem?.id || '';
        const firstClass = filteredClasses.value[0];
        form.class_id = firstClass?.id || '';
        if (firstClass?.grade) form.grade = firstClass.grade;
        form.curriculum_tp_id = '';
    },
);

watch(
    () => form.subject_id,
    (id) => {
        const subject = (props.options.subjects || []).find((s) => s.id === Number(id));
        if (subject?.phase) form.phase = subject.phase;
        form.curriculum_cp_id = '';
        form.curriculum_tp_id = '';
    },
);

watch(
    () => form.class_id,
    (id) => {
        const cls = (props.options.classes || []).find((c) => c.id === Number(id));
        if (cls?.grade) form.grade = cls.grade;
        if (cls?.academic_year_id) form.academic_year_id = cls.academic_year_id;
        form.curriculum_tp_id = '';
    },
);

watch(
    () => form.curriculum_cp_id,
    (id) => {
        form.curriculum_tp_id = '';
        if (!id) return;
        const cp = (props.options.cps || []).find((c) => c.id === Number(id));
        if (cp) {
            form.curriculum_reference = `${cp.label} — ${cp.statement || ''}`.trim();
            if (cp.phase) form.phase = cp.phase;
        }
    },
);

watch(
    () => form.curriculum_tp_id,
    (id) => {
        if (!id) return;
        const tp = (props.options.tps || []).find((t) => t.id === Number(id));
        if (!tp) return;
        form.curriculum_cp_id = tp.cp_id;
        if (tp.grade) form.grade = tp.grade;
        const cp = (props.options.cps || []).find((c) => c.id === tp.cp_id);
        form.curriculum_reference = [cp?.label || 'CP', `TP ${tp.label}`, tp.statement].filter(Boolean).join(' — ');
        if (!String(form.learning_objectives || '').trim()) {
            form.learning_objectives = tp.statement || '';
        }
    },
);

function setMode(m) {
    form.mode = m;
}

function submit() {
    form
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
        .post(props.storeUrl);
}
</script>

<template>
    <AppLayout title="Buat Rencana Pembelajaran">
        <template #header>Buat Rencana Pembelajaran</template>

        <div class="space-y-5">
            <PageHeader
                title="Buat Rencana Pembelajaran"
                description="Pilih mode AI atau isi manual."
            >
                <template #meta>
                    <Link :href="indexUrl" class="inline-flex items-center gap-1 text-sm text-aksara-muted hover:text-aksara-ink">
                        <Icon name="arrow-left" class="h-3.5 w-3.5" />
                        Rencana Pembelajaran
                    </Link>
                </template>
            </PageHeader>

            <div class="aksara-surface p-4 sm:p-5">
                <div class="aksara-form-actions mb-5">
                    <button
                        type="button"
                        class="rounded-lg px-3 py-2 text-xs font-semibold transition"
                        :class="form.mode === 'ai' ? 'bg-aksara-teal text-white' : 'bg-aksara-mist text-aksara-ink hover:bg-aksara-line/40'"
                        @click="setMode('ai')"
                    >
                        <span class="inline-flex items-center gap-1.5">
                            <Icon name="sparkles" class="h-3.5 w-3.5" />
                            Mode AI ({{ dailyAiQuota.used }}/{{ dailyAiQuota.limit }})
                        </span>
                    </button>
                    <button
                        type="button"
                        class="rounded-lg px-3 py-2 text-xs font-semibold transition"
                        :class="form.mode === 'manual' ? 'bg-aksara-teal text-white' : 'bg-aksara-mist text-aksara-ink hover:bg-aksara-line/40'"
                        @click="setMode('manual')"
                    >
                        Mode Manual
                    </button>
                </div>

                <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="submit">
                    <Field label="Tahun Ajaran" required :error="form.errors.academic_year_id">
                        <select v-model="form.academic_year_id" class="aksara-select">
                            <option value="">Pilih…</option>
                            <option v-for="y in options.academicYears" :key="y.id" :value="y.id">{{ y.name }}</option>
                        </select>
                    </Field>
                    <Field label="Semester" required :error="form.errors.semester_id">
                        <select v-model="form.semester_id" class="aksara-select">
                            <option value="">Pilih…</option>
                            <option v-for="s in filteredSemesters" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </Field>
                    <Field label="Kelas" required :error="form.errors.class_id">
                        <select v-model="form.class_id" class="aksara-select">
                            <option value="">Pilih…</option>
                            <option v-for="c in filteredClasses" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </Field>
                    <Field label="Mata Pelajaran" required :error="form.errors.subject_id">
                        <select v-model="form.subject_id" class="aksara-select">
                            <option value="">Pilih…</option>
                            <option v-for="s in options.subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </Field>
                    <Field label="CP" :error="form.errors.curriculum_cp_id">
                        <select v-model="form.curriculum_cp_id" class="aksara-select">
                            <option value="">Opsional</option>
                            <option v-for="cp in filteredCps" :key="cp.id" :value="cp.id">{{ cp.label }}</option>
                        </select>
                    </Field>
                    <Field label="TP" :error="form.errors.curriculum_tp_id">
                        <select v-model="form.curriculum_tp_id" class="aksara-select">
                            <option value="">Opsional</option>
                            <option v-for="tp in filteredTps" :key="tp.id" :value="tp.id">{{ tp.label }}</option>
                        </select>
                    </Field>
                    <Field label="Fase" required :error="form.errors.phase">
                        <select v-model="form.phase" class="aksara-select">
                            <option v-for="p in options.phases" :key="p" :value="p">{{ p }}</option>
                        </select>
                    </Field>
                    <Field label="Kelas (angka)" required :error="form.errors.grade">
                        <input v-model.number="form.grade" type="number" min="1" max="12" class="aksara-input" />
                    </Field>
                    <Field label="Topik" required class="md:col-span-2" :error="form.errors.topic">
                        <input v-model="form.topic" type="text" class="aksara-input" maxlength="255" />
                    </Field>
                    <Field label="Durasi (menit)" required :error="form.errors.duration_minutes">
                        <input v-model.number="form.duration_minutes" type="number" min="10" max="480" class="aksara-input" />
                    </Field>
                    <Field label="Kebutuhan Siswa" :error="form.errors.student_needs">
                        <input v-model="form.student_needs" type="text" class="aksara-input" maxlength="500" />
                    </Field>
                    <Field label="Tujuan Pembelajaran" required class="md:col-span-2" :error="form.errors.learning_objectives">
                        <textarea v-model="form.learning_objectives" rows="3" class="aksara-input" maxlength="1000" />
                    </Field>
                    <Field label="Referensi Kurikulum" required class="md:col-span-2" :error="form.errors.curriculum_reference">
                        <textarea v-model="form.curriculum_reference" rows="2" class="aksara-input" maxlength="2000" />
                    </Field>

                    <div class="aksara-form-actions md:col-span-2 border-t border-aksara-line pt-4">
                        <Btn :href="indexUrl" variant="secondary">Batal</Btn>
                        <Btn type="submit" :disabled="form.processing">
                            {{ form.mode === 'ai' ? 'Generate Draf AI' : 'Simpan Draf Manual' }}
                        </Btn>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
