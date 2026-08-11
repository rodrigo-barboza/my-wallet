<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card as CardComponent, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { ArrowLeft, CreditCard, Pencil, Plus, Search, Trash2 } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Checkbox from '@/Components/Checkbox.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import MonthNavigator from '@/Components/MonthNavigator.vue'
import PurchaseFormModal from '@/Components/PurchaseFormModal.vue'
import SelectionStatsBar from '@/Components/SelectionStatsBar.vue'
import { formatCurrency } from '@/lib/format'
import { monthAbbrs } from '@/lib/constants'
import { useTableSort } from '@/composables/useTableSort'
import type { Card } from '@/types/card'
import type { Purchase } from '@/types/purchase'

defineOptions({ layout: AppLayout })

const props = defineProps<{
    card: Card
    purchases: Purchase[]
    monthlyTotals: { month: number; year: number; total: number }[]
    month: number
    year: number
    cards: Card[]
}>()

const initialPrefs = (usePage().props.preferences as Record<string, any>) ?? {}
const storedSort = initialPrefs.card_purchases_table_sort ?? null

const { sortKey, sortDir, toggleSort, sortIcon } = useTableSort(
    (storedSort?.key ?? 'start_date') as string,
    storedSort?.dir ?? 'asc',
    'card_purchases_table_sort'
)

const showFormModal = ref(false)
const editingPurchase = ref<Purchase | undefined>()
const showDeleteDialog = ref(false)
const deletingPurchase = ref<Purchase | undefined>()
const isMobile = ref(false)
const searchQuery = ref('')
const selectedIds = ref<Set<number>>(new Set())

const maxBarHeight = 100

const visibleTotals = computed(() => {
    if (isMobile.value && props.monthlyTotals.length >= 5) {
        return props.monthlyTotals.slice(1, 6)
    }
    return props.monthlyTotals
})

const maxMonthlyTotal = computed(() => Math.max(...visibleTotals.value.map(m => m.total), 1))

const totalAmount = computed(() =>
    props.purchases.reduce((sum, p) => {
        const value = p.installments_total
            ? parseFloat(String(p.amount)) / p.installments_total
            : parseFloat(String(p.amount))
        return sum + value
    }, 0)
)

const sortedPurchases = computed(() => {
    return [...props.purchases].sort((a, b) => {
        const values: Record<string, number> = {
            name: a.name.localeCompare(b.name),
            amount: a.amount - b.amount,
            installment_value: installmentValue(a) - installmentValue(b),
            start_date: new Date(normalizeDate(a.start_date)).getTime() - new Date(normalizeDate(b.start_date)).getTime(),
        }
        const cmp = values[sortKey.value as string] ?? 0
        return sortDir.value === 'asc' ? cmp : -cmp
    })
})

const filteredPurchases = computed(() => {
    if (!searchQuery.value) return sortedPurchases.value
    const query = searchQuery.value.toLowerCase()
    return sortedPurchases.value.filter(p =>
        p.name.toLowerCase().includes(query)
    )
})

function toggleSelect(id: number): void {
    const next = new Set(selectedIds.value)
    if (next.has(id)) {
        next.delete(id)
    } else {
        next.add(id)
    }
    selectedIds.value = next
}

function toggleSelectAll(): void {
    if (selectedIds.value.size === filteredPurchases.value.length) {
        selectedIds.value = new Set()
    } else {
        selectedIds.value = new Set(filteredPurchases.value.map(p => p.id))
    }
}

const allSelected = computed(() =>
    filteredPurchases.value.length > 0 && selectedIds.value.size === filteredPurchases.value.length
)

const selectionStats = computed(() => {
    const items = filteredPurchases.value.filter(p => selectedIds.value.has(p.id))
    if (items.length === 0) return null
    const values = items.map(p => parseFloat(String(installmentValue(p))))
    const total = values.reduce((sum, v) => sum + v, 0)
    return {
        total,
        avg: total / values.length,
        max: Math.max(...values),
        min: Math.min(...values),
        count: values.length,
    }
})

const selectionBarItems = computed(() => {
    const s = selectionStats.value
    if (!s) return []
    return [
        { label: 'Total', value: formatCurrency(s.total) },
        { label: 'Média', value: formatCurrency(s.avg) },
        { label: 'Max', value: formatCurrency(s.max) },
        { label: 'Min', value: formatCurrency(s.min) },
    ]
})

