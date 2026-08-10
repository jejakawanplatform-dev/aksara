<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Field from '@/Components/ui/Field.vue';
import Btn from '@/Components/ui/Btn.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';

const props = defineProps({
    plan: { type: Object, required: true },
    existingQuizzes: { type: Array, default: () => [] },
    form: { type: Object, required: true },
    storeUrl: { type: String, required: true },
    indexUrl: { type: String, required: true },
});

const editor = useForm({
    id: props.form.id ?? null,
    title: props.form.title,
    questions: props.form.questions.map((q) => ({
        question: q.question || '',
        options: [...(q.options || ['', '', '', ''])],
        correct_answer: q.correct_answer || '',
    })),
    status: props.form.status || 'draft',
});

function addQuestion() {
    editor.questions.push({
        question: '',
        options: ['', '', '', ''],
        correct_answer: '',
    });
}

function removeQuestion(index) {
    if (editor.questions.length <= 1) return;
    editor.questions.splice(index, 1);
}

function startNewQuiz() {
    editor.id = null;
    editor.title = `Kuis: ${props.plan.topic}`;
    editor.questions = [
        {
            question: '',
            options: ['', '', '', ''],
            correct_answer: '',
        },
    ];
    editor.status = 'draft';
    editor.clearErrors();
}

function loadQuiz(row) {
    editor.id = row.id;
    editor.title = row.title;
    editor.status = row.status || 'draft';
    const questions = Array.isArray(row.questions) && row.questions.length
        ? row.questions
        : [{ question: '', options: ['', '', '', ''], correct_answer: '' }];
    editor.questions = questions.map((q) => ({
        question: q.question || '',
        options: [...(q.options || ['', '', '', ''])],
        correct_answer: q.correct_answer || '',
    }));
    editor.clearErrors();
}

function save(status) {
    editor.status = status;
    editor.post(props.storeUrl, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Kuis Online">
        <template #header>Kuis Online</template>

        <div class="mb-4">
            <a :href="indexUrl" class="text-sm text-aksara-muted hover:text-aksara-ink">← Rencana Pembelajaran</a>
        </div>

        <Card
            :title="plan.topic"
            :description="`${plan.subject || '-'} · Kelas ${plan.className || plan.grade}`"
        >
            <div v-if="existingQuizzes.length" class="mb-4 space-y-2">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs font-semibold text-aksara-muted">Kuis yang sudah ada</p>
                    <Btn type="button" variant="secondary" size="sm" @click="startNewQuiz">+ Kuis baru</Btn>
                </div>
                <button
                    v-for="q in existingQuizzes"
                    :key="q.id"
                    type="button"
                    class="flex w-full items-center justify-between rounded-xl border px-3 py-2 text-left text-sm transition"
                    :class="
                        editor.id === q.id
                            ? 'border-aksara-teal bg-aksara-teal/5'
                            : 'border-aksara-line hover:bg-aksara-mist/40'
                    "
                    @click="loadQuiz(q)"
                >
                    <span>{{ q.title }} ({{ q.questionCount }} soal)</span>
                    <StatusBadge :status="q.status" />
                </button>
            </div>

            <p v-if="editor.id" class="mb-3 text-xs text-aksara-muted">
                Menyunting kuis #{{ editor.id }}. Ubah judul tidak membuat salinan baru.
            </p>
            <p v-else class="mb-3 text-xs text-aksara-muted">
                Mode kuis baru — simpan akan membuat entri terpisah.
            </p>
            <Field label="Judul Kuis" required :error="editor.errors.title">
                <input v-model="editor.title" type="text" class="aksara-input" />
            </Field>

            <div class="mt-4 space-y-4">
                <div
                    v-for="(q, qi) in editor.questions"
                    :key="qi"
                    class="rounded-2xl border border-aksara-line bg-aksara-mist/30 p-4 space-y-3"
                >
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-semibold text-aksara-ink">Soal {{ qi + 1 }}</h4>
                        <button
                            type="button"
                            class="text-xs text-red-500"
                            :disabled="editor.questions.length <= 1"
                            @click="removeQuestion(qi)"
                        >
                            Hapus
                        </button>
                    </div>

                    <Field label="Pertanyaan" required :error="editor.errors[`questions.${qi}.question`]">
                        <textarea v-model="q.question" rows="2" class="aksara-input" />
                    </Field>

                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <Field
                            v-for="(opt, oi) in q.options"
                            :key="oi"
                            :label="`Opsi ${oi + 1}`"
                            :error="editor.errors[`questions.${qi}.options.${oi}`]"
                        >
                            <input v-model="q.options[oi]" type="text" class="aksara-input" />
                        </Field>
                    </div>

                    <Field label="Jawaban Benar" required :error="editor.errors[`questions.${qi}.correct_answer`]">
                        <select v-model="q.correct_answer" class="aksara-select">
                            <option value="">Pilih opsi…</option>
                            <option v-for="(opt, oi) in q.options" :key="oi" :value="opt" :disabled="!opt">
                                {{ opt || `(opsi ${oi + 1} kosong)` }}
                            </option>
                        </select>
                    </Field>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <Btn variant="secondary" @click="addQuestion">+ Tambah Soal</Btn>
                <Btn :disabled="editor.processing" @click="save('draft')">Simpan Draf</Btn>
                <Btn :disabled="editor.processing" @click="save('published')">Terbitkan</Btn>
                <Btn :href="indexUrl" variant="secondary">Kembali</Btn>
            </div>
        </Card>
    </AppLayout>
</template>
