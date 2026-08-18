<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Check, X } from '@lucide/vue'
import Checkbox from '@/Components/Checkbox.vue'
import { formatCurrency } from '@/lib/format'
import type { Income, IncomeActionButton, IncomeEditingCell, IncomeEditingName } from '@/types/income'

const props = defineProps<{
    incomes: Income[]
    centerMonth: number
    centerYear: number
    selectedIds: Set<number>
    editingCell: IncomeEditingCell | null
    editingValue: string
    editingName: IncomeEditingName | null
    actionButtons: IncomeActionButton[]
}>()

const emit = defineEmits<{
    toggleSelect: [id: number]
    startEdit: [income: Income, month: number, year: number]
    saveCell: []
    cancelEdit: []
    delayedCancelEdit: []
    startEditName: [income: Income]
    saveName: []
    cancelEditName: []
    openFill: [income: Income, month: number, year: number]
    action: [income: Income, action: 'duplicate' | 'delete']
    'update:editingValue': [value: string]
    'update:editingNameValue': [value: string]
}>()

const currentMonth = { month: props.centerMonth, year: props.centerYear }

function getAmount(income: Income, month: number, year: number): number | null {
    return income.months[year]?.[month]?.amount ?? null
}

function isEditingCell(income: Income): boolean {
    return props.editingCell?.incomeId === income.id
        && props.editingCell?.month === currentMonth.month
        && props.editingCell?.year === currentMonth.year
}

function updateEditingValue(value: string): void {
    emit('update:editingValue', value)
}

function updateEditingNameValue(value: string): void {
    emit('update:editingNameValue', value)
}

function handleAction(income: Income, action: 'duplicate' | 'delete'): void {
    emit('action', income, action)
}
</script>

<template>
    <div class="space-y-3">
        <div
            v-if="incomes.length === 0"
            class="py-12 text-center text-muted-foreground text-sm"
        >
            Nenhuma entrada registrada. Clique em "Nova entrada" para adicionar.
        </div>

        <Card
            v-for="income in incomes"
            :key="income.id"
            class="overflow-hidden"
        >
            <CardContent class="p-4">
                <div class="flex items-center gap-2">
                    <Checkbox
                        :checked="selectedIds.has(income.id)"
                        @update:checked="emit('toggleSelect', income.id)"
                    />
                    <div v-if="editingName?.incomeId === income.id" class="flex flex-1 items-center gap-1 min-w-0">
                        <Input
                            :model-value="editingName.value"
                            class="h-7 text-sm"
                            @update:model-value="updateEditingNameValue"
                            @keydown.enter="emit('saveName')"
                            @keydown.esc="emit('cancelEditName')"
                        />
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-6 shrink-0"
                            @click="emit('saveName')"
                        >
                            <Check class="size-3" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-6 shrink-0"
                            @click="emit('cancelEditName')"
                        >
                            <X class="size-3" />
                        </Button>
                    </div>
                    <button
                        v-else
                        class="flex-1 min-w-0 truncate text-left text-sm font-medium cursor-pointer hover:text-primary"
                        @click="emit('startEditName', income)"
                    >
                        {{ income.name }}
                    </button>
                    <Button
                        v-for="btn in actionButtons"
                        :key="btn.key"
                        variant="ghost"
                        size="icon"
                        class="size-8 shrink-0"
                        :class="btn.color ?? ''"
                        @click="handleAction(income, btn.key)"
                    >
                        <component :is="btn.icon" class="size-4" />
                    </Button>
                </div>

                <div class="mt-3 flex items-center justify-between gap-2 border-t pt-3">
                    <span class="text-sm text-muted-foreground">Valor</span>
                    <div v-if="isEditingCell(income)" class="flex items-center gap-0.5">
                        <Input
                            :model-value="editingValue"
                            class="h-8 w-28 py-0 text-right text-sm tabular-nums"
                            type="text"
                            inputmode="decimal"
                            @update:model-value="updateEditingValue"
                            @keydown.enter="emit('saveCell')"
                            @keydown.esc="emit('cancelEdit')"
                            @blur="emit('delayedCancelEdit')"
                        />
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-7 shrink-0"
                            @click="emit('saveCell')"
                        >
                            <Check class="size-3.5" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-7 shrink-0"
                            @click="emit('openFill', income, currentMonth.month, currentMonth.year)"
                        >
                            <span class="text-xs font-bold">→</span>
                        </Button>
                    </div>
                    <button
                        v-else
                        class="text-sm font-semibold tabular-nums cursor-pointer hover:text-primary"
                        @click="emit('startEdit', income, currentMonth.month, currentMonth.year)"
                    >
                        {{ getAmount(income, currentMonth.month, currentMonth.year) !== null
                            ? formatCurrency(getAmount(income, currentMonth.month, currentMonth.year)!)
                            : '-' }}
                    </button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>