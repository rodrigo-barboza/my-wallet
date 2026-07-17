<script setup lang="ts">
import type { Purchase, PurchaseSummaryItem } from '@/types/purchase';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Card as CardComponent, CardContent } from '@/components/ui/card';
import { CreditCard, Check } from '@lucide/vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps<{
    open: boolean;
    purchaseSummary?: PurchaseSummaryItem;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
}

function formatDate(value: string): string {
    return new Date(value + 'T00:00:00').toLocaleDateString('pt-BR');
}

function formatDateRange(closing: number, due: number): string {
    return `Fech: ${closing} / Venc: ${due}`;
}

const typeLabels: Record<string, string> = {
    subscription: 'Assinatura',
    credit_card: 'Compra no cartão',
    bill: 'Conta mensal',
    financing: 'Financiamento',
    person: 'Pagamento para pessoa',
};

function markAsPaid(): void {
    if (!props.purchaseSummary?.items[0]) return;
    router.patch(route('purchases.mark-as-paid', props.purchaseSummary.items[0].id), {}, {
        onSuccess: () => {
            emit('update:open', false);
            router.get(route('purchases.index'));
        },
    });
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

                <Button
                    v-if="purchaseSummary?.status === 'aberta' || purchaseSummary?.status === 'atrasada'"
                    variant="outline"
                    class="w-full"
                    @click="markAsPaid"
                >
                    <Check class="mr-2 size-4" />
                    Marcar como pago
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
