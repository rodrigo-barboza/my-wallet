<script setup lang="ts">
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
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
    visibleMonths: { month: number; year: number; label: string }[]
    totals: number[]
    selectedIds: Set<number>
    centerMonth: number
    centerYear: number
    sortAsc: boolean
    editingCell: IncomeEditingCell | null
    editingValue: string
    editingName: IncomeEditingName | null
    actionButtons: IncomeActionButton[]
}>()

const emit = defineEmits<{
    toggleSelect: [id: number]
    selectAll: []
    sortToggle: []
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

const visibleGroups = computed<GroupWithItems[]>(() =>
    props.groups
        .map(g => ({ ...g, items: props.incomes.filter(i => i.group_id === g.id) }))
        .filter(g => g.items.length > 0)
        .sort((a, b) => a.name.localeCompare(b.name))
)

const ungrouped = computed(() => props.incomes.filter(i => i.group_id === null))

function groupTotal(group: GroupWithItems): number[] {
    return props.visibleMonths.map(m =>
        group.items.reduce((sum, item) => sum + (item.months[m.year]?.[m.month]?.amount ?? 0), 0)
    )
}

function getAmount(income: Income, month: number, year: number): number | null {
    return income.months[year]?.[month]?.amount ?? null
}

function displayAmount(income: Income, month: number, year: number): string {
    const value = getAmount(income, month, year)
    return value !== null && value > 0 ? formatCurrency(value) : '-'
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
    <div
        id="onboarding-incomes-list"
        class="overflow-x-auto rounded-md border"
    >
        <table class="w-full border-collapse text-sm">
            <thead>
                <tr class="border-b bg-muted/50">
                    <th
                        class="sticky left-0 z-10 bg-muted/50 px-3 py-2.5 text-left font-medium text-muted-foreground min-w-[140px] cursor-pointer select-none"
                        @click="emit('sortToggle')"
                    >
                        <div class="flex items-center gap-2">
                            <Checkbox
                                :checked="selectedIds.size === incomes.length && incomes.length > 0"
                                @update:checked="emit('selectAll')"
                            />
                            Nome
                            <span class="text-xs ml-1">{{ sortAsc ? '▲' : '▼' }}</span>
                        </div>
                    </th>
                    <th
                        v-for="m in visibleMonths"
                        :key="`${m.month}-${m.year}`"
                        class="px-3 py-2.5 text-center font-medium text-muted-foreground min-w-[100px]"
                        :class="m.month === centerMonth && m.year === centerYear ? 'text-foreground bg-primary/5' : ''"
                    >
                        {{ m.label }}
                        <span
                            v-if="m.year !== centerYear"
                            class="text-xs font-normal"
                        >/{{ m.year }}</span>
                    </th>
                    <th class="px-3 py-2.5 text-right font-medium text-muted-foreground min-w-[60px]">
                        Ações
                    </th>
                </tr>
            </thead>
            <tbody>
                <template v-for="group in visibleGroups" :key="`group_${group.id}`">
                    <tr class="border-b bg-muted/30 hover:bg-muted/40">
                        <td
                            class="sticky left-0 z-10 bg-muted/30 px-3 py-2 font-medium cursor-pointer select-none hover:bg-muted/40"
                            @click="emit('toggleGroupCollapse', group.id)"
                        >
                            <div class="flex items-center gap-2">
                                <component
                                    :is="collapsedGroups.has(group.id) ? ChevronRight : ChevronDown"
                                    class="size-4 shrink-0"
                                />
                                <span class="truncate font-semibold">{{ group.name }}</span>
                            </div>
                        </td>
                        <td
                            v-for="(total, ti) in groupTotal(group)"
                            :key="ti"
                            class="px-3 py-2 text-center font-semibold tabular-nums"
                            :class="visibleMonths[ti].month === centerMonth && visibleMonths[ti].year === centerYear ? 'bg-primary/10' : ''"
                        >
                            {{ total > 0 ? formatCurrency(total) : '-' }}
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex items-center justify-end gap-0.5">
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="size-7 cursor-pointer"
                                                @click="emit('renameGroup', group)"
                                            >
                                                <Pencil class="size-3.5" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Renomear grupo</p>
                                        </TooltipContent>
                                    </Tooltip>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="size-7 cursor-pointer text-destructive"
                                                @click="emit('deleteGroup', group)"
                                            >
                                                <FolderX class="size-3.5" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Excluir grupo</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                        </td>
                    </tr>

                    <template v-if="!collapsedGroups.has(group.id)">
                        <tr
                            v-for="income in group.items"
                            :key="income.id"
                            class="border-b last:border-b-0 hover:bg-muted/30"
                        >
                            <td class="sticky left-0 z-10 bg-background px-3 py-2.5 font-medium">
                                <div class="flex items-center gap-2">
                                    <Checkbox
                                        :checked="selectedIds.has(income.id)"
                                        @click.stop
                                        @update:checked="emit('toggleSelect', income.id)"
                                    />
                                    <div
                                        v-if="editingName?.incomeId === income.id"
                                        class="flex items-center gap-1"
                                    >
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
                                    <span
                                        v-else
                                        class="cursor-pointer hover:text-primary"
                                        @click="emit('startEditName', income)"
                                    >
                                        {{ income.name }}
                                    </span>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="size-6 shrink-0 text-muted-foreground"
                                        :title="`Remover do grupo ${group.name}`"
                                        @click="emit('detachIncome', income)"
                                    >
                                        <X class="size-3.5" />
                                    </Button>
                                </div>
                            </td>
                            <td
                                v-for="m in visibleMonths"
                                :key="`${m.month}-${m.year}`"
                                class="px-3 py-2.5 text-center tabular-nums cursor-pointer"
                                :class="m.month === centerMonth && m.year === centerYear ? 'bg-primary/5' : ''"
                                @click="emit('startEdit', income, m.month, m.year)"
                            >
                                <div
                                    v-if="editingCell?.incomeId === income.id && editingCell?.month === m.month && editingCell?.year === m.year"
                                    class="flex items-center justify-center gap-0.5"
                                >
                                    <Input
                                        :model-value="editingValue"
                                        class="h-6 w-20 py-0 text-center text-sm tabular-nums"
                                        type="text"
                                        inputmode="decimal"
                                        @update:model-value="updateEditingValue"
                                        @keydown.enter="emit('saveCell')"
                                        @keydown.esc="emit('cancelEdit')"
                                        @blur="emit('delayedCancelEdit')"
                                        @click.stop
                                    />
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="size-5 shrink-0"
                                        @click.stop="emit('saveCell')"
                                    >
                                        <Check class="size-3" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="size-5 shrink-0"
                                        @click.stop="emit('openFill', income, m.month, m.year)"
                                    >
                                        <span class="text-[10px] font-bold">→</span>
                                    </Button>
                                </div>
                                <span v-else class="text-muted-foreground">
                                    {{ displayAmount(income, m.month, m.year) }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5 text-right">
                                <div class="flex items-center justify-end gap-0.5">
                                    <TooltipProvider>
                                        <Tooltip
                                            v-for="btn in actionButtons"
                                            :key="btn.label"
                                        >
                                            <TooltipTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    class="size-7 cursor-pointer"
                                                    :class="btn.color ?? ''"
                                                    @click="handleAction(income, btn.key)"
                                                >
                                                    <component
                                                        :is="btn.icon"
                                                        class="size-3.5"
                                                    />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>{{ btn.label }}</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </div>
                            </td>
                        </tr>
                    </template>
                </template>

                <template v-if="ungrouped.length > 0">
                    <tr class="border-b bg-muted/30">
                        <td
                            class="sticky left-0 z-10 bg-muted/30 px-3 py-2 font-semibold text-muted-foreground"
                            :colspan="visibleMonths.length + 3"
                        >
                            Sem grupo
                        </td>
                    </tr>
                    <tr
                        v-for="income in ungrouped"
                        :key="income.id"
                        class="border-b last:border-b-0 hover:bg-muted/30"
                    >
                        <td class="sticky left-0 z-10 bg-background px-3 py-2.5 font-medium">
                            <div class="flex items-center gap-2">
                                <Checkbox
                                    :checked="selectedIds.has(income.id)"
                                    @click.stop
                                    @update:checked="emit('toggleSelect', income.id)"
                                />
                                <div
                                    v-if="editingName?.incomeId === income.id"
                                    class="flex items-center gap-1"
                                >
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
                                <span
                                    v-else
                                    class="cursor-pointer hover:text-primary"
                                    @click="emit('startEditName', income)"
                                >
                                    {{ income.name }}
                                </span>
                            </div>
                        </td>
                        <td
                            v-for="m in visibleMonths"
                            :key="`${m.month}-${m.year}`"
                            class="px-3 py-2.5 text-center tabular-nums cursor-pointer"
                            :class="m.month === centerMonth && m.year === centerYear ? 'bg-primary/5' : ''"
                            @click="emit('startEdit', income, m.month, m.year)"
                        >
                            <div
                                v-if="editingCell?.incomeId === income.id && editingCell?.month === m.month && editingCell?.year === m.year"
                                class="flex items-center justify-center gap-0.5"
                            >
                                <Input
                                    :model-value="editingValue"
                                    class="h-6 w-20 py-0 text-center text-sm tabular-nums"
                                    type="text"
                                    inputmode="decimal"
                                    @update:model-value="updateEditingValue"
                                    @keydown.enter="emit('saveCell')"
                                    @keydown.esc="emit('cancelEdit')"
                                    @blur="emit('delayedCancelEdit')"
                                    @click.stop
                                />
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-5 shrink-0"
                                    @click.stop="emit('saveCell')"
                                >
                                    <Check class="size-3" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-5 shrink-0"
                                    @click.stop="emit('openFill', income, m.month, m.year)"
                                >
                                    <span class="text-[10px] font-bold">→</span>
                                </Button>
                            </div>
                            <span v-else class="text-muted-foreground">
                                {{ displayAmount(income, m.month, m.year) }}
                            </span>
                        </td>
                        <td class="px-3 py-2.5 text-right">
                            <div class="flex items-center justify-end gap-0.5">
                                <TooltipProvider>
                                    <Tooltip
                                        v-for="btn in actionButtons"
                                        :key="btn.label"
                                    >
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="size-7 cursor-pointer"
                                                :class="btn.color ?? ''"
                                                @click="handleAction(income, btn.key)"
                                            >
                                                <component
                                                    :is="btn.icon"
                                                    class="size-3.5"
                                                />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{{ btn.label }}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                        </td>
                    </tr>
                </template>

                <tr
                    v-if="incomes.length === 0"
                    class="border-b last:border-b-0"
                >
                    <td
                        :colspan="visibleMonths.length + 3"
                        class="h-24 text-center text-muted-foreground"
                    >
                        Nenhuma entrada registrada. Clique em "Nova entrada" para adicionar.
                    </td>
                </tr>
            </tbody>
            <tfoot v-if="incomes.length > 0">
                <tr class="border-t-2 border-primary/20 bg-primary/5 font-semibold">
                    <td class="sticky left-0 z-10 bg-primary/5 px-3 py-2.5 text-foreground">
                        Total
                    </td>
                    <td
                        v-for="(total, i) in totals"
                        :key="i"
                        class="px-3 py-2.5 text-center tabular-nums text-primary"
                        :class="visibleMonths[i].month === centerMonth && visibleMonths[i].year === centerYear ? 'bg-primary/10' : ''"
                    >
                        {{ total > 0 ? formatCurrency(total) : '-' }}
                    </td>
                    <td class="px-3 py-2.5" />
                </tr>
            </tfoot>
        </table>
    </div>
</template>