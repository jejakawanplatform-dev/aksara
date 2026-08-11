<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Field from '@/Components/ui/Field.vue';
import Btn from '@/Components/ui/Btn.vue';
import Icon from '@/Components/ui/Icon.vue';
import IconButton from '@/Components/ui/IconButton.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import Modal from '@/Components/ui/Modal.vue';

const props = defineProps({
    pageTitle: { type: String, default: 'Manajemen Pengguna' },
    users: { type: Object, required: true },
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

const perPage = computed(() => Number(props.filters.per_page) || Number(props.users.per_page) || 10);

const filterQuery = computed(() => ({
    search: localFilters.search || undefined,
    role: localFilters.role || undefined,
    per_page: perPage.value,
    linksUserId: props.linksUser?.id,
}));

let filterTimer = null;
watch(
    localFilters,
    () => {
        clearTimeout(filterTimer);
        filterTimer = setTimeout(() => {
            router.get(
                props.urls.index,
                {
                    ...filterQuery.value,
                    page: 1,
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
    router.get(
        props.urls.index,
        {
            ...filterQuery.value,
            linksUserId: user.id,
        },
        { preserveState: true },
    );
}

function closeLinks() {
    router.get(
        props.urls.index,
        {
            search: localFilters.search || undefined,
            role: localFilters.role || undefined,
            per_page: perPage.value,
        },
        { preserveState: true, replace: true },
    );
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

        <div class="space-y-5">
            <PageHeader
                title="Kelola Pengguna Sistem"
                description="Pengelolaan akun pengguna, penetapan role, dan penautan wali kelas/ortu."
            >
                <template #actions>
                    <Btn type="button" size="sm" class="gap-1.5" @click="openCreate">
                        <Icon name="plus" class="h-3.5 w-3.5" />
                        Tambah pengguna
                    </Btn>
                </template>
            </PageHeader>

            <div class="aksara-surface p-4 sm:p-5">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
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
                            <option value="">Semua role</option>
                            <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
                        </select>
                    </Field>
                </div>
            </div>

            <div v-if="!users.data?.length" class="aksara-surface-dashed p-10 text-center">
                <h3 class="text-lg font-semibold text-aksara-ink">Belum ada pengguna</h3>
                <p class="mt-2 text-sm text-aksara-muted">Tidak ada data yang sesuai filter.</p>
                <div class="mt-4 flex justify-center">
                    <Btn type="button" size="sm" class="gap-1.5" @click="openCreate">
                        <Icon name="plus" class="h-3.5 w-3.5" />
                        Tambah pengguna
                    </Btn>
                </div>
            </div>

            <div v-else class="aksara-surface">
                <div class="overflow-x-auto">
                    <table class="aksara-table w-full min-w-[640px]">
                        <thead>
                            <tr>
                                <th class="aksara-th">Nama</th>
                                <th class="aksara-th">Email</th>
                                <th class="aksara-th">Role</th>
                                <th class="aksara-th w-36 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-aksara-mist/40">
                                <td class="aksara-td font-semibold text-aksara-ink">{{ user.name }}</td>
                                <td class="aksara-td text-sm text-aksara-muted">{{ user.email }}</td>
                                <td class="aksara-td text-sm">{{ user.roleLabel }}</td>
                                <td class="aksara-td">
                                    <div class="flex flex-wrap items-center justify-end gap-0.5">
                                        <IconButton icon="access" label="Tautan" @click="openLinks(user)" />
                                        <IconButton icon="pencil" label="Edit" @click="openEdit(user)" />
                                        <IconButton icon="trash" label="Hapus" danger @click="deleteUser(user)" />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-4 pb-4 sm:px-5">
                    <Pagination
                        :paginator="users"
                        :per-page="perPage"
                        :base-url="urls.index"
                        :query="filterQuery"
                    />
                </div>
            </div>
        </div>

        <Modal
            :open="showForm"
            :title="editingId ? 'Edit pengguna' : 'Tambah pengguna'"
            @close="closeForm"
        >
            <form class="space-y-3" @submit.prevent="saveUser">
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
            </form>
            <template #footer>
                <Btn type="button" variant="secondary" size="sm" @click="closeForm">Batal</Btn>
                <Btn type="button" size="sm" :disabled="userForm.processing" @click="saveUser">Simpan</Btn>
            </template>
        </Modal>

        <Modal
            :open="!!linksUser"
            :title="linksUser ? `Tautan — ${linksUser.name}` : 'Tautan'"
            description="Rombel, anak, atau homeroom"
            @close="closeLinks"
        >
            <template v-if="linksUser">
                <div v-if="linksUser.isStudent" class="space-y-3">
                    <h4 class="text-sm font-semibold text-aksara-ink">Rombel</h4>
                    <ul class="space-y-1 text-sm">
                        <li v-for="c in linksUser.classes" :key="c.id" class="flex items-center justify-between">
                            <span>{{ c.name }}</span>
                            <IconButton icon="x-mark" label="Lepas rombel" danger @click="detachClass(c.id)" />
                        </li>
                    </ul>
                    <div class="flex gap-2">
                        <select v-model="attachClassForm.class_id" class="aksara-select flex-1">
                            <option value="">Pilih rombel</option>
                            <option v-for="c in classes" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                        </select>
                        <Btn type="button" size="sm" @click="attachClass">Tambah</Btn>
                    </div>
                </div>

                <div v-if="linksUser.isParent" class="space-y-3">
                    <h4 class="text-sm font-semibold text-aksara-ink">Anak</h4>
                    <ul class="space-y-1 text-sm">
                        <li v-for="c in linksUser.children" :key="c.id" class="flex items-center justify-between">
                            <span>{{ c.name }}</span>
                            <IconButton icon="x-mark" label="Lepas tautan" danger @click="detachChild(c.id)" />
                        </li>
                    </ul>
                    <div class="flex gap-2">
                        <select v-model="attachChildForm.child_id" class="aksara-select flex-1">
                            <option value="">Pilih siswa</option>
                            <option v-for="s in students" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
                        </select>
                        <Btn type="button" size="sm" @click="attachChild">Tautkan</Btn>
                    </div>
                </div>

                <div v-if="linksUser.isHomeroomTeacher" class="space-y-3">
                    <h4 class="text-sm font-semibold text-aksara-ink">Homeroom</h4>
                    <select v-model="homeroomForm.homeroom_class_id" class="aksara-select w-full">
                        <option value="">— Tidak ditugaskan —</option>
                        <option v-for="c in classes" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                    </select>
                    <Btn type="button" size="sm" @click="saveHomeroom">Simpan homeroom</Btn>
                </div>
            </template>
        </Modal>
    </AppLayout>
</template>
