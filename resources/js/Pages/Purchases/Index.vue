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
            <CardContent class="flex flex-col items-center gap-4 p-6">
                <div class="relative size-28">
                    <svg class="size-28 -rotate-90" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="52" fill="none" stroke="hsl(var(--muted))" stroke-width="10" />
                        <circle
                            cx="60" cy="60" r="52"
                            fill="none"
                            stroke="hsl(142, 71%, 45%)"
                            stroke-width="10"
                            stroke-linecap="round"
                            stroke-dasharray="326.73"
                            :stroke-dashoffset="totalAmount > 0 ? 326.73 - (326.73 * paidAmount / totalAmount) : 326.73"
                        />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="text-center">
                            <div class="text-lg font-bold">
                                {{ totalAmount > 0 ? Math.round((paidAmount / totalAmount) * 100) : 0 }}%
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-2xl font-bold">{{ formatCurrency(totalAmount) }}</div>
                <div class="flex items-center gap-6 text-sm">
                    <div class="text-center">
                        <div class="font-medium text-green-600">{{ formatCurrency(paidAmount) }}</div>
                        <div class="text-muted-foreground">Pago</div>
                    </div>
                    <div class="text-center">
                        <div class="font-medium text-destructive">{{ formatCurrency(pendingAmount) }}</div>
                        <div class="text-muted-foreground">Falta</div>
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
