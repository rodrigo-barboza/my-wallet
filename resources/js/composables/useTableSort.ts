import { ref, watch } from 'vue'

export function useTableSort(initialKey: string | null = null, initialDir: 'asc' | 'desc' = 'asc', preferenceKey?: string) {
    const sortKey = ref<string | null>(initialKey)
    const sortDir = ref<'asc' | 'desc'>(initialDir)

    function toggleSort(key: string): void {
        if (sortKey.value === key) {
            sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
        } else {
            sortKey.value = key
            sortDir.value = 'asc'
        }
    }

    function sortIcon(key: string): string {
        if (sortKey.value !== key) return ' ⇅'
        return sortDir.value === 'asc' ? ' ▲' : ' ▼'
    }

    if (preferenceKey) {
        watch([sortKey, sortDir], ([key, dir]) => {
            fetch(route('preferences.update'), {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    key: preferenceKey,
                    value: key ? { key, dir } : null,
                }),
            })
        }, { deep: true })
    }

    return { sortKey, sortDir, toggleSort, sortIcon }
}
