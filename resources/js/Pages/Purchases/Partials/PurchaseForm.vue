<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import type { Purchase, PurchaseFormData } from '@/types/purchase';
import type { Card } from '@/types/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import Toggle from '@/Components/Toggle.vue';
import Checkbox from '@/Components/Checkbox.vue';
import CurrencyInput from '@/Components/CurrencyInput.vue';

const props = defineProps<{
    purchase?: Purchase;
    cards: Card[];
    defaultCardId?: number | null;
}>();

const emit = defineEmits<{
    success: [];
}>();

const form = useForm<PurchaseFormData>({
    name: props.purchase?.name ?? '',
    type: props.purchase?.type ?? (props.defaultCardId ? 'credit_card' : ''),
    payment_day: props.purchase?.payment_day ?? 15,
    is_recurring: props.purchase?.is_recurring ?? false,
    card_id: props.purchase?.card_id ?? props.defaultCardId ?? null,
    amount: props.purchase?.amount ?? 0,
    installments_total: props.purchase?.installments_total ?? null,
    start_date: props.purchase?.start_date
        ? (props.purchase.start_date.includes('T') ? props.purchase.start_date.split('T')[0] : props.purchase.start_date.split(' ')[0])
        : new Date().toISOString().split('T')[0],
    notes: props.purchase?.notes ?? '',
    mark_as_paid: false,
    notify_due: false,
});

const isCreditCard = computed(() => form.type === 'credit_card');
const showNotifyDue = computed(() => form.type === 'bill' || form.type === 'financing');

const installmentValue = computed(() => {
    if (!isCreditCard.value || !form.installments_total || form.installments_total <= 0 || !form.amount) {
        return null;
    }
    return form.amount / form.installments_total;
});

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
}

watch(() => form.type, (newType) => {
    if (newType === 'credit_card') {
        form.payment_day = null;
    } else {
        form.card_id = null;
    }
    if (newType === 'bill' || newType === 'financing') {
        if (!props.purchase) form.notify_due = true;
    }
});

function submit(): void {
    if (props.purchase) {
        form.put(route('purchases.update', props.purchase.id), {
            onSuccess: () => emit('success'),
        });
    } else {
        form.post(route('purchases.store'), {
            onSuccess: () => {
                form.reset();
                emit('success');
            },
        });
    }
}
</script>

<template>
    <form @submit.prevent="submit" class="space-y-4">
        <div class="space-y-2">
            <Label for="name">Nome</Label>
            <Input id="name" v-model="form.name" placeholder="Ex: Netflix" />
            <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
        </div>

        <div class="space-y-2">
            <Label for="type">Tipo</Label>
            <Select v-model="form.type">
                <SelectTrigger class="w-full">
                    <SelectValue placeholder="Selecione o tipo" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="credit_card">Compra no cartão</SelectItem>
                    <SelectItem value="bill">Conta mensal</SelectItem>
                    <SelectItem value="financing">Financiamento</SelectItem>
                    <SelectItem value="others">Outros</SelectItem>
                </SelectContent>
            </Select>
            <p v-if="form.errors.type" class="text-sm text-destructive">{{ form.errors.type }}</p>
        </div>

        <div v-if="isCreditCard" class="space-y-2">
            <Label for="card_id">Cartão</Label>
            <Select v-model="form.card_id">
                <SelectTrigger class="w-full">
                    <SelectValue placeholder="Selecione o cartão" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="card in cards" :key="card.id" :value="card.id">
                        {{ card.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <p v-if="form.errors.card_id" class="text-sm text-destructive">{{ form.errors.card_id }}</p>
        </div>

        <div v-if="!isCreditCard" class="space-y-2">
            <Label for="payment_day">Dia de pagamento</Label>
            <Input id="payment_day" v-model="form.payment_day" type="number" min="1" max="31" />
            <p v-if="form.errors.payment_day" class="text-sm text-destructive">{{ form.errors.payment_day }}</p>
        </div>

        <div class="space-y-2">
            <Label for="amount">Valor</Label>
            <CurrencyInput id="amount" v-model="form.amount" />
            <p v-if="form.errors.amount" class="text-sm text-destructive">{{ form.errors.amount }}</p>
            <p v-if="installmentValue !== null" class="text-sm text-muted-foreground">
                Parcela: <span class="font-medium">{{ formatCurrency(installmentValue) }}</span> ({{ form.installments_total }}x)
            </p>
        </div>

        <div class="space-y-2">
            <Label for="start_date">Data de início</Label>
            <Input id="start_date" v-model="form.start_date" type="date" />
            <p v-if="form.errors.start_date" class="text-sm text-destructive">{{ form.errors.start_date }}</p>
        </div>

        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <Label for="is_recurring">Recorrente</Label>
                <Toggle
                    id="is_recurring"
                    :checked="form.is_recurring"
                    @update:checked="form.is_recurring = $event"
                />
            </div>
        </div>

        <div v-if="!form.is_recurring" class="space-y-2">
            <Label for="installments_total">Total de parcelas</Label>
            <Input
                id="installments_total"
                v-model="form.installments_total"
                type="number"
                min="1"
                placeholder="Deixe vazio para à vista"
            />
            <p v-if="form.errors.installments_total" class="text-sm text-destructive">{{ form.errors.installments_total }}</p>
        </div>

        <div v-if="showNotifyDue" class="flex items-center justify-between">
            <Label>Notificar vencimento</Label>
            <Toggle
                :checked="form.notify_due"
                @update:checked="form.notify_due = $event"
            />
        </div>

        <div class="space-y-2">
            <Label for="notes">Observações</Label>
            <Input id="notes" v-model="form.notes" placeholder="Opcional" />
        </div>

        <div v-if="!isCreditCard && !form.is_recurring" class="flex items-center gap-2">
            <Checkbox
                :checked="form.mark_as_paid"
                @update:checked="form.mark_as_paid = $event"
            />
            <Label class="cursor-pointer" @click="form.mark_as_paid = !form.mark_as_paid">Marcar como pago</Label>
        </div>

        <Button type="submit" class="w-full" :disabled="form.processing">
            {{ form.processing ? 'Salvando...' : 'Salvar' }}
        </Button>
    </form>
</template>
