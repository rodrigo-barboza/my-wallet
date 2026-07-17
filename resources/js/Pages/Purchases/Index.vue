<script setup lang="ts">
import { computed, ref } from 'vue';
import type { Purchase, PurchaseSummaryItem } from '@/types/purchase';
import type { Card as CardType } from '@/types/card';
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ChevronLeft, ChevronRight, Plus } from '@lucide/vue';
import PurchaseSummary from './Partials/PurchaseSummary.vue';
import PurchaseFormModal from '@/Components/PurchaseFormModal.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    purchases: Purchase[];
    summary: PurchaseSummaryItem[];
    month: number;
    year: number;
    cards: CardType[];
}>();

const showFormModal = ref(false);

const monthNames = [
    'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
];

const currentMonthName = computed(() => monthNames[props.month - 1]);

const totalAmount = computed(() => props.summary.reduce((sum, item) => sum + parseFloat(String(item.total)), 0));

const paidAmount = computed(() => props.summary
    .filter((item) => item.status === 'paga')
    .reduce((sum, item) => sum + parseFloat(String(item.total)), 0));

const pendingAmount = computed(() => totalAmount.value - paidAmount.value);

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
}

function previousMonth(): void {
    const newMonth = props.month === 1 ? 12 : props.month - 1;
    const newYear = props.month === 1 ? props.year - 1 : props.year;
    router.get(route('purchases.index', { month: newMonth, year: newYear }));
}

function nextMonth(): void {
    const newMonth = props.month === 12 ? 1 : props.month + 1;
    const newYear = props.month === 12 ? props.year + 1 : props.year;
    router.get(route('purchases.index', { month: newMonth, year: newYear }));
}
</script>

<template>
    <div class="w-full space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold">Compras</h2>
            <Button @click="showFormModal = true">
                <Plus class="mr-2 size-4" />
                Nova compra
            </Button>
        </div>

        <div class="flex items-center justify-center gap-4">
            <Button variant="outline" size="icon" @click="previousMonth">
                <ChevronLeft class="size-4" />
            </Button>
            <div class="text-lg font-medium">
                {{ currentMonthName }} {{ year }}
            </div>
            <Button variant="outline" size="icon" @click="nextMonth">
                <ChevronRight class="size-4" />
            </Button>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">Total do Mês</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ formatCurrency(totalAmount) }}</div>
                </CardContent>
            </Card>
            <Card class="border-green-200 bg-green-50/50">
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-green-700">Pago</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-green-700">{{ formatCurrency(paidAmount) }}</div>
                </CardContent>
            </Card>
            <Card class="border-red-200 bg-red-50/50">
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-red-700">Falta</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-red-700">{{ formatCurrency(pendingAmount) }}</div>
                </CardContent>
            </Card>
        </div>

        <PurchaseSummary :items="summary" />

        <PurchaseFormModal
            v-model:open="showFormModal"
            :cards="cards"
        />
    </div>
</template>
