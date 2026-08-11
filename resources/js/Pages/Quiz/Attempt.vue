<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Btn from '@/Components/ui/Btn.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';

const props = defineProps({
    quiz: { type: Object, required: true },
    questions: { type: Array, default: () => [] },
    alreadyDone: { type: Boolean, default: false },
    submitted: { type: Boolean, default: false },
    score: { type: Number, default: null },
    answers: { type: [Array, Object], default: () => [] },
    submitUrl: { type: String, required: true },
});

const page = usePage();
const flashMessage = computed(() => page.props.flash?.message || '');

const form = useForm({
    answers: Object.fromEntries(props.questions.map((q) => [q.index, props.answers?.[q.index] ?? null])),
});

const answeredCount = computed(
    () => Object.values(form.answers).filter((v) => v !== null && v !== undefined && v !== '').length,
);

const passed = computed(() => props.score !== null && props.score >= 70);

function submit() {
    form.post(props.submitUrl, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="`Quiz — ${quiz.title}`">
        <template #header>Quiz</template>

        <div class="mx-auto max-w-3xl space-y-5">
            <PageHeader
                :title="quiz.title"
                :description="`${quiz.questionCount} soal`"
            />

            <div
                v-if="alreadyDone || (submitted && score !== null)"
                class="aksara-surface p-6 text-center"
            >
                <StatusBadge
                    v-if="!alreadyDone"
                    :status="passed ? 'published' : 'draft'"
                    :label="passed ? 'Lulus KKM' : 'Belum KKM'"
                    class="mb-3"
                />
                <p v-if="!alreadyDone" class="text-lg font-semibold text-aksara-ink">Quiz Selesai!</p>
                <p
                    v-else
                    class="text-sm font-semibold text-aksara-info"
                >
                    Sudah dikerjakan sebelumnya
                </p>
                <p
                    class="my-3 text-4xl font-bold"
                    :class="passed ? 'text-aksara-ok' : 'text-aksara-danger'"
                >
                    {{ score }}
                </p>
                <p class="text-sm text-aksara-muted">
                    {{
                        passed
                            ? alreadyDone
                                ? 'Selamat! Kamu lulus.'
                                : 'Kamu lulus KKM (70)!'
                            : alreadyDone
                              ? 'Terus semangat belajar!'
                              : 'Nilai belum mencapai KKM. Pelajari kembali materinya ya.'
                    }}
                </p>
                <p v-if="alreadyDone" class="mt-3 text-xs text-aksara-muted">
                    Kamu sudah mengerjakan quiz ini sebelumnya.
                </p>
                <p v-if="flashMessage && !alreadyDone" class="mt-2 text-xs text-aksara-muted">{{ flashMessage }}</p>
            </div>

            <form v-else class="space-y-4" @submit.prevent="submit">
                <div
                    v-for="q in questions"
                    :key="q.index"
                    :id="`question-${q.index}`"
                    class="aksara-surface p-4 sm:p-5"
                >
                    <p class="mb-3 font-medium text-aksara-ink">{{ q.index + 1 }}. {{ q.question }}</p>
                    <div class="space-y-2">
                        <label
                            v-for="(opt, oi) in q.options"
                            :key="oi"
                            class="flex cursor-pointer items-center gap-3 rounded-lg p-2 hover:bg-aksara-mist/40"
                        >
                            <input
                                v-model="form.answers[q.index]"
                                type="radio"
                                :value="opt"
                                :name="`q${q.index}`"
                                class="h-4 w-4 accent-aksara-teal"
                            />
                            <span class="text-sm text-aksara-ink">{{ opt }}</span>
                        </label>
                    </div>
                </div>

                <div class="aksara-surface p-4 sm:p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-aksara-muted">
                            Terjawab:
                            <span class="font-semibold text-aksara-teal">{{ answeredCount }}</span>/{{ questions.length }}
                        </p>
                        <Btn
                            id="btn-submit-quiz"
                            type="submit"
                            :disabled="form.processing || answeredCount < questions.length"
                            :title="answeredCount < questions.length ? 'Jawab semua soal dulu' : ''"
                        >
                            {{ form.processing ? 'Menilai…' : 'Kumpulkan Jawaban' }}
                        </Btn>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
