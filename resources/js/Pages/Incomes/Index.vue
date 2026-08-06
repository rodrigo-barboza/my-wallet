<script setup lang="ts">
import { computed, ref, watchEffect } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Check, ChevronLeft, ChevronRight, Copy, Plus, Trash2, X } from '@lucide/vue'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import AppLayout from '@/Layouts/AppLayout.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import IncomeFormModal from '@/Components/IncomeFormModal.vue'
import SelectionStatsBar from '@/Components/SelectionStatsBar.vue'
import { formatCurrency } from '@/lib/format'
import { monthAbbrs } from '@/lib/constants'
import type { Income } from '@/types/income'

defineOptions({ layout: AppLayout })

const props = defineProps<{
    incomes: Income[]
    year: number
}>()

const actionButtons = [
    { label: 'Duplicar entrada', icon: Copy, handler: (income: Income) => router.post(route('incomes.duplicate', income.id), { preserveScroll: true }) },
    { label: 'Excluir entrada', icon: Trash2, handler: (income: Income) => { deletingIncome.value = income; showDeleteDialog.value = true }, color: 'text-destructive' },
]

const centerMonth = ref(new Date().getMonth() + 1)
const centerYear = ref(props.year)
const sortAsc = ref(true)
const showAll = ref(false)
const filteredIncomes = ref<Income[]>([])
const showFormModal = ref(false)
const showDeleteDialog = ref(false)
const deletingIncome = ref<Income | undefined>()
const editingCell = ref<{ incomeId: number; month: number; year: number } | null>(null)
const editingValue = ref('')
const editingName = ref<{ incomeId: number; value: string } | null>(null)
const fillDialog = ref<{ incomeId: number; month: number; year: number; amount: number; open: boolean }>({
    incomeId: 0, month: 0, year: 0, amount: 0, open: false,
})
const fillCount = ref(1)

const visibleMonths = computed(() => {
    const months: { month: number; year: number; label: string }[] = []
    for (let i = -3; i <= 3; i++) {
        let m = centerMonth.value + i
        let y = centerYear.value
        if (m < 1) { m += 12; y-- }
        if (m > 12) { m -= 12; y++ }
        months.push({ month: m, year: y, label: monthAbbrs[m - 1] })
    }
    return months
})

const sortedIncomes = computed(() => {
    const sorted = [...filteredIncomes.value]
    sorted.sort((a, b) => a.name.localeCompare(b.name))
    if (!sortAsc.value) sorted.reverse()
    return sorted
})

const totals = computed(() => {
    return visibleMonths.value.map(m => {
        let total = 0
        for (const income of props.incomes) {
            const val = income.months[m.year]?.[m.month]?.amount ?? 0
            total += val
        }
        return total
    })
})

watchEffect(updateFiltered)

function updateFiltered(): void {
    filteredIncomes.value = showAll.value
        ? props.incomes
        : props.incomes.filter(income => visibleMonths.value.some(m => {
            const yearMonths = income.months[m.year]
            return yearMonths && yearMonths[m.month] !== undefined
        }))
}

function getAmount(income: Income, month: number, year: number): number | null {
    return income.months[year]?.[month]?.amount ?? null
}

function getMonthId(income: Income, month: number, year: number): number | null {
    return income.months[year]?.[month]?.id ?? null
}

function startEdit(income: Income, month: number, year: number): void {
    const val = getAmount(income, month, year)
    editingCell.value = { incomeId: income.id, month, year }
    editingValue.value = val !== null ? String(val) : ''
}

function saveCell(): void {
    if (!editingCell.value) return
    const { incomeId, month, year } = editingCell.value
    const monthId = getMonthId(props.incomes.find(i => i.id === incomeId)!, month, year)
    const amount = parseFloat(editingValue.value.replace(',', '.'))

    if (monthId !== null && !isNaN(amount)) {
        router.patch(route('incomes.update-month', monthId), { amount }, {
            preserveScroll: true,
            onSuccess: () => { editingCell.value = null },
        })
        return
    }

    editingCell.value = null
}

function cancelEdit(): void {
    editingCell.value = null
}

function delayedCancelEdit(): void {
    setTimeout(() => {
        if (editingCell.value) {
            editingCell.value = null
        }
    }, 150)
}

