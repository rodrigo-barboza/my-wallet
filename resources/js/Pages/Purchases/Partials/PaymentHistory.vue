<script setup lang="ts">
import { computed } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Card, CardContent } from '@/components/ui/card'
import { Receipt } from '@lucide/vue'
import { useIsMobile } from '@/composables/useIsMobile'
import { formatCurrency } from '@/lib/format'
import { typeIcons } from '@/lib/constants'

interface PaymentHistoryItem {
    id: number
    name: string
    amount: number
    paid_at: string
    type: string
    partial?: boolean
}

const props = defineProps<{
    items: PaymentHistoryItem[]
}>()

const isMobile = useIsMobile()

const typeLabels: Record<string, string> = {
    credit_card: 'Cartão',
    bill: 'Compra mensal',
    financing: 'Financiamento',
    others: 'Outros',
}

const sortedItems = computed(() =>
    [...props.items].sort((a, b) => new Date(b.paid_at).getTime() - new Date(a.paid_at).getTime())
)

function formatDateTime(value: string): string {
    const date = new Date(value)
    return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })
}

function typeLabel(type: string): string {
    return typeLabels[type] ?? type
}

function statusBadgeClass(item: PaymentHistoryItem): string {
    return item.partial
        ? 'bg-amber-100 text-amber-700 hover:bg-amber-100'
        : 'bg-green-100 text-green-700 hover:bg-green-100'
}
</script>

<template>
    <template v-if="!isMobile">
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
                            {{ typeLabel(item.type) }}
                        </TableCell>
                        <TableCell class="py-2.5">
                            <Badge
                                variant="secondary"
                                :class="statusBadgeClass(item)"
                            >
                                {{ item.partial ? 'Parcial' : 'Total' }}
                            </Badge>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="items.length === 0">
                        <TableCell
                            colspan="6"
                            class="h-24 text-center text-muted-foreground"
                        >
                            Nenhum pagamento registrado neste mês
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </template>

    <div
        v-else
        class="space-y-3"
    >
        <div
            v-if="sortedItems.length === 0"
            class="rounded-md border py-12 text-center text-muted-foreground"
        >
            Nenhum pagamento registrado neste mês
        </div>

        <Card
            v-for="(item, index) in sortedItems"
            :key="`${item.type}-${item.id}`"
            class="overflow-hidden"
        >
            <CardContent class="p-4">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-muted-foreground tabular-nums">{{ index + 1 }}</span>
                    <component
                        :is="typeIcons[item.type] ?? Receipt"
                        class="size-4 shrink-0 text-muted-foreground"
                    />
                    <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ item.name }}</span>
                    <Badge
                        variant="secondary"
                        class="shrink-0"
                        :class="statusBadgeClass(item)"
                    >
                        {{ item.partial ? 'Parcial' : 'Total' }}
                    </Badge>
                </div>
                <div class="mt-3 flex items-center justify-between border-t pt-3 text-sm">
                    <span class="text-muted-foreground">
                        {{ formatDateTime(item.paid_at) }} · {{ typeLabel(item.type) }}
                    </span>
                    <span class="font-semibold tabular-nums">{{ formatCurrency(item.amount) }}</span>
                </div>
            </CardContent>
        </Card>
    </div>
</template>