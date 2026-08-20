<script setup lang="ts">
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Check, ChevronDown, ChevronRight, FolderX, Pencil, X } from '@lucide/vue'
import Checkbox from '@/Components/Checkbox.vue'
import { formatCurrency } from '@/lib/format'
import type { Income, IncomeGroup } from '@/types/income'
import type { IncomeActionButton, IncomeEditingCell, IncomeEditingName } from '@/types/income'

interface GroupWithItems extends IncomeGroup {
    items: Income[]
}

const props = defineProps<{
    incomes: Income[]
    groups: IncomeGroup[]
    collapsedGroups: Set<number>
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
    toggleGroupCollapse: [id: number]
    renameGroup: [group: IncomeGroup]
    deleteGroup: [group: IncomeGroup]
    detachIncome: [income: Income]
}>()

const currentMonth = { month: props.centerMonth, year: props.centerYear }

const visibleGroups = computed<GroupWithItems[]>(() =>
    props.groups
        .map(g => ({ ...g, items: props.incomes.filter(i => i.group_id === g.id) }))
        .filter(g => g.items.length > 0)
        .sort((a, b) => a.name.localeCompare(b.name))
)

const ungrouped = computed(() => props.incomes.filter(i => i.group_id === null))

function groupTotal(group: GroupWithItems): number {
    return group.items.reduce((sum, item) => sum + (item.months[currentMonth.year]?.[currentMonth.month]?.amount ?? 0), 0)
}

function getAmount(income: Income, month: number, year: number): number | null {
    return income.months[year]?.[month]?.amount ?? null
}

function displayAmount(income: Income, month: number, year: number): string {
    const value = getAmount(income, month, year)
    return value !== null && value > 0 ? formatCurrency(value) : '-'
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
            v-for="group in visibleGroups"
            :key="`group_${group.id}`"
            class="overflow-hidden"
        >
            <CardContent class="p-0">
                <div class="flex items-center gap-2 p-4 pb-3">
                    <button
                        class="flex flex-1 min-w-0 items-center gap-2 text-left cursor-pointer"
                        @click="emit('toggleGroupCollapse', group.id)"
                    >
                        <component
                            :is="collapsedGroups.has(group.id) ? ChevronRight : ChevronDown"
                            class="size-4 shrink-0"
                        />
                        <span class="min-w-0 truncate font-semibold">{{ group.name }}</span>
                    </button>
                    <span class="shrink-0 text-sm font-semibold tabular-nums">
                        {{ formatCurrency(groupTotal(group)) }}
                    </span>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-8 shrink-0"
                        @click="emit('renameGroup', group)"
                    >
                        <Pencil class="size-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-8 shrink-0 text-destructive"
                        @click="emit('deleteGroup', group)"
                    >
                        <FolderX class="size-4" />
                    </Button>
                </div>

                <div
                    v-if="!collapsedGroups.has(group.id)"
                    class="divide-y divide-muted/30 border-t"
                >
                    <CardContent
                        v-for="income in group.items"
                        :key="income.id"
                        class="p-4"
                    >
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
                                variant="ghost"
                                size="icon"
                                class="size-8 shrink-0 text-muted-foreground"
                                :title="`Remover do grupo ${group.name}`"
                                @click="emit('detachIncome', income)"
                            >
                                <X class="size-4" />
                            </Button>
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
                                {{ displayAmount(income, currentMonth.month, currentMonth.year) }}
                            </button>
                        </div>
                    </CardContent>
                </div>
            </CardContent>
        </Card>

        <Card
            v-for="income in ungrouped"
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
                        {{ displayAmount(income, currentMonth.month, currentMonth.year) }}
                    </button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>