<script setup lang="ts">
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import { ChevronLeft, ChevronRight } from '@lucide/vue'
import { monthNames } from '@/lib/constants'

const props = defineProps<{
    month: number
    year: number
    minMonth?: number
    minYear?: number
}>()

const emit = defineEmits<{
    navigate: [month: number, year: number]
}>()

const currentMonthName = computed(() => monthNames[props.month - 1])

const canGoBack = computed(() => {
    if (!props.minMonth || !props.minYear) return true
    return !(props.year === props.minYear && props.month === props.minMonth)
})

function previousMonth(): void {
    let newMonth = props.month - 1
    let newYear = props.year
    if (newMonth < 1) { newMonth = 12; newYear-- }
    emit('navigate', newMonth, newYear)
}

function nextMonth(): void {
    let newMonth = props.month + 1
    let newYear = props.year
    if (newMonth > 12) { newMonth = 1; newYear++ }
    emit('navigate', newMonth, newYear)
}
</script>

<template>
    <div class="flex items-center justify-center gap-4">
        <Button variant="outline" size="icon" :disabled="!canGoBack" @click="previousMonth">
            <ChevronLeft class="size-4" />
        </Button>
        <div class="text-lg font-medium">
            {{ currentMonthName }} {{ year }}
        </div>
        <Button variant="outline" size="icon" @click="nextMonth">
            <ChevronRight class="size-4" />
        </Button>
    </div>
</template>
