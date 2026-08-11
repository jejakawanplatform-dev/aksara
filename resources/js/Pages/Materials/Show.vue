<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
import { onMounted, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import Btn from '@/Components/ui/Btn.vue';
import Icon from '@/Components/ui/Icon.vue';

const props = defineProps({
    material: { type: Object, required: true },
    isStem: { type: Boolean, default: false },
    isStudent: { type: Boolean, default: false },
    urls: { type: Object, required: true },
});

const contentRoot = ref(null);

const statusLabel = props.material.status === 'published' ? 'Diterbitkan' : 'Draf';

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
        <template #header>Materi Pembelajaran</template>

        <div class="mx-auto max-w-4xl space-y-5">
            <PageHeader :title="material.title">
                <template #meta>
                    <span class="rounded-lg bg-aksara-teal/10 px-2.5 py-1 text-xs font-semibold text-aksara-teal">
                        {{ material.plan.subject }}
                    </span>
                    <span class="text-xs text-aksara-muted">· Kelas {{ material.plan.className || material.plan.grade }}</span>
                    <StatusBadge v-if="!isStudent" :status="material.status" :label="statusLabel" />
                </template>
                <template #actions>
                    <Btn :href="urls.index" variant="secondary" size="sm" class="gap-1.5">
                        <Icon name="material" class="h-3.5 w-3.5" />
                        Daftar Materi
                    </Btn>
                    <Btn v-if="!isStudent" :href="urls.edit" size="sm" class="gap-1.5">
                        <Icon name="pencil" class="h-3.5 w-3.5" />
                        Sunting
                    </Btn>
                    <Btn v-if="urls.quizAttempt" :href="urls.quizAttempt" size="sm" class="gap-1.5">
                        <Icon name="quiz" class="h-3.5 w-3.5" />
                        Kerjakan Kuis
                    </Btn>
                </template>
            </PageHeader>

            <div ref="contentRoot" class="aksara-surface space-y-6 p-6 md:p-8">
                <div
                    v-for="(section, idx) in material.sections"
                    :key="idx"
                    class="space-y-2 border-b border-aksara-line/60 pb-6 last:border-0 last:pb-0"
                >
                    <h3 v-if="section.heading" class="text-lg font-semibold text-aksara-ink">
                        {{ section.heading }}
                    </h3>
                    <div
                        v-if="section.body"
                        class="aksara-prose ProseMirror min-h-0 !p-0 text-sm leading-relaxed text-aksara-ink"
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
                    class="rounded-xl border border-aksara-line border-l-4 border-l-aksara-teal bg-aksara-mist/40 p-5"
                >
                    <h4 class="mb-2 text-sm font-semibold text-aksara-teal-dark">Pertanyaan Refleksi</h4>
                    <ul class="m-0 list-inside list-disc space-y-1.5 text-sm text-aksara-ink">
                        <li v-for="(item, i) in material.reflections" :key="i">{{ item }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
