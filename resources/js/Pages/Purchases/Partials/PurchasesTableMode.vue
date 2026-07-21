<script setup lang="ts">
import { computed, ref } from 'vue';
import type { PurchaseSummaryItem } from '@/types/purchase';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { CreditCard, ShoppingCart, Calendar, Banknote } from '@lucide/vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps<{
    items: PurchaseSummaryItem[];
    month: number;
    year: number;
}>();

const emit = defineEmits<{
    select: [item: PurchaseSummaryItem];
    cardSelect: [item: PurchaseSummaryItem];
}>();

type SortKey = 'name' | 'status' | 'amount';

const sortKey = ref<SortKey | null>(null);
const sortDir = ref<'asc' | 'desc'>('asc');

const typeIcons: Record<string, typeof CreditCard> = {
    credit_card: CreditCard,
    bill: Calendar,
    financing: Banknote,
    others: ShoppingCart,
};

const typeColors: Record<string, string> = {
    bill: '#a8a29e',
    financing: '#78716c',
    others: '#57534e',
};

const statusOrder: Record<string, number> = {
    paga: 0,
    aberta: 1,
    atrasada: 2,
    fechada: 3,
};

const sortedItems = computed(() => {
    if (!sortKey.value) return props.items;

    return [...props.items].sort((a, b) => {
        let cmp = 0;

        if (sortKey.value === 'name') {
            cmp = (a.name ?? '').localeCompare(b.name ?? '');
        } else if (sortKey.value === 'status') {
            const aOrder = statusOrder[a.status ?? 'aberta'] ?? 0;
            const bOrder = statusOrder[b.status ?? 'aberta'] ?? 0;
            cmp = aOrder - bOrder;
        } else if (sortKey.value === 'amount') {
            cmp = a.total - b.total;
        }

        return sortDir.value === 'asc' ? cmp : -cmp;
    });
});

function toggleSort(key: SortKey): void {
    if (sortKey.value === key) {
        if (sortDir.value === 'asc') {
            sortDir.value = 'desc';
        } else {
            sortKey.value = null;
        }
    } else {
        sortKey.value = key;
        sortDir.value = 'asc';
    }
}

function sortIcon(key: SortKey): string {
    if (sortKey.value !== key) return ' ⇅';
    return sortDir.value === 'asc' ? ' ▲' : ' ▼';
}

function getIcon(item: PurchaseSummaryItem): typeof CreditCard {
    const first = item.items[0];
    return first?.card_id ? CreditCard : (typeIcons[first?.type] ?? ShoppingCart);
}

function getIconColor(item: PurchaseSummaryItem): string {
    const first = item.items[0];
    if (first?.card_id) {
        return first.card?.color ?? '#6b7280';
    }
    return typeColors[first?.type] ?? '#6b7280';
}

function getName(item: PurchaseSummaryItem): string {
    if (item.name) return item.name;
    const first = item.items[0];
    return first?.name ? toTitleCase(first.name) : 'Sem nome';
}

function getDates(item: PurchaseSummaryItem): string {
    if (!item.dates) return '';

    if (Array.isArray(item.dates)) {
        return `Dia ${item.dates[0]}`;
    }

    return `Fechamento: ${item.dates.closing} / Vencimento: ${item.dates.due}`;
}

function isCardGroup(item: PurchaseSummaryItem): boolean {
    return !!item.items[0]?.card_id;
}

function handleRowClick(item: PurchaseSummaryItem): void {
    if (isCardGroup(item)) {
        emit('cardSelect', item);
    } else {
        emit('select', item);
    }
}

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
}

function toTitleCase(str: string): string {
    return str.split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
}
</script>

<template>
    <div class="rounded-md border">
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead class="w-10">#</TableHead>
                    <TableHead class="w-10" />
                    <TableHead class="cursor-pointer select-none" @click="toggleSort('name')">
                        Nome<span class="text-muted-foreground">{{ sortIcon('name') }}</span>
                    </TableHead>
                    <TableHead class="hidden sm:table-cell">Datas</TableHead>
                    <TableHead class="cursor-pointer select-none" @click="toggleSort('status')">
                        Status<span class="text-muted-foreground">{{ sortIcon('status') }}</span>
                    </TableHead>
                    <TableHead class="cursor-pointer select-none text-right" @click="toggleSort('amount')">
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
                        {{ getName(item) }}
                    </TableCell>
                    <TableCell class="py-2.5 text-muted-foreground hidden sm:table-cell">
                        {{ getDates(item) }}
                    </TableCell>
                    <TableCell class="py-2.5">
                        <StatusBadge :status="item.status ?? 'aberta'" />
                    </TableCell>
                    <TableCell class="py-2.5 text-right font-medium">
                        {{ formatCurrency(item.total) }}
                    </TableCell>
                </TableRow>
                <TableRow v-if="sortedItems.length === 0">
                    <TableCell colspan="6" class="h-24 text-center text-muted-foreground">
                        Nenhuma compra neste mês
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
