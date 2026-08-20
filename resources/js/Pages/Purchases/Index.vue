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
import SelectionStatsBar from '@/Components/SelectionStatsBar.vue'
import PaymentHistory from '@/Pages/Purchases/Partials/PaymentHistory.vue'
import PurchasesTableMode from '@/Pages/Purchases/Partials/PurchasesTableMode.vue'
import PurchaseSummary from '@/Pages/Purchases/Partials/PurchaseSummary.vue'
import { useIsMobile } from '@/composables/useIsMobile'
import { useMonthNavigation } from '@/composables/useMonthNavigation'
import { formatCurrency } from '@/lib/format'

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

const isMobile = useIsMobile()
const storedViewMode = localStorage.getItem('purchases_view_mode') as 'card' | 'table' | null
const rawViewMode = ref<'card' | 'table'>(storedViewMode ?? 'card')
const viewMode = computed<'card' | 'table'>(() => isMobile.value ? 'card' : rawViewMode.value)

function setViewMode(mode: 'card' | 'table'): void {
    rawViewMode.value = mode
}

watch(rawViewMode, (mode) => localStorage.setItem('purchases_view_mode', mode))

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

const selectedItems = computed(() =>
    filteredSummary.value.filter(item => selectedIds.value.has(getItemKey(item)))
)

const selectionStats = computed(() => {
    const items = selectedItems.value
    if (items.length === 0) return null
    const values = items.map(i => parseFloat(String(i.installment_value ?? i.total)) || 0)
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
    <div
        class="w-full space-y-6"
        :class="selectedIds.size > 0 ? 'pb-36 md:pb-20' : ''"
    >
        <Head title="My Wallet - Compras" />

        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold">Compras</h2>
            <div class="flex items-center gap-2">
                <template v-if="activeTab === 'compras' && !isMobile">
                    <div id="onboarding-purchases-viewmode">
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
                                        @click="setViewMode(mode.key)"
                                    >
                                        <component :is="mode.icon" class="size-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>{{ mode.label }}</TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </template>
                <Button id="onboarding-purchases-add" @click="showFormModal = true">
                    <Plus class="mr-2 size-4" />
                    Nova compra
                </Button>
            </div>
        </div>

        <div id="onboarding-purchases-month">
            <MonthNavigator
                :month="month"
                :year="year"
                @navigate="goToMonth"
            />
        </div>

        <div id="onboarding-purchases-tabs" class="flex justify-center">
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

        <div id="onboarding-purchases-summary" class="grid gap-6 sm:grid-cols-2">
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
            <div id="onboarding-purchases-filters" class="relative w-full">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-5 text-muted-foreground" />
                <Input
                    v-model="searchQuery"
                    placeholder="Buscar compra..."
                    class="h-11 pl-10 text-base"
                />
            </div>
            <div id="onboarding-purchases-list">
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
            </div>
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

        <SelectionStatsBar
            :count="selectionStats?.count ?? 0"
            :items="selectionBarItems"
            @clear="selectedIds = new Set()"
        />
    </div>
</template>
