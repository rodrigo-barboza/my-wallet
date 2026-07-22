<script setup lang="ts">
import type { Purchase } from '@/types/purchase';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Trash2, Check, Undo2 } from '@lucide/vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps<{
    open: boolean;
    purchase?: Purchase;
    month: number;
    year: number;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    edit: [purchase: Purchase];
}>();

const showDeleteDialog = ref(false);

const typeLabels: Record<string, string> = {
    credit_card: 'Compra no cartão',
    bill: 'Conta mensal',
    financing: 'Financiamento',
    others: 'Outros',
};

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

function close(): void {
    emit('update:open', false);
}

function deletePurchase(): void {
    if (!props.purchase) return;
    router.delete(route('purchases.destroy', props.purchase.id), {
        onSuccess: close,
    });
}

function markAsPaid(): void {
    if (!props.purchase) return;
    router.patch(route('purchases.mark-as-paid', props.purchase.id), {
        month: props.month,
        year: props.year,
    }, {
        onSuccess: close,
    });
}

function unmarkAsPaid(): void {
    if (!props.purchase) return;
    router.patch(route('purchases.unmark-as-paid', props.purchase.id), {
        month: props.month,
        year: props.year,
    }, {
        onSuccess: close,
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <div class="flex items-center gap-2">
                    <DialogTitle>{{ purchase?.name }}</DialogTitle>
                    <StatusBadge v-if="purchase?.status" :status="purchase.status" />
                </div>
                <DialogDescription>Detalhes da compra</DialogDescription>
            </DialogHeader>

            <div v-if="purchase" class="space-y-4">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="text-muted-foreground">Tipo</div>
                        <div class="font-medium">{{ typeLabels[purchase.type] ?? purchase.type }}</div>
                    </div>
                    <div>
                        <div class="text-muted-foreground">Valor</div>
                        <div class="font-medium">{{ formatCurrency(purchase.amount) }}</div>
                    </div>
                    <div>
                        <div class="text-muted-foreground">Data de início</div>
                        <div class="font-medium">{{ formatDate(purchase.start_date) }}</div>
                    </div>
                    <div v-if="purchase.payment_day">
                        <div class="text-muted-foreground">Dia de pagamento</div>
                        <div class="font-medium">Dia {{ purchase.payment_day }}</div>
                    </div>
                    <div v-if="purchase.card">
                        <div class="text-muted-foreground">Cartão</div>
                        <div class="font-medium">{{ purchase.card.name }}</div>
                    </div>
                    <div v-if="purchase.installments_total">
                        <div class="text-muted-foreground">Parcelas</div>
                        <div class="font-medium">{{ purchase.installments_total }}x</div>
                    </div>
                    <div>
                        <div class="text-muted-foreground">Recorrente</div>
                        <div class="font-medium">{{ purchase.is_recurring ? 'Sim' : 'Não' }}</div>
                    </div>
                    <div v-if="purchase.paid_at">
                        <div class="text-muted-foreground">Pago em</div>
                        <div class="font-medium">{{ formatDateTime(purchase.paid_at) }}</div>
                    </div>
                </div>

                <div v-if="purchase.notes">
                    <div class="text-sm text-muted-foreground">Observações</div>
                    <div class="text-sm font-medium">{{ purchase.notes }}</div>
                </div>

                <div class="space-y-1">
                <Button
                    v-if="purchase?.status !== 'paga'"
                    variant="outline"
                    class="w-full cursor-pointer"
                    @click="markAsPaid"
                >
                    <Check class="mr-2 size-4" />
                    Marcar como pago
                </Button>

                <Button
                    v-if="purchase?.status === 'paga'"
                    variant="outline"
                    class="w-full cursor-pointer"
                    @click="unmarkAsPaid"
                >
                    <Undo2 class="mr-2 size-4" />
                    Desmarcar pagamento
                </Button>

                <Button
                    v-if="!purchase.card_id"
                    variant="outline"
                    class="w-full cursor-pointer"
                    @click="emit('edit', purchase); close()"
                >
                    Editar
                </Button>

                <Button variant="destructive" class="w-full cursor-pointer" @click="showDeleteDialog = true">
                    <Trash2 class="mr-2 size-4" />
                    Excluir
                </Button>
            </div>
        </div>
        </DialogContent>
    </Dialog>

    <ConfirmDialog
        v-model:open="showDeleteDialog"
        title="Excluir compra"
        description="Tem certeza que deseja excluir esta compra? Esta ação não pode ser desfeita."
        confirm-text="Excluir"
        @confirm="deletePurchase"
    />
</template>
