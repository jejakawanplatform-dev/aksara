<script setup>
import { reactive, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Field from '@/Components/ui/Field.vue';
import Btn from '@/Components/ui/Btn.vue';

const props = defineProps({
    pageTitle: { type: String, default: 'Manajemen Pengguna' },
    users: { type: Array, required: true },
    filters: { type: Object, default: () => ({}) },
    roles: { type: Array, default: () => [] },
    classes: { type: Array, default: () => [] },
    students: { type: Array, default: () => [] },
    linksUser: { type: Object, default: null },
    urls: { type: Object, required: true },
});

const localFilters = reactive({
    search: props.filters.search || '',
    role: props.filters.role || '',
});

let filterTimer = null;
watch(
    localFilters,
    () => {
        clearTimeout(filterTimer);
        filterTimer = setTimeout(() => {
            router.get(
                props.urls.index,
                {
                    search: localFilters.search || undefined,
                    role: localFilters.role || undefined,
                    linksUserId: props.linksUser?.id,
                },
                { preserveState: true, replace: true },
            );
        }, 300);
    },
    { deep: true },
);

const showForm = ref(false);
const editingId = ref(null);

const userForm = useForm({
    name: '',
    email: '',
    role: 'student',
    password: '',
    password_confirmation: '',
});

function openCreate() {
    editingId.value = null;
    userForm.reset();
    userForm.role = 'student';
    userForm.clearErrors();
    showForm.value = true;
}

function openEdit(user) {
    editingId.value = user.id;
    userForm.name = user.name;
    userForm.email = user.email;
    userForm.role = user.role || 'student';
    userForm.password = '';
    userForm.password_confirmation = '';
    userForm.clearErrors();
    showForm.value = true;
}

function closeForm() {
    showForm.value = false;
    editingId.value = null;
}

function urlFor(template, replacements) {
    let url = template;
    Object.entries(replacements).forEach(([key, value]) => {
        url = url.replace(key, String(value));
    });
    return url;
}

function saveUser() {
    if (editingId.value) {
        userForm.put(urlFor(props.urls.update, { __ID__: editingId.value }), {
            onSuccess: () => closeForm(),
        });
    } else {
        userForm.post(props.urls.store, {
            onSuccess: () => closeForm(),
        });
    }
}

function deleteUser(user) {
    if (!window.confirm(`Hapus pengguna ${user.name}?`)) return;
    router.delete(urlFor(props.urls.destroy, { __ID__: user.id }));
}

function openLinks(user) {
    router.get(props.urls.index, {
        search: localFilters.search || undefined,
        role: localFilters.role || undefined,
        linksUserId: user.id,
    }, { preserveState: true });
}

function closeLinks() {
    router.get(props.urls.index, {
        search: localFilters.search || undefined,
        role: localFilters.role || undefined,
    }, { preserveState: true, replace: true });
}

const attachClassForm = useForm({ class_id: '' });
const attachChildForm = useForm({ child_id: '' });
const homeroomForm = useForm({
    homeroom_class_id: props.linksUser?.homeroomClassId ? String(props.linksUser.homeroomClassId) : '',
});

watch(
    () => props.linksUser,
    (lu) => {
        homeroomForm.homeroom_class_id = lu?.homeroomClassId ? String(lu.homeroomClassId) : '';
        attachClassForm.class_id = '';
        attachChildForm.child_id = '';
    },
);

function attachClass() {
    if (!props.linksUser) return;
    attachClassForm.post(urlFor(props.urls.attachClass, { __ID__: props.linksUser.id }), {
        onSuccess: () => attachClassForm.reset(),
    });
}

function detachClass(classId) {
    if (!props.linksUser) return;
    router.delete(urlFor(props.urls.detachClass, { __UID__: props.linksUser.id, __CID__: classId }));
}

function attachChild() {
    if (!props.linksUser) return;
    attachChildForm.post(urlFor(props.urls.attachChild, { __ID__: props.linksUser.id }), {
        onSuccess: () => attachChildForm.reset(),
    });
}

function detachChild(childId) {
    if (!props.linksUser) return;
    router.delete(urlFor(props.urls.detachChild, { __UID__: props.linksUser.id, __CID__: childId }));
}

function saveHomeroom() {
    if (!props.linksUser) return;
    homeroomForm.post(urlFor(props.urls.homeroom, { __ID__: props.linksUser.id }));
}
</script>

<template>
    <AppLayout :title="pageTitle">
        <template #header>{{ pageTitle }}</template>

        <div class="space-y-6">
            <Card
                title="Kelola Pengguna Sistem"
                description="Pengelolaan akun pengguna, penetapan role, dan penautan wali kelas/ortu."
            >
                <template #actions>
                    <Btn type="button" @click="openCreate">Tambah pengguna</Btn>
                </template>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3">
                    <Field label="Cari" for-id="user-search">
                        <input
                            id="user-search"
                            v-model="localFilters.search"
                            type="search"
                            class="aksara-input"
                            placeholder="Nama atau email"
                        />
                    </Field>
                    <Field label="Role" for-id="user-role">
                        <select id="user-role" v-model="localFilters.role" class="aksara-select">
                            <option value="">Semua</option>
                            <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
                        </select>
                    </Field>
                </div>
            </Card>

            <div class="overflow-hidden rounded-2xl border border-aksara-line bg-white">
                <table class="w-full text-sm">
                    <thead class="bg-aksara-mist text-left text-xs uppercase text-aksara-muted">
                        <tr>
                            <th class="px-5 py-3">Nama</th>
                            <th class="px-5 py-3">Email</th>
                            <th class="px-5 py-3">Role</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-aksara-line">
                        <tr v-for="user in users" :key="user.id">
                            <td class="px-5 py-3 font-medium">{{ user.name }}</td>
                            <td class="px-5 py-3 text-aksara-muted">{{ user.email }}</td>
                            <td class="px-5 py-3">{{ user.roleLabel }}</td>
                            <td class="space-x-2 px-5 py-3 text-right">
                                <button type="button" class="text-xs font-semibold text-aksara-teal" @click="openLinks(user)">
                                    Tautan
                                </button>
                                <button type="button" class="text-xs font-semibold text-aksara-ink" @click="openEdit(user)">
                                    Edit
                                </button>
                                <button type="button" class="text-xs font-semibold text-red-600" @click="deleteUser(user)">
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create / Edit modal -->
        <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-aksara-ink/40" @click="closeForm" />
            <div class="relative z-10 w-full max-w-lg rounded-2xl border border-aksara-line bg-white p-6 shadow-lg">
                <h3 class="font-display text-lg font-semibold text-aksara-ink">
                    {{ editingId ? 'Edit pengguna' : 'Tambah pengguna' }}
                </h3>
                <form class="mt-4 space-y-3" @submit.prevent="saveUser">
                    <Field label="Nama" required for-id="uf-name" :error="userForm.errors.name">
                        <input id="uf-name" v-model="userForm.name" class="aksara-input" />
                    </Field>
                    <Field label="Email" required for-id="uf-email" :error="userForm.errors.email">
                        <input id="uf-email" v-model="userForm.email" type="email" class="aksara-input" />
                    </Field>
                    <Field label="Role" required for-id="uf-role" :error="userForm.errors.role">
                        <select id="uf-role" v-model="userForm.role" class="aksara-select">
                            <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
                        </select>
                    </Field>
                    <Field
                        :label="editingId ? 'Password (opsional)' : 'Password'"
                        :required="!editingId"
                        for-id="uf-password"
                        :error="userForm.errors.password"
                    >
                        <input id="uf-password" v-model="userForm.password" type="password" class="aksara-input" />
                    </Field>
                    <Field label="Konfirmasi password" for-id="uf-password2">
                        <input id="uf-password2" v-model="userForm.password_confirmation" type="password" class="aksara-input" />
                    </Field>
                    <div class="flex justify-end gap-2 pt-2">
                        <Btn type="button" variant="secondary" @click="closeForm">Batal</Btn>
                        <Btn type="submit" :disabled="userForm.processing">Simpan</Btn>
                    </div>
                </form>
            </div>
        </div>

        <!-- Links modal -->
        <div v-if="linksUser" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-aksara-ink/40" @click="closeLinks" />
            <div class="relative z-10 max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-aksara-line bg-white p-6 shadow-lg">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-display text-lg font-semibold text-aksara-ink">Tautan — {{ linksUser.name }}</h3>
                        <p class="text-xs text-aksara-muted">Rombel, anak, atau homeroom</p>
                    </div>
                    <button type="button" class="text-sm text-aksara-muted" @click="closeLinks">Tutup</button>
                </div>

                <div v-if="linksUser.isStudent" class="mt-4 space-y-3">
                    <h4 class="text-sm font-semibold">Rombel</h4>
                    <ul class="space-y-1 text-sm">
                        <li v-for="c in linksUser.classes" :key="c.id" class="flex justify-between">
                            <span>{{ c.name }}</span>
                            <button type="button" class="text-xs text-red-600" @click="detachClass(c.id)">Lepas</button>
                        </li>
                    </ul>
                    <div class="flex gap-2">
                        <select v-model="attachClassForm.class_id" class="aksara-select flex-1">
                            <option value="">Pilih rombel</option>
                            <option v-for="c in classes" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                        </select>
                        <Btn type="button" class="!px-3 !py-2 text-xs" @click="attachClass">Tambah</Btn>
                    </div>
                </div>

                <div v-if="linksUser.isParent" class="mt-4 space-y-3">
                    <h4 class="text-sm font-semibold">Anak</h4>
                    <ul class="space-y-1 text-sm">
                        <li v-for="c in linksUser.children" :key="c.id" class="flex justify-between">
                            <span>{{ c.name }}</span>
                            <button type="button" class="text-xs text-red-600" @click="detachChild(c.id)">Lepas</button>
                        </li>
                    </ul>
                    <div class="flex gap-2">
                        <select v-model="attachChildForm.child_id" class="aksara-select flex-1">
                            <option value="">Pilih siswa</option>
                            <option v-for="s in students" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
                        </select>
                        <Btn type="button" class="!px-3 !py-2 text-xs" @click="attachChild">Tautkan</Btn>
                    </div>
                </div>

                <div v-if="linksUser.isHomeroomTeacher" class="mt-4 space-y-3">
                    <h4 class="text-sm font-semibold">Homeroom</h4>
                    <select v-model="homeroomForm.homeroom_class_id" class="aksara-select w-full">
                        <option value="">— Tidak ditugaskan —</option>
                        <option v-for="c in classes" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                    </select>
                    <Btn type="button" class="!px-3 !py-2 text-xs" @click="saveHomeroom">Simpan homeroom</Btn>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
