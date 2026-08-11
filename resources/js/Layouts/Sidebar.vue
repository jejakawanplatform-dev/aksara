<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import NavIcon from '@/Components/ui/NavIcon.vue';
import BrandCopyright from '@/Components/brand/BrandCopyright.vue';

defineProps({
    collapsed: { type: Boolean, default: false },
});

const page = usePage();
const openGroups = ref({});
const openItems = ref({});

const permissions = computed(() => page.props.auth?.permissions ?? []);
const dashboardUrl = computed(() => page.props.urls?.dashboard || '/dashboard');

const groups = computed(() => {
    const raw = page.props.nav ?? [];
    return raw
        .map((group) => ({
            ...group,
            items: (group.items || []).filter(
                (item) => !item.permission || permissions.value.includes(item.permission),
            ).map((item) => ({
                ...item,
                children: item.children
                    ? item.children.filter(
                        (child) => !child.permission || permissions.value.includes(child.permission),
                    )
                    : undefined,
            })),
        }))
        .filter((group) => group.items.length > 0);
});

function isOpen(title) {
    return openGroups.value[title] !== false;
}

function toggleGroup(title) {
    if (document.documentElement.classList.contains('sidebar-collapsed')) {
        return;
    }
    openGroups.value = {
        ...openGroups.value,
        [title]: !isOpen(title),
    };
}

function isItemOpen(label, item) {
    if (openItems.value[label] !== undefined) {
        return openItems.value[label];
    }
    return item.children?.some((c) => c.active) ?? false;
}

function toggleItem(label, item) {
    if (document.documentElement.classList.contains('sidebar-collapsed')) {
        return;
    }
    openItems.value = {
        ...openItems.value,
        [label]: !isItemOpen(label, item),
    };
}
</script>

