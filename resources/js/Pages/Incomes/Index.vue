<script setup lang="ts">
import { computed, ref, watchEffect } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { ChevronLeft, ChevronRight, Copy, Plus, Trash2 } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import IncomeFormModal from '@/Components/IncomeFormModal.vue'
import IncomesCardMode from '@/Pages/Incomes/Partials/IncomesCardMode.vue'
import IncomesTableMode from '@/Pages/Incomes/Partials/IncomesTableMode.vue'
import ResponsiveModal from '@/Components/ResponsiveModal.vue'
import SelectionStatsBar from '@/Components/SelectionStatsBar.vue'
import { useIsMobile } from '@/composables/useIsMobile'
import { formatCurrency } from '@/lib/format'
import { monthAbbrs, monthNames } from '@/lib/constants'
import type { Income, IncomeEditingCell, IncomeEditingName } from '@/types/income'

defineOptions({ layout: AppLayout })

const props = defineProps<{
    incomes: Income[]
    year: number
}>()

const actionButtons = [
    { key: 'duplicate' as const, label: 'Duplicar entrada', icon: Copy, handler: (income: Income) => router.post(route('incomes.duplicate', income.id), { preserveScroll: true }) },
    { key: 'delete' as const, label: 'Excluir entrada', icon: Trash2, handler: (income: Income) => { deletingIncome.value = income; showDeleteDialog.value = true }, color: 'text-destructive' },
]

const isMobile = useIsMobile()
const centerMonth = ref(new Date().getMonth() + 1)
const centerYear = ref(props.year)
const sortAsc = ref(true)
const showAll = ref(false)
const filteredIncomes = ref<Income[]>([])
const showFormModal = ref(false)
const showDeleteDialog = ref(false)
const deletingIncome = ref<Income | undefined>()
const editingCell = ref<IncomeEditingCell | null>(null)
const editingValue = ref('')
const editingName = ref<IncomeEditingName | null>(null)
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

function handleAction(income: Income, action: 'duplicate' | 'delete'): void {
    const btn = actionButtons.find(b => b.key === action)
    btn?.handler(income)
}

function updateEditingNameValue(value: string): void {
    if (!editingName.value) return
    editingName.value.value = value
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
        total += income.months[centerYear.value]?.[centerMonth.value]?.amount ?? 0
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
    <div
        class="w-full space-y-6"
        :class="selectedIds.size > 0 ? 'pb-36 md:pb-20' : ''"
    >
        <Head title="My Wallet - Entradas" />

        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold">Entradas</h2>
            <div class="flex items-center gap-2">
                <Button id="onboarding-incomes-add" @click="showFormModal = true">
                    <Plus class="mr-2 size-4" />
                    Nova entrada
                </Button>
            </div>
        </div>

        <div id="onboarding-incomes-month" class="flex items-center justify-center gap-4">
            <Button
                variant="outline"
                size="icon"
                @click="previousMonth"
            >
                <ChevronLeft class="size-4" />
            </Button>
            <div v-if="!isMobile" class="text-sm text-muted-foreground">
                <span class="text-base font-semibold text-foreground">{{ monthAbbrs[visibleMonths[0].month - 1] }}/{{ visibleMonths[0].year }}</span>
                <span class="mx-1">até</span>
                <span class="text-base font-semibold text-foreground">{{ monthAbbrs[visibleMonths[6].month - 1] }}/{{ visibleMonths[6].year }}</span>
            </div>
            <div v-else class="text-base font-semibold text-foreground">
                {{ monthNames[centerMonth - 1] }} {{ centerYear }}
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

        <IncomesTableMode
            v-if="!isMobile"
            :incomes="sortedIncomes"
            :visible-months="visibleMonths"
            :totals="totals"
            :selected-ids="selectedIds"
            :center-month="centerMonth"
            :center-year="centerYear"
            :sort-asc="sortAsc"
            :editing-cell="editingCell"
            :editing-value="editingValue"
            :editing-name="editingName"
            :action-buttons="actionButtons"
            @toggle-select="toggleSelect"
            @select-all="selectAll"
            @sort-toggle="sortAsc = !sortAsc"
            @start-edit="startEdit"
            @save-cell="saveCell"
            @cancel-edit="cancelEdit"
            @delayed-cancel-edit="delayedCancelEdit"
            @start-edit-name="startEditName"
            @save-name="saveName"
            @cancel-edit-name="cancelEditName"
            @open-fill="openFill"
            @action="handleAction"
            @update:editing-value="editingValue = $event"
            @update:editing-name-value="updateEditingNameValue"
        />

        <IncomesCardMode
            v-else
            :incomes="sortedIncomes"
            :selected-ids="selectedIds"
            :center-month="centerMonth"
            :center-year="centerYear"
            :editing-cell="editingCell"
            :editing-value="editingValue"
            :editing-name="editingName"
            :action-buttons="actionButtons"
            @toggle-select="toggleSelect"
            @start-edit="startEdit"
            @save-cell="saveCell"
            @cancel-edit="cancelEdit"
            @delayed-cancel-edit="delayedCancelEdit"
            @start-edit-name="startEditName"
            @save-name="saveName"
            @cancel-edit-name="cancelEditName"
            @open-fill="openFill"
            @action="handleAction"
            @update:editing-value="editingValue = $event"
            @update:editing-name-value="updateEditingNameValue"
        />

        <div
            v-if="!isMobile && filteredIncomes.length > 0"
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
            @clear="selectedIds = new Set()"
        />
    </div>

    <ResponsiveModal
        :open="fillDialog.open"
        title="Preencher meses"
        @update:open="fillDialog.open = $event"
    >
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
    </ResponsiveModal>
</template>
