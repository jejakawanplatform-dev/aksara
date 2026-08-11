<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import TipTapEditor from '@/Components/tiptap/TipTapEditor.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import Btn from '@/Components/ui/Btn.vue';
import Field from '@/Components/ui/Field.vue';
import Icon from '@/Components/ui/Icon.vue';
import IconButton from '@/Components/ui/IconButton.vue';

const props = defineProps({
    material: { type: Object, required: true },
    form: { type: Object, required: true },
    isStem: { type: Boolean, default: false },
    canGenerateImages: { type: Boolean, default: false },
    activeModelLabel: { type: String, default: '' },
    modelChoices: { type: Array, default: () => [] },
    urls: { type: Object, required: false, default: null },
    endpoints: { type: Object, required: true },
});

const api = computed(() => props.endpoints || props.urls || {});

const showCopilot = ref(true);
const showModelInfo = ref(false);
const copilotBusy = ref(false);
const copilotError = ref('');
const copilotInput = ref('');
const copilotMessages = ref([]);
const modelLabel = ref(props.activeModelLabel);
const canImages = ref(props.canGenerateImages);
const editorVersion = ref(0);
const openSections = ref({});
const editorOpen = ref(true);

const defaultModelId = computed(() => {
    const marked = props.modelChoices.find((m) => m.isDefault);
    return marked?.id || props.modelChoices[0]?.id || '';
});

const preferredModel = ref('');

const selectedModelMeta = computed(() =>
    props.modelChoices.find((m) => m.id === preferredModel.value) || null,
);

function onDocPointer(event) {
    if (!showModelInfo.value) return;
    if (event.target?.closest?.('[data-model-info]')) return;
    showModelInfo.value = false;
}

onMounted(() => {
    const saved = localStorage.getItem('aksara_copilot_open');
    if (saved !== null) {
        showCopilot.value = saved === 'true';
    }

    const savedModel = localStorage.getItem('aksara_asisten_model');
    if (savedModel && props.modelChoices.some((m) => m.id === savedModel)) {
        preferredModel.value = savedModel;
    } else {
        preferredModel.value = defaultModelId.value;
    }

    openSections.value = Object.fromEntries(
        props.form.sections.map((_, i) => [i, i === 0]),
    );

    document.addEventListener('pointerdown', onDocPointer, true);
});

onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', onDocPointer, true);
});

function toggleCopilot() {
    showCopilot.value = !showCopilot.value;
    localStorage.setItem('aksara_copilot_open', String(showCopilot.value));
}

function onModelChange() {
    localStorage.setItem('aksara_asisten_model', preferredModel.value);
}

function toggleModelInfo() {
    showModelInfo.value = !showModelInfo.value;
}

function toggleSection(index) {
    openSections.value[index] = !openSections.value[index];
}

function isSectionOpen(index) {
    return openSections.value[index] !== false;
}

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
    openSections.value[n - 1] = true;
}

function removeSection(index) {
    if (editorForm.sections.length <= 1) return;
    editorForm.sections.splice(index, 1);
    const next = {};
    editorForm.sections.forEach((_, i) => {
        next[i] = openSections.value[i >= index ? i + 1 : i] ?? i === 0;
    });
    openSections.value = next;
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
            preferredModel: preferredModel.value || undefined,
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
            illustrationTips: Array.isArray(data.illustrationTips) ? data.illustrationTips : [],
            applyMode: data.applyMode,
            intent: data.intent,
        });
    } catch (err) {
        copilotError.value =
            err?.response?.data?.message ||
            err?.message ||
            'Gagal menghubungi Asisten Aksara.';
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
    openSections.value = Object.fromEntries(
        editorForm.sections.map((_, i) => [i, true]),
    );
}

async function copyIllustrationPrompt(text) {
    const value = String(text || '').trim();
    if (!value) return;
    try {
        await navigator.clipboard.writeText(value);
    } catch {
        window.prompt('Salin prompt ini:', value);
    }
}

