import { describe, it, expect } from 'vitest';
import { useBulkSelect } from '@/Composables/useBulkSelect.js';

describe('useBulkSelect composable', () => {
    it('menginisialisasi state dengan nilai awal kosong', () => {
        const { selectedIds, isAllMatching, hasSelection } = useBulkSelect();

        expect(selectedIds.value).toEqual([]);
        expect(isAllMatching.value).toBe(false);
        expect(hasSelection.value).toBe(false);
    });

    it('dapat memilih dan membatalkan seleksi satu item (toggleSelect)', () => {
        const { selectedIds, isSelected, toggleSelect, hasSelection } = useBulkSelect();

        toggleSelect(10);
        expect(isSelected(10)).toBe(true);
        expect(selectedIds.value).toEqual([10]);
        expect(hasSelection.value).toBe(true);

        toggleSelect(10);
        expect(isSelected(10)).toBe(false);
        expect(selectedIds.value).toEqual([]);
        expect(hasSelection.value).toBe(false);
    });

    it('dapat memilih seluruh item di halaman aktif (toggleSelectAll)', () => {
        const { selectedIds, toggleSelectAll, isAllSelected, isIndeterminate } = useBulkSelect();
        const pageItems = [{ id: 1 }, { id: 2 }, { id: 3 }];

        expect(isAllSelected(pageItems)).toBe(false);
        expect(isIndeterminate(pageItems)).toBe(false);

        // Pilih semua
        toggleSelectAll(pageItems);
        expect(selectedIds.value).toEqual([1, 2, 3]);
        expect(isAllSelected(pageItems)).toBe(true);
        expect(isIndeterminate(pageItems)).toBe(false);

        // Batalkan semua
        toggleSelectAll(pageItems);
        expect(selectedIds.value).toEqual([]);
        expect(isAllSelected(pageItems)).toBe(false);
    });

    it('mendeteksi status indeterminate secara tepat bila hanya sebagian terpilih', () => {
        const { toggleSelect, isIndeterminate, isAllSelected } = useBulkSelect();
        const pageItems = [{ id: 1 }, { id: 2 }, { id: 3 }];

        toggleSelect(2);
        expect(isIndeterminate(pageItems)).toBe(true);
        expect(isAllSelected(pageItems)).toBe(false);

        toggleSelect(1);
        toggleSelect(3);
        expect(isIndeterminate(pageItems)).toBe(false);
        expect(isAllSelected(pageItems)).toBe(true);
    });

    it('mendukung mode selectAllMatching lintas halaman dan reset selection', () => {
        const { isAllMatching, hasSelection, selectAllMatching, clearSelection, getSelectedCount } = useBulkSelect();

        expect(getSelectedCount(150)).toBe(0);

        selectAllMatching();
        expect(isAllMatching.value).toBe(true);
        expect(hasSelection.value).toBe(true);
        expect(getSelectedCount(150)).toBe(150);

        clearSelection();
        expect(isAllMatching.value).toBe(false);
        expect(hasSelection.value).toBe(false);
        expect(getSelectedCount(150)).toBe(0);
    });

    it('mendukung array item berupa scalar id langsung maupun objek { id }', () => {
        const { selectedIds, toggleSelectAll, isAllSelected, isIndeterminate } = useBulkSelect();
        const idList = [101, 102, 103];

        expect(isAllSelected(idList)).toBe(false);
        toggleSelectAll(idList);
        expect(selectedIds.value).toEqual([101, 102, 103]);
        expect(isAllSelected(idList)).toBe(true);
        expect(isIndeterminate(idList)).toBe(false);

        toggleSelectAll(idList);
        expect(selectedIds.value).toEqual([]);
        expect(isAllSelected(idList)).toBe(false);
    });
});