function normalizeDate(dateStr: string): string {
    return dateStr.includes('T') ? dateStr : dateStr.split(' ')[0] + 'T00:00:00'
}

function installmentValue(purchase: Purchase): number {
    if (!purchase.installments_total || purchase.installments_total === 0) return parseFloat(String(purchase.amount))
    return parseFloat(String(purchase.amount)) / purchase.installments_total
}

function barHeight(total: number): number {
    if (maxMonthlyTotal.value === 0) return 2
    return Math.max(Math.round((total / maxMonthlyTotal.value) * maxBarHeight), 2)
}

function isCurrentMonth(m: { month: number; year: number }): boolean {
    return m.month === props.month && m.year === props.year
}

function formatDate(value: string): string {
    const date = value.includes('T') ? value : value.split(' ')[0] + 'T00:00:00'
    return new Date(date).toLocaleDateString('pt-BR')
}

function currentInstallment(purchase: Purchase): string {
    if (purchase.is_recurring) return 'Recorrente'
    if (!purchase.installments_total) return 'À vista'
    const [startYear, startMonth] = purchase.start_date.split(/[-/]/).map(Number)
    const monthsDiff = (props.year - startYear) * 12 + (props.month - startMonth)
    return `${monthsDiff + 1} de ${purchase.installments_total}`
}

function openNewPurchase(): void {
    editingPurchase.value = undefined
    showFormModal.value = true
}

function openEdit(purchase: Purchase): void {
    editingPurchase.value = purchase
    showFormModal.value = true
}

function closeForm(): void {
    showFormModal.value = false
    editingPurchase.value = undefined
}

function confirmDelete(purchase: Purchase): void {
    deletingPurchase.value = purchase
    showDeleteDialog.value = true
}

function deletePurchase(): void {
    if (!deletingPurchase.value) return
    router.delete(route('purchases.destroy', deletingPurchase.value.id), {
        onSuccess: () => {
            showDeleteDialog.value = false
            deletingPurchase.value = undefined
        },
    })
}

function goToMonth(month: number, year: number): void {
    router.get(route('cards.purchases', { card: props.card.id, month, year }))
}

function checkMobile(): void {
    isMobile.value = window.innerWidth < 768
}

if (typeof window !== 'undefined') {
    checkMobile()
    window.addEventListener('resize', checkMobile)
}

onBeforeUnmount(() => {
    window.removeEventListener('resize', checkMobile)
})
</script>

