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
import { Card as CardComponent, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { CreditCard, Undo2, Trash2, Pencil } from '@lucide/vue';
import { router, usePage } from '@inertiajs/vue3';
import StatusBadge from '@/Components/StatusBadge.vue';
import CurrencyInput from '@/Components/CurrencyInput.vue';
import PurchaseFormModal from '@/Components/PurchaseFormModal.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import type { Card } from '@/types/card';

const props = defineProps<{
    open: boolean;
    purchaseSummary?: PurchaseSummaryItem;
    month: number;
    year: number;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const cards = computed<Card[]>(() => (usePage().props.cards as Card[]) ?? []);

const paymentAmount = ref<number>(0);

const originalTotal = computed(() => {
    return props.purchaseSummary?.total ?? 0;
});

watch(() => props.purchaseSummary, (summary) => {
    paymentAmount.value = Math.max(0, (summary?.total ?? 0) - (summary?.paid_amount ?? 0));
}, { immediate: true });

const showEditModal = ref(false);
const editingPurchase = ref<Purchase | undefined>();

const showDeleteDialog = ref(false);
const deletingPurchase = ref<Purchase | undefined>();

function openEdit(purchase: Purchase): void {
    editingPurchase.value = purchase;
    showEditModal.value = true;
}

function closeEdit(): void {
    showEditModal.value = false;
    editingPurchase.value = undefined;
}

function confirmDelete(purchase: Purchase): void {
    deletingPurchase.value = purchase;
    showDeleteDialog.value = true;
}

function deletePurchase(): void {
    if (!deletingPurchase.value) return;
    router.delete(route('purchases.destroy', deletingPurchase.value.id), {
        onSuccess: () => {
            showDeleteDialog.value = false;
            deletingPurchase.value = undefined;
            emit('update:open', false);
        },
    });
}

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
}

function formatDate(value: string): string {
    const date = value.includes('T') ? value : value.split(' ')[0] + 'T00:00:00';
    return new Date(date).toLocaleDateString('pt-BR');
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

function markAsPaid(): void {
    if (!props.purchaseSummary?.items[0]) return;
    router.patch(route('purchases.mark-as-paid', props.purchaseSummary.items[0].id), {
        amount: paymentAmount.value,
        month: props.month,
        year: props.year,
    }, {
        onSuccess: close,
    });
}

function unmarkAsPaid(): void {
    if (!props.purchaseSummary?.items[0]) return;
    router.patch(route('purchases.unmark-as-paid', props.purchaseSummary.items[0].id), {
        month: props.month,
        year: props.year,
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

            <div v-if="purchaseSummary" class="space-y-3">
                <CardComponent
                    v-for="purchase in purchaseSummary.items"
                    :key="purchase.id"
                >
                    <CardContent class="p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <div class="font-medium truncate">{{ purchase.name }}</div>
                                <div class="text-sm text-muted-foreground">
                                    {{ typeLabels[purchase.type] ?? purchase.type }}
                                    <span v-if="purchase.installments_total">
                                        · {{ purchase.installments_total }}x
                                    </span>
                                </div>
                                <div v-if="purchase.notes" class="mt-0.5 text-xs text-muted-foreground truncate">
                                    {{ purchase.notes }}
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="font-semibold">{{ formatCurrency(purchase.amount) }}</div>
                                <div class="text-sm text-muted-foreground">
                                    {{ formatDate(purchase.start_date) }}
                                </div>
                                <div class="mt-1 flex items-center justify-end gap-1">
                                    <Button variant="ghost" size="icon" class="size-7 cursor-pointer" @click="openEdit(purchase)">
                                        <Pencil class="size-3.5" />
                                    </Button>
                                    <Button variant="ghost" size="icon" class="size-7 text-destructive cursor-pointer" @click="confirmDelete(purchase)">
                                        <Trash2 class="size-3.5" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </CardComponent>

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

    <PurchaseFormModal
        v-if="editingPurchase"
        v-model:open="showEditModal"
        :purchase="editingPurchase"
        :cards="cards"
        @update:open="closeEdit"
    />

    <ConfirmDialog
        v-model:open="showDeleteDialog"
        title="Excluir compra"
        description="Tem certeza que deseja excluir esta compra? Esta ação não pode ser desfeita."
        confirm-text="Excluir"
        @confirm="deletePurchase"
    />
</template>