const templateOptions = [
    { key: 'illustrations', label: 'Ilustrasi AI', needsImage: true },
    { key: 'illustration_links', label: 'Saran ilustrasi' },
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

        <!-- Konten scroll | Co-Pilot full-height (tidak ikut scroll konten) -->
        <div class="aksara-material-edit -m-4 flex min-h-0 sm:-m-6">
            <div class="min-w-0 flex-1 overflow-y-auto p-4 sm:p-6">
                <div class="space-y-5">
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
                            <Btn
                                type="button"
                                variant="secondary"
                                size="sm"
                                class="gap-1.5"
                                @click="toggleCopilot"
                            >
                                <Icon name="sparkles" class="h-3.5 w-3.5" />
                                {{ showCopilot ? 'Sembunyikan Asisten' : 'Tampilkan Asisten' }}
                            </Btn>
                        </template>
                    </PageHeader>

                    <div class="aksara-surface overflow-hidden p-0 sm:p-0">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left sm:px-6"
                            :aria-expanded="editorOpen"
                            @click="editorOpen = !editorOpen"
                        >
                            <div class="min-w-0">
                                <h3 class="text-lg font-semibold text-aksara-ink">Editor Bahan Ajar</h3>
                                <p class="text-xs text-aksara-muted">Judul, seksi TipTap, dan pertanyaan refleksi.</p>
                            </div>
                            <Icon
                                name="chevron-down"
                                class="h-4 w-4 shrink-0 text-aksara-muted transition-transform"
                                :class="{ '-rotate-180': !editorOpen }"
                            />
                        </button>

                        <div v-show="editorOpen" class="space-y-6 border-t border-aksara-line px-5 py-5 sm:px-6">
                        <form class="space-y-6" @submit.prevent="saveDraft">
                            <Field label="Judul Bahan Ajar" for-id="material-title" :error="editorForm.errors.title">
                                <input
                                    id="material-title"
                                    v-model="editorForm.title"
                                    type="text"
                                    class="aksara-input"
                                    required
                                />
                            </Field>

                            <div class="space-y-3">
                                <div class="flex items-center justify-between gap-3">
                                    <h4 class="text-base font-semibold text-aksara-ink">
                                        Bagian-Bagian Teks Bacaan
                                    </h4>
                                    <Btn type="button" variant="secondary" size="sm" class="shrink-0 gap-1.5" @click="addSection">
                                        <Icon name="plus" class="h-3.5 w-3.5" />
                                        Tambah Seksi
                                    </Btn>
                                </div>

                                <div
                                    v-for="(section, index) in editorForm.sections"
                                    :key="`sec-${index}-v${editorVersion}`"
                                    class="overflow-hidden rounded-xl border border-aksara-line bg-aksara-mist/30"
                                >
                                    <div
                                        role="button"
                                        tabindex="0"
                                        class="flex w-full cursor-pointer items-center gap-2 px-4 py-3 text-left transition hover:bg-aksara-mist/60"
                                        :aria-expanded="isSectionOpen(index)"
                                        @click="toggleSection(index)"
                                        @keydown.enter.prevent="toggleSection(index)"
                                        @keydown.space.prevent="toggleSection(index)"
                                    >
                                        <Icon
                                            name="chevron-down"
                                            class="h-3.5 w-3.5 shrink-0 text-aksara-muted transition-transform"
                                            :class="{ '-rotate-180': !isSectionOpen(index) }"
                                        />
                                        <span class="text-xs font-semibold uppercase tracking-wider text-aksara-muted">
                                            Seksi {{ index + 1 }}
                                        </span>
                                        <span class="min-w-0 flex-1 truncate text-sm font-medium text-aksara-ink">
                                            {{ section.heading || '(tanpa judul)' }}
                                        </span>
                                        <IconButton
                                            v-if="editorForm.sections.length > 1"
                                            icon="trash"
                                            label="Hapus seksi"
                                            danger
                                            class="shrink-0"
                                            @click.stop="removeSection(index)"
                                        />
                                    </div>

                                    <div
                                        v-show="isSectionOpen(index)"
                                        class="space-y-4 border-t border-aksara-line px-4 py-4"
                                    >
                                        <Field :label="`Judul Seksi ${index + 1}`" :for-id="`section-heading-${index}`">
                                            <input
                                                :id="`section-heading-${index}`"
                                                v-model="section.heading"
                                                type="text"
                                                class="aksara-input font-medium"
                                                required
                                            />
                                        </Field>

                                        <Field label="Isi Penjelasan">
                                            <TipTapEditor
                                                v-model="section.body"
                                                :with-math="isStem"
                                                :media="{
                                                    listUrl: api.media,
                                                    uploadUrl: api.images,
                                                    deleteUrl: api.mediaDestroyBase,
                                                }"
                                            />
                                        </Field>
                                    </div>
                                </div>
                            </div>

                            <Field
                                label="Pertanyaan Refleksi (satu baris per pertanyaan)"
                                for-id="material-reflections"
                            >
                                <textarea
                                    id="material-reflections"
                                    v-model="editorForm.reflectionsText"
                                    rows="4"
                                    class="aksara-input font-mono text-sm"
                                />
                            </Field>

                            <div class="aksara-form-actions border-t border-aksara-line pt-4">
                                <Btn :href="api.show" variant="secondary" size="sm" class="gap-1.5">
                                    <Icon name="eye" class="h-3.5 w-3.5" />
                                    Lihat
                                </Btn>
                                <Btn
                                    type="button"
                                    variant="secondary"
                                    size="sm"
                                    :disabled="editorForm.processing"
                                    @click="publish"
                                >
                                    Terbitkan
                                </Btn>
                                <Btn type="submit" size="sm" :disabled="editorForm.processing">
                                    Simpan Draf
                                </Btn>
                            </div>
                        </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desktop: full height di dalam main (di bawah topbar), tidak ikut scroll konten -->
            <aside
                v-show="showCopilot"
                class="aksara-copilot-rail hidden w-[min(22rem,100%)] shrink-0 flex-col border-l border-aksara-line bg-white xl:flex"
                aria-label="Asisten Aksara"
            >
                <div class="flex items-start justify-between gap-2 border-b border-aksara-line px-4 py-3">
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-aksara-ink">Asisten Aksara</h3>
                        <p class="mt-0.5 text-[11px] text-aksara-muted">{{ intentHint }}</p>
                    </div>
                    <IconButton icon="x-mark" label="Sembunyikan Asisten" @click="toggleCopilot" />
                </div>

                <div
                    v-if="modelChoices.length"
                    data-model-info
                    class="relative space-y-2 border-b border-aksara-line px-4 py-3"
                >
                    <div class="flex items-center justify-between gap-2">
                        <label class="text-[11px] font-medium text-aksara-muted" for="asisten-model">
                            Model AI
                        </label>
                        <IconButton
                            icon="information-circle"
                            label="Info model"
                            :aria-expanded="showModelInfo"
                            @click="toggleModelInfo"
                        />
                    </div>
                    <select
                        id="asisten-model"
                        v-model="preferredModel"
                        class="aksara-select text-xs"
                        @change="onModelChange"
                    >
                        <option v-for="m in modelChoices" :key="m.id" :value="m.id">
                            {{ m.label }}{{ m.isDefault ? ' (default)' : '' }}
                        </option>
                    </select>
                    <div
                        v-if="showModelInfo && selectedModelMeta"
                        class="absolute left-3 right-3 top-full z-20 -mt-1 rounded-lg border border-aksara-line bg-white p-3 shadow-md"
                        role="dialog"
                        aria-label="Detail model AI"
                    >
                        <p class="text-[11px] font-semibold text-aksara-teal">
                            {{ selectedModelMeta.tag }}
                        </p>
                        <p class="mt-1 text-[11px] leading-relaxed text-aksara-ink">
                            {{ selectedModelMeta.recommend }}
                        </p>
                        <p class="mt-1.5 text-[11px] leading-relaxed text-aksara-muted">
                            <span class="font-medium text-aksara-ink">Batasan:</span>
                            {{ selectedModelMeta.limit }}
                        </p>
                        <p class="mt-1.5 break-words text-[11px] leading-relaxed text-aksara-muted">
                            <span class="font-medium text-aksara-ink">Vendor:</span>
                            {{ selectedModelMeta.provider }}
                        </p>
                        <p
                            v-if="modelLabel"
                            class="mt-1.5 break-words text-[11px] leading-relaxed text-aksara-muted"
                        >
                            <span class="font-medium text-aksara-ink">Terakhir dipakai:</span>
                            {{ modelLabel }}
                        </p>
                    </div>
                </div>

                <div class="border-b border-aksara-line px-4 py-3">
                    <div class="grid grid-cols-2 gap-1.5">
                        <label
                            v-for="opt in templateOptions"
                            :key="opt.key"
                            class="flex min-h-10 items-center gap-1.5 rounded-lg border border-aksara-line px-2 py-1.5 text-[11px] leading-tight"
                            :class="{ 'opacity-40': opt.needsImage && !canImages }"
                        >
                            <input
                                v-model="templates[opt.key]"
                                type="checkbox"
                                class="shrink-0 rounded border-aksara-line text-aksara-teal"
                                :disabled="opt.needsImage && !canImages"
                            />
                            <span class="min-w-0">{{ opt.label }}</span>
                        </label>
                    </div>
                </div>

                <div class="min-h-0 flex-1 space-y-3 overflow-y-auto px-4 py-3 text-sm">
                    <p v-if="!copilotMessages.length" class="text-xs text-aksara-muted">
                        Tulis instruksi untuk menyusun atau memperbaiki materi.
                    </p>
                    <div
                        v-for="(msg, idx) in copilotMessages"
                        :key="idx"
                        class="rounded-lg p-2.5"
                        :class="msg.role === 'user' ? 'bg-aksara-mist/50' : 'bg-aksara-teal/5'"
                    >
                        <p class="mb-1 text-[10px] font-bold uppercase tracking-wide text-aksara-muted">
                            {{ msg.role === 'user' ? 'Anda' : 'Asisten' }}
                        </p>
                        <p class="whitespace-pre-wrap text-aksara-ink">{{ msg.content }}</p>
                        <div
                            v-if="msg.illustrationTips?.length"
                            class="mt-2 space-y-2 border-t border-aksara-line/70 pt-2"
                        >
                            <p class="text-[10px] font-bold uppercase tracking-wide text-aksara-teal">
                                Saran ilustrasi (hanya di chat)
                            </p>
                            <div
                                v-for="(tip, tipIdx) in msg.illustrationTips"
                                :key="`tip-${idx}-${tipIdx}`"
                                class="rounded-md border border-aksara-line bg-white p-2"
                            >
                                <p class="text-[11px] font-semibold text-aksara-ink">
                                    {{ tip.sectionHeading || `Seksi ${(tip.sectionIndex ?? tipIdx) + 1}` }}
                                </p>
                                <p class="mt-0.5 text-[11px] text-aksara-muted">{{ tip.description }}</p>
                                <p class="mt-1 break-words font-mono text-[10px] text-aksara-ink">
                                    {{ tip.prompt }}
                                </p>
                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    <Btn
                                        type="button"
                                        size="sm"
                                        variant="secondary"
                                        class="!px-2 !py-1 text-[10px]"
                                        @click="copyIllustrationPrompt(tip.prompt)"
                                    >
                                        Salin prompt
                                    </Btn>
                                    <a
                                        v-if="tip.unsplashUrl"
                                        :href="tip.unsplashUrl"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-[10px] font-medium text-aksara-teal underline"
                                    >
                                        Unsplash
                                    </a>
                                    <a
                                        v-if="tip.commonsUrl"
                                        :href="tip.commonsUrl"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-[10px] font-medium text-aksara-teal underline"
                                    >
                                        Wikimedia
                                    </a>
                                </div>
                            </div>
                        </div>
                        <Btn
                            v-if="msg.materialData"
                            type="button"
                            size="sm"
                            class="mt-2 !px-2 !py-1 text-[11px]"
                            @click="applyCopilot(idx)"
                        >
                            Terapkan ke editor
                        </Btn>
                    </div>
                </div>

                <div class="border-t border-aksara-line bg-aksara-paper px-4 py-3">
                    <p v-if="copilotError" class="mb-2 text-xs text-aksara-danger">{{ copilotError }}</p>
                    <textarea
                        v-model="copilotInput"
                        rows="3"
                        class="aksara-input mb-2 text-sm"
                        placeholder="Contoh: Buatkan materi lengkap untuk topik ini…"
                        :disabled="copilotBusy"
                        @keydown.ctrl.enter.prevent="sendCopilot"
                    />
                    <Btn
                        type="button"
                        class="w-full"
                        size="sm"
                        :disabled="copilotBusy || !copilotInput.trim()"
                        @click="sendCopilot"
                    >
                        {{ copilotBusy ? 'Menunggu AI…' : 'Kirim' }}
                    </Btn>
                </div>
            </aside>

            <!-- Mobile/tablet: drawer -->
            <div
                v-if="showCopilot"
                class="fixed inset-0 z-40 xl:hidden"
                @keydown.escape.window="toggleCopilot"
            >
                <div class="absolute inset-0 bg-aksara-ink/40" @click="toggleCopilot" />
                <aside
                    class="absolute inset-y-0 right-0 flex w-[min(22rem,92vw)] flex-col border-l border-aksara-line bg-white shadow-md"
                    aria-label="Asisten Aksara"
                >
                    <div class="flex items-start justify-between gap-2 border-b border-aksara-line px-4 py-3">
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-aksara-ink">Asisten Aksara</h3>
                            <p class="mt-0.5 text-[11px] text-aksara-muted">{{ intentHint }}</p>
                        </div>
                        <IconButton icon="x-mark" label="Tutup Asisten" @click="toggleCopilot" />
                    </div>
                    <div
                        v-if="modelChoices.length"
                        data-model-info
                        class="relative space-y-2 border-b border-aksara-line px-4 py-3"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <label class="text-[11px] font-medium text-aksara-muted" for="asisten-model-mobile">
                                Model AI
                            </label>
                            <IconButton
                                icon="information-circle"
                                label="Info model"
                                :aria-expanded="showModelInfo"
                                @click="toggleModelInfo"
                            />
                        </div>
                        <select
                            id="asisten-model-mobile"
                            v-model="preferredModel"
                            class="aksara-select text-xs"
                            @change="onModelChange"
                        >
                            <option v-for="m in modelChoices" :key="`m-${m.id}`" :value="m.id">
                                {{ m.label }}{{ m.isDefault ? ' (default)' : '' }}
                            </option>
                        </select>
                        <div
                            v-if="showModelInfo && selectedModelMeta"
                            class="absolute left-3 right-3 top-full z-20 -mt-1 rounded-lg border border-aksara-line bg-white p-3 shadow-md"
                            role="dialog"
                            aria-label="Detail model AI"
                        >
                            <p class="text-[11px] font-semibold text-aksara-teal">
                                {{ selectedModelMeta.tag }}
                            </p>
                            <p class="mt-1 text-[11px] leading-relaxed text-aksara-ink">
                                {{ selectedModelMeta.recommend }}
                            </p>
                            <p class="mt-1.5 text-[11px] leading-relaxed text-aksara-muted">
                                <span class="font-medium text-aksara-ink">Batasan:</span>
                                {{ selectedModelMeta.limit }}
                            </p>
                            <p class="mt-1.5 break-words text-[11px] leading-relaxed text-aksara-muted">
                                <span class="font-medium text-aksara-ink">Vendor:</span>
                                {{ selectedModelMeta.provider }}
                            </p>
                            <p
                                v-if="modelLabel"
                                class="mt-1.5 break-words text-[11px] leading-relaxed text-aksara-muted"
                            >
                                <span class="font-medium text-aksara-ink">Terakhir dipakai:</span>
                                {{ modelLabel }}
                            </p>
                        </div>
                    </div>
                    <div class="border-b border-aksara-line px-4 py-3">
                        <div class="grid grid-cols-2 gap-1.5">
                            <label
                                v-for="opt in templateOptions"
                                :key="`m-${opt.key}`"
                                class="flex min-h-10 items-center gap-1.5 rounded-lg border border-aksara-line px-2 py-1.5 text-[11px] leading-tight"
                                :class="{ 'opacity-40': opt.needsImage && !canImages }"
                            >
                                <input
                                    v-model="templates[opt.key]"
                                    type="checkbox"
                                    class="shrink-0 rounded border-aksara-line text-aksara-teal"
                                    :disabled="opt.needsImage && !canImages"
                                />
                                <span class="min-w-0">{{ opt.label }}</span>
                            </label>
                        </div>
                    </div>
                    <div class="min-h-0 flex-1 space-y-3 overflow-y-auto px-4 py-3 text-sm">
                        <p v-if="!copilotMessages.length" class="text-xs text-aksara-muted">
                            Tulis instruksi untuk menyusun atau memperbaiki materi.
                        </p>
                        <div
                            v-for="(msg, idx) in copilotMessages"
                            :key="`m-${idx}`"
                            class="rounded-lg p-2.5"
                            :class="msg.role === 'user' ? 'bg-aksara-mist/50' : 'bg-aksara-teal/5'"
                        >
                            <p class="mb-1 text-[10px] font-bold uppercase tracking-wide text-aksara-muted">
                                {{ msg.role === 'user' ? 'Anda' : 'Asisten' }}
                            </p>
                            <p class="whitespace-pre-wrap text-aksara-ink">{{ msg.content }}</p>
                            <div
                                v-if="msg.illustrationTips?.length"
                                class="mt-2 space-y-2 border-t border-aksara-line/70 pt-2"
                            >
                                <p class="text-[10px] font-bold uppercase tracking-wide text-aksara-teal">
                                    Saran ilustrasi (hanya di chat)
                                </p>
                                <div
                                    v-for="(tip, tipIdx) in msg.illustrationTips"
                                    :key="`mtip-${idx}-${tipIdx}`"
                                    class="rounded-md border border-aksara-line bg-white p-2"
                                >
                                    <p class="text-[11px] font-semibold text-aksara-ink">
                                        {{ tip.sectionHeading || `Seksi ${(tip.sectionIndex ?? tipIdx) + 1}` }}
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-aksara-muted">{{ tip.description }}</p>
                                    <p class="mt-1 break-words font-mono text-[10px] text-aksara-ink">
                                        {{ tip.prompt }}
                                    </p>
                                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                                        <Btn
                                            type="button"
                                            size="sm"
                                            variant="secondary"
                                            class="!px-2 !py-1 text-[10px]"
                                            @click="copyIllustrationPrompt(tip.prompt)"
                                        >
                                            Salin prompt
                                        </Btn>
                                        <a
                                            v-if="tip.unsplashUrl"
                                            :href="tip.unsplashUrl"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-[10px] font-medium text-aksara-teal underline"
                                        >
                                            Unsplash
                                        </a>
                                        <a
                                            v-if="tip.commonsUrl"
                                            :href="tip.commonsUrl"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-[10px] font-medium text-aksara-teal underline"
                                        >
                                            Wikimedia
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <Btn
                                v-if="msg.materialData"
                                type="button"
                                size="sm"
                                class="mt-2 !px-2 !py-1 text-[11px]"
                                @click="applyCopilot(idx)"
                            >
                                Terapkan ke editor
                            </Btn>
                        </div>
                    </div>
                    <div class="border-t border-aksara-line bg-aksara-paper px-4 py-3">
                        <p v-if="copilotError" class="mb-2 text-xs text-aksara-danger">{{ copilotError }}</p>
                        <textarea
                            v-model="copilotInput"
                            rows="3"
                            class="aksara-input mb-2 text-sm"
                            placeholder="Contoh: Buatkan materi lengkap untuk topik ini…"
                            :disabled="copilotBusy"
                            @keydown.ctrl.enter.prevent="sendCopilot"
                        />
                        <Btn
                            type="button"
                            class="w-full"
                            size="sm"
                            :disabled="copilotBusy || !copilotInput.trim()"
                            @click="sendCopilot"
                        >
                            {{ copilotBusy ? 'Menunggu AI…' : 'Kirim' }}
                        </Btn>
                    </div>
                </aside>
            </div>
        </div>
    </AppLayout>
</template>
