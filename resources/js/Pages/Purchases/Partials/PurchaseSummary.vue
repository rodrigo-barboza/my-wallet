<script setup lang="ts">
import type { PurchaseSummaryItem } from '@/types/purchase';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { ChevronRight } from '@lucide/vue';
import { router } from '@inertiajs/vue3';

defineProps<{
    items: PurchaseSummaryItem[];
}>();

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
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Resumo do Mês</CardTitle>
        </CardHeader>
        <CardContent>
            <div v-if="items.length === 0" class="py-8 text-center text-muted-foreground">
                Nenhuma compra neste mês
            </div>
            <div v-else class="space-y-4">
                <div
                    v-for="item in items"
                    :key="item.name ?? 'individual'"
                    class="flex items-center justify-between rounded-lg border p-4"
                >
                    <div class="space-y-1">
                        <div v-if="item.name" class="font-medium">
                            {{ item.name }}
                        </div>
                        <div class="text-sm text-muted-foreground">
                            <template v-if="Array.isArray(item.dates)">
                                <span v-for="(day, index) in item.dates" :key="index">
                                    {{ formatDate(day) }}<span v-if="index < item.dates.length - 1">, </span>
                                </span>
                            </template>
                            <template v-else>
                                {{ formatDateRange(item.dates.closing, item.dates.due) }}
                            </template>
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{ item.items.length }} {{ item.items.length === 1 ? 'compra' : 'compras' }}
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-right">
                            <div class="font-semibold">{{ formatCurrency(item.total) }}</div>
                        </div>
                        <Button
                            v-if="item.name"
                            variant="ghost"
                            size="icon"
                            @click="router.get(route('purchases.show', item.items[0]))"
                        >
                            <ChevronRight class="size-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
