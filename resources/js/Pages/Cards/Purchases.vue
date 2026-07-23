<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import type { Purchase } from '@/types/purchase';
import type { Card } from '@/types/card';
import AppLayout from '@/Layouts/AppLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card as CardComponent, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { CreditCard, ChevronLeft, ChevronRight, Plus, Pencil, Trash2, ArrowLeft } from '@lucide/vue';
import PurchaseFormModal from '@/Components/PurchaseFormModal.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    card: Card;
    purchases: Purchase[];
    monthlyTotals: { month: number; year: number; total: number }[];
    month: number;
    year: number;
    cards: Card[];
}>();

const showFormModal = ref(false);
const editingPurchase = ref<Purchase | undefined>();

const showDeleteDialog = ref(false);
const deletingPurchase = ref<Purchase | undefined>();

type SortKey = 'name' | 'amount' | 'installment_value' | 'start_date';

const page = usePage();
const initialPrefs = (page.props.preferences as Record<string, any>) ?? {};
const storedSort = initialPrefs.card_purchases_table_sort ?? null;
const sortKey = ref<SortKey>(storedSort?.key ?? 'start_date');
const sortDir = ref<'asc' | 'desc'>(storedSort?.dir ?? 'asc');

watch([sortKey, sortDir], ([key, dir]) => {
    fetch(route('preferences.update'), {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            key: 'card_purchases_table_sort',
            value: { key, dir },
        }),
    });
}, { deep: true });

const monthNames = [
    'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
];

const monthAbbrs = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

const currentMonthName = computed(() => monthNames[props.month - 1]);

const totalAmount = computed(() =>
    props.purchases.reduce((sum, p) => {
        const value = p.installments_total
            ? parseFloat(String(p.amount)) / p.installments_total
            : parseFloat(String(p.amount));
        return sum + value;
    }, 0)
);

const maxMonthlyTotal = computed(() =>
    Math.max(...visibleTotals.value.map(m => m.total), 1)
);

const isMobile = ref(typeof window !== 'undefined' && window.innerWidth < 768);

if (typeof window !== 'undefined') {
    window.addEventListener('resize', () => {
        isMobile.value = window.innerWidth < 768;
    });
}

const visibleTotals = computed(() => {
    if (isMobile.value && props.monthlyTotals.length >= 5) {
        return props.monthlyTotals.slice(1, 6);
    }
    return props.monthlyTotals;
});

const maxBarHeight = 100;

function installmentValue(purchase: Purchase): number {
    if (!purchase.installments_total || purchase.installments_total === 0) return purchase.amount;
    return purchase.amount / purchase.installments_total;
}

const sortedPurchases = computed(() => {
    return [...props.purchases].sort((a, b) => {
        let cmp = 0;

        if (sortKey.value === 'name') {
            cmp = a.name.localeCompare(b.name);
        } else if (sortKey.value === 'amount') {
            cmp = a.amount - b.amount;
        } else if (sortKey.value === 'installment_value') {
            cmp = installmentValue(a) - installmentValue(b);
        } else if (sortKey.value === 'start_date') {
            const dateA = a.start_date.includes('T') ? a.start_date : a.start_date.split(' ')[0] + 'T00:00:00';
            const dateB = b.start_date.includes('T') ? b.start_date : b.start_date.split(' ')[0] + 'T00:00:00';
            cmp = new Date(dateA).getTime() - new Date(dateB).getTime();
        }

        return sortDir.value === 'asc' ? cmp : -cmp;
    });
});

function toggleSort(key: SortKey): void {
    if (sortKey.value === key) {
        if (sortDir.value === 'asc') {
            sortDir.value = 'desc';
        } else {
            sortKey.value = 'start_date';
            sortDir.value = 'asc';
        }
    } else {
        sortKey.value = key;
        sortDir.value = 'asc';
    }
}

function sortIcon(key: SortKey): string {
    if (sortKey.value !== key) return ' ⇅';
    return sortDir.value === 'asc' ? ' ▲' : ' ▼';
}

function barHeight(total: number): number {
    if (maxMonthlyTotal.value === 0) return 2;
    const h = Math.round((total / maxMonthlyTotal.value) * maxBarHeight);
    return Math.max(h, 2);
}

function isCurrentMonth(m: { month: number; year: number }): boolean {
    return m.month === props.month && m.year === props.year;
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

function currentInstallment(purchase: Purchase): string {
    if (purchase.is_recurring) return 'Recorrente';
    if (!purchase.installments_total) return 'À vista';
    const [startYear, startMonth] = purchase.start_date.split(/[-/]/).map(Number);
    const monthsDiff = (props.year - startYear) * 12 + (props.month - startMonth);
    return `${monthsDiff + 1} de ${purchase.installments_total}`;
}

function openNewPurchase(): void {
    editingPurchase.value = undefined;
    showFormModal.value = true;
}

function openEdit(purchase: Purchase): void {
    editingPurchase.value = purchase;
    showFormModal.value = true;
}

function closeForm(): void {
    showFormModal.value = false;
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
        },
    });
}

function previousMonth(): void {
    const newMonth = props.month === 1 ? 12 : props.month - 1;
    const newYear = props.month === 1 ? props.year - 1 : props.year;
    router.get(route('cards.purchases', { card: props.card.id, month: newMonth, year: newYear }));
}

function nextMonth(): void {
    const newMonth = props.month === 12 ? 1 : props.month + 1;
    const newYear = props.month === 12 ? props.year + 1 : props.year;
    router.get(route('cards.purchases', { card: props.card.id, month: newMonth, year: newYear }));
}

