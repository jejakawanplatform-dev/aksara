<script setup>
import { computed, reactive, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import TipTapEditor from '@/Components/tiptap/TipTapEditor.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import Btn from '@/Components/ui/Btn.vue';

const props = defineProps({
    material: { type: Object, required: true },
    form: { type: Object, required: true },
    isStem: { type: Boolean, default: false },
    canGenerateImages: { type: Boolean, default: false },
    activeModelLabel: { type: String, default: '' },
    urls: { type: Object, required: false, default: null },
    endpoints: { type: Object, required: true },
});

const api = computed(() => props.endpoints || props.urls || {});

const showCopilot = ref(true);
const copilotBusy = ref(false);
const copilotError = ref('');
const copilotInput = ref('');
const copilotMessages = ref([]);
const modelLabel = ref(props.activeModelLabel);
const canImages = ref(props.canGenerateImages);
const editorVersion = ref(0);

const editorForm = useForm({
    title: props.form.title,
    sections: props.form.sections.map((s) => ({ heading: s.heading, body: s.body })),
    reflectionsText: props.form.reflectionsText,
});

const templates = reactive({
    illustrations: false,
    illustration_links: false,
    references: true,
    case_studies: false,
    stem_code: false,
    glossary: false,
});

const statusLabel = computed(() =>
    props.material.status === 'published' ? 'Diterbitkan' : 'Draf',
);

const intentHint = computed(() => {
    const empty = isEssentiallyEmpty();
    return empty ? 'Mode: buat materi baru' : 'Mode: perbaiki / lengkapi (patch)';
});

function isEssentiallyEmpty() {
    const placeholders = [
        'tuliskan penjelasan',
        'isi teks materi',
        'sub-topik baru',
        'penjelasan materi lengkap',
    ];
    for (const sec of editorForm.sections) {
        const plain = (sec.body || '')
            .replace(/<[^>]+>/g, ' ')
            .replace(/\s+/g, ' ')
            .trim()
            .toLowerCase();
        if (!plain) continue;
        const isPh = placeholders.some((p) => plain.includes(p));
        if (!isPh && plain.length > 60) return false;
    }
    return true;
}

function addSection() {
    const n = editorForm.sections.length + 1;
    editorForm.sections.push({
        heading: `${n}. Sub-Topik Baru`,
        body: '<p>Tuliskan penjelasan materi lengkap di sini...</p>',
    });
}

function removeSection(index) {
    if (editorForm.sections.length <= 1) return;
    editorForm.sections.splice(index, 1);
}

function saveDraft() {
    editorForm.put(api.value.update, { preserveScroll: true });
}

function publish() {
    if (!window.confirm('Terbitkan bahan ajar ke siswa sekarang?')) return;
    editorForm.post(api.value.publish);
}

async function sendCopilot() {
    const message = copilotInput.value.trim();
    if (!message || copilotBusy.value) return;

    copilotBusy.value = true;
    copilotError.value = '';
    copilotMessages.value.push({
        role: 'user',
        content: message,
        materialData: null,
    });
    copilotInput.value = '';

    try {
        const history = copilotMessages.value.map((m) => ({
            role: m.role,
            content: m.content,
        }));
        // history already includes the user message; API also appends message — send without last dup
        history.pop();

        const { data } = await axios.post(api.value.copilot, {
            message,
            history,
            templates: {
                ...templates,
                illustrations: canImages.value ? templates.illustrations : false,
            },
            title: editorForm.title,
            sections: editorForm.sections,
            reflectionsText: editorForm.reflectionsText,
        });

        if (data.modelLabel) modelLabel.value = data.modelLabel;
        if (typeof data.canGenerateImages === 'boolean') {
            canImages.value = data.canGenerateImages;
        }

        copilotMessages.value.push({
            role: 'assistant',
            content: data.replyMessage || 'Bahan ajar telah disesuaikan.',
            materialData: data.materialData,
            proposedOutline: data.proposedOutline,
            applyMode: data.applyMode,
            intent: data.intent,
        });
    } catch (err) {
        copilotError.value =
            err?.response?.data?.message ||
            err?.message ||
            'Gagal menghubungi Co-Pilot.';
        copilotMessages.value.push({
            role: 'assistant',
            content: 'Maaf, terjadi kesalahan saat memanggil AI.',
            materialData: null,
        });
    } finally {
        copilotBusy.value = false;
    }
}

function headingsMatch(a, b) {
    const norm = (h) =>
        String(h || '')
            .toLowerCase()
            .trim()
            .replace(/^\d+[\.)]\s*/, '')
            .replace(/\s+/g, ' ');
    const na = norm(a);
    const nb = norm(b);
    if (!na || !nb) return false;
    return na === nb || na.includes(nb) || nb.includes(na);
}