function startEditName(income: Income): void {
    editingName.value = { incomeId: income.id, value: income.name }
}

function saveName(): void {
    if (!editingName.value) return
    const income = props.incomes.find(i => i.id === editingName.value!.incomeId)
    if (!income) return

    router.put(route('incomes.update', income.id), { name: editingName.value.value }, {
        preserveScroll: true,
        onSuccess: () => { editingName.value = null },
    })
}

function cancelEditName(): void {
    editingName.value = null
}

function openFill(income: Income, month: number, year: number): void {
    const val = getAmount(income, month, year)
    if (val === null) return
    fillDialog.value = { incomeId: income.id, month, year, amount: val, open: true }
    fillCount.value = 1
}

function confirmFill(): void {
    const { incomeId, month, year, amount } = fillDialog.value
    const income = props.incomes.find(i => i.id === incomeId)
    if (!income) return

    router.post(route('incomes.fill-months', income.id), {
        start_month: month, start_year: year, repeat_count: fillCount.value, amount,
    }, {
        preserveScroll: true,
        onSuccess: () => { fillDialog.value.open = false },
    })
}

function confirmDelete(): void {
    if (!deletingIncome.value) return
    router.delete(route('incomes.destroy', deletingIncome.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteDialog.value = false
            deletingIncome.value = undefined
        },
    })
}

function toggleShowAll(checked: boolean): void {
    showAll.value = checked
}

const selectedIds = ref<Set<number>>(new Set())

function toggleSelect(id: number): void {
    const next = new Set(selectedIds.value)
    if (next.has(id)) {
        next.delete(id)
    } else {
        next.add(id)
    }
    selectedIds.value = next
}

function selectAll(): void {
    if (selectedIds.value.size === sortedIncomes.value.length) {
        selectedIds.value = new Set()
    } else {
        selectedIds.value = new Set(sortedIncomes.value.map(i => i.id))
    }
}

const selectedTotal = computed(() => {
    let total = 0
    for (const income of props.incomes) {
        if (!selectedIds.value.has(income.id)) continue
        for (const m of visibleMonths.value) {
            total += income.months[m.year]?.[m.month]?.amount ?? 0
        }
    }
    return total
})

const selectionBarItems = computed(() => [
    { label: 'Total', value: formatCurrency(selectedTotal.value) },
])

function previousMonth(): void {
    centerMonth.value--
    if (centerMonth.value < 1) {
        centerMonth.value = 12
        centerYear.value--
    }
}

function nextMonth(): void {
    centerMonth.value++
    if (centerMonth.value > 12) {
        centerMonth.value = 1
        centerYear.value++
    }
}
</script>

