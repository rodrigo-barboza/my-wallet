<script setup lang="ts">
import { computed } from 'vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { CreditCard, Receipt, Calendar, Banknote, ShoppingCart } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';

interface PaymentHistoryItem {
    id: number;
    name: string;
    amount: number;
    paid_at: string;
    type: string;
    partial?: boolean;
}

const props = defineProps<{
    items: PaymentHistoryItem[];
}>();

const typeLabels: Record<string, string> = {
    credit_card: 'Cartão',
    bill: 'Compra mensal',
    financing: 'Financiamento',
    others: 'Outros',
};

const typeIcons: Record<string, typeof CreditCard> = {
    credit_card: CreditCard,
    bill: Calendar,
    financing: Banknote,
    others: ShoppingCart,
};

const sortedItems = computed(() =>
    [...props.items].sort((a, b) => new Date(b.paid_at).getTime() - new Date(a.paid_at).getTime())
);

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
}

function formatDateTime(value: string): string {
    const date = new Date(value);
    return date.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
    });
}
</script>

<template>
    <div class="rounded-md border">
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead class="w-10">#</TableHead>
                    <TableHead>Data</TableHead>
                    <TableHead>Nome</TableHead>
                    <TableHead class="text-right">Valor</TableHead>
                    <TableHead>Tipo</TableHead>
                    <TableHead>Status</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow
                    v-for="(item, index) in sortedItems"
                    :key="`${item.type}-${item.id}`"
                >
                    <TableCell class="py-2.5 text-muted-foreground text-xs tabular-nums">
                        {{ index + 1 }}
                    </TableCell>
                    <TableCell class="py-2.5 font-medium">
                        {{ formatDateTime(item.paid_at) }}
                    </TableCell>
                    <TableCell class="py-2.5">
                        <div class="flex items-center gap-1.5">
                            <component
                                :is="typeIcons[item.type] ?? Receipt"
                                class="size-4 text-muted-foreground"
                            />
                            {{ item.name }}
                        </div>
                    </TableCell>
                    <TableCell class="py-2.5 text-right font-medium">
                        {{ formatCurrency(item.amount) }}
                    </TableCell>
                    <TableCell class="py-2.5 text-muted-foreground">
                        {{ typeLabels[item.type] ?? item.type }}
                    </TableCell>
                    <TableCell class="py-2.5">
                        <Badge v-if="item.partial" variant="secondary" class="bg-amber-100 text-amber-700 hover:bg-amber-100">
                            Parcial
                        </Badge>
                        <Badge v-else variant="secondary" class="bg-green-100 text-green-700 hover:bg-green-100">
                            Total
                        </Badge>
                    </TableCell>
                </TableRow>
                <TableRow v-if="items.length === 0">
                    <TableCell colspan="6" class="h-24 text-center text-muted-foreground">
                        Nenhum pagamento registrado neste mês
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
