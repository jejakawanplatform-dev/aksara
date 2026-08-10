import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useFlash() {
    const page = usePage();
    return computed(() => page.props.flash?.message ?? null);
}
