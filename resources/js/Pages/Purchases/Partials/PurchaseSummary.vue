<script setup lang="ts">
import { ref } from 'vue';
import type { Purchase, PurchaseSummaryItem } from '@/types/purchase';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { ChevronRight } from '@lucide/vue';
import PurchaseDetailsModal from '@/Components/PurchaseDetailsModal.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

defineProps<{
    items: PurchaseSummaryItem[];
}>();

const selectedPurchase = ref<Purchase | undefined>();
const showDetailsModal = ref(false);

function openDetails(item: PurchaseSummaryItem): void {
    if (item.items[0].card_id) return;
    selectedPurchase.value = item.items[0];
    showDetailsModal.value = true;
}

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
}

function formatDate(value: number): string {
    return `Dia ${value}`;
}

function formatDateRange(closing: number, due: number): string {
    return `Fech: ${closing} / Venc: ${due}`;
}

function toTitleCase(str: string): string {
    return str.replace(/\b\w/g, (char) => char.toUpperCase());
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Resumo do Mês</CardTitle>
        </CardHeader>
        <CardContent class="p-0">
            <div v-if="items.length === 0" class="py-8 text-center text-muted-foreground">
                Nenhuma compra neste mês
            </div>
            <div v-else>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/50">
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Nome</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Quando</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Status</th>
                            <th class="px-4 py-3 text-right font-medium text-muted-foreground">Valor</th>
                            <th class="w-12"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in items"
                            :key="item.name"
                            class="border-b transition-colors"
                            :class="item.items[0].card_id ? '' : 'cursor-pointer hover:bg-muted/30'"
                            @click="openDetails(item)"
                        >
                            <td class="px-4 py-3 font-medium">{{ toTitleCase(item.name) }}</td>
                            <td class="px-4 py-3 text-muted-foreground">
                                <template v-if="Array.isArray(item.dates)">
                                    <span v-for="(day, index) in item.dates" :key="index">
                                        {{ formatDate(day) }}<span v-if="index < item.dates.length - 1">, </span>
                                    </span>
                                </template>
                                <template v-else>
                                    {{ formatDateRange(item.dates.closing, item.dates.due) }}
                                </template>
                            </td>
                            <td class="px-4 py-3">
                                <StatusBadge v-if="item.status" :status="item.status" />
                            </td>
                            <td class="px-4 py-3 text-right font-semibold">{{ formatCurrency(item.total) }}</td>
                            <td class="px-4 py-3 text-right">
                                <Button
                                    v-if="!item.items[0].card_id"
                                    variant="ghost"
                                    size="icon"
                                    class="size-8"
                                    @click.stop="openDetails(item)"
                                >
                                    <ChevronRight class="size-4" />
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </CardContent>
    </Card>

    <PurchaseDetailsModal
        v-model:open="showDetailsModal"
        :purchase="selectedPurchase"
    />
</template>
