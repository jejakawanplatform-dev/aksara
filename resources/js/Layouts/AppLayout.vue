<script setup>
import { onMounted, ref } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import Sidebar from '@/Layouts/Sidebar.vue';
import Topbar from '@/Layouts/Topbar.vue';
import Flash from '@/Components/ui/Flash.vue';

defineProps({
    title: { type: String, default: '' },
});

const page = usePage();
const sidebarOpen = ref(false);
const sidebarCollapsed = ref(false);

onMounted(() => {
    sidebarCollapsed.value = localStorage.getItem('aksara_sidebar_collapsed') === 'true';
    document.documentElement.classList.toggle('sidebar-collapsed', sidebarCollapsed.value);
});

function toggleCollapse() {
    document.documentElement.classList.add('sidebar-animate');
    sidebarCollapsed.value = !sidebarCollapsed.value;
    localStorage.setItem('aksara_sidebar_collapsed', String(sidebarCollapsed.value));
    document.documentElement.classList.toggle('sidebar-collapsed', sidebarCollapsed.value);
}
</script>

<template>
    <div>
        <Head :title="title" />

        <div class="flex h-dvh min-h-0 overflow-hidden">
            <div class="aksara-sidebar-rail z-30 hidden lg:fixed lg:inset-y-0 lg:flex lg:flex-col">
                <Sidebar :collapsed="sidebarCollapsed" />
            </div>

            <div
                v-show="sidebarOpen"
                class="fixed inset-0 z-40 lg:hidden"
                @keydown.escape.window="sidebarOpen = false"
            >
                <div class="absolute inset-0 bg-aksara-ink/40" @click="sidebarOpen = false" />
                <div
                    class="absolute inset-y-0 left-0 w-64 shadow-aksara transition-transform duration-200"
                    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                >
                    <Sidebar />
                </div>
            </div>

            <div class="aksara-content-rail flex min-h-0 min-w-0 flex-1 flex-col">
                <Topbar
                    :title="title"
                    :collapsed="sidebarCollapsed"
                    @toggle-mobile="sidebarOpen = !sidebarOpen"
                    @toggle-collapse="toggleCollapse"
                >
                    <template #title>
                        <slot name="header">{{ title }}</slot>
                    </template>
                </Topbar>

                <main class="min-h-0 flex-1 overflow-x-hidden overflow-y-auto p-4 sm:p-6">
                    <Flash />
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