<template>
    <div class="w-full space-y-6">
        <Head title="My Wallet - Compras do cartão" />

        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <Button
                    variant="ghost"
                    size="icon"
                    @click="router.visit(route('purchases.index', { month, year }))"
                >
                    <ArrowLeft class="size-5" />
                </Button>
                <h2 class="text-2xl font-bold">{{ card.name }}</h2>
                <CreditCard
                    class="size-6"
                    :style="{ color: card.color }"
                />
            </div>
            <Button @click="openNewPurchase">
                <Plus class="mr-2 size-4" />
                Nova compra
            </Button>
        </div>

        <CardComponent class="overflow-hidden">
            <CardContent class="p-4 pb-3">
                <div
                    class="flex items-end justify-between gap-1"
                    style="height: 140px"
                >
                    <div
                        v-for="m in visibleTotals"
                        :key="`${m.month}-${m.year}`"
                        class="flex flex-1 flex-col items-center cursor-pointer self-stretch transition-all hover:opacity-80"
                        @click="goToMonth(m.month, m.year)"
                    >
                        <div class="flex-1 w-full flex flex-col justify-end">
                            <span
                                class="text-[10px] leading-none tabular-nums text-center"
                                :class="isCurrentMonth(m) ? 'font-semibold text-foreground' : 'text-muted-foreground'"
                            >
                                {{ m.total > 0 ? formatCurrency(m.total) : '' }}
                            </span>
                            <div
                                class="w-full rounded-t transition-all mt-0.5"
                                :style="{
                                    height: barHeight(m.total) + 'px',
                                    backgroundColor: card.color,
                                    opacity: isCurrentMonth(m) ? 1 : 0.3,
                                }"
                            />
                        </div>
                        <span
                            class="text-[10px] mt-0.5"
                            :class="isCurrentMonth(m) ? 'font-semibold text-foreground' : 'text-muted-foreground'"
                        >
                            {{ monthAbbrs[m.month - 1] }}<span
                                v-if="m.year !== year"
                                class="text-[8px]"
                            > {{ m.year }}</span>
                        </span>
                    </div>
                </div>
            </CardContent>
        </CardComponent>

        <MonthNavigator
            :month="month"
            :year="year"
            @navigate="goToMonth"
        />

        <div
            v-if="purchases.length > 0"
            class="text-right text-sm text-muted-foreground"
        >
            Total: <span class="font-semibold">{{ formatCurrency(totalAmount) }}</span>
        </div>

        <div class="relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
            <Input
                v-model="searchQuery"
                placeholder="Buscar compra..."
                class="pl-9"
            />
        </div>

        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-10">
                            <Checkbox
                                :checked="allSelected"
                                @update:checked="toggleSelectAll"
                            />
                        </TableHead>
                        <TableHead class="w-10">#</TableHead>
                        <TableHead
                            class="cursor-pointer select-none"
                            @click="toggleSort('name')"
                        >
                            Nome<span class="text-muted-foreground">{{ sortIcon('name') }}</span>
                        </TableHead>
                        <TableHead
                            class="cursor-pointer select-none"
                            @click="toggleSort('amount')"
                        >
                            Valor<span class="text-muted-foreground">{{ sortIcon('amount') }}</span>
                        </TableHead>
                        <TableHead
                            class="hidden sm:table-cell cursor-pointer select-none"
                            @click="toggleSort('installment_value')"
                        >
                            Valor parcela<span class="text-muted-foreground">{{ sortIcon('installment_value') }}</span>
                        </TableHead>
                        <TableHead
                            class="hidden sm:table-cell cursor-pointer select-none"
                            @click="toggleSort('start_date')"
                        >
                            Data<span class="text-muted-foreground">{{ sortIcon('start_date') }}</span>
                        </TableHead>
                        <TableHead class="hidden sm:table-cell">Parcela</TableHead>
                        <TableHead class="text-right">Ações</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="(purchase, index) in filteredPurchases"
                        :key="purchase.id"
                    >
                        <TableCell class="py-2.5">
                            <Checkbox
                                :checked="selectedIds.has(purchase.id)"
                                @update:checked="toggleSelect(purchase.id)"
                            />
                        </TableCell>
                        <TableCell class="py-2.5 text-muted-foreground text-xs tabular-nums">
                            {{ index + 1 }}
                        </TableCell>
                        <TableCell class="py-2.5 font-medium">
                            {{ purchase.name }}
                        </TableCell>
                        <TableCell class="py-2.5 font-medium">
                            {{ formatCurrency(purchase.amount) }}
                        </TableCell>
                        <TableCell class="py-2.5 text-muted-foreground hidden sm:table-cell">
                            {{ purchase.installments_total ? formatCurrency(installmentValue(purchase)) : '-' }}
                        </TableCell>
                        <TableCell class="py-2.5 text-muted-foreground hidden sm:table-cell">
                            {{ formatDate(purchase.start_date) }}
                        </TableCell>
                        <TableCell class="py-2.5 text-muted-foreground hidden sm:table-cell">
                            {{ currentInstallment(purchase) }}
                        </TableCell>
                        <TableCell class="py-2.5 text-right">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-7 cursor-pointer"
                                @click="openEdit(purchase)"
                            >
                                <Pencil class="size-3.5" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-7 cursor-pointer text-destructive"
                                @click="confirmDelete(purchase)"
                            >
                                <Trash2 class="size-3.5" />
                            </Button>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="purchases.length === 0">
                        <TableCell
                            colspan="8"
                            class="h-24 text-center text-muted-foreground"
                        >
                            Nenhuma compra neste mês
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <PurchaseFormModal
            :open="showFormModal"
            :purchase="editingPurchase"
            :cards="cards"
            :default-card-id="card.id"
            @update:open="closeForm"
        />

        <ConfirmDialog
            v-model:open="showDeleteDialog"
            title="Excluir compra"
            description="Tem certeza que deseja excluir esta compra? Esta ação não pode ser desfeita."
            confirm-text="Excluir"
            @confirm="deletePurchase"
        />

        <SelectionStatsBar
            :count="selectionStats?.count ?? 0"
            :items="selectionBarItems"
            @clear="selectedIds = new Set()"
        />
    </div>
</template>
