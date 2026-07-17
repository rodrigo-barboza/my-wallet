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

        <Card>
            <CardContent class="flex flex-wrap items-center justify-between gap-4 p-5">
                <div>
                    <div class="text-sm text-muted-foreground">Total do Mês</div>
                    <div class="text-2xl font-bold">{{ formatCurrency(totalAmount) }}</div>
                </div>
                <div class="flex flex-wrap items-center gap-4">
                    <div class="text-right">
                        <div class="text-sm text-muted-foreground">Pago</div>
                        <div class="text-lg font-semibold text-green-600">{{ formatCurrency(paidAmount) }}</div>
                    </div>
                    <div class="h-8 w-px bg-border" />
                    <div class="text-right">
                        <div class="text-sm text-muted-foreground">Falta</div>
                        <div class="text-lg font-semibold text-destructive">{{ formatCurrency(pendingAmount) }}</div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <PurchaseSummary :items="summary" />

        <PurchaseFormModal
            v-model:open="showFormModal"
            :cards="cards"
        />
    </div>
</template>