function applyCopilot(msgIndex) {
    const msg = copilotMessages.value[msgIndex];
    if (!msg?.materialData) return;

    const data = msg.materialData;
    const mode = msg.applyMode || msg.intent || 'rewrite';

    if (mode === 'patch') {
        if (data.title) editorForm.title = String(data.title);
        const incoming = data.sections || [];
        for (let i = 0; i < incoming.length; i++) {
            const sec = incoming[i];
            let matched = -1;
            for (let j = 0; j < editorForm.sections.length; j++) {
                if (headingsMatch(editorForm.sections[j].heading, sec.heading)) {
                    matched = j;
                    break;
                }
            }
            if (matched >= 0) {
                editorForm.sections[matched].heading = sec.heading;
                editorForm.sections[matched].body = sec.body;
            } else if (editorForm.sections[i] && incoming.length <= editorForm.sections.length) {
                editorForm.sections[i].heading = sec.heading;
                editorForm.sections[i].body = sec.body;
            } else {
                editorForm.sections.push({ heading: sec.heading, body: sec.body });
            }
        }
        if (Array.isArray(data.reflectionQuestion)) {
            editorForm.reflectionsText = data.reflectionQuestion.join('\n');
        }
    } else {
        if (data.title) editorForm.title = String(data.title);
        editorForm.sections = (data.sections || []).map((s) => ({
            heading: s.heading || '',
            body: s.body || '',
        }));
        if (Array.isArray(data.reflectionQuestion)) {
            editorForm.reflectionsText = data.reflectionQuestion.join('\n');
        }
    }
    editorVersion.value += 1;
}

const templateOptions = [
    { key: 'illustrations', label: 'Ilustrasi AI', needsImage: true },
    { key: 'illustration_links', label: 'Saran tautan ilustrasi' },
    { key: 'references', label: 'Referensi' },
    { key: 'case_studies', label: 'Studi kasus' },
    { key: 'stem_code', label: 'STEM / kode' },
    { key: 'glossary', label: 'Glosarium' },
];

// Patch merge semantics: keep in sync with App\Support\MaterialCopilotPatch (tests ADR-009).
</script>

