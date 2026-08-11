<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Field from '@/Components/ui/Field.vue';
import Btn from '@/Components/ui/Btn.vue';
import Icon from '@/Components/ui/Icon.vue';
import IconButton from '@/Components/ui/IconButton.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import Modal from '@/Components/ui/Modal.vue';
import Alert from '@/Components/ui/Alert.vue';

const props = defineProps({
    pageTitle: { type: String, default: 'Pengaturan Sistem Global' },
    activeTab: { type: String, default: 'ai' },
    settings: { type: Object, required: true },
    providers: { type: Array, default: () => [] },
    usage: { type: Object, default: () => ({}) },
    featureModelOptions: { type: Array, default: () => [] },
    featureRecs: { type: Object, default: () => ({}) },
    urls: { type: Object, required: true },
});

const tab = computed(() => props.activeTab);

const tabs = [
    { key: 'ai', label: 'Integrasi AI' },
    { key: 'security', label: 'Keamanan' },
    { key: 'features', label: 'Fitur & Pemeliharaan' },
];

function setTab(next) {
    router.get(props.urls.index, { tab: next }, { preserveState: true, replace: true });
}

const settingsForm = useForm({ ...props.settings });

watch(
    () => props.settings,
    (s) => Object.assign(settingsForm, s),
    { deep: true },
);

function saveSettings() {
    settingsForm
        .transform((data) => ({
            ...data,
            ai_anonymize_student_data: !!data.ai_anonymize_student_data,
            security_allow_public_registration: !!data.security_allow_public_registration,
            features_quiz_module: !!data.features_quiz_module,
            features_parent_portal: !!data.features_parent_portal,
            system_maintenance_mode: !!data.system_maintenance_mode,
        }))
        .put(props.urls.save, { preserveScroll: true });
}

function urlFor(template, id) {
    return template.replace('__ID__', String(id));
}

function toggleProvider(id) {
    router.post(urlFor(props.urls.providersToggle, id), {}, { preserveScroll: true });
}

function movePriority(id, direction) {
    router.post(urlFor(props.urls.providersPriority, id), { direction }, { preserveScroll: true });
}

function deleteProvider(id) {
    if (!window.confirm('Hapus vendor AI kustom ini?')) return;
    router.delete(urlFor(props.urls.providersDestroy, id), { preserveScroll: true });
}

const providerPerPage = ref(10);
const providerPage = ref(1);

watch(
    () => props.providers.length,
    () => {
        providerPage.value = 1;
    },
);

const providerPaginator = computed(() => {
    const all = props.providers;
    const per = providerPerPage.value;
    const total = all.length;
    const lastPage = Math.max(1, Math.ceil(total / per) || 1);
    const page = Math.min(providerPage.value, lastPage);
    const from = total === 0 ? 0 : (page - 1) * per + 1;
    const to = Math.min(page * per, total);
    const slice = all.slice((page - 1) * per, page * per);
    const links = [];
    if (lastPage > 1) {
        links.push({
            url: page > 1 ? `#${page - 1}` : null,
            label: '&laquo; Previous',
            active: false,
        });
        for (let i = 1; i <= lastPage; i += 1) {
            links.push({
                url: `#${i}`,
                label: String(i),
                active: i === page,
            });
        }
        links.push({
            url: page < lastPage ? `#${page + 1}` : null,
            label: 'Next &raquo;',
            active: false,
        });
    }

    return {
        data: slice,
        current_page: page,
        last_page: lastPage,
        per_page: per,
        from,
        to,
        total,
        path: props.urls.index,
        links,
    };
});

