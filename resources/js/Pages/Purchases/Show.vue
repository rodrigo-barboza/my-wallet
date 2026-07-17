<script setup lang="ts">
import type { Purchase } from '@/types/purchase';
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, Pencil, Trash2 } from '@lucide/vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { ref } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    purchase: Purchase;
}>();

const showDeleteDialog = ref(false);

const typeLabels: Record<string, string> = {
    subscription: 'Assinatura',
    credit_card: 'Compra no cartão',
    bill: 'Conta mensal',
    financing: 'Financiamento',
    person: 'Pagamento para pessoa',
};

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
}

function formatDate(value: string): string {
    return new Date(value + 'T00:00:00').toLocaleDateString('pt-BR');
}

function deletePurchase(): void {
    router.delete(route('purchases.destroy', props.purchase.id), {
        onSuccess: () => router.get(route('purchases.index')),
    });
}
</script>

<template>
    <div class="mx-auto w-full max-w-2xl space-y-6">
        <div class="flex items-center gap-4">
            <Button variant="ghost" size="icon" @click="router.get(route('purchases.index'))">
                <ArrowLeft class="size-4" />
            </Button>
            <h2 class="text-2xl font-bold">Detalhes da Compra</h2>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>{{ purchase.name }}</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-sm text-muted-foreground">Tipo</div>
                        <div class="font-medium">{{ typeLabels[purchase.type] ?? purchase.type }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-muted-foreground">Valor</div>
                        <div class="font-medium">{{ formatCurrency(purchase.amount) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-muted-foreground">Data de início</div>
                        <div class="font-medium">{{ formatDate(purchase.start_date) }}</div>
                    </div>
                    <div v-if="purchase.payment_day">
                        <div class="text-sm text-muted-foreground">Dia de pagamento</div>
                        <div class="font-medium">Dia {{ purchase.payment_day }}</div>
                    </div>
                    <div v-if="purchase.card">
                        <div class="text-sm text-muted-foreground">Cartão</div>
                        <div class="font-medium">{{ purchase.card.name }}</div>
                    </div>
                    <div v-if="purchase.installments_total">
                        <div class="text-sm text-muted-foreground">Parcelas</div>
                        <div class="font-medium">{{ purchase.installments_total }}x</div>
                    </div>
                    <div>
                        <div class="text-sm text-muted-foreground">Recorrente</div>
                        <div class="font-medium">{{ purchase.is_recurring ? 'Sim' : 'Não' }}</div>
                    </div>
                </div>

                <div v-if="purchase.notes">
                    <div class="text-sm text-muted-foreground">Observações</div>
                    <div class="font-medium">{{ purchase.notes }}</div>
                </div>

                <div class="flex gap-4 pt-4">
                    <Button variant="outline" class="flex-1" @click="showDeleteDialog = true">
                        <Trash2 class="mr-2 size-4" />
                        Excluir
                    </Button>
                </div>
            </CardContent>
        </Card>

        <ConfirmDialog
            v-model:open="showDeleteDialog"
            title="Excluir compra"
            description="Tem certeza que deseja excluir esta compra? Esta ação não pode ser desfeita."
            confirm-text="Excluir"
            @confirm="deletePurchase"
        />
    </div>
</template>
