<script setup>
import { usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import NavIcon from '@/Components/ui/NavIcon.vue';

defineProps({
    title: { type: String, default: '' },
    collapsed: { type: Boolean, default: false },
});

const emit = defineEmits(['toggle-mobile', 'toggle-collapse']);

const page = usePage();
const profileUrl = computed(() => page.props.urls?.profile || '/profile');
const logoutUrl = computed(() => page.props.urls?.logout || '/logout');

function logout() {
    router.post(logoutUrl.value);
}
</script>

<template>
    <header class="aksara-topbar">
        <div class="flex items-center gap-3">
            <button
                type="button"
                class="inline-flex rounded-lg border border-aksara-line p-2 text-aksara-ink lg:hidden"
                aria-label="Buka menu"
                @click="emit('toggle-mobile')"
            >
                <NavIcon name="menu" />
            </button>

            <button
                type="button"
                class="hidden items-center justify-center rounded-xl border border-aksara-line p-2 text-aksara-muted shadow-2xs transition hover:bg-aksara-mist hover:text-aksara-ink lg:inline-flex"
                title="Minimize / Expand Sidebar"
                @click="emit('toggle-collapse')"
            >
                <NavIcon
                    name="chevron-left"
                    class="h-5 w-5 transition-transform duration-300"
                    :class="{ 'rotate-180': collapsed }"
                />
            </button>

            <div class="text-sm font-semibold text-aksara-ink sm:text-base">
                <slot name="title">{{ title || 'Aksara' }}</slot>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a :href="profileUrl" class="hidden text-sm text-aksara-muted hover:text-aksara-ink sm:inline">Profil</a>
            <button type="button" class="aksara-btn-secondary !px-3 !py-1.5 text-xs" @click="logout">
                Keluar
            </button>
        </div>
    </header>
</template>
