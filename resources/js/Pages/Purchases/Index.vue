<script setup lang="ts">
import { computed } from 'vue';
import type { Purchase, PurchaseSummaryItem } from '@/types/purchase';
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ChevronLeft, ChevronRight, Plus } from '@lucide/vue';
import PurchaseSummary from './Partials/PurchaseSummary.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    purchases: Purchase[];
    summary: PurchaseSummaryItem[];
    month: number;
    year: number;
}>();

const monthNames = [
    'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
];

const currentMonthName = computed(() => monthNames[props.month - 1]);

const totalAmount = computed(() => props.summary.reduce((sum, item) => sum + item.total, 0));

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
            <Button @click="router.get(route('purchases.store'))">
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

        <Card>
            <CardHeader>
                <CardTitle>Total do Mês</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="text-3xl font-bold">{{ formatCurrency(totalAmount) }}</div>
            </CardContent>
        </Card>

        <PurchaseSummary :items="summary" />
    </div>
</template>
