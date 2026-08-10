<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Field from '@/Components/ui/Field.vue';
import Btn from '@/Components/ui/Btn.vue';
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
    settingsForm.transform((data) => ({
        ...data,
        ai_anonymize_student_data: !!data.ai_anonymize_student_data,
        security_allow_public_registration: !!data.security_allow_public_registration,
        features_quiz_module: !!data.features_quiz_module,
        features_parent_portal: !!data.features_parent_portal,
        system_maintenance_mode: !!data.system_maintenance_mode,
    })).put(props.urls.save, { preserveScroll: true });
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

function openCreateVendor() {
    editingProviderId.value = null;
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
</script>

<template>
    <AppLayout :title="pageTitle">
        <template #header>{{ pageTitle }}</template>

        <div class="space-y-6">
            <Card
                title="Pengaturan Sistem Global"
                description="Pengelolaan kontrol teknis, integrasi AI multi-vendor, keamanan, dan fitur platform secara terpusat."
            >
                <div class="flex overflow-x-auto border-b border-aksara-line text-xs font-semibold">
                    <button
                        type="button"
                        class="flex items-center gap-1.5 border-b-2 px-4 py-3 transition"
                        :class="tab === 'ai' ? 'border-aksara-teal text-aksara-teal' : 'border-transparent text-aksara-muted'"
                        @click="setTab('ai')"
                    >
                        Layanan & Integrasi Multi-Vendor AI
                    </button>
                    <button
                        type="button"
                        class="flex items-center gap-1.5 border-b-2 px-4 py-3 transition"
                        :class="tab === 'security' ? 'border-aksara-teal text-aksara-teal' : 'border-transparent text-aksara-muted'"
                        @click="setTab('security')"
                    >
                        Keamanan & Akses
                    </button>
                    <button
                        type="button"
                        class="flex items-center gap-1.5 border-b-2 px-4 py-3 transition"
                        :class="tab === 'features' ? 'border-aksara-teal text-aksara-teal' : 'border-transparent text-aksara-muted'"
                        @click="setTab('features')"
                    >
                        Feature Flags & Pemeliharaan
                    </button>
                </div>

                <form class="mt-6 space-y-6" @submit.prevent="saveSettings">
                    <template v-if="tab === 'ai'">
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="rounded-xl border border-aksara-line bg-white p-3.5">
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-aksara-muted">Total Generasi (Hari Ini)</p>
                                <p class="mt-1 font-display text-xl font-bold text-aksara-ink">{{ usage.totalCallsToday ?? 0 }}</p>
                            </div>
                            <div class="rounded-xl border border-aksara-line bg-white p-3.5">
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-aksara-muted">Total Tokens Terpakai</p>
                                <p class="mt-1 font-display text-xl font-bold text-aksara-teal">{{ usage.totalTokensToday ?? 0 }}</p>
                            </div>
                            <div class="rounded-xl border border-aksara-line bg-white p-3.5">
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-aksara-muted">Estimasi Biaya AI</p>
                                <p class="mt-1 font-display text-xl font-bold text-amber-700">${{ Number(usage.totalCostToday || 0).toFixed(5) }}</p>
                            </div>
                            <div class="rounded-xl border border-aksara-line bg-white p-3.5">
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-aksara-muted">Failover (Pengalihan)</p>
                                <p class="mt-1 font-display text-xl font-bold text-blue-700">{{ usage.failoverCallsToday ?? 0 }} kali</p>
                            </div>
                        </div>

                        <div class="space-y-4 rounded-2xl border border-aksara-line bg-white p-5">
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-aksara-line pb-4">
                                <div>
                                    <h4 class="text-base font-semibold text-aksara-ink">Tabel Konfigurasi & Rantai Failover Vendor AI</h4>
                                    <p class="mt-0.5 text-xs text-aksara-muted">Urutan prioritas menentukan rantai failover otomatis.</p>
                                </div>
                                <Btn type="button" class="!px-3 !py-2 text-xs" @click="openCreateVendor">+ Tambah Vendor AI Custom</Btn>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead>
                                        <tr class="border-b border-aksara-line bg-aksara-mist/40 font-semibold text-aksara-muted">
                                            <th class="p-3 text-center">Prioritas</th>
                                            <th class="p-3">Vendor AI & Model</th>
                                            <th class="p-3">Endpoint</th>
                                            <th class="p-3 text-center">Status</th>
                                            <th class="p-3 text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-aksara-line/60">
                                        <tr v-for="p in providers" :key="p.id">
                                            <td class="p-3 text-center">
                                                <div class="flex items-center justify-center gap-1.5">
                                                    <button type="button" class="rounded border px-1.5" @click="movePriority(p.id, 'up')">↑</button>
                                                    <span class="font-bold">#{{ p.priority_order }}</span>
                                                    <button type="button" class="rounded border px-1.5" @click="movePriority(p.id, 'down')">↓</button>
                                                </div>
                                            </td>
                                            <td class="p-3">
                                                <p class="font-semibold text-aksara-ink">{{ p.name }}</p>
                                                <p class="text-aksara-muted">{{ p.model }}</p>
                                            </td>
                                            <td class="p-3 text-aksara-muted">{{ p.base_url || '—' }}</td>
                                            <td class="p-3 text-center">
                                                <button type="button" @click="toggleProvider(p.id)">
                                                    <StatusBadge
                                                        :status="p.is_active ? 'published' : 'archived'"
                                                        :label="p.is_active ? 'Aktif' : 'Nonaktif'"
                                                    />
                                                </button>
                                            </td>
                                            <td class="space-x-2 p-3 text-right">
                                                <button type="button" class="font-semibold text-aksara-teal" @click="openEditVendor(p)">Edit</button>
                                                <button
                                                    v-if="p.is_custom"
                                                    type="button"
                                                    class="font-semibold text-red-600"
                                                    @click="deleteProvider(p.id)"
                                                >
                                                    Hapus
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <Field label="Provider AI utama (guard)" for-id="ai_provider">
                                <input id="ai_provider" v-model="settingsForm.ai_provider" class="aksara-input" />
                            </Field>
                            <Field label="Batas generasi AI / guru / hari" for-id="ai_daily_limit">
                                <input id="ai_daily_limit" v-model.number="settingsForm.ai_daily_limit_per_teacher" type="number" class="aksara-input" />
                            </Field>
                            <Field label="Model: Rencana" for-id="ai_model_plan">
                                <select id="ai_model_plan" v-model="settingsForm.ai_model_plan" class="aksara-select">
                                    <option v-for="m in featureModelOptions" :key="m" :value="m">{{ m }}</option>
                                </select>
                            </Field>
                            <Field label="Model: Materi" for-id="ai_model_material">
                                <select id="ai_model_material" v-model="settingsForm.ai_model_material" class="aksara-select">
                                    <option v-for="m in featureModelOptions" :key="m" :value="m">{{ m }}</option>
                                </select>
                            </Field>
                            <Field label="Model: Perbaiki teks" for-id="ai_model_improve">
                                <select id="ai_model_improve" v-model="settingsForm.ai_model_improve" class="aksara-select">
                                    <option v-for="m in featureModelOptions" :key="m" :value="m">{{ m }}</option>
                                </select>
                            </Field>
                            <Field label="Model: Kuis" for-id="ai_model_quiz">
                                <select id="ai_model_quiz" v-model="settingsForm.ai_model_quiz" class="aksara-select">
                                    <option v-for="m in featureModelOptions" :key="m" :value="m">{{ m }}</option>
                                </select>
                            </Field>
                            <label class="flex items-center gap-2 text-sm">
                                <input v-model="settingsForm.ai_anonymize_student_data" type="checkbox" class="rounded" />
                                Anonimisasi data siswa di prompt AI
                            </label>
                        </div>
                    </template>

                    <template v-else-if="tab === 'security'">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <label class="flex items-center gap-2 text-sm">
                                <input v-model="settingsForm.security_allow_public_registration" type="checkbox" class="rounded" />
                                Izinkan pendaftaran publik
                            </label>
                            <Field label="Timeout sesi (menit)" for-id="session_timeout">
                                <input id="session_timeout" v-model.number="settingsForm.security_session_timeout_minutes" type="number" class="aksara-input" />
                            </Field>
                            <Field label="Batas percobaan login" for-id="max_login">
                                <input id="max_login" v-model.number="settingsForm.security_max_login_attempts" type="number" class="aksara-input" />
                            </Field>
                        </div>
                    </template>

                    <template v-else>
                        <div class="space-y-3">
                            <label class="flex items-center gap-2 text-sm">
                                <input v-model="settingsForm.features_quiz_module" type="checkbox" class="rounded" />
                                Modul kuis online
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input v-model="settingsForm.features_parent_portal" type="checkbox" class="rounded" />
                                Portal akses wali murid
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input v-model="settingsForm.system_maintenance_mode" type="checkbox" class="rounded" />
                                Mode pemeliharaan sistem
                            </label>
                        </div>
                    </template>

                    <div class="flex justify-end border-t border-aksara-line pt-4">
                        <Btn type="submit" :disabled="settingsForm.processing">Simpan pengaturan</Btn>
                    </div>
                </form>
            </Card>
        </div>

        <Modal
            :open="showVendorModal"
            :title="editingProviderId ? 'Edit Vendor AI' : 'Tambah Vendor AI Custom'"
            @close="showVendorModal = false"
        >
            <form id="settings-vendor-form" class="space-y-3" @submit.prevent="saveVendor">
                <Field label="Nama" required for-id="vn-name" :error="vendorForm.errors.name">
                    <input id="vn-name" v-model="vendorForm.name" class="aksara-input" />
                </Field>
                <Field label="Base URL" for-id="vn-url">
                    <input id="vn-url" v-model="vendorForm.base_url" class="aksara-input" />
                </Field>
                <Field label="API Key" for-id="vn-key">
                    <input id="vn-key" v-model="vendorForm.api_key" class="aksara-input" />
                </Field>
                <Field label="Model" required for-id="vn-model" :error="vendorForm.errors.model">
                    <input id="vn-model" v-model="vendorForm.model" class="aksara-input" />
                </Field>
                <div class="grid grid-cols-3 gap-2">
                    <Field label="Max tokens" for-id="vn-tokens">
                        <input id="vn-tokens" v-model.number="vendorForm.max_tokens" type="number" class="aksara-input" />
                    </Field>
                    <Field label="Temperature" for-id="vn-temp">
                        <input id="vn-temp" v-model.number="vendorForm.temperature" type="number" step="0.1" class="aksara-input" />
                    </Field>
                    <Field label="Timeout" for-id="vn-timeout">
                        <input id="vn-timeout" v-model.number="vendorForm.timeout" type="number" class="aksara-input" />
                    </Field>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="vendorForm.is_active" type="checkbox" class="rounded" />
                    Aktif
                </label>
                <Alert v-if="ping.message" :tone="ping.type === 'success' ? 'ok' : 'danger'">
                    {{ ping.message }}
                </Alert>
            </form>
            <template #footer>
                <Btn type="button" variant="secondary" size="sm" @click="testConnection">Uji koneksi</Btn>
                <div class="flex-1" />
                <Btn type="button" variant="secondary" size="sm" @click="showVendorModal = false">Batal</Btn>
                <Btn type="submit" form="settings-vendor-form" size="sm" :disabled="vendorForm.processing">Simpan</Btn>
            </template>
        </Modal>
    </AppLayout>
</template>
