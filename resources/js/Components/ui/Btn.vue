<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    type: { type: String, default: 'button' },
    variant: { type: String, default: 'primary' }, // primary | secondary | danger
    size: { type: String, default: 'md' }, // sm | md
    disabled: { type: Boolean, default: false },
    href: { type: String, default: null },
    /**
     * Gunakan `external` untuk link download, ekspor file, atau URL eksternal.
     * Link internal (navigasi antar halaman Inertia) tidak perlu prop ini —
     * secara default Btn akan memakai Inertia <Link> agar SPA tidak full-reload.
     */
    external: { type: Boolean, default: false },
});

const variantClass = computed(() => {
    if (props.variant === 'secondary') return 'aksara-btn-secondary';
    if (props.variant === 'danger') return 'aksara-btn-danger';
    return 'aksara-btn-primary';
});

const sizeClass = computed(() => {
    if (props.size === 'sm') return '!px-3 !py-1.5 text-xs';
    return '';
});
</script>

<template>
    <!-- Navigasi internal SPA: pakai Inertia Link agar tidak full-reload -->
    <Link
        v-if="href && !external"
        :href="href"
        :class="[variantClass, sizeClass, $attrs.class]"
    >
        <slot />
    </Link>

    <!-- Link eksternal / download / ekspor file -->
    <a
        v-else-if="href && external"
        :href="href"
        :class="[variantClass, sizeClass, $attrs.class]"
    >
        <slot />
    </a>

    <!-- Tombol biasa -->
    <button
        v-else
        :type="type"
        :disabled="disabled"
        :class="[variantClass, sizeClass, $attrs.class]"
    >
        <slot />
    </button>
</template>
