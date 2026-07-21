<script setup lang="ts">
import { ref, watch } from 'vue';
import type { Purchase, PurchaseSummaryItem } from '@/types/purchase';
import { useSortable } from '@vueuse/integrations/useSortable';
import { Card as CardComponent, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CreditCard, ShoppingCart, Calendar, Banknote } from '@lucide/vue';
import PurchaseDetailsModal from '@/Components/PurchaseDetailsModal.vue';
import CardPurchaseDetailsModal from '@/Components/CardPurchaseDetailsModal.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps<{
    items: PurchaseSummaryItem[];
}>();

const emit = defineEmits<{
    reorder: [order: string[]];
}>();

const selectedPurchase = ref<Purchase | undefined>();
const showDetailsModal = ref(false);
const selectedCardPurchase = ref<PurchaseSummaryItem | undefined>();
const showCardDetailsModal = ref(false);

const list = ref([...props.items]);
watch(() => props.items, (newItems) => {
    list.value = [...newItems];
});

const el = ref<HTMLElement | null>(null);

function getItemKey(item: PurchaseSummaryItem): string {
    const first = item.items[0];
    if (first?.card_id) {
        return `card_${first.card_id}`;
    }
    return `purchase_${first?.id}`;
}

useSortable(el, list, {
    animation: 200,
    onEnd: () => {
        const order = list.value.map(getItemKey);
        emit('reorder', order);
    },
});

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

function openIndividualDetails(item: PurchaseSummaryItem): void {
    selectedPurchase.value = item.items[0];
    showDetailsModal.value = true;
}

function openCardDetails(item: PurchaseSummaryItem): void {
    selectedCardPurchase.value = item;
    showCardDetailsModal.value = true;
}

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
}

function formatDateRange(closing: number, due: number): string {
    return `Fechamento: ${closing} / Vencimento: ${due}`;
}

function toTitleCase(str: string): string {
    return str.split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
}
</script>

<template>
    <div ref="el" class="space-y-3">
        <div v-if="list.length === 0" class="text-center text-muted-foreground">
            Nenhuma compra neste mês
        </div>

        <template v-for="(item, index) in list" :key="getItemKey(item)">
            <!-- Card purchase (grouped by card) -->
            <CardComponent v-if="item.items[0].card_id"
                class="relative cursor-grab active:cursor-grabbing overflow-hidden transition-colors hover:bg-muted/30"
                :style="{ borderRadius: '0 var(--radius) var(--radius) 0' }"
                @click="openCardDetails(item)">
                <div class="absolute inset-y-0 left-0 w-1"
                    :style="{ backgroundColor: item.items[0].card?.color ?? '#6b7280' }" />
                <CardHeader class="pb-2">
                    <CardTitle class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <CreditCard class="size-5" :style="{ color: item.items[0].card?.color ?? '#6b7280' }" />
                            {{ item.name }}
                        </div>
                        <StatusBadge v-if="item.status" :status="item.status" />
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">
                            {{ formatDateRange(item.dates.closing, item.dates.due) }}
                        </span>
                        <span class="font-semibold">{{ formatCurrency(item.total) }}</span>
                    </div>
                </CardContent>
            </CardComponent>

            <!-- Individual purchase -->
            <CardComponent v-else
                class="relative cursor-grab active:cursor-grabbing overflow-hidden transition-colors hover:bg-muted/30"
                :style="{ borderRadius: '0 var(--radius) var(--radius) 0' }"
                @click="openIndividualDetails(item)">
                <div class="absolute inset-y-0 left-0 w-1"
                    :style="{ backgroundColor: typeColors[item.items[0].type] ?? '#6b7280' }" />
                <CardHeader class="pb-2">
                    <CardTitle class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <component :is="typeIcons[item.items[0].type] ?? ShoppingCart"
                                class="size-5"
                                :style="{ color: typeColors[item.items[0].type] ?? '#6b7280' }" />
                            {{ item.name ? toTitleCase(item.name) : 'Sem nome' }}
                        </div>
                        <StatusBadge v-if="item.status" :status="item.status" />
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">
                            Dia {{ item.dates[0] }}
                        </span>
                        <span class="font-semibold">{{ formatCurrency(item.total) }}</span>
                    </div>
                </CardContent>
            </CardComponent>
        </template>
    </div>

    <PurchaseDetailsModal v-model:open="showDetailsModal" :purchase="selectedPurchase" />

    <CardPurchaseDetailsModal v-model:open="showCardDetailsModal" :purchase-summary="selectedCardPurchase" />
</template>
