<script setup>
import { computed, onMounted, onBeforeUnmount, ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import NavIcon from '@/Components/ui/NavIcon.vue';

defineProps({
    title: { type: String, default: '' },
});

const emit = defineEmits(['toggle-mobile']);

const page = usePage();
const menuOpen = ref(false);
const menuRoot = ref(null);

const user = computed(() => page.props.auth?.user);
const profileUrl = computed(() => page.props.urls?.profile || '/profile');
const logoutUrl = computed(() => page.props.urls?.logout || '/logout');

function initial(name) {
    return (name || 'U').charAt(0).toUpperCase();
}

function toggleMenu() {
    menuOpen.value = !menuOpen.value;
}

function closeMenu() {
    menuOpen.value = false;
}

function logout() {
    closeMenu();
    router.post(logoutUrl.value);
}

function onDocClick(event) {
    if (!menuOpen.value || !menuRoot.value) return;
    if (!menuRoot.value.contains(event.target)) {
        closeMenu();
    }
}

onMounted(() => document.addEventListener('click', onDocClick));
onBeforeUnmount(() => document.removeEventListener('click', onDocClick));
</script>

<template>
    <header class="aksara-topbar">
        <div class="flex min-w-0 items-center gap-3">
            <button
                type="button"
                class="inline-flex rounded-lg border border-aksara-line p-2 text-aksara-ink lg:hidden"
                aria-label="Buka menu"
                @click="emit('toggle-mobile')"
            >
                <NavIcon name="menu" />
            </button>

            <div class="min-w-0 text-sm font-semibold text-aksara-ink sm:text-base">
                <slot name="title">{{ title || 'Aksara' }}</slot>
            </div>
        </div>

        <div ref="menuRoot" class="relative">
            <button
                type="button"
                class="aksara-profile-toggle"
                :aria-expanded="menuOpen"
                aria-haspopup="menu"
                @click.stop="toggleMenu"
            >
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-aksara-teal/10 text-sm font-semibold text-aksara-teal">
                    {{ initial(user?.name) }}
                </span>
                <span class="hidden min-w-0 text-left sm:block">
                    <span class="block truncate text-sm font-semibold leading-tight text-aksara-ink">
                        {{ user?.name || 'Pengguna' }}
                    </span>
                    <span class="block truncate text-[11px] leading-tight text-aksara-muted">
                        {{ user?.roleLabel || '—' }}
                    </span>
                </span>
                <NavIcon
                    name="chevron-down"
                    class="hidden h-4 w-4 shrink-0 text-aksara-muted transition sm:block"
                    :class="{ 'rotate-180': menuOpen }"
                />
            </button>

            <div
                v-show="menuOpen"
                class="aksara-profile-menu"
                role="menu"
            >
                <div class="border-b border-aksara-line px-3 py-2.5 sm:hidden">
                    <p class="truncate text-sm font-semibold text-aksara-ink">{{ user?.name }}</p>
                    <p class="truncate text-[11px] text-aksara-muted">{{ user?.roleLabel }}</p>
                </div>
                <a
                    :href="profileUrl"
                    class="aksara-profile-menu__item"
                    role="menuitem"
                    @click="closeMenu"
                >
                    <NavIcon name="users" class="h-4 w-4" />
                    Profil
                </a>
                <button
                    type="button"
                    class="aksara-profile-menu__item w-full text-left text-red-600 hover:bg-red-50"
                    role="menuitem"
                    @click="logout"
                >
                    <NavIcon name="arrow-right" class="h-4 w-4" />
                    Keluar
                </button>
            </div>
        </div>
    </header>
</template>
