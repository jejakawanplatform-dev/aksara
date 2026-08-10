<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Btn from '@/Components/ui/Btn.vue';

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
        <template #header>
            <div class="flex items-center gap-3">
                <a :href="plansUrl" class="text-aksara-muted hover:text-aksara-ink">← Rencana</a>
                <span class="text-aksara-line">/</span>
                <span>Input Kehadiran — {{ plan.topic }}</span>
            </div>
        </template>

        <Card>
            <div class="mb-4 flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700">
                <span>
                    Input kehadiran untuk: <strong>{{ plan.topic }}</strong> — Kelas {{ plan.className || '-' }}
                </span>
            </div>

            <p v-if="!students.length" class="py-8 text-center text-sm text-aksara-muted">
                Tidak ada siswa terdaftar di kelas ini.
            </p>

            <form v-else class="space-y-4" @submit.prevent="submit">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-aksara-line text-left text-xs uppercase tracking-wide text-aksara-muted">
                                <th class="pb-3 pr-4">Nama Siswa</th>
                                <th
                                    v-for="(label, val) in statuses"
                                    :key="val"
                                    class="pb-3 pr-3 text-center"
                                >
                                    {{ label }}
                                </th>
                                <th class="pb-3">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-aksara-line/60">
                            <tr v-for="student in students" :key="student.id" class="hover:bg-aksara-mist/30">
                                <td class="py-3 pr-4 font-medium text-aksara-ink">{{ student.name }}</td>
                                <td
                                    v-for="(label, val) in statuses"
                                    :key="val"
                                    class="py-3 pr-3 text-center"
                                >
                                    <input
                                        v-model="form.attendance[student.id].status"
                                        type="radio"
                                        :value="val"
                                        :name="`att_${student.id}`"
                                        class="h-4 w-4 accent-aksara-teal"
                                    />
                                </td>
                                <td class="py-3">
                                    <input
                                        v-model="form.attendance[student.id].notes"
                                        type="text"
                                        placeholder="opsional..."
                                        class="aksara-input text-xs"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end border-t border-aksara-line pt-4">
                    <Btn type="submit" id="btn-save-attendance" :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Kehadiran' }}
                    </Btn>
                </div>
            </form>
        </Card>
    </AppLayout>
</template>
