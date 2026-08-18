<script setup lang="ts">
import type { Purchase } from '@/types/purchase'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Pencil, Trash2 } from '@lucide/vue'
import Checkbox from '@/Components/Checkbox.vue'
import { formatCurrency } from '@/lib/format'

const props = defineProps<{
    purchases: Purchase[]
    selectedIds: Set<number>
    month: number
    year: number
}>()

const emit = defineEmits<{
    toggleSelect: [id: number]
    edit: [purchase: Purchase]
    delete: [purchase: Purchase]
}>()

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
</script>

<template>
    <div
        v-if="purchases.length === 0"
        class="rounded-md border py-12 text-center text-muted-foreground"
    >
        Nenhuma compra neste mês
    </div>

    <div
        v-else
        class="space-y-3"
    >
        <Card
            v-for="(purchase, index) in purchases"
            :key="purchase.id"
            class="relative overflow-hidden"
        >
            <CardContent class="p-4">
                <div class="flex items-center gap-2">
                    <Checkbox
                        :checked="selectedIds.has(purchase.id)"
                        @update:checked="emit('toggleSelect', purchase.id)"
                    />
                    <span class="text-xs text-muted-foreground tabular-nums">{{ index + 1 }}</span>
                    <span class="flex-1 min-w-0 truncate text-sm font-medium">{{ purchase.name }}</span>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-8 shrink-0 cursor-pointer"
                        @click="emit('edit', purchase)"
                    >
                        <Pencil class="size-3.5" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-8 shrink-0 cursor-pointer text-destructive"
                        @click="emit('delete', purchase)"
                    >
                        <Trash2 class="size-3.5" />
                    </Button>
                </div>

                <div class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    <div class="text-muted-foreground">
                        Valor
                        <span class="block font-semibold text-foreground">{{ formatCurrency(purchase.amount) }}</span>
                    </div>
                    <div
                        v-if="purchase.installments_total"
                        class="text-muted-foreground"
                    >
                        Valor parcela
                        <span class="block font-semibold text-foreground">{{ formatCurrency(installmentValue(purchase)) }}</span>
                    </div>
                    <div class="text-muted-foreground">
                        Data
                        <span class="block font-medium text-foreground">{{ formatDate(purchase.start_date) }}</span>
                    </div>
                    <div class="text-muted-foreground">
                        Parcela
                        <span class="block font-medium text-foreground">{{ currentInstallment(purchase) }}</span>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>