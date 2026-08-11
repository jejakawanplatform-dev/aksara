/**
 * Aksara — platform pembelajaran berbantuan AI.
 *
 * @copyright 2026 jejakawan (https://jejakawan.com)
 * @license   MIT
 *
 * Clone, fork, and modification are permitted under the MIT License.
 * See the LICENSE file in the project root.
 */
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
