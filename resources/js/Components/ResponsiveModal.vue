<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { useIsMobile } from '@/composables/useIsMobile'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import MobileSheetGrip from '@/Components/MobileSheetGrip.vue'

defineProps<{
    open: boolean
    title?: string | null
    description?: string | null
    class?: HTMLAttributes['class']
}>()

const emit = defineEmits<{
    'update:open': [value: boolean]
}>()

const isMobile = useIsMobile()
</script>

<template>
    <Dialog
        v-if="!isMobile"
        :open="open"
        @update:open="emit('update:open', $event)"
    >
        <DialogContent :class="cn('sm:max-w-md', $props.class)">
            <DialogHeader v-if="title || description">
                <DialogTitle v-if="title">{{ title }}</DialogTitle>
                <DialogDescription v-if="description">{{ description }}</DialogDescription>
            </DialogHeader>
            <slot />
            <DialogFooter v-if="$slots.footer">
                <slot name="footer" />
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Sheet
        v-else
        :open="open"
        @update:open="emit('update:open', $event)"
    >
        <SheetContent
            side="bottom"
            :class="cn('max-h-[90dvh]', $props.class)"
        >
            <MobileSheetGrip class="order-first shrink-0" />
            <SheetHeader
                v-if="title || description"
                class="pb-1 text-left"
            >
                <SheetTitle v-if="title">{{ title }}</SheetTitle>
                <SheetDescription v-if="description">{{ description }}</SheetDescription>
            </SheetHeader>
            <div class="flex-1 min-h-0 overflow-y-auto px-4">
                <div class="pb-4 min-h-4">
                    <slot />
                </div>
            </div>
            <div
                v-if="$slots.footer"
                class="mt-auto shrink-0 border-t px-4 pt-4 pb-2"
            >
                <slot name="footer" />
            </div>
        </SheetContent>
    </Sheet>
</template>