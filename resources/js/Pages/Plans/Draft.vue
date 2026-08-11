<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
import { computed } from 'vue';
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

/** Normalisasi item draf AI jadi teks yang bisa dibaca (bukan JSON mentah). */
function itemText(item) {
    if (item == null) return '';
    if (typeof item === 'string' || typeof item === 'number') return String(item);
    if (typeof item !== 'object') return String(item);

    if (item.statement) return String(item.statement);
    if (item.activity) return String(item.activity);
    if (item.title && item.description) return `${item.title}: ${item.description}`;
    if (item.title) return String(item.title);
    if (item.description) return String(item.description);
    if (item.text) return String(item.text);
    if (item.content) return String(item.content);
    if (item.note) return String(item.note);
    if (item.label) return String(item.label);

    // Hindari dump JSON penuh — ambil nilai string pertama yang berguna
    const skip = new Set(['sequence', 'order', 'id', 'index', 'step']);
    for (const [key, value] of Object.entries(item)) {
        if (skip.has(key)) continue;
        if (typeof value === 'string' && value.trim()) return value;
    }

    return '';
}

function toLines(items) {
    if (!Array.isArray(items) || !items.length) return [];
    return items
        .map((item, index) => {
            const text = itemText(item).trim();
            if (!text) return null;
            const seq =
                typeof item === 'object' && item !== null
                    ? Number(item.sequence ?? item.order ?? item.step ?? index + 1)
                    : index + 1;
            return { seq: Number.isFinite(seq) && seq > 0 ? seq : index + 1, text };
        })
        .filter(Boolean);
}

const tpLines = computed(() => toLines(approveForm.tpDraft));
const atpLines = computed(() => toLines(approveForm.atpDraft));
const noteLines = computed(() => toLines(approveForm.reviewNotes));
const lessonLines = computed(() => toLines(approveForm.lessonPlan));

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
            <h3 class="text-lg font-semibold text-aksara-ink">Belum ada generasi AI</h3>
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
                <ol v-if="tpLines.length" class="list-decimal space-y-2 pl-5 text-sm text-aksara-ink">
                    <li v-for="(line, i) in tpLines" :key="`tp-${i}`">{{ line.text }}</li>
                </ol>
                <p v-else class="text-sm text-aksara-muted">—</p>
            </Card>

            <Card title="Alur Tujuan Pembelajaran (ATP)">
                <ol v-if="atpLines.length" class="list-decimal space-y-2 pl-5 text-sm text-aksara-ink">
                    <li v-for="(line, i) in atpLines" :key="`atp-${i}`" :value="line.seq">
                        {{ line.text }}
                    </li>
                </ol>
                <p v-else class="text-sm text-aksara-muted">—</p>
            </Card>

            <Card v-if="lessonLines.length" title="Langkah pembelajaran">
                <ol class="list-decimal space-y-2 pl-5 text-sm text-aksara-ink">
                    <li v-for="(line, i) in lessonLines" :key="`lp-${i}`" :value="line.seq">
                        {{ line.text }}
                    </li>
                </ol>
            </Card>

            <Card title="Catatan Review">
                <ul v-if="noteLines.length" class="list-disc space-y-2 pl-5 text-sm text-aksara-ink">
                    <li v-for="(line, i) in noteLines" :key="`note-${i}`">{{ line.text }}</li>
                </ul>
                <p v-else class="text-sm text-aksara-muted">—</p>
            </Card>

            <Card title="Materi (ringkas)">
                <p class="text-sm font-semibold text-aksara-ink">
                    {{ approveForm.materialDraft?.title || plan.topic }}
                </p>
                <p class="mt-1 text-xs text-aksara-muted">
                    {{ (approveForm.materialDraft?.sections || []).length }} bagian materi
                </p>
            </Card>

            <div class="aksara-form-actions">
                <Btn :href="urls.index" variant="secondary">Kembali</Btn>
                <Btn
                    variant="secondary"
                    :disabled="!canPublish || publishForm.processing"
                    @click="publish"
                >
                    Terbitkan
                </Btn>
                <Btn :disabled="!canApprove || approveForm.processing" @click="approve">Setujui Draf</Btn>
            </div>
        </div>
    </AppLayout>
</template>
