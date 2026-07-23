<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import type { Purchase, PurchaseSummaryItem } from '@/types/purchase';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { CreditCard, Undo2, ExternalLink } from '@lucide/vue';
import { router } from '@inertiajs/vue3';
import StatusBadge from '@/Components/StatusBadge.vue';
import CurrencyInput from '@/Components/CurrencyInput.vue';

const props = defineProps<{
    open: boolean;
    purchaseSummary?: PurchaseSummaryItem;
    month: number;
    year: number;
    context?: 'purchases' | 'card';
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const paymentAmount = ref<number>(0);

const originalTotal = computed(() => {
    return props.purchaseSummary?.total ?? 0;
});

const itemCount = computed(() => {
    return props.purchaseSummary?.items.length ?? 0;
});

const cardId = computed(() => {
    return props.purchaseSummary?.items[0]?.card_id;
});

watch(() => props.purchaseSummary, (summary) => {
    paymentAmount.value = Math.max(0, (summary?.total ?? 0) - (summary?.paid_amount ?? 0));
}, { immediate: true });

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
}

function formatDateTime(value: string): string {
    return new Date(value).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatDateRange(closing: number, due: number): string {
    return `Fech: ${closing} / Venc: ${due}`;
}

function close(): void {
    emit('update:open', false);
}

function navigateToCardPurchases(): void {
    if (!cardId.value) return;
    router.visit(route('cards.purchases', {
        card: cardId.value,
        month: props.month,
        year: props.year,
    }));
}

function markAsPaid(): void {
    if (!props.purchaseSummary?.items[0]) return;
    router.patch(route('purchases.mark-as-paid', props.purchaseSummary.items[0].id), {
        amount: paymentAmount.value,
        month: props.month,
        year: props.year,
        redirect: props.context ?? 'purchases',
    }, {
        onSuccess: close,
    });
}

function unmarkAsPaid(): void {
    if (!props.purchaseSummary?.items[0]) return;
    router.patch(route('purchases.unmark-as-paid', props.purchaseSummary.items[0].id), {
        month: props.month,
        year: props.year,
        redirect: props.context ?? 'purchases',
    }, {
        onSuccess: close,
    });
}

function isFullyPaid(): boolean {
    return props.purchaseSummary?.status === 'paga';
}

function isPartiallyPaid(): boolean {
    return props.purchaseSummary?.status === 'parcialmente_paga';
}

function hasPayment(): boolean {
    return isFullyPaid() || isPartiallyPaid();
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <div class="flex items-center gap-2">
                    <CreditCard
                        v-if="purchaseSummary?.items[0].card"
                        class="size-5"
                        :style="{ color: purchaseSummary.items[0].card.color }"
                    />
                    <DialogTitle>{{ purchaseSummary?.name }}</DialogTitle>
                    <StatusBadge v-if="purchaseSummary?.status" :status="purchaseSummary.status" />
                </div>
                <DialogDescription>
                    <span v-if="purchaseSummary?.dates && !Array.isArray(purchaseSummary.dates)">
                        {{ formatDateRange(purchaseSummary.dates.closing, purchaseSummary.dates.due) }}
                    </span>
                </DialogDescription>
            </DialogHeader>

            <div v-if="purchaseSummary" class="space-y-4">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-muted-foreground">
                        {{ itemCount }} {{ itemCount === 1 ? 'compra' : 'compras' }} nesta fatura
                    </span>
                    <Button variant="outline" size="sm" class="cursor-pointer" @click="navigateToCardPurchases">
                        Ver detalhes da fatura
                        <ExternalLink class="ml-1 size-3.5" />
                    </Button>
                </div>

                <div class="flex items-center justify-between border-t pt-3">
                    <span class="text-sm font-medium text-muted-foreground">Total</span>
                    <span class="text-lg font-bold">{{ formatCurrency(purchaseSummary.total) }}</span>
                </div>

                <div v-if="isPartiallyPaid() && purchaseSummary.paid_amount" class="text-sm text-muted-foreground">
                    Pago {{ formatCurrency(purchaseSummary.paid_amount) }} de {{ formatCurrency(originalTotal) }}
                </div>

                <div v-if="isFullyPaid() && purchaseSummary.paid_at" class="text-sm text-muted-foreground">
                    Pago {{ formatCurrency(purchaseSummary.paid_amount ?? 0) }} em {{ formatDateTime(purchaseSummary.paid_at) }}
                </div>

                <template v-if="!isFullyPaid()">
                    <div class="flex items-center gap-2">
                        <CurrencyInput v-model="paymentAmount" class="flex-1 min-w-0" />
                        <Button class="cursor-pointer" @click="markAsPaid">
                            Pagar
                        </Button>
                    </div>
                </template>

                <Button
                    v-if="hasPayment()"
                    variant="outline"
                    class="w-full cursor-pointer"
                    @click="unmarkAsPaid"
                >
                    <Undo2 class="mr-2 size-4" />
                    Desmarcar pagamento
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
