<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { Purchase, PurchaseSummaryItem } from '@/types/purchase'
import type { Card as CardType } from '@/types/card'
import { Head } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import { LayoutList, Plus, Receipt, Search, Table as TableIcon } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import CardPurchaseDetailsModal from '@/Components/CardPurchaseDetailsModal.vue'
import MonthNavigator from '@/Components/MonthNavigator.vue'
import PurchaseDetailsModal from '@/Components/PurchaseDetailsModal.vue'
import PurchaseFormModal from '@/Components/PurchaseFormModal.vue'
import PaymentHistory from '@/Pages/Purchases/Partials/PaymentHistory.vue'
import PurchasesTableMode from '@/Pages/Purchases/Partials/PurchasesTableMode.vue'
import PurchaseSummary from '@/Pages/Purchases/Partials/PurchaseSummary.vue'
import { formatCurrency } from '@/lib/format'
import { useMonthNavigation } from '@/composables/useMonthNavigation'

interface PaymentHistoryItem {
    id: number
    name: string
    amount: number
    paid_at: string
    type: string
    partial?: boolean
}

defineOptions({ layout: AppLayout })

const props = defineProps<{
    purchases: Purchase[]
    summary: PurchaseSummaryItem[]
    paymentHistory: PaymentHistoryItem[]
    incomeTotal: number
    month: number
    year: number
    cards: CardType[]
}>()

const storedViewMode = localStorage.getItem('purchases_view_mode') as 'card' | 'table' | null
const viewMode = ref<'card' | 'table'>(storedViewMode ?? 'card')

watch(viewMode, (mode) => localStorage.setItem('purchases_view_mode', mode))

const activeTab = ref<'compras' | 'pagamentos'>('compras')
const showFormModal = ref(false)
const selectedPurchase = ref<Purchase | undefined>()
const showDetailsModal = ref(false)
const editingPurchase = ref<Purchase | undefined>()
const selectedCardPurchase = ref<PurchaseSummaryItem | undefined>()
const showCardDetailsModal = ref(false)

const searchQuery = ref('')

const { goToMonth } = useMonthNavigation('purchases.index')

function getItemKey(item: PurchaseSummaryItem): string {
    const first = item.items[0]
    return first?.card_id ? `card_${first.card_id}` : `purchase_${first?.id}`
}

const selectedIds = ref<Set<string>>(new Set())

function toggleSelect(key: string): void {
    const next = new Set(selectedIds.value)
    if (next.has(key)) {
        next.delete(key)
    } else {
        next.add(key)
    }
    selectedIds.value = next
}

function selectAll(): void {
    if (selectedIds.value.size === filteredSummary.value.length) {
        selectedIds.value = new Set()
    } else {
        selectedIds.value = new Set(filteredSummary.value.map(getItemKey))
    }
}

const hasSelection = computed(() => selectedIds.value.size > 0)

const selectedItems = computed(() =>
    filteredSummary.value.filter(item => selectedIds.value.has(getItemKey(item)))
)

const selectionStats = computed(() => {
    const items = selectedItems.value
    if (items.length === 0) return null
    const values = items.map(i => i.installment_value ?? i.total)
    const total = values.reduce((sum, v) => sum + v, 0)
    return {
        total,
        avg: total / values.length,
        max: Math.max(...values),
        min: Math.min(...values),
        count: values.length,
    }
})

const totalAmount = computed(() => props.summary.reduce((sum, item) => sum + parseFloat(String(item.total)), 0))

const paidAmount = computed(() => props.summary.reduce((sum, item) => {
    const total = parseFloat(String(item.total))
    if (item.paid_amount) return sum + Math.min(parseFloat(String(item.paid_amount)), total)
    if (item.status === 'paga') return sum + total
    return sum
}, 0))

const pendingAmount = computed(() => {
    const pending = totalAmount.value - paidAmount.value
    return Math.abs(pending) < 0.01 ? 0 : pending
})

const hasOverdue = computed(() => props.summary.some((item) => item.status === 'atrasada'))
const balance = computed(() => props.incomeTotal - totalAmount.value)

const filteredSummary = computed(() => {
    if (!searchQuery.value) return props.summary
    const query = searchQuery.value.toLowerCase()
    return props.summary.filter(item =>
        item.name?.toLowerCase().includes(query) ||
        item.items.some(p => p.name?.toLowerCase().includes(query))
    )
})

const tabs = [
    { key: 'compras' as const, label: 'Visão geral', condition: true },
    { key: 'pagamentos' as const, label: 'Pagamentos', icon: Receipt },
]

const viewModes = [
    { key: 'card' as const, icon: LayoutList, label: 'Visualização em cards' },
    { key: 'table' as const, icon: TableIcon, label: 'Visualização em tabela' },
]

function onTableSelect(item: PurchaseSummaryItem): void {
    selectedPurchase.value = {
        ...item.items[0],
        status: item.status ?? 'aberta',
        paid_at: item.paid_at ?? null,
    } as Purchase
    showDetailsModal.value = true
}

function onTableCardSelect(item: PurchaseSummaryItem): void {
    selectedCardPurchase.value = item
    showCardDetailsModal.value = true
}

function onEditPurchase(purchase: Purchase): void {
    editingPurchase.value = purchase
    showFormModal.value = true
}

function onCloseForm(open: boolean): void {
    showFormModal.value = open
    if (!open) editingPurchase.value = undefined
}

async function handleReorder(order: string[]): Promise<void> {
    try {
        await fetch(route('purchases.reorder'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ order }),
        })
    } catch {
        // Silently fail
    }
}
</script>