function onProviderPageNav(link) {
    if (!link?.url || link.active) return;
    const match = String(link.url).match(/#(\d+)/);
    if (match) providerPage.value = Number(match[1]);
}

function onProviderPerPage(event) {
    providerPerPage.value = Number(event.target.value) || 10;
    providerPage.value = 1;
}

const activeProviderOptions = computed(() =>
    props.providers.filter((p) => p.is_active).map((p) => ({
        value: p.vendor_key,
        label: p.name,
    })),
);

const showVendorModal = ref(false);
const editingProviderId = ref(null);
const ping = reactive({ type: '', message: '' });

const vendorForm = useForm({
    vendor_key: 'custom',
    name: '',
    is_active: true,
    api_key: '',
    base_url: '',
    model: '',
    max_tokens: 2048,
    temperature: 0.7,
    timeout: 30,
});

const editingCatalogModels = ref([]);

function openCreateVendor() {
    editingProviderId.value = null;
    editingCatalogModels.value = [];
    vendorForm.vendor_key = `custom_${Date.now()}`;
    vendorForm.name = 'Server AI Custom Baru';
    vendorForm.is_active = true;
    vendorForm.api_key = '';
    vendorForm.base_url = 'https://your-ai-server.com/v1';
    vendorForm.model = 'custom-model';
    vendorForm.max_tokens = 2048;
    vendorForm.temperature = 0.7;
    vendorForm.timeout = 30;
    ping.type = '';
    ping.message = '';
    showVendorModal.value = true;
}

function openEditVendor(p) {
    editingProviderId.value = p.id;
    editingCatalogModels.value = Array.isArray(p.catalogModels) ? p.catalogModels : [];
    vendorForm.vendor_key = p.vendor_key;
    vendorForm.name = p.name;
    vendorForm.is_active = p.is_active;
    vendorForm.api_key = p.api_key || '';
    vendorForm.base_url = p.base_url || '';
    vendorForm.model = p.model || '';
    vendorForm.max_tokens = p.max_tokens;
    vendorForm.temperature = p.temperature;
    vendorForm.timeout = p.timeout_seconds;
    ping.type = '';
    ping.message = '';
    showVendorModal.value = true;
}

function saveVendor() {
    const payload = {
        onSuccess: () => {
            showVendorModal.value = false;
        },
        preserveScroll: true,
    };
    if (editingProviderId.value) {
        vendorForm.put(urlFor(props.urls.providersUpdate, editingProviderId.value), payload);
    } else {
        vendorForm.post(props.urls.providersStore, payload);
    }
}

async function testConnection() {
    ping.type = 'info';
    ping.message = 'Menguji koneksi...';
    try {
        const res = await fetch(props.urls.providersTest, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                vendor_key: vendorForm.vendor_key,
                name: vendorForm.name,
                api_key: vendorForm.api_key,
                base_url: vendorForm.base_url,
                timeout: vendorForm.timeout,
            }),
        });
        const data = await res.json();
        ping.type = data.type || 'danger';
        ping.message = data.message || 'Tidak ada respons.';
    } catch (e) {
        ping.type = 'danger';
        ping.message = e.message || 'Gagal menguji koneksi.';
    }
}

function recHint(key) {
    const rec = props.featureRecs?.[key];
    if (!rec?.default) return '';
    return `Rekomendasi: ${rec.default}`;
}

function modelId(entry) {
    return typeof entry === 'string' ? entry : entry?.id || entry?.model || '';
}

function modelLabel(entry) {
    if (typeof entry === 'string') return entry;
    return entry?.label || entry?.name || entry?.id || '';
}
</script>

