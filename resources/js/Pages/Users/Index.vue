<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
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
import BulkToolbar from '@/Components/ui/BulkToolbar.vue';
import BulkConfirmModal from '@/Components/ui/BulkConfirmModal.vue';
import UserImportModal from '@/Components/users/UserImportModal.vue';
import { useBulkSelect } from '@/Composables/useBulkSelect';

const props = defineProps({
    pageTitle: { type: String, default: 'Manajemen Pengguna' },
    users: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    roles: { type: Array, default: () => [] },
    classes: { type: Array, default: () => [] },
    students: { type: Array, default: () => [] },
    linksUser: { type: Object, default: null },
    urls: { type: Object, required: true },
    importErrors: { type: Array, default: () => [] },
    hasCredentials: { type: Boolean, default: false },
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

const bulk = useBulkSelect();
const showBulkDeleteModal = ref(false);
const isBulkDeleting = ref(false);

function openBulkDelete() {
    showBulkDeleteModal.value = true;
}

function submitBulkDelete() {
    isBulkDeleting.value = true;
    router.post(
        props.urls.bulkDestroy,
        {
            ids: bulk.isAllMatching.value ? [] : bulk.selectedIds.value,
            select_all_matching: bulk.isAllMatching.value,
            search: localFilters.search || undefined,
            role: localFilters.role || undefined,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                bulk.clearSelection();
                showBulkDeleteModal.value = false;
            },
            onFinish: () => {
                isBulkDeleting.value = false;
            },
        },
    );
}

const showImportModal = ref(props.importErrors?.length > 0);

function exportFiltered() {
    const params = new URLSearchParams();
    if (localFilters.search) params.append('search', localFilters.search);
    if (localFilters.role) params.append('role', localFilters.role);
    const qs = params.toString();
    window.location.href = props.urls.export + (qs ? `?${qs}` : '');
}

function exportSelected() {
    const params = new URLSearchParams();
    if (bulk.isAllMatching.value) {
        if (localFilters.search) params.append('search', localFilters.search);
        if (localFilters.role) params.append('role', localFilters.role);
    } else {
        bulk.selectedIds.value.forEach((id) => params.append('ids[]', String(id)));
    }
    const qs = params.toString();
    window.location.href = props.urls.export + (qs ? `?${qs}` : '');
}

let filterTimer = null;
watch(
    localFilters,
    () => {
        bulk.clearSelection();
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
                    <div class="flex flex-wrap items-center gap-2">
                        <Btn type="button" variant="secondary" size="sm" class="gap-1.5" @click="showImportModal = true">
                            <Icon name="upload" class="h-3.5 w-3.5 text-aksara-primary" />
                            Impor Excel
                        </Btn>
                        <Btn type="button" variant="secondary" size="sm" class="gap-1.5" @click="exportFiltered">
                            <Icon name="download" class="h-3.5 w-3.5 text-aksara-primary" />
                            Ekspor Excel
                        </Btn>
                        <Btn type="button" size="sm" class="gap-1.5" @click="openCreate">
                            <Icon name="plus" class="h-3.5 w-3.5" />
                            Tambah pengguna
                        </Btn>
                    </div>
                </template>
            </PageHeader>

            <!-- Banner Kredensial Baru Tersedia Pasca-Impor -->
            <div
                v-if="hasCredentials"
                class="flex flex-col gap-3 rounded-xl border border-aksara-ok/30 bg-aksara-ok/10 p-4 text-sm text-aksara-ink sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-2.5">
                    <Icon name="check" class="h-5 w-5 shrink-0 text-aksara-ok" />
                    <div>
                        <p class="font-semibold text-aksara-ok">Impor Pengguna Berhasil Diproses!</p>
                        <p class="text-xs text-aksara-muted">
                            Kata sandi acak telah dibuat untuk akun baru. Silakan unduh rekap kredensial sebelum meninggalkan sesi ini.
                        </p>
                    </div>
                </div>
                <a
                    :href="urls.credentialsDownload"
                    class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-lg bg-aksara-primary px-3.5 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-aksara-primary/90"
                    download
                >
                    <Icon name="download" class="h-4 w-4" />
                    Unduh Rekap Kredensial (.xlsx)
                </a>
            </div>

            <!-- Contextual Toolbar: Swap antara Filter biasa dan Aksi Massal -->
            <BulkToolbar
                v-if="bulk.hasSelection.value"
                :selected-count="bulk.getSelectedCount(users.total)"
                :total-count="users.total || 0"
                :current-page-count="users.data?.length || 0"
                :is-all-matching="bulk.isAllMatching.value"
                item-label="pengguna"
                @clear="bulk.clearSelection"
                @select-all-matching="bulk.selectAllMatching"
                @clear-matching="bulk.isAllMatching.value = false"
            >
                <template #actions>
                    <div class="flex flex-wrap items-center gap-2">
                        <Btn
                            type="button"
                            variant="secondary"
                            size="sm"
                            class="gap-1.5"
                            @click="exportSelected"
                        >
                            <Icon name="download" class="h-3.5 w-3.5" />
                            Ekspor terpilih ({{ bulk.getSelectedCount(users.total) }})
                        </Btn>
                        <Btn
                            type="button"
                            variant="danger"
                            size="sm"
                            class="gap-1.5"
                            @click="openBulkDelete"
                        >
                            <Icon name="trash" class="h-3.5 w-3.5" />
                            Hapus terpilih ({{ bulk.getSelectedCount(users.total) }})
                        </Btn>
                    </div>
                </template>
            </BulkToolbar>

            <div v-else class="aksara-surface p-4 sm:p-5">
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
                                <th class="aksara-th w-10 text-center">
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 cursor-pointer rounded border-aksara-line text-aksara-primary focus:ring-aksara-primary/30"
                                        :checked="bulk.isAllSelected(users.data)"
                                        :indeterminate="bulk.isIndeterminate(users.data)"
                                        aria-label="Pilih semua di halaman ini"
                                        @change="bulk.toggleSelectAll(users.data)"
                                    />
                                </th>
                                <th class="aksara-th">Nama</th>
                                <th class="aksara-th">Email</th>
                                <th class="aksara-th">Role</th>
                                <th class="aksara-th w-36 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="user in users.data"
                                :key="user.id"
                                :class="[
                                    'transition-colors',
                                    bulk.isSelected(user.id) || bulk.isAllMatching.value
                                        ? 'bg-aksara-primary/5 hover:bg-aksara-primary/10'
                                        : 'hover:bg-aksara-mist/40',
                                ]"
                            >
                                <td class="aksara-td w-10 text-center">
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 cursor-pointer rounded border-aksara-line text-aksara-primary focus:ring-aksara-primary/30"
                                        :checked="bulk.isSelected(user.id) || bulk.isAllMatching.value"
                                        aria-label="Pilih pengguna"
                                        @change="bulk.toggleSelect(user.id)"
                                    />
                                </td>
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

        <BulkConfirmModal
            :open="showBulkDeleteModal"
            title="Hapus Pengguna Terpilih"
            description="Akun pengguna yang dipilih akan dihapus secara permanen beserta relasi rombel dan perwalian. Guru yang memiliki rencana pembelajaran aktif dan wali kelas yang terikat rombel akan otomatis dilewati demi integritas data."
            :count="bulk.getSelectedCount(users.total)"
            item-label="pengguna"
            confirm-word="HAPUS"
            :danger="true"
            :processing="isBulkDeleting"
            confirm-button-text="Ya, Hapus Sekarang"
            @close="showBulkDeleteModal = false"
            @confirm="submitBulkDelete"
        />

        <UserImportModal
            :open="showImportModal"
            :import-url="urls.import"
            :template-url="urls.template"
            :server-errors="importErrors"
            @close="showImportModal = false"
        />
    </AppLayout>
</template>
