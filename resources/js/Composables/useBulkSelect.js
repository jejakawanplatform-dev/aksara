/**
 * Aksara — platform pembelajaran berbantuan AI.
 *
 * @copyright 2026 jejakawan (https://jejakawan.com)
 * @license   MIT
 *
 * Clone, fork, and modification are permitted under the MIT License.
 * See the LICENSE file in the project root.
 */
import { ref, computed } from 'vue';

export function useBulkSelect() {
    const selectedIds = ref([]);
    const isAllMatching = ref(false);

    function isSelected(id) {
        return selectedIds.value.includes(id);
    }

    function toggleSelect(id) {
        const index = selectedIds.value.indexOf(id);
        if (index > -1) {
            selectedIds.value.splice(index, 1);
            isAllMatching.value = false;
        } else {
            selectedIds.value.push(id);
        }
    }

    const getId = (item) => (typeof item === 'object' && item !== null ? item.id : item);

    function isAllSelected(items = []) {
        if (!items || items.length === 0) return false;
        return items.every((item) => selectedIds.value.includes(getId(item)));
    }

    function isIndeterminate(items = []) {
        if (!items || items.length === 0) return false;
        const someSelected = items.some((item) => selectedIds.value.includes(getId(item)));
        const allSelected = items.every((item) => selectedIds.value.includes(getId(item)));
        return someSelected && !allSelected;
    }

    function toggleSelectAll(items = []) {
        if (!items || items.length === 0) return;

        if (isAllSelected(items)) {
            const itemIds = new Set(items.map(getId));
            selectedIds.value = selectedIds.value.filter((id) => !itemIds.has(id));
            isAllMatching.value = false;
        } else {
            const currentSet = new Set(selectedIds.value);
            items.forEach((item) => currentSet.add(getId(item)));
            selectedIds.value = Array.from(currentSet);
        }
    }

    function selectAllMatching() {
        isAllMatching.value = true;
    }

    function clearSelection() {
        selectedIds.value = [];
        isAllMatching.value = false;
    }

    function getSelectedCount(totalCount = 0) {
        if (isAllMatching.value && totalCount > 0) {
            return totalCount;
        }
        return selectedIds.value.length;
    }

    const hasSelection = computed(() => selectedIds.value.length > 0 || isAllMatching.value);

    return {
        selectedIds,
        isAllMatching,
        hasSelection,
        isSelected,
        toggleSelect,
        isAllSelected,
        isIndeterminate,
        toggleSelectAll,
        selectAllMatching,
        clearSelection,
        getSelectedCount,
    };
}
