<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { X } from '@lucide/vue'

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
            <!-- Desktop: horizontal -->
            <div
                class="mx-auto hidden max-w-5xl flex-wrap items-center gap-4 px-4 py-3 text-sm md:flex"
            >
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

            <!-- Mobile: grade compacta -->
            <div class="md:hidden">
                <div class="flex items-center justify-between gap-2 px-4 pt-3">
                    <span class="font-medium text-muted-foreground">{{ count }} selecionado(s)</span>
                    <Button
                        variant="ghost"
                        size="sm"
                        class="h-7 px-2 text-xs"
                        @click="emit('clear')"
                    >
                        <X class="mr-1 size-3.5" />
                        Limpar
                    </Button>
                </div>
                <div class="grid grid-cols-2 gap-x-4 gap-y-2 px-4 pb-4 pt-2">
                    <div
                        v-for="item in items"
                        :key="item.label"
                        class="flex items-baseline justify-between gap-2 rounded-md bg-muted/50 px-3 py-1.5"
                    >
                        <span class="text-xs text-muted-foreground">{{ item.label }}</span>
                        <span class="text-sm font-semibold tabular-nums text-foreground">{{ item.value }}</span>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>