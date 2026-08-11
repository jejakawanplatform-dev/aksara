<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Field from '@/Components/ui/Field.vue';
import Btn from '@/Components/ui/Btn.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import IconButton from '@/Components/ui/IconButton.vue';
import Icon from '@/Components/ui/Icon.vue';

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

        <div class="space-y-5">
            <PageHeader
                :title="plan.topic"
                :description="`${plan.subject || '—'} · Kelas ${plan.className || plan.grade}`"
            >
                <template #actions>
                    <Btn :href="indexUrl" variant="secondary" size="sm" class="gap-1.5">
                        <Icon name="arrow-left" class="h-3.5 w-3.5" />
                        Rencana
                    </Btn>
                </template>
            </PageHeader>

            <div v-if="existingQuizzes.length" class="aksara-surface p-4 sm:p-5">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <p class="text-xs font-semibold text-aksara-muted">Kuis yang sudah ada</p>
                    <Btn type="button" variant="secondary" size="sm" class="gap-1.5" @click="startNewQuiz">
                        <Icon name="plus" class="h-3.5 w-3.5" />
                        Kuis baru
                    </Btn>
                </div>
                <div class="space-y-2">
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
            </div>

            <div class="aksara-surface p-4 sm:p-5">
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
                        class="aksara-surface-soft space-y-3 p-4"
                    >
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-aksara-ink">Soal {{ qi + 1 }}</h4>
                            <IconButton
                                icon="trash"
                                label="Hapus soal"
                                danger
                                :disabled="editor.questions.length <= 1"
                                @click="removeQuestion(qi)"
                            />
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

                <div class="aksara-form-actions mt-4 border-t border-aksara-line pt-4">
                    <Btn variant="secondary" size="sm" class="gap-1.5" @click="addQuestion">
                        <Icon name="plus" class="h-3.5 w-3.5" />
                        Tambah Soal
                    </Btn>
                    <Btn size="sm" variant="secondary" :disabled="editor.processing" @click="save('draft')">Simpan Draf</Btn>
                    <Btn size="sm" :disabled="editor.processing" @click="save('published')">Terbitkan</Btn>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