<template>
    <AppLayout title="Edit Materi">
        <template #header>Edit Materi</template>

        <div class="space-y-6">
            <PageHeader
                :title="material.plan.topic"
                description="Editor Vue + TipTap (Inertia)."
            >
                <template #meta>
                    <span class="rounded-lg bg-aksara-teal/10 px-2.5 py-1 text-xs font-semibold text-aksara-teal">
                        {{ material.plan.subject || '-' }}
                    </span>
                    <span class="text-xs text-aksara-muted">
                        · Kelas {{ material.plan.className || material.plan.grade }}
                    </span>
                    <StatusBadge :status="material.status" :label="statusLabel" />
                </template>
                <template #actions>
                    <Btn type="button" variant="secondary" size="sm" @click="showCopilot = !showCopilot">
                        {{ showCopilot ? 'Sembunyikan Co-Pilot' : 'Tampilkan Co-Pilot' }}
                    </Btn>
                </template>
            </PageHeader>

            <div
                class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]"
            >
                <div class="min-w-0">
                    <div class="rounded-2xl border border-aksara-line bg-white p-5 shadow-sm sm:p-6">
                        <div class="mb-5">
                            <h3 class="font-display text-lg font-semibold text-aksara-ink">Editor Bahan Ajar</h3>
                            <p class="text-xs text-aksara-muted">Judul, seksi TipTap, dan pertanyaan refleksi.</p>
                        </div>

                        <form class="space-y-6" @submit.prevent="saveDraft">
                            <label class="block space-y-1.5">
                                <span class="text-sm font-semibold text-aksara-ink">Judul Bahan Ajar</span>
                                <input
                                    v-model="editorForm.title"
                                    type="text"
                                    class="aksara-input"
                                    required
                                />
                                <p v-if="editorForm.errors.title" class="text-xs text-red-600">
                                    {{ editorForm.errors.title }}
                                </p>
                            </label>

                            <div class="space-y-6">
                                <div class="flex items-center justify-between gap-3">
                                    <h4 class="font-display text-base font-semibold text-aksara-ink">
                                        Bagian-Bagian Teks Bacaan
                                    </h4>
                                    <button type="button" class="aksara-btn-secondary shrink-0 text-xs" @click="addSection">
                                        + Tambah Seksi
                                    </button>
                                </div>

                                <div
                                    v-for="(section, index) in editorForm.sections"
                                    :key="`sec-${index}-v${editorVersion}`"
                                    class="relative space-y-4 overflow-visible rounded-xl border border-aksara-line bg-aksara-mist/30 p-5"
                                >
                                    <div class="flex items-center justify-between gap-4">
                                        <label class="text-xs font-semibold uppercase tracking-wider text-aksara-muted">
                                            Seksi {{ index + 1 }}
                                        </label>
                                        <button
                                            v-if="editorForm.sections.length > 1"
                                            type="button"
                                            class="text-xs font-medium text-red-500 hover:text-red-700"
                                            @click="removeSection(index)"
                                        >
                                            Hapus Seksi ×
                                        </button>
                                    </div>

                                    <label class="block space-y-1.5">
                                        <span class="text-sm font-semibold text-aksara-ink">Judul Seksi</span>
                                        <input
                                            v-model="section.heading"
                                            type="text"
                                            class="aksara-input font-medium"
                                            required
                                        />
                                    </label>

                                    <div class="space-y-1.5">
                                        <span class="text-sm font-semibold text-aksara-ink">Isi Penjelasan</span>
                                        <TipTapEditor
                                            v-model="section.body"
                                            :with-math="isStem"
                                            :media="{
                                                listUrl: api.media,
                                                uploadUrl: api.images,
                                                deleteUrl: api.mediaDestroyBase,
                                            }"
                                        />
                                    </div>
                                </div>
                            </div>

                            <label class="block space-y-1.5">
                                <span class="text-sm font-semibold text-aksara-ink">
                                    Pertanyaan Refleksi (satu baris per pertanyaan)
                                </span>
                                <textarea
                                    v-model="editorForm.reflectionsText"
                                    rows="4"
                                    class="aksara-input font-mono text-sm"
                                />
                            </label>

                            <div class="flex flex-wrap gap-3 border-t border-aksara-line pt-4">
                                <button
                                    type="submit"
                                    class="aksara-btn-primary text-sm"
                                    :disabled="editorForm.processing"
                                >
                                    Simpan Draf
                                </button>
                                <button
                                    type="button"
                                    class="aksara-btn-secondary text-sm"
                                    :disabled="editorForm.processing"
                                    @click="publish"
                                >
                                    Terbitkan
                                </button>
                                <a :href="api.show" class="aksara-btn-secondary text-sm">Lihat</a>
                            </div>
                        </form>
                    </div>
                </div>

                <aside
                    v-if="showCopilot"
                    class="min-w-0 xl:sticky xl:top-4 xl:self-start"
                >
                    <div class="rounded-2xl border border-aksara-line bg-white p-4 shadow-sm">
                        <div class="mb-3">
                            <h3 class="font-display text-base font-semibold text-aksara-ink">AI Co-Pilot</h3>
                            <p class="text-xs text-aksara-muted">
                                {{ modelLabel || 'Model aktif' }} · {{ intentHint }}
                            </p>
                        </div>

                        <div class="mb-3 flex flex-wrap gap-2">
                            <label
                                v-for="opt in templateOptions"
                                :key="opt.key"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-aksara-line px-2 py-1 text-[11px]"
                                :class="{
                                    'opacity-40': opt.needsImage && !canImages,
                                }"
                            >
                                <input
                                    v-model="templates[opt.key]"
                                    type="checkbox"
                                    class="rounded border-aksara-line text-aksara-teal"
                                    :disabled="opt.needsImage && !canImages"
                                />
                                {{ opt.label }}
                            </label>
                        </div>

                        <div class="mb-3 max-h-72 space-y-3 overflow-y-auto rounded-xl bg-aksara-mist/40 p-3 text-sm">
                            <p v-if="!copilotMessages.length" class="text-xs text-aksara-muted">
                                Tulis instruksi untuk menyusun atau memperbaiki materi.
                            </p>
                            <div
                                v-for="(msg, idx) in copilotMessages"
                                :key="idx"
                                class="rounded-lg p-2"
                                :class="msg.role === 'user' ? 'bg-white' : 'bg-aksara-teal/5'"
                            >
                                <p class="mb-1 text-[10px] font-bold uppercase tracking-wide text-aksara-muted">
                                    {{ msg.role === 'user' ? 'Anda' : 'Co-Pilot' }}
                                </p>
                                <p class="whitespace-pre-wrap text-aksara-ink">{{ msg.content }}</p>
                                <button
                                    v-if="msg.materialData"
                                    type="button"
                                    class="mt-2 aksara-btn-primary !px-2 !py-1 text-[11px]"
                                    @click="applyCopilot(idx)"
                                >
                                    Terapkan ke editor
                                </button>
                            </div>
                        </div>

                        <p v-if="copilotError" class="mb-2 text-xs text-red-600">{{ copilotError }}</p>

                        <textarea
                            v-model="copilotInput"
                            rows="3"
                            class="aksara-input mb-2 text-sm"
                            placeholder="Contoh: Buatkan materi lengkap untuk topik ini…"
                            :disabled="copilotBusy"
                            @keydown.ctrl.enter.prevent="sendCopilot"
                        />
                        <button
                            type="button"
                            class="aksara-btn-primary w-full text-sm"
                            :disabled="copilotBusy || !copilotInput.trim()"
                            @click="sendCopilot"
                        >
                            {{ copilotBusy ? 'Menunggu AI…' : 'Kirim' }}
                        </button>
                    </div>
                </aside>
            </div>
        </div>
    </AppLayout>
</template>
