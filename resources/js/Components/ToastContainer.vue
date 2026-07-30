<script setup lang="ts">
import { useToast, type Toast } from '@/composables/useToast'

const { toasts } = useToast()

function removeToast(id: number): void {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index !== -1) toasts.value.splice(index, 1)
}

const bgMap: Record<Toast['type'], string> = {
    success: 'bg-green-600',
    error: 'bg-red-600',
    info: 'bg-blue-600',
}

const iconMap: Record<Toast['type'], string> = {
    success: '✓',
    error: '✕',
    info: 'ℹ',
}
</script>

<template>
    <div class="fixed top-4 right-4 z-50 flex flex-col gap-2 pointer-events-none">
        <div
            v-for="toast in toasts"
            :key="toast.id"
            class="pointer-events-auto flex items-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white shadow-lg transition-all duration-300"
            :class="bgMap[toast.type]"
            @click="removeToast(toast.id)"
        >
            <span class="text-base font-bold">{{ iconMap[toast.type] }}</span>
            {{ toast.message }}
        </div>
    </div>
</template>
