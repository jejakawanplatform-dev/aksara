<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import NavIcon from '@/Components/ui/NavIcon.vue';

defineProps({
    collapsed: { type: Boolean, default: false },
});

const page = usePage();
const openGroups = ref({});

const permissions = computed(() => page.props.auth?.permissions ?? []);
const user = computed(() => page.props.auth?.user);
const dashboardUrl = computed(() => page.props.urls?.dashboard || '/dashboard');

const groups = computed(() => {
    const raw = page.props.nav ?? [];
    return raw
        .map((group) => ({
            ...group,
            items: (group.items || []).filter(
                (item) => !item.permission || permissions.value.includes(item.permission),
            ),
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

function initial(name) {
    return (name || 'U').charAt(0).toUpperCase();
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
                <div v-if="group.items.length === 1" class="sb-group">
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
                        <a
                            v-for="item in group.items"
                            :key="item.href"
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
                        <a
                            v-for="item in group.items"
                            :key="`popup-${item.href}`"
                            :href="item.href"
                            class="aksara-sidebar-link"
                            :class="{ 'aksara-sidebar-link-active': item.active }"
                        >
                            <span class="shrink-0 text-aksara-muted">
                                <NavIcon :name="item.icon" class="h-4 w-4" />
                            </span>
                            <span>{{ item.label }}</span>
                        </a>
                    </div>
                </div>
            </template>
        </nav>

        <div class="border-t border-aksara-line bg-aksara-mist/30 p-3 text-xs text-aksara-muted">
            <div
                class="sb-user flex items-center gap-2.5"
                :title="`${user?.name || ''} (${user?.roleLabel || ''})`"
            >
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-aksara-teal/10 font-semibold text-aksara-teal">
                    {{ initial(user?.name) }}
                </div>
                <div class="sb-label overflow-hidden">
                    <p class="truncate font-medium text-aksara-ink">{{ user?.name }}</p>
                    <p class="truncate text-[11px] text-aksara-muted">{{ user?.roleLabel }}</p>
                </div>
            </div>
        </div>
    </aside>
</template>
