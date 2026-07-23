<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import type { Purchase, PurchaseSummaryItem } from '@/types/purchase';
import type { Card as CardType } from '@/types/card';
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ChevronLeft, ChevronRight, LayoutList, Table as TableIcon, Plus, Receipt } from '@lucide/vue';
import PurchaseSummary from './Partials/PurchaseSummary.vue';
import PurchasesTableMode from './Partials/PurchasesTableMode.vue';
import PaymentHistory from './Partials/PaymentHistory.vue';
import PurchaseFormModal from '@/Components/PurchaseFormModal.vue';
import PurchaseDetailsModal from '@/Components/PurchaseDetailsModal.vue';
import CardPurchaseDetailsModal from '@/Components/CardPurchaseDetailsModal.vue';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

interface PaymentHistoryItem {
    id: number;
    name: string;
    amount: number;
    paid_at: string;
    type: string;
    partial?: boolean;
}

defineOptions({ layout: AppLayout });

const props = defineProps<{
    purchases: Purchase[];
    summary: PurchaseSummaryItem[];
    paymentHistory: PaymentHistoryItem[];
    month: number;
    year: number;
    cards: CardType[];
}>();

const storedViewMode = localStorage.getItem('purchases_view_mode') as 'card' | 'table' | null;
const viewMode = ref<'card' | 'table'>(storedViewMode ?? 'card');
watch(viewMode, (mode) => localStorage.setItem('purchases_view_mode', mode));

const activeTab = ref<'compras' | 'pagamentos'>('compras');

const showFormModal = ref(false);

const selectedPurchase = ref<Purchase | undefined>();
const showDetailsModal = ref(false);
const editingPurchase = ref<Purchase | undefined>();
const selectedCardPurchase = ref<PurchaseSummaryItem | undefined>();
const showCardDetailsModal = ref(false);

const monthNames = [
    'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
];

const currentMonthName = computed(() => monthNames[props.month - 1]);

const totalAmount = computed(() => props.summary.reduce((sum, item) => {
    return sum + parseFloat(String(item.total));
}, 0));

const paidAmount = computed(() => props.summary.reduce((sum, item) => {
    const total = parseFloat(String(item.total));
    if (item.paid_amount) {
        return sum + Math.min(parseFloat(String(item.paid_amount)), total);
    }
    if (item.status === 'paga') {
        return sum + total;
    }
    return sum;
}, 0));

const pendingAmount = computed(() => {
    const pending = totalAmount.value - paidAmount.value;
    return Math.abs(pending) < 0.01 ? 0 : pending;
});

const hasOverdue = computed(() => props.summary.some((item) => item.status === 'atrasada'));

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
}

function onTableSelect(item: PurchaseSummaryItem): void {
    selectedPurchase.value = {
        ...item.items[0],
        status: item.status ?? 'aberta',
        paid_at: item.paid_at,
    };
    showDetailsModal.value = true;
}

function onTableCardSelect(item: PurchaseSummaryItem): void {
    selectedCardPurchase.value = item;
    showCardDetailsModal.value = true;
}

function onEditPurchase(purchase: Purchase): void {
    editingPurchase.value = purchase;
    showFormModal.value = true;
}

function onCloseForm(open: boolean): void {
    showFormModal.value = open;
    if (!open) editingPurchase.value = undefined;
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

async function handleReorder(order: string[]): Promise<void> {
    try {
        await fetch(route('purchases.reorder'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ order }),
        });
    } catch {
        // Silently fail — não afeta a UI
    }
}
</script>

<template>
    <div class="w-full space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold">Compras</h2>
            <div class="flex items-center gap-2">
                <template v-if="activeTab === 'compras'">
                    <TooltipProvider>
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button variant="outline" size="icon"
                                    :class="{ 'bg-primary text-primary-foreground': viewMode === 'card' }"
                                    @click="viewMode = 'card'">
                                    <LayoutList class="size-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Visualização em cards</TooltipContent>
                        </Tooltip>
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button variant="outline" size="icon"
                                    :class="{ 'bg-primary text-primary-foreground': viewMode === 'table' }"
                                    @click="viewMode = 'table'">
                                    <TableIcon class="size-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>Visualização em tabela</TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </template>
                <Button @click="showFormModal = true">
                    <Plus class="mr-2 size-4" />
                    Nova compra
                </Button>
            </div>
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

        <div class="flex justify-center">
            <div class="flex items-center gap-1 rounded-lg bg-muted p-1">
                <button
                    class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors cursor-pointer"
                    :class="activeTab === 'compras' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                    @click="activeTab = 'compras'"
                >
                    Visão geral
                </button>
                <button
                    class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors flex items-center gap-1.5 cursor-pointer"
                    :class="activeTab === 'pagamentos' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                    @click="activeTab = 'pagamentos'"
                >
                    <Receipt class="size-3.5" />
                    Pagamentos
                </button>
            </div>
        </div>

        <Card>
            <CardHeader class="pb-2">
                <CardTitle class="text-sm font-semibold text-muted-foreground">Total do Mês</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="text-3xl font-bold">{{ formatCurrency(totalAmount) }}</div>
                <div class="space-y-2">
                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-muted">
                        <div
                            class="h-full rounded-full bg-green-500 transition-all"
                            :style="{ width: totalAmount > 0 ? `${(paidAmount / totalAmount) * 100}%` : '0%' }"
                        />
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">
                            <span class="font-semibold text-green-600">{{ formatCurrency(paidAmount) }}</span> pago
                        </span>
                        <span class="text-muted-foreground">
                            <template v-if="pendingAmount > 0">
                                <span class="font-semibold" :class="hasOverdue ? 'text-destructive' : 'text-amber-500'">Faltam {{ formatCurrency(pendingAmount) }}</span>
                            </template>
                            <span v-else class="font-semibold text-green-600">Tudo pago</span>
                        </span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <template v-if="activeTab === 'compras'">
            <PurchaseSummary
                v-if="viewMode === 'card'"
                :items="summary"
                :month="month"
                :year="year"
                @reorder="handleReorder"
                @edit-purchase="onEditPurchase"
            />

            <PurchasesTableMode
                v-else
                :items="summary"
                :month="month"
                :year="year"
                @select="onTableSelect"
                @card-select="onTableCardSelect"
            />
        </template>

        <PaymentHistory
            v-else
            :items="paymentHistory"
        />

        <PurchaseDetailsModal v-model:open="showDetailsModal" :purchase="selectedPurchase" :month="month" :year="year"
            @edit="onEditPurchase" />

        <CardPurchaseDetailsModal v-model:open="showCardDetailsModal" :purchase-summary="selectedCardPurchase" :month="month" :year="year" context="purchases" />

        <PurchaseFormModal
            :open="showFormModal"
            :purchase="editingPurchase"
            :cards="cards"
            @update:open="onCloseForm"
        />
    </div>
</template>
