<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import Btn from '@/Components/ui/Btn.vue';
import Field from '@/Components/ui/Field.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import IconButton from '@/Components/ui/IconButton.vue';
import Icon from '@/Components/ui/Icon.vue';
import BulkToolbar from '@/Components/ui/BulkToolbar.vue';
import BulkConfirmModal from '@/Components/ui/BulkConfirmModal.vue';
import ExportMenu from '@/Components/ui/ExportMenu.vue';
import { useBulkSelect } from '@/Composables/useBulkSelect';

const props = defineProps({
    materials: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    indexUrl: { type: String, required: true },
    bulkDestroyUrl: { type: String, default: null },
    isStudent: { type: Boolean, default: false },
});

const localFilters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
});

const perPage = computed(
    () => Number(props.filters.per_page) || Number(props.materials.per_page) || 10,
);

const filterQuery = computed(() => ({
    search: localFilters.search || undefined,
    status: localFilters.status || undefined,
    per_page: perPage.value,
}));

// ── Bulk Select (hanya untuk Guru / Admin) ────────────────────
const bulk = useBulkSelect();
const showBulkDeleteModal = ref(false);
const isBulkDeleting = ref(false);

function openBulkDelete() {
    showBulkDeleteModal.value = true;
}

function submitBulkDelete() {
    if (!props.bulkDestroyUrl) return;
    isBulkDeleting.value = true;
    router.post(
        props.bulkDestroyUrl,
        {
            ids: bulk.isAllMatching.value ? [] : bulk.selectedIds.value,
            select_all_matching: bulk.isAllMatching.value,
            search: localFilters.search || undefined,
            status: localFilters.status || undefined,
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

let filterTimer = null;

watch(
    localFilters,
    () => {
        bulk.clearSelection();
        clearTimeout(filterTimer);
        filterTimer = setTimeout(() => {
            router.get(
                props.indexUrl,
                { ...filterQuery.value, page: 1 },
                { preserveState: true, replace: true },
            );
        }, 300);
    },
    { deep: true },
);

watch(
    () => props.materials.data,
    () => bulk.clearSelection(),
);

function exportItems(material) {
    return [
        { label: 'PDF / Cetak', href: material.exportPdf, icon: 'pdf', target: '_blank' },
        { label: 'Word (.docx)', href: material.exportWord, icon: 'document' },
        { label: 'Markdown (.md)', href: material.exportMarkdown, icon: 'download' },
    ];
}
// ─────────────────────────────────────────────────────────────
</script>

<template>
    <AppLayout title="Materi Pembelajaran">
        <template #header>Materi Pembelajaran</template>

        <div class="space-y-5">
            <PageHeader
                title="Materi Pembelajaran"
                :description="
                    isStudent
                        ? 'Bahan ajar yang diterbitkan untuk kelas Anda.'
                        : 'Bahan ajar yang terhubung ke rencana pembelajaran.'
                "
            />

            <!-- Contextual Toolbar: Swap antara Filter biasa dan Aksi Massal -->
            <BulkToolbar
                v-if="!isStudent && bulk.hasSelection.value"
                :selected-count="bulk.getSelectedCount(materials.total)"
                :total-count="materials.total || 0"
                :current-page-count="materials.data?.length || 0"
                :is-all-matching="bulk.isAllMatching.value"
                item-label="materi"
                @clear="bulk.clearSelection"
                @select-all-matching="bulk.selectAllMatching"
                @clear-matching="bulk.isAllMatching.value = false"
            >
                <template #actions>
                    <Btn
                        type="button"
                        variant="danger"
                        size="sm"
                        class="gap-1.5"
                        @click="openBulkDelete"
                    >
                        <Icon name="trash" class="h-3.5 w-3.5" />
                        Hapus terpilih ({{ bulk.getSelectedCount(materials.total) }})
                    </Btn>
                </template>
            </BulkToolbar>

            <div v-else class="aksara-surface p-4 sm:p-5">
                <div
                    class="grid grid-cols-1 gap-3"
                    :class="isStudent ? 'md:grid-cols-1' : 'md:grid-cols-2'"
                >
                    <Field label="Pencarian judul" for-id="search-materials">
                        <input
                            id="search-materials"
                            v-model="localFilters.search"
                            type="search"
                            class="aksara-input"
                            placeholder="Cari judul / topik…"
                        />
                    </Field>
                    <Field v-if="!isStudent" label="Status" for-id="filter-status">
                        <select id="filter-status" v-model="localFilters.status" class="aksara-select">
                            <option value="">Semua</option>
                            <option value="draft">Draf</option>
                            <option value="published">Diterbitkan</option>
                        </select>
                    </Field>
                </div>
            </div>

            <div v-if="!materials.data?.length" class="aksara-surface-dashed p-10 text-center">
                <h3 class="text-lg font-semibold text-aksara-ink">Belum ada bahan ajar</h3>
                <p class="mt-2 text-sm text-aksara-muted">
                    {{
                        isStudent
                            ? 'Materi pembelajaran hanya muncul setelah diterbitkan oleh guru pengampu kelas Anda.'
                            : 'Tidak ada data yang sesuai filter. Buat draf rencana pembelajaran terlebih dahulu.'
                    }}
                </p>
            </div>

            <div v-else class="aksara-surface">
                <div class="overflow-x-auto">
                    <table class="aksara-table w-full min-w-[640px]">
                        <thead>
                            <tr>
                                <!-- Checkbox header hanya untuk Guru/Admin -->
                                <th v-if="!isStudent" class="aksara-th w-10 text-center">
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 cursor-pointer rounded border-aksara-line text-aksara-primary focus:ring-aksara-primary/30"
                                        :checked="bulk.isAllSelected(materials.data)"
                                        :indeterminate="bulk.isIndeterminate(materials.data)"
                                        aria-label="Pilih semua di halaman ini"
                                        @change="bulk.toggleSelectAll(materials.data)"
                                    />
                                </th>
                                <th class="aksara-th">Judul</th>
                                <th class="aksara-th">Mapel / Kelas</th>
                                <th v-if="!isStudent" class="aksara-th">Status</th>
                                <th class="aksara-th w-36 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="material in materials.data"
                                :key="material.id"
                                :class="[
                                    'transition-colors',
                                    !isStudent && (bulk.isSelected(material.id) || bulk.isAllMatching.value)
                                        ? 'bg-aksara-primary/5 hover:bg-aksara-primary/10'
                                        : 'hover:bg-aksara-mist/40',
                                ]"
                            >
                                <!-- Checkbox baris hanya untuk Guru/Admin -->
                                <td v-if="!isStudent" class="aksara-td w-10 text-center">
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 cursor-pointer rounded border-aksara-line text-aksara-primary focus:ring-aksara-primary/30"
                                        :checked="bulk.isSelected(material.id) || bulk.isAllMatching.value"
                                        aria-label="Pilih materi"
                                        @change="bulk.toggleSelect(material.id)"
                                    />
                                </td>
                                <td class="aksara-td">
                                    <div class="font-semibold text-aksara-ink">{{ material.title }}</div>
                                    <div class="mt-0.5 text-xs text-aksara-muted">
                                        {{ material.durationMinutes }} menit
                                    </div>
                                </td>
                                <td class="aksara-td text-sm">
                                    <div>{{ material.subject || '—' }}</div>
                                    <div class="text-xs text-aksara-muted">Kelas {{ material.className || '—' }}</div>
                                </td>
                                <td v-if="!isStudent" class="aksara-td">
                                    <StatusBadge :status="material.status" :label="material.statusLabel" />
                                </td>
                                <td class="aksara-td">
                                    <div class="flex flex-wrap items-center justify-end gap-0.5">
                                        <IconButton
                                            icon="eye"
                                            :label="isStudent ? 'Baca Materi' : 'Pratinjau'"
                                            :href="material.showUrl"
                                        />
                                        <IconButton
                                            v-if="!isStudent"
                                            icon="pencil"
                                            label="Edit"
                                            :href="material.editUrl"
                                        />
                                        <ExportMenu
                                            label="Ekspor"
                                            icon="download"
                                            :items="exportItems(material)"
                                        />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-4 pb-4 sm:px-5">
                    <Pagination
                        :paginator="materials"
                        :per-page="perPage"
                        :base-url="indexUrl"
                        :query="filterQuery"
                    />
                </div>
            </div>
        </div>

        <BulkConfirmModal
            v-if="!isStudent"
            v-model="showBulkDeleteModal"
            :count="bulk.getSelectedCount(materials.total)"
            :loading="isBulkDeleting"
            item-label="materi"
            danger-word="HAPUS"
            @confirm="submitBulkDelete"
        />
    </AppLayout>
</template>