<template>
    <div class="w-full space-y-6">
        <Head title="My Wallet - Compras" />

        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold">Compras</h2>
            <div class="flex items-center gap-2">
                <template v-if="activeTab === 'compras'">
                    <TooltipProvider>
                        <Tooltip
                            v-for="mode in viewModes"
                            :key="mode.key"
                        >
                            <TooltipTrigger as-child>
                                <Button
                                    variant="outline"
                                    size="icon"
                                    :class="viewMode === mode.key ? 'bg-primary text-primary-foreground' : ''"
                                    @click="viewMode = mode.key"
                                >
                                    <component :is="mode.icon" class="size-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>{{ mode.label }}</TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </template>
                <Button @click="showFormModal = true">
                    <Plus class="mr-2 size-4" />
                    Nova compra
                </Button>
            </div>
        </div>

        <MonthNavigator
            :month="month"
            :year="year"
            @navigate="goToMonth"
        />

        <div class="flex justify-center">
            <div class="flex items-center gap-1 rounded-lg bg-muted p-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors cursor-pointer flex items-center gap-1.5"
                    :class="activeTab === tab.key ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                    @click="activeTab = tab.key"
                >
                    <component :is="tab.icon" v-if="tab.icon" class="size-3.5" />
                    {{ tab.label }}
                </button>
            </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-semibold text-muted-foreground">Total do Mês</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="text-3xl font-bold">{{ formatCurrency(totalAmount) }}</div>
                    <div class="space-y-2">
                        <div class="h-2.5 w-full overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-green-500 transition-all"
                                :style="{ width: totalAmount > 0 ? `${(paidAmount / totalAmount) * 100}%` : '0%' }"
                            />
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-muted-foreground">
                                <span class="font-semibold text-green-600">{{ formatCurrency(paidAmount) }}</span> pago
                            </span>
                            <span class="text-muted-foreground">
                                <span
                                    v-if="pendingAmount > 0"
                                    class="font-semibold"
                                    :class="hasOverdue ? 'text-destructive' : 'text-amber-500'"
                                >
                                    Faltam {{ formatCurrency(pendingAmount) }}
                                </span>
                                <span v-else class="font-semibold text-green-600">Tudo pago</span>
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-semibold text-muted-foreground">Receitas vs Gastos</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">Total de entradas</span>
                        <span class="font-semibold text-green-600">{{ formatCurrency(props.incomeTotal) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">Total de gastos</span>
                        <span class="font-semibold text-destructive">{{ formatCurrency(totalAmount) }}</span>
                    </div>
                    <div class="border-t pt-2 flex items-center justify-between text-sm font-semibold">
                        <span class="text-muted-foreground">Saldo</span>
                        <span
                            :class="balance >= 0 ? 'text-green-600' : 'text-destructive'"
                        >
                            {{ balance >= 0 ? '+' : '' }}{{ formatCurrency(balance) }}
                        </span>
                    </div>
                </CardContent>
            </Card>
        </div>

        <template v-if="activeTab === 'compras'">
            <div class="flex items-center gap-3">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                    <Input
                        v-model="searchQuery"
                        placeholder="Buscar compra..."
                        class="pl-9"
                    />
                </div>
                <Button
                    variant="outline"
                    size="sm"
                    class="shrink-0"
                    @click="selectAll"
                >
                    {{ selectedIds.size === filteredSummary.length && filteredSummary.length > 0 ? 'Desmarcar' : 'Selecionar' }}
                </Button>
            </div>
            <PurchaseSummary
                v-if="viewMode === 'card'"
                :items="filteredSummary"
                :month="month"
                :year="year"
                :selected-ids="selectedIds"
                @reorder="handleReorder"
                @edit-purchase="onEditPurchase"
                @toggle-select="toggleSelect"
            />
            <PurchasesTableMode
                v-else
                :items="filteredSummary"
                :month="month"
                :year="year"
                :selected-ids="selectedIds"
                @select="onTableSelect"
                @card-select="onTableCardSelect"
                @toggle-select="toggleSelect"
            />
        </template>

        <PaymentHistory
            v-else
            :items="paymentHistory"
        />

        <PurchaseDetailsModal
            v-model:open="showDetailsModal"
            :purchase="selectedPurchase"
            :month="month"
            :year="year"
            @edit="onEditPurchase"
        />

        <CardPurchaseDetailsModal
            v-model:open="showCardDetailsModal"
            :purchase-summary="selectedCardPurchase"
            :month="month"
            :year="year"
            context="purchases"
        />

        <PurchaseFormModal
            :open="showFormModal"
            :purchase="editingPurchase"
            :cards="cards"
            @update:open="onCloseForm"
        />

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-full opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-full opacity-0"
        >
            <div
                v-if="hasSelection && selectionStats"
                class="fixed bottom-0 left-0 right-0 z-50 border-t bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60"
            >
                <div class="mx-auto flex max-w-5xl flex-wrap items-center gap-4 px-4 py-3 text-sm">
                    <span class="font-medium text-muted-foreground">
                        {{ selectionStats.count }} selecionado(s)
                    </span>
                    <span class="text-muted-foreground">·</span>
                    <span>Total: <span class="font-semibold text-foreground">{{ formatCurrency(selectionStats.total) }}</span></span>
                    <span class="text-muted-foreground">·</span>
                    <span>Média: <span class="font-semibold text-foreground">{{ formatCurrency(selectionStats.avg) }}</span></span>
                    <span class="text-muted-foreground">·</span>
                    <span>Max: <span class="font-semibold text-foreground">{{ formatCurrency(selectionStats.max) }}</span></span>
                    <span class="text-muted-foreground">·</span>
                    <span>Min: <span class="font-semibold text-foreground">{{ formatCurrency(selectionStats.min) }}</span></span>
                </div>
            </div>
        </Transition>
    </div>
</template>