<template>
    <div class="w-full space-y-6">
        <Head title="My Wallet - Entradas" />

        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold">Entradas</h2>
            <div class="flex items-center gap-2">
                <Button @click="showFormModal = true">
                    <Plus class="mr-2 size-4" />
                    Nova entrada
                </Button>
            </div>
        </div>

        <div class="flex items-center justify-center gap-4">
            <Button
                variant="outline"
                size="icon"
                @click="previousMonth"
            >
                <ChevronLeft class="size-4" />
            </Button>
            <div class="text-sm text-muted-foreground">
                <span class="text-base font-semibold text-foreground">{{ monthAbbrs[visibleMonths[0].month - 1] }}/{{ visibleMonths[0].year }}</span>
                <span class="mx-1">até</span>
                <span class="text-base font-semibold text-foreground">{{ monthAbbrs[visibleMonths[6].month - 1] }}/{{ visibleMonths[6].year }}</span>
            </div>
            <Button
                variant="outline"
                size="icon"
                @click="nextMonth"
            >
                <ChevronRight class="size-4" />
            </Button>
        </div>

        <div class="flex items-center justify-end text-sm">
            <Button
                variant="outline"
                size="sm"
                @click="toggleShowAll(!showAll)"
            >
                {{ showAll ? 'Ocultar vazias' : 'Mostrar todas' }}
            </Button>
        </div>

        <div class="overflow-x-auto rounded-md border">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th
                            class="sticky left-0 z-10 bg-muted/50 px-3 py-2.5 text-left font-medium text-muted-foreground min-w-[140px] cursor-pointer select-none"
                            @click="sortAsc = !sortAsc"
                        >
                            <div class="flex items-center gap-2">
                                <Checkbox
                                    :checked="selectedIds.size === sortedIncomes.length && sortedIncomes.length > 0"
                                    @update:checked="selectAll"
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
                    <tr
                        v-for="income in sortedIncomes"
                        :key="income.id"
                        class="border-b last:border-b-0 hover:bg-muted/30"
                    >
                        <td class="sticky left-0 z-10 bg-background px-3 py-2.5 font-medium">
                            <div class="flex items-center gap-2">
                                <Checkbox
                                    :checked="selectedIds.has(income.id)"
                                    @click.stop
                                    @update:checked="toggleSelect(income.id)"
                                />
                                <div
                                    v-if="editingName?.incomeId === income.id"
                                    class="flex items-center gap-1"
                                >
                                    <Input
                                        v-model="editingName.value"
                                        class="h-7 text-sm"
                                        @keydown.enter="saveName"
                                        @keydown.esc="cancelEditName"
                                    />
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="size-6 shrink-0"
                                        @click="saveName"
                                    >
                                        <Check class="size-3" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="size-6 shrink-0"
                                        @click="cancelEditName"
                                    >
                                        <X class="size-3" />
                                    </Button>
                                </div>
                                <span
                                    v-else
                                    class="cursor-pointer hover:text-primary"
                                    @click="startEditName(income)"
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
                            @click="startEdit(income, m.month, m.year)"
                        >
                            <div
                                v-if="editingCell?.incomeId === income.id && editingCell?.month === m.month && editingCell?.year === m.year"
                                class="flex items-center justify-center gap-0.5"
                            >
                                <Input
                                    v-model="editingValue"
                                    class="h-6 w-20 py-0 text-center text-sm tabular-nums"
                                    type="text"
                                    inputmode="decimal"
                                    @keydown.enter="saveCell"
                                    @keydown.esc="cancelEdit"
                                    @blur="delayedCancelEdit"
                                    @click.stop
                                />
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-5 shrink-0"
                                    @click.stop="saveCell"
                                >
                                    <Check class="size-3" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-5 shrink-0"
                                    @click.stop="openFill(income, m.month, m.year)"
                                >
                                    <span class="text-[10px] font-bold">→</span>
                                </Button>
                            </div>
                            <span v-else class="text-muted-foreground">
                                {{ getAmount(income, m.month, m.year) !== null ? formatCurrency(getAmount(income, m.month, m.year)!) : '-' }}
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
                                                @click="btn.handler(income)"
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
                    <tr
                        v-if="sortedIncomes.length === 0"
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
                <tfoot v-if="filteredIncomes.length > 0">
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

        <div
            v-if="filteredIncomes.length > 0"
            class="text-sm text-muted-foreground text-center"
        >
            Clique em uma célula para editar o valor. Use o ícone
            <span class="font-bold text-xs">→</span>
            para preencher os próximos meses com o mesmo valor.
        </div>

        <IncomeFormModal
            :open="showFormModal"
            @update:open="showFormModal = $event"
        />

        <ConfirmDialog
            v-model:open="showDeleteDialog"
            title="Excluir entrada"
            description="Tem certeza que deseja excluir esta entrada? Esta ação não pode ser desfeita."
            confirm-text="Excluir"
            @confirm="confirmDelete"
        />

        <SelectionStatsBar
            :count="selectedIds.size"
            :items="selectionBarItems"
        />
    </div>

    <Dialog
        :open="fillDialog.open"
        @update:open="fillDialog.open = $event"
    >
        <DialogContent class="sm:max-w-xs">
            <DialogHeader>
                <DialogTitle>Preencher meses</DialogTitle>
            </DialogHeader>
            <div class="space-y-4">
                <p class="text-sm text-muted-foreground">
                    Preencher <span class="font-semibold">{{ formatCurrency(fillDialog.amount) }}</span> a partir de
                    {{ monthAbbrs[fillDialog.month - 1] }}/{{ fillDialog.year }} por quantos meses?
                </p>
                <div class="flex items-center gap-2">
                    <Input
                        v-model="fillCount"
                        type="number"
                        min="1"
                        max="12"
                        class="w-20"
                    />
                    <span class="text-sm text-muted-foreground">mês(es)</span>
                </div>
                <Button
                    class="w-full"
                    @click="confirmFill"
                >
                    Preencher
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
