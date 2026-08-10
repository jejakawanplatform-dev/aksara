<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Field from '@/Components/ui/Field.vue';
import Btn from '@/Components/ui/Btn.vue';
import Alert from '@/Components/ui/Alert.vue';
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
        <template #header>
            <div class="flex items-center gap-3">
                <a :href="plansUrl" class="text-aksara-muted hover:text-aksara-ink">← Rencana</a>
                <span class="text-aksara-line">/</span>
                <span>Refleksi Guru — {{ plan.topic }}</span>
            </div>
        </template>

        <div class="mx-auto max-w-3xl space-y-5">
            <Alert tone="ai" title="Refleksi pembelajaran">
                Refleksi adalah bagian penting dari siklus pembelajaran. Tuliskan dengan jujur apa yang terjadi, apa
                yang bisa diperbaiki, dan langkah konkret berikutnya.
            </Alert>

            <Card
                :title="`Refleksi Guru: ${plan.topic}`"
                description="Refleksi pembelajaran digunakan untuk mengevaluasi efektivitas alur pembelajaran dan peningkatan kualitas mengajar."
            >
                <form class="space-y-6" @submit.prevent="submit">
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

                    <div class="flex justify-end border-t border-aksara-line pt-4">
                        <Btn id="btn-save-eval" type="submit" :disabled="editor.processing">
                            {{ editor.processing ? 'Menyimpan...' : 'Simpan Refleksi' }}
                        </Btn>
                    </div>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
