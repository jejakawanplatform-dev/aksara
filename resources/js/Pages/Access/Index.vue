<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Btn from '@/Components/ui/Btn.vue';
import Icon from '@/Components/ui/Icon.vue';
import IconButton from '@/Components/ui/IconButton.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';

const props = defineProps({
    pageTitle: { type: String, default: 'Hak Akses (RBAC)' },
    matrix: { type: Object, required: true },
    roles: { type: Array, required: true },
    permissions: { type: Array, required: true },
    lockedAdmin: { type: Array, default: () => [] },
    urls: { type: Object, required: true },
});

const form = useForm({
    // props.* is a Vue Proxy — structuredClone cannot clone proxies
    matrix: JSON.parse(JSON.stringify(props.matrix)),
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

        <div class="space-y-5">
            <PageHeader
                title="Hak Akses (RBAC)"
                description="Matrix permission per role. Role tetap (enum); permission wajib admin terkunci."
            >
                <template #actions>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <Btn type="button" size="sm" class="gap-1.5" :disabled="form.processing" @click="save">
                            <Icon name="save" class="h-3.5 w-3.5" />
                            Simpan matrix
                        </Btn>
                        <IconButton
                            icon="x-mark"
                            label="Reset default"
                            :disabled="form.processing"
                            @click="resetDefaults"
                        />
                    </div>
                </template>
            </PageHeader>

            <div class="aksara-surface overflow-x-auto">
                <table class="aksara-table min-w-full">
                    <thead>
                        <tr>
                            <th class="aksara-th sticky left-0 z-10 bg-aksara-mist/50">Permission</th>
                            <th
                                v-for="role in roles"
                                :key="role.value"
                                class="aksara-th text-center whitespace-nowrap"
                            >
                                {{ role.label }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="perm in permissions" :key="perm.name" class="hover:bg-aksara-mist/40">
                            <td class="aksara-td sticky left-0 z-10 bg-white">
                                <p class="font-medium text-aksara-ink">{{ perm.label }}</p>
                                <p class="text-xs text-aksara-muted">{{ perm.name }}</p>
                            </td>
                            <td
                                v-for="role in roles"
                                :key="`${role.value}-${perm.wireKey}`"
                                class="aksara-td text-center"
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
