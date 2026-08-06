<script setup lang="ts">
import { Button } from '@/components/ui/button'

defineProps<{
    count: number
    items: { label: string; value: string }[]
}>()

const emit = defineEmits<{
    clear: []
}>()
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-full opacity-0"
    >
        <div
            v-if="count > 0"
            class="fixed bottom-0 left-0 right-0 z-50 border-t bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60"
        >
            <div class="mx-auto flex max-w-5xl flex-wrap items-center gap-4 px-4 py-3 text-sm">
                <span class="font-medium text-muted-foreground">
                    {{ count }} selecionado(s)
                </span>
                <template v-for="(item, i) in items" :key="i">
                    <span class="text-muted-foreground">·</span>
                    <span>
                        {{ item.label }}:
                        <span class="font-semibold text-foreground">{{ item.value }}</span>
                    </span>
                </template>
                <span class="text-muted-foreground">·</span>
                <Button
                    variant="ghost"
                    size="sm"
                    class="h-7 text-xs"
                    @click="emit('clear')"
                >
                    Desmarcar
                </Button>
            </div>
        </div>
    </Transition>
</template>
