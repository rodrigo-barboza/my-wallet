<script setup lang="ts">
import { computed, ref } from 'vue';
import type { PurchaseSummaryItem } from '@/types/purchase';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Card as CardComponent, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { CreditCard, Check, Undo2 } from '@lucide/vue';
import { router } from '@inertiajs/vue3';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps<{
    open: boolean;
    purchaseSummary?: PurchaseSummaryItem;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const paymentAmount = ref<number>(0);

const remainingAmount = computed(() => {
    const total = props.purchaseSummary?.total ?? 0;
    const paid = props.purchaseSummary?.paid_amount ?? 0;
    return Math.max(0, total - paid);
});

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
}

function formatDate(value: string): string {
    return new Date(value + 'T00:00:00').toLocaleDateString('pt-BR');
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

const typeLabels: Record<string, string> = {
    credit_card: 'Compra no cartão',
    bill: 'Conta mensal',
    financing: 'Financiamento',
    others: 'Outros',
};

function close(): void {
    emit('update:open', false);
}

function openPaymentForm(): void {
    paymentAmount.value = remainingAmount.value;
}

function markAsPaid(): void {
    if (!props.purchaseSummary?.items[0]) return;
    router.patch(route('purchases.mark-as-paid', props.purchaseSummary.items[0].id), {
        amount: paymentAmount.value,
    }, {
        onSuccess: close,
    });
}

function unmarkAsPaid(): void {
    if (!props.purchaseSummary?.items[0]) return;
    router.patch(route('purchases.unmark-as-paid', props.purchaseSummary.items[0].id), {}, {
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

            <div v-if="purchaseSummary" class="space-y-3">
                <CardComponent
                    v-for="purchase in purchaseSummary.items"
                    :key="purchase.id"
                >
                    <CardContent class="flex items-center justify-between p-4">
                        <div>
                            <div class="font-medium">{{ purchase.name }}</div>
                            <div class="text-sm text-muted-foreground">
                                {{ typeLabels[purchase.type] ?? purchase.type }}
                                <span v-if="purchase.installments_total">
                                    · {{ purchase.installments_total }}x
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-semibold">{{ formatCurrency(purchase.amount) }}</div>
                            <div class="text-sm text-muted-foreground">
                                {{ formatDate(purchase.start_date) }}
                            </div>
                        </div>
                    </CardContent>
                </CardComponent>

                <div class="flex items-center justify-between border-t pt-3">
                    <span class="text-sm font-medium text-muted-foreground">Total</span>
                    <span class="text-lg font-bold">{{ formatCurrency(purchaseSummary.total) }}</span>
                </div>

                <div v-if="isPartiallyPaid() && purchaseSummary.paid_amount" class="text-sm text-muted-foreground">
                    Pago {{ formatCurrency(purchaseSummary.paid_amount) }} de {{ formatCurrency(purchaseSummary.total) }}
                </div>

                <div v-if="isFullyPaid() && purchaseSummary.paid_at" class="text-sm text-muted-foreground">
                    Pago {{ formatCurrency(purchaseSummary.total) }} em {{ formatDateTime(purchaseSummary.paid_at) }}
                </div>

                <template v-if="!isFullyPaid()">
                    <div class="flex items-center gap-2">
                        <Input
                            v-model.number="paymentAmount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            :max="remainingAmount"
                        />
                        <Button class="cursor-pointer" @click="markAsPaid">
                            <Check class="mr-2 size-4" />
                            Pagar
                        </Button>
                    </div>

                    <div v-if="paymentAmount !== remainingAmount" class="text-xs text-muted-foreground">
                        Valor restante: {{ formatCurrency(remainingAmount) }}
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
