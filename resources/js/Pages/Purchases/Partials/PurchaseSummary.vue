<script setup lang="ts">
import { ref } from 'vue';
import type { Purchase, PurchaseSummaryItem } from '@/types/purchase';
import { Card as CardComponent, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CreditCard, ShoppingCart, Calendar, FileText, Banknote, User } from '@lucide/vue';
import PurchaseDetailsModal from '@/Components/PurchaseDetailsModal.vue';
import CardPurchaseDetailsModal from '@/Components/CardPurchaseDetailsModal.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

defineProps<{
    items: PurchaseSummaryItem[];
}>();

const selectedPurchase = ref<Purchase | undefined>();
const showDetailsModal = ref(false);
const selectedCardPurchase = ref<PurchaseSummaryItem | undefined>();
const showCardDetailsModal = ref(false);

const typeIcons: Record<string, typeof CreditCard> = {
    subscription: FileText,
    credit_card: CreditCard,
    bill: Calendar,
    financing: Banknote,
    person: User,
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
    return `Fech: ${closing} / Venc: ${due}`;
}

function toTitleCase(str: string): string {
    return str.replace(/\b\w/g, (char) => char.toUpperCase());
}
</script>

<template>
    <div class="space-y-3">
        <div v-if="items.length === 0" class="text-center text-muted-foreground">
            Nenhuma compra neste mês
        </div>

        <template v-for="(item, index) in items" :key="item.name ?? `individual-${index}`">
            <!-- Card purchase (grouped by card) -->
            <CardComponent v-if="item.items[0].card_id"
                class="relative cursor-pointer overflow-hidden transition-colors hover:bg-muted/30"
                @click="openCardDetails(item)">
                <div class="absolute inset-x-0 top-0 h-2"
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
            <CardComponent v-else class="cursor-pointer transition-colors hover:bg-muted/30"
                @click="openIndividualDetails(item)">
                <CardHeader class="pb-2">
                    <CardTitle class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <component :is="typeIcons[item.items[0].type] ?? ShoppingCart"
                                class="size-5 text-muted-foreground" />
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
