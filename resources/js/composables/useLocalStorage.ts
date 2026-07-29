import { ref, watch } from 'vue'

export function useLocalStorage<T>(key: string, defaultValue: T) {
    const stored = localStorage.getItem(key)
    let parsed: T = defaultValue

    if (stored !== null) {
        try {
            parsed = JSON.parse(stored) as T
        } catch {
            parsed = stored as T
        }
    }

    const value = ref<T>(parsed)

    watch(value, (newValue) => {
        localStorage.setItem(key, JSON.stringify(newValue))
    }, { deep: true })

    return value
}