function goBack(): void {
    router.visit(route('purchases.index', { month: props.month, year: props.year }));
}

function goToMonth(month: number, year?: number): void {
    router.get(route('cards.purchases', { card: props.card.id, month, year: year ?? props.year }));
}
</script>

<template>
    <div class="w-full space-y-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <Button variant="ghost" size="icon" @click="goBack">
                    <ArrowLeft class="size-5" />
                </Button>
                <h2 class="text-2xl font-bold">{{ card.name }}</h2>
                <CreditCard class="size-6" :style="{ color: card.color }" />
            </div>
            <Button @click="openNewPurchase">
                <Plus class="mr-2 size-4" />
                Nova compra
            </Button>
        </div>

        <CardComponent class="overflow-hidden">
            <CardContent class="p-4 pb-3">
                <div class="flex items-end justify-between gap-1" style="height: 140px">
                    <div
                        v-for="m in visibleTotals"
                        :key="`${m.month}-${m.year}`"
                        class="flex flex-1 flex-col items-center cursor-pointer self-stretch min-h-0 transition-all hover:opacity-80"
                        @click="goToMonth(m.month, m.year)"
                    >
                        <div class="flex-1 w-full flex flex-col justify-end min-h-0">
                            <span
                                class="text-[10px] leading-none tabular-nums text-center"
                                :class="isCurrentMonth(m) ? 'font-semibold text-foreground' : 'text-muted-foreground'"
                            >
                                {{ m.total > 0 ? formatCurrency(m.total) : '' }}
                            </span>
                            <div
                                class="w-full rounded-t transition-all mt-0.5"
                                :style="{
                                    height: barHeight(m.total) + 'px',
                                    backgroundColor: card.color,
                                    opacity: isCurrentMonth(m) ? 1 : 0.3,
                                }"
                            />
                        </div>
                        <span
                            class="text-[10px] mt-0.5"
                            :class="isCurrentMonth(m) ? 'font-semibold text-foreground' : 'text-muted-foreground'"
                        >
                            {{ monthAbbrs[m.month - 1] }}<span v-if="m.year !== year" class="text-[8px]"> {{ m.year }}</span>
                        </span>
                    </div>
                </div>
            </CardContent>
        </CardComponent>

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

        <div v-if="purchases.length > 0" class="text-right text-sm text-muted-foreground">
            Total: <span class="font-semibold">{{ formatCurrency(totalAmount) }}</span>
        </div>

        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-10">#</TableHead>
                        <TableHead class="cursor-pointer select-none" @click="toggleSort('name')">
                            Nome<span class="text-muted-foreground">{{ sortIcon('name') }}</span>
                        </TableHead>
                        <TableHead class="cursor-pointer select-none" @click="toggleSort('amount')">
                            Valor<span class="text-muted-foreground">{{ sortIcon('amount') }}</span>
                        </TableHead>
                        <TableHead class="hidden sm:table-cell cursor-pointer select-none" @click="toggleSort('installment_value')">
                            Valor parcela<span class="text-muted-foreground">{{ sortIcon('installment_value') }}</span>
                        </TableHead>
                        <TableHead class="hidden sm:table-cell cursor-pointer select-none" @click="toggleSort('start_date')">
                            Data<span class="text-muted-foreground">{{ sortIcon('start_date') }}</span>
                        </TableHead>
                        <TableHead class="hidden sm:table-cell">Parcela</TableHead>
                        <TableHead class="text-right">Ações</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="(purchase, index) in sortedPurchases"
                        :key="purchase.id"
                    >
                        <TableCell class="py-2.5 text-muted-foreground text-xs tabular-nums">
                            {{ index + 1 }}
                        </TableCell>
                        <TableCell class="py-2.5 font-medium">
                            <div class="flex items-center gap-1.5">
                                {{ purchase.name }}
                            </div>
                        </TableCell>
                        <TableCell class="py-2.5 font-medium">
                            {{ formatCurrency(purchase.amount) }}
                        </TableCell>
                        <TableCell class="py-2.5 text-muted-foreground hidden sm:table-cell">
                            {{ purchase.installments_total ? formatCurrency(installmentValue(purchase)) : '-' }}
                        </TableCell>
                        <TableCell class="py-2.5 text-muted-foreground hidden sm:table-cell">
                            {{ formatDate(purchase.start_date) }}
                        </TableCell>
                        <TableCell class="py-2.5 text-muted-foreground hidden sm:table-cell">
                            {{ currentInstallment(purchase) }}
                        </TableCell>
                        <TableCell class="py-2.5 text-right">
                            <Button variant="ghost" size="icon" class="size-7 cursor-pointer" @click="openEdit(purchase)">
                                <Pencil class="size-3.5" />
                            </Button>
                            <Button variant="ghost" size="icon" class="size-7 text-destructive cursor-pointer" @click="confirmDelete(purchase)">
                                <Trash2 class="size-3.5" />
                            </Button>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="purchases.length === 0">
                        <TableCell colspan="7" class="h-24 text-center text-muted-foreground">
                            Nenhuma compra neste mês
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <PurchaseFormModal
            :open="showFormModal"
            :purchase="editingPurchase"
            :cards="cards"
            :default-card-id="card.id"
            @update:open="closeForm"
        />

        <ConfirmDialog
            v-model:open="showDeleteDialog"
            title="Excluir compra"
            description="Tem certeza que deseja excluir esta compra? Esta ação não pode ser desfeita."
            confirm-text="Excluir"
            @confirm="deletePurchase"
        />
    </div>
</template>
