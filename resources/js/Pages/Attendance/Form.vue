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
import Alert from '@/Components/ui/Alert.vue';
import Btn from '@/Components/ui/Btn.vue';
import Icon from '@/Components/ui/Icon.vue';

const props = defineProps({
    plan: { type: Object, required: true },
    students: { type: Array, default: () => [] },
    statuses: { type: Object, required: true },
    saveUrl: { type: String, required: true },
    plansUrl: { type: String, required: true },
});

const form = useForm({
    attendance: Object.fromEntries(
        props.students.map((s) => [s.id, { status: s.status, notes: s.notes || '' }]),
    ),
});

function submit() {
    form.post(props.saveUrl, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="`Input Kehadiran — ${plan.topic}`">
        <template #header>Input Kehadiran</template>

        <div class="space-y-5">
            <PageHeader
                title="Input Kehadiran"
                :description="`${plan.topic} · Kelas ${plan.className || '—'}`"
            >
                <template #actions>
                    <Btn :href="plansUrl" variant="secondary" size="sm" class="gap-1.5">
                        <Icon name="arrow-left" class="h-3.5 w-3.5" />
                        Rencana
                    </Btn>
                </template>
            </PageHeader>

            <Alert tone="warning" title="Catatan kehadiran">
                Input kehadiran untuk <strong>{{ plan.topic }}</strong> — Kelas {{ plan.className || '—' }}.
            </Alert>

            <div v-if="!students.length" class="aksara-surface-dashed p-10 text-center">
                <h3 class="text-lg font-semibold text-aksara-ink">Tidak ada siswa</h3>
                <p class="mt-2 text-sm text-aksara-muted">Tidak ada siswa terdaftar di kelas ini.</p>
            </div>

            <form v-else class="aksara-surface" @submit.prevent="submit">
                <div class="overflow-x-auto">
                    <table class="aksara-table w-full min-w-[640px]">
                        <thead>
                            <tr>
                                <th class="aksara-th">Nama Siswa</th>
                                <th
                                    v-for="(label, val) in statuses"
                                    :key="val"
                                    class="aksara-th text-center"
                                >
                                    {{ label }}
                                </th>
                                <th class="aksara-th">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="student in students" :key="student.id" class="hover:bg-aksara-mist/40">
                                <td class="aksara-td font-medium text-aksara-ink">{{ student.name }}</td>
                                <td
                                    v-for="(label, val) in statuses"
                                    :key="val"
                                    class="aksara-td text-center"
                                >
                                    <input
                                        v-model="form.attendance[student.id].status"
                                        type="radio"
                                        :value="val"
                                        :name="`att_${student.id}`"
                                        class="h-4 w-4 accent-aksara-teal"
                                    />
                                </td>
                                <td class="aksara-td">
                                    <input
                                        v-model="form.attendance[student.id].notes"
                                        type="text"
                                        placeholder="Opsional…"
                                        class="aksara-input text-xs"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="aksara-form-actions border-t border-aksara-line px-4 py-4 sm:px-5">
                    <Btn type="submit" id="btn-save-attendance" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan…' : 'Simpan Kehadiran' }}
                    </Btn>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
