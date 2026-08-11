<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    /** Laravel LengthAwarePaginator serialized (Inertia) */
    paginator: { type: Object, required: true },
    /** Current per-page size */
    perPage: { type: [Number, String], default: 10 },
    perPageOptions: {
        type: Array,
        default: () => [10, 25, 50, 100],
    },
    /** Base URL for navigation (defaults to paginator.path / window) */
    baseUrl: { type: String, default: null },
    /** Extra query preserved on page/per_page change */
    query: { type: Object, default: () => ({}) },
    /** Inertia `only` partial reload keys */
    only: { type: Array, default: null },
});

const options = computed(() =>
    props.perPageOptions.map((n) => Number(n)).filter((n) => n > 0),
);

const currentPerPage = computed(() => Number(props.perPage) || Number(props.paginator?.per_page) || 10);

const from = computed(() => props.paginator?.from ?? 0);
const to = computed(() => props.paginator?.to ?? 0);
const total = computed(() => props.paginator?.total ?? 0);
const currentPage = computed(() => props.paginator?.current_page ?? 1);
const lastPage = computed(() => props.paginator?.last_page ?? 1);

const pageLinks = computed(() => {
    const links = props.paginator?.links;
    if (Array.isArray(links) && links.length) {
        return links;
    }
    return [];
});

function navigate(params) {
    const url = props.baseUrl || props.paginator?.path || window.location.pathname;
    const next = {
        ...props.query,
        ...params,
    };
    // Drop empty
    Object.keys(next).forEach((key) => {
        if (next[key] === '' || next[key] === null || next[key] === undefined) {
            delete next[key];
        }
    });

    router.get(url, next, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: props.only || undefined,
    });
}

function onPerPageChange(event) {
    const value = Number(event.target.value);
    navigate({
        page: 1,
        per_page: value,
    });
}

function go(link) {
    if (!link?.url || link.active) return;
    // Laravel link.url already includes query; use as-is for page clicks
    router.get(link.url, {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: props.only || undefined,
    });
}
</script>

<template>
    <div
        v-if="total > 0"
        class="flex flex-col gap-3 border-t border-aksara-line pt-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <div class="flex flex-wrap items-center gap-3 text-xs text-aksara-muted">
            <span>
                Menampilkan
                <span class="font-semibold text-aksara-ink">{{ from }}–{{ to }}</span>
                dari
                <span class="font-semibold text-aksara-ink">{{ total }}</span>
            </span>
            <label class="inline-flex items-center gap-2">
                <span>Per halaman</span>
                <select
                    class="aksara-select !w-auto !py-1 text-xs"
                    :value="currentPerPage"
                    @change="onPerPageChange"
                >
                    <option v-for="n in options" :key="n" :value="n">{{ n }}</option>
                </select>
            </label>
        </div>

        <nav v-if="lastPage > 1" class="flex flex-wrap items-center gap-1" aria-label="Paginasi">
            <template v-for="(link, i) in pageLinks" :key="i">
                <button
                    v-if="link.url"
                    type="button"
                    class="inline-flex min-w-8 items-center justify-center rounded-lg border px-2.5 py-1.5 text-xs font-medium transition"
                    :class="
                        link.active
                            ? 'border-aksara-teal bg-aksara-teal text-white'
                            : 'border-aksara-line bg-white text-aksara-ink hover:bg-aksara-mist'
                    "
                    :aria-current="link.active ? 'page' : undefined"
                    @click="go(link)"
                    v-html="link.label"
                />
                <span
                    v-else
                    class="inline-flex min-w-8 items-center justify-center rounded-lg border border-transparent px-2.5 py-1.5 text-xs text-aksara-muted"
                    v-html="link.label"
                />
            </template>
            <span class="ml-1 hidden text-[11px] text-aksara-muted sm:inline">
                Hal. {{ currentPage }}/{{ lastPage }}
            </span>
        </nav>
    </div>
</template>
