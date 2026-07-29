import { ref, watch } from 'vue'

export function useLocalStorage<T>(key: string, defaultValue: T) {
    const stored = localStorage.getItem(key)
    const value = ref<T>(stored ? JSON.parse(stored) : defaultValue)

    watch(value, (newValue) => {
        localStorage.setItem(key, JSON.stringify(newValue))
    }, { deep: true })

    return value
}
