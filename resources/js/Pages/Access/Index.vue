<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Btn from '@/Components/ui/Btn.vue';

const props = defineProps({
    pageTitle: { type: String, default: 'Hak Akses (RBAC)' },
    matrix: { type: Object, required: true },
    roles: { type: Array, required: true },
    permissions: { type: Array, required: true },
    lockedAdmin: { type: Array, default: () => [] },
    urls: { type: Object, required: true },
});

const form = useForm({
    matrix: structuredClone(props.matrix),
});

function isLocked(roleValue, permName) {
    return roleValue === 'admin' && props.lockedAdmin.includes(permName);
}

function save() {
    form.put(props.urls.save, { preserveScroll: true });
}

function resetDefaults() {
    if (!window.confirm('Kembalikan semua role ke matrix default?')) return;
    form.post(props.urls.resetDefaults, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="pageTitle">
        <template #header>{{ pageTitle }}</template>

        <div class="space-y-6">
            <Card
                title="Matrix Hak Akses & Otorisasi"
                description="Pengaturan hak akses permission per role secara terpusat. Role tetap (enum). Permission wajib admin terkunci."
            >
                <template #actions>
                    <div class="flex flex-wrap gap-2">
                        <Btn type="button" :disabled="form.processing" @click="save">Simpan matrix</Btn>
                        <Btn type="button" variant="secondary" :disabled="form.processing" @click="resetDefaults">
                            Reset default
                        </Btn>
                    </div>
                </template>
            </Card>

            <div class="overflow-x-auto rounded-2xl border border-aksara-line bg-white">
                <table class="min-w-full text-sm">
                    <thead class="bg-aksara-mist text-left text-xs uppercase text-aksara-muted">
                        <tr>
                            <th class="sticky left-0 z-10 bg-aksara-mist px-4 py-3">Permission</th>
                            <th
                                v-for="role in roles"
                                :key="role.value"
                                class="px-4 py-3 text-center whitespace-nowrap"
                            >
                                {{ role.label }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-aksara-line">
                        <tr v-for="perm in permissions" :key="perm.name">
                            <td class="sticky left-0 z-10 bg-white px-4 py-3">
                                <p class="font-medium text-aksara-ink">{{ perm.label }}</p>
                                <p class="text-xs text-aksara-muted">{{ perm.name }}</p>
                            </td>
                            <td
                                v-for="role in roles"
                                :key="`${role.value}-${perm.wireKey}`"
                                class="px-4 py-3 text-center"
                            >
                                <input
                                    v-model="form.matrix[role.value][perm.wireKey]"
                                    type="checkbox"
                                    class="rounded border-aksara-line text-aksara-teal focus:ring-aksara-teal disabled:opacity-60"
                                    :disabled="isLocked(role.value, perm.name)"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