<template>
    <aside class="aksara-sidebar">
        <div class="sb-header-logo flex h-16 items-center justify-between border-b border-aksara-line px-4">
            <a :href="dashboardUrl" class="flex items-center gap-2.5 overflow-hidden">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-aksara-teal font-display text-sm font-bold text-white shadow-sm">A</span>
                <span class="sb-label">
                    <span class="block font-display text-base font-semibold leading-none text-aksara-ink">Aksara</span>
                    <span class="mt-0.5 block whitespace-nowrap text-[11px] text-aksara-muted">Pembelajaran AI</span>
                </span>
            </a>
        </div>

        <nav class="flex-1 space-y-3 overflow-x-hidden overflow-y-auto p-3">
            <template v-for="group in groups" :key="group.title">
                <div v-if="group.items.length === 1 && !group.items[0].children" class="sb-group">
                    <a
                        :href="group.items[0].href"
                        :title="group.items[0].label"
                        class="aksara-sidebar-link sb-link"
                        :class="{ 'aksara-sidebar-link-active': group.items[0].active }"
                    >
                        <span
                            class="shrink-0 text-aksara-muted"
                            :class="{ 'font-semibold text-aksara-teal-dark': group.items[0].active }"
                        >
                            <NavIcon :name="group.items[0].icon" />
                        </span>
                        <span class="sb-label truncate">{{ group.items[0].label }}</span>
                    </a>
                    <div class="sb-popup">
                        <a
                            :href="group.items[0].href"
                            class="aksara-sidebar-link"
                            :class="{ 'aksara-sidebar-link-active': group.items[0].active }"
                        >
                            {{ group.items[0].label }}
                        </a>
                    </div>
                </div>

                <!-- Item tunggal yang punya children (expandable tanpa group header) -->
                <div v-else-if="group.items.length === 1 && group.items[0].children" class="sb-group space-y-0.5">
                    <button
                        type="button"
                        :title="group.items[0].label"
                        class="aksara-sidebar-link sb-link w-full"
                        :class="{ 'aksara-sidebar-link-active': group.items[0].active && !isItemOpen(group.items[0].label, group.items[0]) }"
                        @click="toggleItem(group.items[0].label, group.items[0])"
                    >
                        <span
                            class="shrink-0 text-aksara-muted"
                            :class="{ 'font-semibold text-aksara-teal-dark': group.items[0].active }"
                        >
                            <NavIcon :name="group.items[0].icon" />
                        </span>
                        <span class="sb-label flex-1 truncate text-left">{{ group.items[0].label }}</span>
                        <NavIcon
                            name="chevron-down"
                            class="sb-label h-3.5 w-3.5 shrink-0 text-aksara-muted transition-transform duration-200"
                            :class="{ '-rotate-90': !isItemOpen(group.items[0].label, group.items[0]) }"
                        />
                    </button>
                    <div v-show="isItemOpen(group.items[0].label, group.items[0])" class="space-y-0.5 pl-4">
                        <a
                            v-for="child in group.items[0].children"
                            :key="child.href"
                            :href="child.href"
                            :title="child.label"
                            class="aksara-sidebar-link sb-link pl-2 text-sm"
                            :class="{ 'aksara-sidebar-link-active': child.active }"
                        >
                            <span class="h-1 w-1 shrink-0 rounded-full bg-current opacity-50" />
                            <span class="sb-label truncate">{{ child.label }}</span>
                        </a>
                    </div>
                    <div class="sb-popup">
                        <p class="px-3 pb-0.5 pt-1 text-[10px] font-semibold text-aksara-muted/60">
                            {{ group.items[0].label }}
                        </p>
                        <a
                            v-for="child in group.items[0].children"
                            :key="`popup-${child.href}`"
                            :href="child.href"
                            class="aksara-sidebar-link pl-5"
                            :class="{ 'aksara-sidebar-link-active': child.active }"
                        >
                            <span class="h-1 w-1 shrink-0 rounded-full bg-current opacity-50" />
                            <span>{{ child.label }}</span>
                        </a>
                    </div>
                </div>

                <div v-else class="sb-group space-y-1">
                    <div
                        class="sb-divider flex cursor-pointer select-none items-center justify-between px-3 pb-1 pt-1.5 text-[10px] font-bold uppercase tracking-wider text-aksara-muted/70 transition hover:text-aksara-ink"
                        @click="toggleGroup(group.title)"
                    >
                        <span class="truncate">{{ group.title }}</span>
                        <span class="sb-chevron">
                            <NavIcon
                                name="chevron-down"
                                class="h-3.5 w-3.5 shrink-0 transition-transform duration-200"
                                :class="{ '-rotate-90': !isOpen(group.title) }"
                            />
                        </span>
                    </div>

                    <div v-show="isOpen(group.title)" class="sb-divider space-y-1">
                        <template v-for="item in group.items" :key="item.href ?? item.label">
                            <!-- Item tanpa children -->
                            <a
                                v-if="!item.children"
                                :href="item.href"
                                :title="item.label"
                                class="aksara-sidebar-link sb-link"
                                :class="{ 'aksara-sidebar-link-active': item.active }"
                            >
                                <span
                                    class="shrink-0 text-aksara-muted"
                                    :class="{ 'font-semibold text-aksara-teal-dark': item.active }"
                                >
                                    <NavIcon :name="item.icon" />
                                </span>
                                <span class="sb-label truncate">{{ item.label }}</span>
                            </a>

                            <!-- Item dengan children (expandable) -->
                            <div v-else>
                                <button
                                    type="button"
                                    :title="item.label"
                                    class="aksara-sidebar-link sb-link w-full"
                                    :class="{ 'aksara-sidebar-link-active': item.active && !isItemOpen(item.label, item) }"
                                    @click="toggleItem(item.label, item)"
                                >
                                    <span
                                        class="shrink-0 text-aksara-muted"
                                        :class="{ 'font-semibold text-aksara-teal-dark': item.active }"
                                    >
                                        <NavIcon :name="item.icon" />
                                    </span>
                                    <span class="sb-label flex-1 truncate text-left">{{ item.label }}</span>
                                    <NavIcon
                                        name="chevron-down"
                                        class="sb-label h-3.5 w-3.5 shrink-0 text-aksara-muted transition-transform duration-200"
                                        :class="{ '-rotate-90': !isItemOpen(item.label, item) }"
                                    />
                                </button>
                                <div v-show="isItemOpen(item.label, item)" class="mt-0.5 space-y-0.5 pl-4">
                                    <a
                                        v-for="child in item.children"
                                        :key="child.href"
                                        :href="child.href"
                                        :title="child.label"
                                        class="aksara-sidebar-link sb-link pl-2 text-sm"
                                        :class="{ 'aksara-sidebar-link-active': child.active }"
                                    >
                                        <span class="h-1 w-1 shrink-0 rounded-full bg-current opacity-50" />
                                        <span class="sb-label truncate">{{ child.label }}</span>
                                    </a>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="sb-strip hidden">
                        <div
                            class="aksara-sidebar-link sb-link"
                            :class="{ 'aksara-sidebar-link-active': group.items.some((i) => i.active) }"
                            :title="group.title"
                        >
                            <span
                                class="shrink-0 text-aksara-muted"
                                :class="{ 'font-semibold text-aksara-teal-dark': group.items.some((i) => i.active) }"
                            >
                                <NavIcon :name="group.items[0].icon" />
                            </span>
                        </div>
                    </div>

                    <div class="sb-popup">
                        <p class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-aksara-muted/70">
                            {{ group.title }}
                        </p>
                        <template v-for="item in group.items" :key="`popup-${item.href ?? item.label}`">
                            <a
                                v-if="!item.children"
                                :href="item.href"
                                class="aksara-sidebar-link"
                                :class="{ 'aksara-sidebar-link-active': item.active }"
                            >
                                <span class="shrink-0 text-aksara-muted">
                                    <NavIcon :name="item.icon" class="h-4 w-4" />
                                </span>
                                <span>{{ item.label }}</span>
                            </a>
                            <template v-else>
                                <p class="px-3 pb-0.5 pt-2 text-[10px] font-semibold text-aksara-muted/60">
                                    {{ item.label }}
                                </p>
                                <a
                                    v-for="child in item.children"
                                    :key="`popup-child-${child.href}`"
                                    :href="child.href"
                                    class="aksara-sidebar-link pl-5"
                                    :class="{ 'aksara-sidebar-link-active': child.active }"
                                >
                                    <span class="h-1 w-1 shrink-0 rounded-full bg-current opacity-50" />
                                    <span>{{ child.label }}</span>
                                </a>
                            </template>
                        </template>
                    </div>
                </div>
            </template>
        </nav>

        <div class="sb-footer border-t border-aksara-line bg-aksara-mist/30 px-3 py-3">
            <BrandCopyright variant="sidebar" />
        </div>
    </aside>
</template>
