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

const counts = computed(() => {
    let present = 0;
    let excused = 0;
    let sick = 0;
    let absent = 0;

    for (const student of props.students) {
        const item = form.attendance[student.id];
        if (!item) continue;
        if (item.status === 'present') present++;
        else if (item.status === 'excused') excused++;
        else if (item.status === 'sick') sick++;
        else if (item.status === 'absent') absent++;
    }

    return {
        present,
        excused,
        sick,
        absent,
        total: props.students.length,
    };
});

function markAllPresent() {
    for (const student of props.students) {
        if (form.attendance[student.id]) {
            form.attendance[student.id].status = 'present';
        }
    }
}

function resetToDefault() {
    for (const student of props.students) {
        if (form.attendance[student.id]) {
            form.attendance[student.id].status = student.status;
            form.attendance[student.id].notes = student.notes || '';
        }
    }
}

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
                <!-- Toolbar Aksi Cepat -->
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-aksara-line/80 px-4 py-3 sm:px-5">
                    <div class="flex items-center gap-2">
                        <Btn
                            type="button"
                            variant="secondary"
                            size="sm"
                            class="gap-1.5 font-medium"
                            id="btn-mark-all-present"
                            @click="markAllPresent"
                        >
                            <Icon name="check-circle" class="h-3.5 w-3.5 text-aksara-teal" />
                            Tandai Semua Hadir
                        </Btn>
                        <Btn
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="gap-1.5 text-aksara-muted hover:text-aksara-ink"
                            id="btn-reset-attendance"
                            @click="resetToDefault"
                        >
                            <Icon name="arrow-path" class="h-3.5 w-3.5" />
                            Reset
                        </Btn>
                    </div>
                    <div class="text-xs font-medium text-aksara-muted">
                        Total Siswa: <strong class="text-aksara-ink">{{ counts.total }}</strong>
                    </div>
                </div>

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

                <!-- Footer dengan Live Counter & Tombol Simpan -->
                <div class="flex flex-wrap items-center justify-between gap-4 border-t border-aksara-line px-4 py-4 sm:px-5">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-emerald-50 px-2.5 py-1 text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            {{ counts.present }} Hadir
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-sky-50 px-2.5 py-1 text-sky-700 ring-1 ring-inset ring-sky-600/20">
                            <span class="h-1.5 w-1.5 rounded-full bg-sky-500"></span>
                            {{ counts.excused }} Izin
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-amber-50 px-2.5 py-1 text-amber-700 ring-1 ring-inset ring-amber-600/20">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            {{ counts.sick }} Sakit
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-md bg-rose-50 px-2.5 py-1 text-rose-700 ring-1 ring-inset ring-rose-600/20">
                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                            {{ counts.absent }} Alpha
                        </span>
                    </div>
                    <Btn type="submit" id="btn-save-attendance" :disabled="form.processing" class="gap-1.5">
                        <Icon name="check" class="h-3.5 w-3.5" />
                        {{ form.processing ? 'Menyimpan…' : 'Simpan Kehadiran' }}
                    </Btn>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
