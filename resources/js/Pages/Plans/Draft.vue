<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import Btn from '@/Components/ui/Btn.vue';
import Field from '@/Components/ui/Field.vue';

const props = defineProps({
    plan: { type: Object, required: true },
    generation: { type: Object, default: null },
    output: { type: Object, required: true },
    canApprove: { type: Boolean, default: false },
    canPublish: { type: Boolean, default: false },
    urls: { type: Object, required: true },
});

const approveForm = useForm({
    cpDraft: props.output.cpDraft || '',
    tpDraft: props.output.tpDraft || [],
    atpDraft: props.output.atpDraft || [],
    lessonPlan: props.output.lessonPlan || [],
    materialDraft: props.output.materialDraft || {},
    reviewNotes: props.output.reviewNotes || [],
});

const publishForm = useForm({});

function formatList(items) {
    if (!Array.isArray(items) || !items.length) return '—';
    return items
        .map((item) => {
            if (typeof item === 'string') return item;
            if (item?.statement) return item.statement;
            if (item?.title) return item.title;
            return JSON.stringify(item);
        })
        .join('\n');
}

function approve() {
    approveForm.post(props.urls.approve, { preserveScroll: true });
}

function publish() {
    if (!window.confirm('Terbitkan rencana pembelajaran sekarang?')) return;
    publishForm.post(props.urls.publish);
}
</script>

<template>
    <AppLayout title="Review Draf AI">
        <template #header>Review Draf AI</template>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <a :href="urls.index" class="text-sm text-aksara-muted hover:text-aksara-ink">← Rencana Pembelajaran</a>
            <StatusBadge :status="plan.status" :label="plan.statusLabel" />
        </div>

        <div v-if="!generation" class="aksara-panel p-8 text-center">
            <h3 class="font-display text-lg font-semibold text-aksara-ink">Belum ada generasi AI</h3>
            <p class="mt-2 text-sm text-aksara-muted">Buat draf baru dari halaman create dengan mode AI.</p>
            <Btn :href="urls.index" class="mt-4">Kembali</Btn>
        </div>

        <div v-else class="space-y-4">
            <Card :title="plan.topic" :description="generation.model ? `Model: ${generation.model}` : null">
                <Field label="Capaian Pembelajaran (CP)">
                    <textarea v-model="approveForm.cpDraft" rows="4" class="aksara-input" />
                </Field>
            </Card>

            <Card title="Tujuan Pembelajaran (TP)">
                <pre class="whitespace-pre-wrap rounded-xl bg-aksara-mist/50 p-4 text-sm text-aksara-ink">{{
                    formatList(approveForm.tpDraft)
                }}</pre>
            </Card>

            <Card title="Alur Tujuan Pembelajaran (ATP)">
                <pre class="whitespace-pre-wrap rounded-xl bg-aksara-mist/50 p-4 text-sm text-aksara-ink">{{
                    formatList(approveForm.atpDraft)
                }}</pre>
            </Card>

            <Card title="Catatan Review">
                <pre class="whitespace-pre-wrap rounded-xl bg-aksara-mist/50 p-4 text-sm text-aksara-ink">{{
                    formatList(approveForm.reviewNotes)
                }}</pre>
            </Card>

            <Card title="Materi (ringkas)">
                <p class="text-sm font-semibold text-aksara-ink">
                    {{ approveForm.materialDraft?.title || plan.topic }}
                </p>
                <p class="mt-1 text-xs text-aksara-muted">
                    {{ (approveForm.materialDraft?.sections || []).length }} bagian materi
                </p>
            </Card>

            <div class="flex flex-wrap gap-2">
                <Btn :disabled="!canApprove || approveForm.processing" @click="approve">Setujui Draf</Btn>
                <Btn
                    variant="secondary"
                    :disabled="!canPublish || publishForm.processing"
                    @click="publish"
                >
                    Terbitkan
                </Btn>
                <Btn :href="urls.index" variant="secondary">Kembali</Btn>
            </div>
        </div>
    </AppLayout>
</template>
