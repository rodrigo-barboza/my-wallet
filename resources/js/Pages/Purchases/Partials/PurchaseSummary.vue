<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useSortable } from '@vueuse/integrations/useSortable'
import type { Purchase, PurchaseSummaryItem } from '@/types/purchase'
import { Card as CardComponent, CardContent } from '@/components/ui/card'
import Checkbox from '@/Components/Checkbox.vue'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import { Bell, CreditCard, ShoppingCart } from '@lucide/vue'
import CardPurchaseDetailsModal from '@/Components/CardPurchaseDetailsModal.vue'
import PurchaseDetailsModal from '@/Components/PurchaseDetailsModal.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import { useIsMobile } from '@/composables/useIsMobile'
import { formatCurrency, formatDateRange, toTitleCase } from '@/lib/format'
import { typeColors } from '@/lib/colors'
import { typeIcons } from '@/lib/constants'

const props = defineProps<{
    items: PurchaseSummaryItem[]
    month: number
    year: number
    selectedIds?: Set<string>
}>()

const emit = defineEmits<{
    reorder: [order: string[]]
    editPurchase: [purchase: Purchase]
    toggleSelect: [key: string]
}>()

const selectedPurchase = ref<Purchase | undefined>()
const showDetailsModal = ref(false)
const selectedCardPurchase = ref<PurchaseSummaryItem | undefined>()
const showCardDetailsModal = ref(false)

const list = ref([...props.items])
watch(() => props.items, (newItems) => { list.value = [...newItems] })

const el = ref<HTMLElement | null>(null)

function getItemKey(item: PurchaseSummaryItem): string {
    const first = item.items[0]
    return first?.card_id ? `card_${first.card_id}` : `purchase_${first?.id}`
}

const isMobile = useIsMobile()

const sortable = useSortable(el, list, {
    animation: 200,
    onUpdate: (e) => {
        const { oldIndex, newIndex } = e
        if (oldIndex === undefined || newIndex === undefined || oldIndex === newIndex) return

        const item = list.value.splice(oldIndex, 1)[0]
        list.value.splice(newIndex, 0, item)

        const order = list.value.map(getItemKey)
        emit('reorder', order)
    },
})

function applyDragDisabled(): void {
    sortable.option('disabled', isMobile.value)
}

onMounted(applyDragDisabled)
watch(isMobile, applyDragDisabled)

function getClosingDate(item: PurchaseSummaryItem): number {
    return !Array.isArray(item.dates) ? item.dates.closing : 0
}

function getDueDate(item: PurchaseSummaryItem): number {
    return !Array.isArray(item.dates) ? item.dates.due : 0
}

function getPaymentDay(item: PurchaseSummaryItem): number {
    return Array.isArray(item.dates) ? item.dates[0] : 0
}

function openIndividualDetails(item: PurchaseSummaryItem): void {
    selectedPurchase.value = {
        ...item.items[0],
        status: item.status ?? 'aberta',
        paid_at: item.paid_at ?? null,
    } as Purchase
    showDetailsModal.value = true
}

function openCardDetails(item: PurchaseSummaryItem): void {
    selectedCardPurchase.value = item
    showCardDetailsModal.value = true
}

function onEditPurchase(purchase: Purchase): void {
    emit('editPurchase', purchase)
}
</script>

<template>
    <div ref="el" class="space-y-3">
        <div
            v-if="list.length === 0"
            class="text-center text-muted-foreground"
        >
            Nenhuma compra neste mês
        </div>

        <template v-for="(item) in list" :key="getItemKey(item)">
            <CardComponent
                v-if="item.items[0].card_id"
                class="relative overflow-hidden transition-colors hover:bg-muted/30"
                :class="isMobile ? 'cursor-pointer' : 'cursor-grab active:cursor-grabbing'"
                :style="{ borderRadius: '0 var(--radius) var(--radius) 0' }"
                @click="openCardDetails(item)"
            >
                <div
                    class="absolute inset-y-0 left-0 w-1"
                    :style="{ backgroundColor: item.items[0].card?.color ?? typeColors.credit_card }"
                />
                <CardContent class="flex items-start gap-3 p-4">
                    <Checkbox
                        :checked="selectedIds?.has(getItemKey(item))"
                        class="mt-0.5 cursor-pointer"
                        @click.stop
                        @update:checked="emit('toggleSelect', getItemKey(item))"
                    />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <CreditCard
                                class="size-5 shrink-0"
                                :style="{ color: item.items[0].card?.color ?? '#6b7280' }"
                            />
                            <span class="truncate font-medium">{{ item.name }}</span>
                        </div>
                        <div
                            v-if="item.status"
                            class="mt-1.5"
                        >
                            <StatusBadge
                                :status="item.status"
                                :base-status="item.base_status"
                            />
                        </div>
                        <div class="mt-1.5 flex items-center justify-between gap-2 text-sm">
                            <span class="text-muted-foreground">
                                {{ formatDateRange(getClosingDate(item), getDueDate(item)) }}
                            </span>
                            <span class="shrink-0 font-semibold tabular-nums">{{ formatCurrency(item.total) }}</span>
                        </div>
                        <div
                            v-if="item.paid_amount && item.paid_amount < item.total"
                            class="mt-1 text-xs text-muted-foreground"
                        >
                            Pago {{ formatCurrency(item.paid_amount) }} de {{ formatCurrency(item.total) }}
                        </div>
                    </div>
                </CardContent>
            </CardComponent>

            <CardComponent
                v-else
                class="relative overflow-hidden transition-colors hover:bg-muted/30"
                :class="isMobile ? 'cursor-pointer' : 'cursor-grab active:cursor-grabbing'"
                :style="{ borderRadius: '0 var(--radius) var(--radius) 0' }"
                @click="openIndividualDetails(item)"
            >
                <div
                    class="absolute inset-y-0 left-0 w-1"
                    :style="{ backgroundColor: typeColors[item.items[0].type] ?? '#6b7280' }"
                />
                <CardContent class="flex items-start gap-3 p-4">
                    <Checkbox
                        :checked="selectedIds?.has(getItemKey(item))"
                        class="mt-0.5 cursor-pointer"
                        @click.stop
                        @update:checked="emit('toggleSelect', getItemKey(item))"
                    />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <component
                                :is="typeIcons[item.items[0].type] ?? ShoppingCart"
                                class="size-5 shrink-0"
                                :style="{ color: typeColors[item.items[0].type] ?? '#6b7280' }"
                            />
                            <span class="truncate font-medium">{{ item.name ? toTitleCase(item.name) : 'Sem nome' }}</span>
                            <TooltipProvider v-if="item.items[0].notify_due">
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Bell class="size-3.5 shrink-0 text-amber-500" />
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>Notificação de vencimento ativa</p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </div>
                        <div
                            v-if="item.status"
                            class="mt-1.5"
                        >
                            <StatusBadge
                                :status="item.status"
                                :base-status="item.base_status"
                            />
                        </div>
                        <p
                            v-if="item.current_installment && item.installments_total"
                            class="mt-1 text-xs text-muted-foreground"
                        >
                            Parcela {{ item.current_installment }} de {{ item.installments_total }}
                        </p>
                        <div class="mt-1.5 flex items-center justify-between gap-2 text-sm">
                            <span class="text-muted-foreground">
                                Dia {{ getPaymentDay(item) }}
                            </span>
                            <span class="shrink-0 font-semibold tabular-nums">{{ formatCurrency(item.installment_value ?? item.total) }}</span>
                        </div>
                    </div>
                </CardContent>
            </CardComponent>
        </template>
    </div>

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
</template>
