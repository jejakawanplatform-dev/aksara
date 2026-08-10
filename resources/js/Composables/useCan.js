import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useCan() {
    const page = usePage();
    const permissions = computed(() => page.props.auth?.permissions ?? []);

    function can(permission) {
        if (!permission) return true;
        return permissions.value.includes(permission);
    }

    return { permissions, can };
}
