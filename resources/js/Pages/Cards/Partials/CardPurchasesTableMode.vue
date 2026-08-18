<script setup lang="ts">
import { computed } from 'vue'
import type { Purchase } from '@/types/purchase'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Pencil, Trash2 } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import Checkbox from '@/Components/Checkbox.vue'
import { formatCurrency } from '@/lib/format'

const props = defineProps<{
    purchases: Purchase[]
    selectedIds: Set<number>
    month: number
    year: number
    sortKey: string | null
    sortDir: 'asc' | 'desc'
}>()

const emit = defineEmits<{
    toggleSelect: [id: number]
    toggleSelectAll: []
    toggleSort: [key: string]
    edit: [purchase: Purchase]
    delete: [purchase: Purchase]
}>()

const allSelected = computed(() =>
    props.purchases.length > 0 && props.selectedIds.size === props.purchases.length
)

function installmentValue(purchase: Purchase): number {
    if (!purchase.installments_total || purchase.installments_total === 0) return parseFloat(String(purchase.amount))
    return parseFloat(String(purchase.amount)) / purchase.installments_total
}

function normalizeDate(dateStr: string): string {
    return dateStr.includes('T') ? dateStr : dateStr.split(' ')[0] + 'T00:00:00'
}

function formatDate(value: string): string {
    return new Date(normalizeDate(value)).toLocaleDateString('pt-BR')
}

function currentInstallment(purchase: Purchase): string {
    if (purchase.is_recurring) return 'Recorrente'
    if (!purchase.installments_total) return 'À vista'
    const [startYear, startMonth] = purchase.start_date.split(/[-/]/).map(Number)
    const monthsDiff = (props.year - startYear) * 12 + (props.month - startMonth)
    return `${monthsDiff + 1} de ${purchase.installments_total}`
}

function sortIcon(key: string): string {
    if (props.sortKey !== key) return ' ⇅'
    return props.sortDir === 'asc' ? ' ▲' : ' ▼'
}
</script>

<template>
    <div class="rounded-md border">
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead class="w-10">
                        <Checkbox
                            :checked="allSelected"
                            @update:checked="emit('toggleSelectAll')"
                        />
                    </TableHead>
                    <TableHead class="w-10">#</TableHead>
                    <TableHead
                        class="cursor-pointer select-none"
                        @click="emit('toggleSort', 'name')"
                    >
                        Nome<span class="text-muted-foreground">{{ sortIcon('name') }}</span>
                    </TableHead>
                    <TableHead
                        class="cursor-pointer select-none"
                        @click="emit('toggleSort', 'amount')"
                    >
                        Valor<span class="text-muted-foreground">{{ sortIcon('amount') }}</span>
                    </TableHead>
                    <TableHead
                        class="hidden sm:table-cell cursor-pointer select-none"
                        @click="emit('toggleSort', 'installment_value')"
                    >
                        Valor parcela<span class="text-muted-foreground">{{ sortIcon('installment_value') }}</span>
                    </TableHead>
                    <TableHead
                        class="hidden sm:table-cell cursor-pointer select-none"
                        @click="emit('toggleSort', 'start_date')"
                    >
                        Data<span class="text-muted-foreground">{{ sortIcon('start_date') }}</span>
                    </TableHead>
                    <TableHead class="hidden sm:table-cell">Parcela</TableHead>
                    <TableHead class="text-right">Ações</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow
                    v-for="(purchase, index) in purchases"
                    :key="purchase.id"
                >
                    <TableCell class="py-2.5">
                        <Checkbox
                            :checked="selectedIds.has(purchase.id)"
                            @update:checked="emit('toggleSelect', purchase.id)"
                        />
                    </TableCell>
                    <TableCell class="py-2.5 text-muted-foreground text-xs tabular-nums">
                        {{ index + 1 }}
                    </TableCell>
                    <TableCell class="py-2.5 font-medium">
                        {{ purchase.name }}
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
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-7 cursor-pointer"
                            @click="emit('edit', purchase)"
                        >
                            <Pencil class="size-3.5" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-7 cursor-pointer text-destructive"
                            @click="emit('delete', purchase)"
                        >
                            <Trash2 class="size-3.5" />
                        </Button>
                    </TableCell>
                </TableRow>
                <TableRow v-if="purchases.length === 0">
                    <TableCell
                        colspan="8"
                        class="h-24 text-center text-muted-foreground"
                    >
                        Nenhuma compra neste mês
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>