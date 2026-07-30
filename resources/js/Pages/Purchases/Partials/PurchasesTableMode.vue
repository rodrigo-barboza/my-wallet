<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import type { PurchaseSummaryItem } from '@/types/purchase'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import { Banknote, Bell, Calendar, CreditCard, FileText, ShoppingCart } from '@lucide/vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import { formatCurrency, toTitleCase } from '@/lib/format'
import { typeIcons } from '@/lib/constants'
import { useTableSort } from '@/composables/useTableSort'

const props = defineProps<{
    items: PurchaseSummaryItem[]
    month: number
    year: number
}>()

const emit = defineEmits<{
    select: [item: PurchaseSummaryItem]
    cardSelect: [item: PurchaseSummaryItem]
}>()

const page = usePage()
const initialPrefs = (page.props.preferences as Record<string, any>) ?? {}
const storedSort = initialPrefs.purchases_table_sort ?? null

const { sortKey, sortDir, toggleSort, sortIcon } = useTableSort(
    storedSort?.key ?? null,
    storedSort?.dir ?? 'asc',
    'purchases_table_sort',
)

const typeColors: Record<string, string> = {
    bill: '#a8a29e',
    financing: '#78716c',
    others: '#57534e',
}

const statusOrder: Record<string, number> = {
    paga: 0,
    parcialmente_paga: 1,
    aberta: 2,
    fechada: 3,
    atrasada: 4,
}

const sortedItems = computed(() => {
    if (!sortKey.value) return props.items

    return [...props.items].sort((a, b) => {
        const sortMap: Record<string, () => number> = {
            name: () => (a.name ?? '').localeCompare(b.name ?? ''),
            status: () => (statusOrder[a.status ?? 'aberta'] ?? 0) - (statusOrder[b.status ?? 'aberta'] ?? 0),
            amount: () => a.total - b.total,
        }
        const cmp = (sortMap[sortKey.value as string] ?? (() => 0))()
        return sortDir.value === 'asc' ? cmp : -cmp
    })
})

function getIcon(item: PurchaseSummaryItem) {
    const first = item.items[0]
    return first?.card_id ? CreditCard : (typeIcons[first?.type] ?? ShoppingCart)
}

function getIconColor(item: PurchaseSummaryItem): string {
    const first = item.items[0]
    return first?.card_id ? (first.card?.color ?? '#6b7280') : (typeColors[first?.type] ?? '#6b7280')
}

function getName(item: PurchaseSummaryItem): string {
    if (item.name) return item.name
    const first = item.items[0]
    return first?.name ? toTitleCase(first.name) : 'Sem nome'
}

function getDates(item: PurchaseSummaryItem): string {
    if (!item.dates) return ''
    return Array.isArray(item.dates) ? `Dia ${item.dates[0]}` : `Fechamento: ${item.dates.closing} / Vencimento: ${item.dates.due}`
}

function isCardGroup(item: PurchaseSummaryItem): boolean {
    return !!item.items[0]?.card_id
}

function handleRowClick(item: PurchaseSummaryItem): void {
    emit(isCardGroup(item) ? 'cardSelect' : 'select', item)
}

const statusLabels: Record<string, string> = {
    paga: 'Paga',
    parcialmente_paga: 'Parcial',
    aberta: 'Aberta',
    fechada: 'Fechada',
    atrasada: 'Atrasada',
}
</script>

<template>
    <div class="rounded-md border">
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead class="w-10">#</TableHead>
                    <TableHead class="w-10" />
                    <TableHead
                        class="cursor-pointer select-none"
                        @click="toggleSort('name')"
                    >
                        Nome<span class="text-muted-foreground">{{ sortIcon('name') }}</span>
                    </TableHead>
                    <TableHead class="hidden sm:table-cell">Datas</TableHead>
                    <TableHead
                        class="cursor-pointer select-none"
                        @click="toggleSort('status')"
                    >
                        Status<span class="text-muted-foreground">{{ sortIcon('status') }}</span>
                    </TableHead>
                    <TableHead
                        class="cursor-pointer select-none text-right"
                        @click="toggleSort('amount')"
                    >
                        Valor<span class="text-muted-foreground">{{ sortIcon('amount') }}</span>
                    </TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow
                    v-for="(item, index) in sortedItems"
                    :key="isCardGroup(item) ? `card_${item.items[0].card_id}` : `purchase_${item.items[0].id}`"
                    class="cursor-pointer"
                    @click="handleRowClick(item)"
                >
                    <TableCell class="py-2.5 text-muted-foreground text-xs tabular-nums">
                        {{ index + 1 }}
                    </TableCell>
                    <TableCell class="py-2.5">
                        <component
                            :is="getIcon(item)"
                            class="size-4"
                            :style="{ color: getIconColor(item) }"
                        />
                    </TableCell>
                    <TableCell class="py-2.5 font-medium">
                        <div class="flex items-center gap-1.5">
                            {{ getName(item) }}
                            <TooltipProvider v-if="item.items[0]?.notify_due">
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <Bell class="size-3.5 text-amber-500 shrink-0" />
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>Notificação de vencimento ativa</p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </div>
                    </TableCell>
                    <TableCell class="py-2.5 text-muted-foreground hidden sm:table-cell">
                        {{ getDates(item) }}
                    </TableCell>
                    <TableCell class="py-2.5">
                        <StatusBadge :status="item.status ?? 'aberta'" />
                    </TableCell>
                    <TableCell class="py-2.5 text-right font-medium">
                        <template v-if="item.paid_amount && item.paid_amount < item.total">
                            {{ formatCurrency(item.paid_amount) }}
                            <span class="text-xs text-muted-foreground"> / </span>
                            {{ formatCurrency(item.total) }}
                        </template>
                        <template v-else>
                            {{ formatCurrency(item.total) }}
                        </template>
                    </TableCell>
                </TableRow>
                <TableRow v-if="sortedItems.length === 0">
                    <TableCell
                        colspan="6"
                        class="h-24 text-center text-muted-foreground"
                    >
                        Nenhuma compra neste mês
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
