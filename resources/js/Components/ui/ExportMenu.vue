<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Icon from '@/Components/ui/Icon.vue';

const props = defineProps({
    /** [{ label, href, icon?, target? }] */
    items: { type: Array, default: () => [] },
    label: { type: String, default: 'Ekspor' },
    icon: { type: String, default: 'download' },
    disabled: { type: Boolean, default: false },
});

const open = ref(false);
const triggerRef = ref(null);
const menuRef = ref(null);
const coords = ref({ top: 0, left: 0 });

const menuStyle = computed(() => ({
    top: `${coords.value.top}px`,
    left: `${coords.value.left}px`,
}));

function updatePosition() {
    const el = triggerRef.value;
    if (!el) return;
    const rect = el.getBoundingClientRect();
    const menuWidth = 176;
    const gap = 6;
    let left = rect.right - menuWidth;
    left = Math.max(8, Math.min(left, window.innerWidth - menuWidth - 8));
    coords.value = { top: rect.bottom + gap, left };
}

function toggle() {
    if (props.disabled) return;
    open.value = !open.value;
    if (open.value) nextTick(updatePosition);
}

function close() {
    open.value = false;
}

function onDocPointer(event) {
    if (!open.value) return;
    const t = event.target;
    if (triggerRef.value?.contains?.(t) || menuRef.value?.contains?.(t)) return;
    close();
}

function onScrollOrResize() {
    if (open.value) updatePosition();
}

watch(
    () => props.disabled,
    (d) => {
        if (d) close();
    },
);

onMounted(() => {
    document.addEventListener('pointerdown', onDocPointer, true);
    window.addEventListener('scroll', onScrollOrResize, true);
    window.addEventListener('resize', onScrollOrResize);
});

onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', onDocPointer, true);
    window.removeEventListener('scroll', onScrollOrResize, true);
    window.removeEventListener('resize', onScrollOrResize);
});
</script>

<template>
    <div class="relative inline-flex">
        <button
            ref="triggerRef"
            type="button"
            class="aksara-icon-btn"
            :class="{ 'pointer-events-none opacity-40': disabled }"
            :aria-label="label"
            :aria-expanded="open"
            :aria-haspopup="true"
            :disabled="disabled"
            @click="toggle"
        >
            <Icon :name="icon" class="h-3.5 w-3.5" />
        </button>

        <Teleport to="body">
            <div
                v-show="open"
                ref="menuRef"
                class="aksara-export-menu"
                role="menu"
                :aria-label="label"
                :style="menuStyle"
            >
                <a
                    v-for="(item, i) in items"
                    :key="i"
                    :href="item.href"
                    :target="item.target || undefined"
                    role="menuitem"
                    class="aksara-export-menu__item"
                    @click="close"
                >
                    <Icon :name="item.icon || 'document'" class="h-3.5 w-3.5 shrink-0 text-aksara-muted" />
                    <span>{{ item.label }}</span>
                </a>
            </div>
        </Teleport>
    </div>
</template>
