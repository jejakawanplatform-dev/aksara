<script setup>
import { onMounted, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';

const props = defineProps({
    material: { type: Object, required: true },
    isStem: { type: Boolean, default: false },
    isStudent: { type: Boolean, default: false },
    urls: { type: Object, required: true },
});

const contentRoot = ref(null);

onMounted(async () => {
    if (!props.isStem || !contentRoot.value) return;
    try {
        const math = await import('@/tiptap-math.js');
        math.renderKaTeXInElement?.(contentRoot.value);
    } catch (_) {
        // Math optional
    }
});
</script>

<template>
    <AppLayout title="Materi Pembelajaran">
        <template #header>
            <div class="flex items-center gap-3">
                <a :href="urls.index" class="text-aksara-muted hover:text-aksara-ink">← Kembali</a>
                <span class="text-aksara-line">/</span>
                <span>Materi Pembelajaran</span>
            </div>
        </template>

        <div class="mx-auto max-w-4xl space-y-6">
            <Card>
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-lg bg-aksara-teal/10 px-2.5 py-1 text-xs font-semibold text-aksara-teal">
                                {{ material.plan.subject }}
                            </span>
                            <span class="text-xs text-aksara-muted">· Kelas {{ material.plan.grade }}</span>
                        </div>
                        <h2 class="mt-2 font-display text-2xl font-bold text-aksara-ink">{{ material.title }}</h2>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <a
                            v-if="!isStudent"
                            :href="urls.edit"
                            class="aksara-btn-secondary text-xs"
                        >
                            Sunting Teks Bahan Ajar
                        </a>
                        <a
                            v-if="urls.quizAttempt"
                            :href="urls.quizAttempt"
                            class="aksara-btn-primary text-xs"
                        >
                            Kerjakan Kuis →
                        </a>
                    </div>
                </div>
            </Card>

            <div ref="contentRoot" class="aksara-card space-y-6 p-6 md:p-8">
                <div
                    v-for="(section, idx) in material.sections"
                    :key="idx"
                    class="space-y-2 border-b border-aksara-line/60 pb-6 last:border-0 last:pb-0"
                >
                    <h3 v-if="section.heading" class="font-display text-lg font-semibold text-aksara-ink">
                        {{ section.heading }}
                    </h3>
                    <div
                        v-if="section.body"
                        class="ProseMirror min-h-0 p-0 text-sm leading-relaxed text-aksara-ink"
                        v-html="section.body"
                    />
                </div>

                <div
                    v-if="!material.sections.length"
                    class="py-8 text-center text-sm text-aksara-muted"
                >
                    Konten belum tersedia.
                </div>

                <div
                    v-if="material.reflections?.length"
                    class="mt-8 rounded-2xl border border-aksara-teal/20 bg-aksara-mist/60 p-5"
                >
                    <h4 class="mb-2 font-display font-semibold text-aksara-teal-dark">Pertanyaan Refleksi</h4>
                    <ul class="m-0 list-inside list-disc space-y-1.5 text-sm text-aksara-ink">
                        <li v-for="(item, i) in material.reflections" :key="i">{{ item }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
