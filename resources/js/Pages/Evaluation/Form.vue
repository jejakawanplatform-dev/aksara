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
import Alert from '@/Components/ui/Alert.vue';
import Icon from '@/Components/ui/Icon.vue';
import TipTapEditor from '@/Components/tiptap/TipTapEditor.vue';

const props = defineProps({
    plan: { type: Object, required: true },
    form: { type: Object, required: true },
    isStem: { type: Boolean, default: false },
    saveUrl: { type: String, required: true },
    plansUrl: { type: String, required: true },
});

const editor = useForm({
    notes: props.form.notes || '',
    challenges: props.form.challenges || '',
    next_action: props.form.next_action || '',
});

function submit() {
    editor.post(props.saveUrl, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="`Refleksi Guru — ${plan.topic}`">
        <template #header>Refleksi Guru</template>

        <div class="mx-auto max-w-3xl space-y-5">
            <PageHeader
                title="Refleksi Guru"
                :description="`${plan.topic} · Evaluasi efektivitas alur pembelajaran dan peningkatan kualitas mengajar.`"
            >
                <template #actions>
                    <Btn :href="plansUrl" variant="secondary" size="sm" class="gap-1.5">
                        <Icon name="arrow-left" class="h-3.5 w-3.5" />
                        Rencana
                    </Btn>
                </template>
            </PageHeader>

            <Alert tone="ai" title="Refleksi pembelajaran">
                Refleksi adalah bagian penting dari siklus pembelajaran. Tuliskan dengan jujur apa yang terjadi, apa
                yang bisa diperbaiki, dan langkah konkret berikutnya.
            </Alert>

            <form class="aksara-surface p-4 sm:p-6" @submit.prevent="submit">
                <div class="space-y-6">
                    <Field
                        label="Catatan Pembelajaran"
                        required
                        hint="Apa yang berjalan baik hari ini? Momen penting atau metode yang berhasil."
                        :error="editor.errors.notes"
                    >
                        <TipTapEditor v-model="editor.notes" :with-math="isStem" />
                    </Field>

                    <Field
                        label="Tantangan yang Dihadapi"
                        required
                        hint="Apa yang masih sulit? Kendala teknis, pemahaman siswa, atau manajemen waktu."
                        :error="editor.errors.challenges"
                    >
                        <TipTapEditor v-model="editor.challenges" :with-math="isStem" />
                    </Field>

                    <Field
                        label="Rencana Tindak Lanjut"
                        required
                        hint="Langkah konkret perbaikan untuk pembelajaran berikutnya."
                        :error="editor.errors.next_action"
                    >
                        <TipTapEditor v-model="editor.next_action" :with-math="isStem" />
                    </Field>
                </div>

                <div class="aksara-form-actions mt-6 border-t border-aksara-line pt-4">
                    <Btn id="btn-save-eval" type="submit" :disabled="editor.processing">
                        {{ editor.processing ? 'Menyimpan…' : 'Simpan Refleksi' }}
                    </Btn>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