<template>
    <AppLayout :title="pageTitle">
        <template #header>{{ pageTitle }}</template>

        <div class="space-y-5">
            <PageHeader
                title="Pengaturan Sistem Global"
                description="Kontrol teknis, rantai failover AI, keamanan, dan fitur platform."
            />

            <div class="aksara-surface overflow-hidden">
                <div class="flex overflow-x-auto border-b border-aksara-line text-xs font-semibold">
                    <button
                        v-for="t in tabs"
                        :key="t.key"
                        type="button"
                        class="shrink-0 border-b-2 px-4 py-3 transition"
                        :class="tab === t.key ? 'border-aksara-teal text-aksara-teal' : 'border-transparent text-aksara-muted hover:text-aksara-ink'"
                        @click="setTab(t.key)"
                    >
                        {{ t.label }}
                    </button>
                </div>

                <form class="space-y-5 p-4 sm:p-5" @submit.prevent="saveSettings">
                    <template v-if="tab === 'ai'">
                        <!-- Usage -->
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                            <div class="rounded-lg border border-aksara-line bg-white px-3 py-2.5">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-aksara-muted">Generasi hari ini</p>
                                <p class="mt-0.5 text-lg font-bold tabular-nums text-aksara-ink">{{ usage.totalCallsToday ?? 0 }}</p>
                            </div>
                            <div class="rounded-lg border border-aksara-line bg-white px-3 py-2.5">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-aksara-muted">Tokens</p>
                                <p class="mt-0.5 text-lg font-bold tabular-nums text-aksara-teal">{{ usage.totalTokensToday ?? 0 }}</p>
                            </div>
                            <div class="rounded-lg border border-aksara-line bg-white px-3 py-2.5">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-aksara-muted">Estimasi biaya</p>
                                <p class="mt-0.5 text-lg font-bold tabular-nums text-aksara-warn">${{ Number(usage.totalCostToday || 0).toFixed(5) }}</p>
                            </div>
                            <div class="rounded-lg border border-aksara-line bg-white px-3 py-2.5">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-aksara-muted">Failover</p>
                                <p class="mt-0.5 text-lg font-bold tabular-nums text-aksara-info">{{ usage.failoverCallsToday ?? 0 }}×</p>
                            </div>
                        </div>

                        <!-- Vendor chain -->
                        <section class="space-y-3">
                            <div class="flex flex-wrap items-end justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-semibold text-aksara-ink">Rantai failover vendor</h3>
                                    <p class="mt-0.5 text-xs text-aksara-muted">Urutan prioritas dipakai saat vendor gagal merespons.</p>
                                </div>
                                <Btn type="button" size="sm" class="gap-1.5" @click="openCreateVendor">
                                    <Icon name="plus" class="h-3.5 w-3.5" />
                                    Tambah vendor
                                </Btn>
                            </div>

                            <div class="overflow-x-auto rounded-xl border border-aksara-line">
                                <table class="aksara-table min-w-[720px]">
                                    <thead>
                                        <tr>
                                            <th class="aksara-th w-20 text-center">#</th>
                                            <th class="aksara-th">Vendor & model</th>
                                            <th class="aksara-th">Endpoint</th>
                                            <th class="aksara-th w-24 text-center">Status</th>
                                            <th class="aksara-th w-24 text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="p in providerPaginator.data"
                                            :key="p.id"
                                            class="hover:bg-aksara-mist/40"
                                        >
                                            <td class="aksara-td">
                                                <div class="flex items-center justify-center gap-1">
                                                    <div class="flex flex-col">
                                                        <button
                                                            type="button"
                                                            class="inline-flex h-5 w-5 items-center justify-center rounded text-aksara-muted hover:bg-aksara-mist hover:text-aksara-ink"
                                                            title="Naik prioritas"
                                                            @click="movePriority(p.id, 'up')"
                                                        >
                                                            <Icon name="chevron-up" class="h-3.5 w-3.5" />
                                                        </button>
                                                        <button
                                                            type="button"
                                                            class="inline-flex h-5 w-5 items-center justify-center rounded text-aksara-muted hover:bg-aksara-mist hover:text-aksara-ink"
                                                            title="Turun prioritas"
                                                            @click="movePriority(p.id, 'down')"
                                                        >
                                                            <Icon name="chevron-down" class="h-3.5 w-3.5" />
                                                        </button>
                                                    </div>
                                                    <span class="min-w-5 text-center text-xs font-bold tabular-nums text-aksara-ink">{{ p.priority_order }}</span>
                                                </div>
                                            </td>
                                            <td class="aksara-td">
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-1.5">
                                                        <span class="font-medium text-aksara-ink">{{ p.name }}</span>
                                                        <span
                                                            v-if="p.meta?.badge"
                                                            class="rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-aksara-muted ring-1 ring-aksara-line"
                                                        >
                                                            {{ p.meta.badge }}
                                                        </span>
                                                        <span
                                                            v-if="p.is_custom"
                                                            class="rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-aksara-teal ring-1 ring-aksara-teal/30"
                                                        >
                                                            Custom
                                                        </span>
                                                    </div>
                                                    <p class="mt-0.5 truncate font-mono text-[11px] text-aksara-muted" :title="p.model">{{ p.model }}</p>
                                                </div>
                                            </td>
                                            <td class="aksara-td max-w-[14rem]">
                                                <p class="truncate font-mono text-[11px] text-aksara-muted" :title="p.base_url || ''">
                                                    {{ p.base_url || '—' }}
                                                </p>
                                            </td>
                                            <td class="aksara-td text-center">
                                                <button type="button" class="inline-flex" :title="p.is_active ? 'Nonaktifkan' : 'Aktifkan'" @click="toggleProvider(p.id)">
                                                    <StatusBadge
                                                        :status="p.is_active ? 'published' : 'archived'"
                                                        :label="p.is_active ? 'Aktif' : 'Nonaktif'"
                                                    />
                                                </button>
                                            </td>
                                            <td class="aksara-td">
                                                <div class="flex flex-nowrap items-center justify-end gap-0.5">
                                                    <IconButton icon="pencil" label="Edit vendor" @click="openEditVendor(p)" />
                                                    <IconButton
                                                        v-if="p.is_custom"
                                                        icon="trash"
                                                        label="Hapus vendor"
                                                        danger
                                                        @click="deleteProvider(p.id)"
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="!providers.length">
                                            <td colspan="5" class="aksara-td py-6 text-center text-sm text-aksara-muted">
                                                Belum ada vendor AI. Tambahkan vendor untuk mengaktifkan failover.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div
                                v-if="providers.length"
                                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="flex flex-wrap items-center gap-3 text-xs text-aksara-muted">
                                    <span>
                                        Menampilkan
                                        <span class="font-semibold text-aksara-ink">{{ providerPaginator.from }}–{{ providerPaginator.to }}</span>
                                        dari
                                        <span class="font-semibold text-aksara-ink">{{ providerPaginator.total }}</span>
                                    </span>
                                    <label class="inline-flex items-center gap-2">
                                        <span>Per halaman</span>
                                        <select
                                            class="aksara-select !w-auto !py-1 text-xs"
                                            :value="providerPerPage"
                                            @change="onProviderPerPage"
                                        >
                                            <option :value="10">10</option>
                                            <option :value="25">25</option>
                                            <option :value="50">50</option>
                                        </select>
                                    </label>
                                </div>
                                <nav
                                    v-if="providerPaginator.last_page > 1"
                                    class="flex flex-wrap items-center gap-1"
                                    aria-label="Paginasi vendor"
                                >
                                    <button
                                        v-for="(link, i) in providerPaginator.links"
                                        :key="i"
                                        type="button"
                                        class="inline-flex min-w-8 items-center justify-center rounded-lg border px-2.5 py-1.5 text-xs font-medium transition"
                                        :class="
                                            link.active
                                                ? 'border-aksara-teal bg-aksara-teal text-white'
                                                : link.url
                                                  ? 'border-aksara-line bg-white text-aksara-ink hover:bg-aksara-mist'
                                                  : 'pointer-events-none border-transparent text-aksara-muted opacity-50'
                                        "
                                        :disabled="!link.url || link.active"
                                        @click="onProviderPageNav(link)"
                                        v-html="link.label"
                                    />
                                </nav>
                            </div>
                        </section>

                        <!-- Guard & limits -->
                        <section class="space-y-3 border-t border-aksara-line pt-5">
                            <div>
                                <h3 class="text-sm font-semibold text-aksara-ink">Guard & kuota</h3>
                                <p class="mt-0.5 text-xs text-aksara-muted">Provider utama dan batas pemakaian per guru.</p>
                            </div>
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <Field label="Provider AI utama (guard)" for-id="ai_provider">
                                    <select id="ai_provider" v-model="settingsForm.ai_provider" class="aksara-select">
                                        <option
                                            v-for="opt in activeProviderOptions"
                                            :key="opt.value"
                                            :value="opt.value"
                                        >
                                            {{ opt.label }} ({{ opt.value }})
                                        </option>
                                        <option
                                            v-if="!activeProviderOptions.some((o) => o.value === settingsForm.ai_provider)"
                                            :value="settingsForm.ai_provider"
                                        >
                                            {{ settingsForm.ai_provider }}
                                        </option>
                                    </select>
                                </Field>
                                <Field label="Batas generasi AI / guru / hari" for-id="ai_daily_limit">
                                    <input
                                        id="ai_daily_limit"
                                        v-model.number="settingsForm.ai_daily_limit_per_teacher"
                                        type="number"
                                        min="1"
                                        class="aksara-input"
                                    />
                                </Field>
                                <label class="flex items-center gap-2 text-sm text-aksara-ink md:col-span-2">
                                    <input
                                        v-model="settingsForm.ai_anonymize_student_data"
                                        type="checkbox"
                                        class="rounded border-aksara-line text-aksara-teal focus:ring-aksara-teal"
                                    />
                                    Anonimisasi data siswa di prompt AI
                                </label>
                            </div>
                        </section>

                        <!-- Feature models -->
                        <section class="space-y-3 border-t border-aksara-line pt-5">
                            <div>
                                <h3 class="text-sm font-semibold text-aksara-ink">Model per fitur</h3>
                                <p class="mt-0.5 text-xs text-aksara-muted">Pilih model default untuk setiap alur generasi.</p>
                            </div>
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <Field label="Rencana pembelajaran" for-id="ai_model_plan" :hint="recHint('plan')">
                                    <select id="ai_model_plan" v-model="settingsForm.ai_model_plan" class="aksara-select">
                                        <option v-for="m in featureModelOptions" :key="`plan-${m}`" :value="m">{{ m }}</option>
                                    </select>
                                </Field>
                                <Field label="Bahan ajar / Co-Pilot" for-id="ai_model_material" :hint="recHint('material')">
                                    <select id="ai_model_material" v-model="settingsForm.ai_model_material" class="aksara-select">
                                        <option v-for="m in featureModelOptions" :key="`mat-${m}`" :value="m">{{ m }}</option>
                                    </select>
                                </Field>
                                <Field label="Perbaiki teks" for-id="ai_model_improve" :hint="recHint('improve')">
                                    <select id="ai_model_improve" v-model="settingsForm.ai_model_improve" class="aksara-select">
                                        <option v-for="m in featureModelOptions" :key="`imp-${m}`" :value="m">{{ m }}</option>
                                    </select>
                                </Field>
                                <Field label="Soal / kuis" for-id="ai_model_quiz" :hint="recHint('quiz')">
                                    <select id="ai_model_quiz" v-model="settingsForm.ai_model_quiz" class="aksara-select">
                                        <option v-for="m in featureModelOptions" :key="`quiz-${m}`" :value="m">{{ m }}</option>
                                    </select>
                                </Field>
                            </div>
                        </section>
                    </template>

                    <template v-else-if="tab === 'security'">
                        <section class="space-y-3">
                            <div>
                                <h3 class="text-sm font-semibold text-aksara-ink">Keamanan & akses</h3>
                                <p class="mt-0.5 text-xs text-aksara-muted">Kontrol pendaftaran, sesi, dan proteksi login.</p>
                            </div>
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <label class="flex items-center gap-2 text-sm text-aksara-ink md:col-span-2">
                                    <input
                                        v-model="settingsForm.security_allow_public_registration"
                                        type="checkbox"
                                        class="rounded border-aksara-line text-aksara-teal focus:ring-aksara-teal"
                                    />
                                    Izinkan pendaftaran publik
                                </label>
                                <Field label="Timeout sesi (menit)" for-id="session_timeout">
                                    <input
                                        id="session_timeout"
                                        v-model.number="settingsForm.security_session_timeout_minutes"
                                        type="number"
                                        class="aksara-input"
                                    />
                                </Field>
                                <Field label="Batas percobaan login" for-id="max_login">
                                    <input
                                        id="max_login"
                                        v-model.number="settingsForm.security_max_login_attempts"
                                        type="number"
                                        class="aksara-input"
                                    />
                                </Field>
                            </div>
                        </section>
                    </template>

                    <template v-else>
                        <section class="space-y-3">
                            <div>
                                <h3 class="text-sm font-semibold text-aksara-ink">Feature flags</h3>
                                <p class="mt-0.5 text-xs text-aksara-muted">Aktifkan modul atau mode pemeliharaan.</p>
                            </div>
                            <div class="space-y-2.5">
                                <label class="flex items-center gap-2 rounded-lg border border-aksara-line px-3 py-2.5 text-sm text-aksara-ink">
                                    <input
                                        v-model="settingsForm.features_quiz_module"
                                        type="checkbox"
                                        class="rounded border-aksara-line text-aksara-teal focus:ring-aksara-teal"
                                    />
                                    Modul kuis online
                                </label>
                                <label class="flex items-center gap-2 rounded-lg border border-aksara-line px-3 py-2.5 text-sm text-aksara-ink">
                                    <input
                                        v-model="settingsForm.features_parent_portal"
                                        type="checkbox"
                                        class="rounded border-aksara-line text-aksara-teal focus:ring-aksara-teal"
                                    />
                                    Portal akses wali murid
                                </label>
                                <label class="flex items-center gap-2 rounded-lg border border-aksara-warn/30 bg-aksara-warn/5 px-3 py-2.5 text-sm text-aksara-ink">
                                    <input
                                        v-model="settingsForm.system_maintenance_mode"
                                        type="checkbox"
                                        class="rounded border-aksara-line text-aksara-teal focus:ring-aksara-teal"
                                    />
                                    Mode pemeliharaan sistem
                                </label>
                            </div>
                        </section>
                    </template>

                    <div class="flex justify-end border-t border-aksara-line pt-4">
                        <Btn type="submit" size="sm" :disabled="settingsForm.processing">Simpan pengaturan</Btn>
                    </div>
                </form>
            </div>
        </div>

        <Modal
            :open="showVendorModal"
            :title="editingProviderId ? 'Edit vendor AI' : 'Tambah vendor AI custom'"
            @close="showVendorModal = false"
        >
            <form id="settings-vendor-form" class="space-y-3" @submit.prevent="saveVendor">
                <Field label="Nama" required for-id="vn-name" :error="vendorForm.errors.name">
                    <input id="vn-name" v-model="vendorForm.name" class="aksara-input" />
                </Field>
                <Field label="Base URL" for-id="vn-url">
                    <input id="vn-url" v-model="vendorForm.base_url" class="aksara-input font-mono text-sm" placeholder="https://api.example.com/v1" />
                </Field>
                <Field label="API Key" for-id="vn-key">
                    <input id="vn-key" v-model="vendorForm.api_key" class="aksara-input font-mono text-sm" autocomplete="off" />
                </Field>
                <Field label="Model" required for-id="vn-model" :error="vendorForm.errors.model">
                    <input
                        id="vn-model"
                        v-model="vendorForm.model"
                        list="vn-model-list"
                        class="aksara-input font-mono text-sm"
                    />
                    <datalist v-if="editingCatalogModels.length" id="vn-model-list">
                        <option
                            v-for="(m, i) in editingCatalogModels"
                            :key="`cm-${i}-${modelId(m)}`"
                            :value="modelId(m)"
                        >
                            {{ modelLabel(m) }}
                        </option>
                    </datalist>
                </Field>
                <div class="grid grid-cols-3 gap-2">
                    <Field label="Max tokens" for-id="vn-tokens">
                        <input id="vn-tokens" v-model.number="vendorForm.max_tokens" type="number" class="aksara-input" />
                    </Field>
                    <Field label="Temperature" for-id="vn-temp">
                        <input id="vn-temp" v-model.number="vendorForm.temperature" type="number" step="0.1" class="aksara-input" />
                    </Field>
                    <Field label="Timeout (dtk)" for-id="vn-timeout">
                        <input id="vn-timeout" v-model.number="vendorForm.timeout" type="number" class="aksara-input" />
                    </Field>
                </div>
                <label class="flex items-center gap-2 text-sm text-aksara-ink">
                    <input v-model="vendorForm.is_active" type="checkbox" class="rounded border-aksara-line text-aksara-teal focus:ring-aksara-teal" />
                    Aktifkan vendor
                </label>
                <Alert v-if="ping.message" :tone="ping.type === 'success' ? 'ok' : ping.type === 'info' ? 'info' : 'danger'">
                    {{ ping.message }}
                </Alert>
            </form>
            <template #footer>
                <Btn type="button" variant="secondary" size="sm" class="gap-1.5" @click="testConnection">
                    <Icon name="bolt" class="h-3.5 w-3.5" />
                    Uji koneksi
                </Btn>
                <Btn type="button" variant="secondary" size="sm" @click="showVendorModal = false">Batal</Btn>
                <Btn type="submit" form="settings-vendor-form" size="sm" :disabled="vendorForm.processing">Simpan</Btn>
            </template>
        </Modal>
    </AppLayout>
</template>
