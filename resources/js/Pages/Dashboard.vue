<script setup lang="ts">
import { computed } from 'vue'
import { useMediaQuery } from '@vueuse/core'
import AppLayout from '@/Layouts/AppLayout.vue'
import { router, Head } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { ChevronLeft, ChevronRight, Wallet, TrendingUp, TrendingDown, AlertCircle } from '@lucide/vue'
import DashboardBarChart from '@/Components/DashboardBarChart.vue'
import { chartPalette, typeColors, colors } from '@/lib/colors'
import type {
    DashboardWindowMonth,
    DashboardMatrixItem,
    MonthlySummary,
    CategoryDistribution,
    UpcomingPayment,
} from '@/types/dashboard'

defineOptions({ layout: AppLayout })

const props = defineProps<{
    window: DashboardWindowMonth[]
    matrix: DashboardMatrixItem[]
    monthlySummary: MonthlySummary[]
    categoryDistribution: CategoryDistribution[]
    upcomingPayments: UpcomingPayment[]
}>()

const isMobile = useMediaQuery('(max-width: 767px)')
const visibleCount = computed(() => isMobile.value ? 3 : 6)

const visibleWindow = computed(() => props.window.slice(0, visibleCount.value))
const visibleMonthlySummary = computed(() => props.monthlySummary.slice(0, visibleCount.value))
const visibleMatrix = computed(() =>
    props.matrix.map(row => ({ ...row, totals: row.totals.slice(0, visibleCount.value) }))
)

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value)
}

function formatShortCurrency(value: number): string {
    if (value >= 1000) return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value)
    return formatCurrency(value)
}

function formatDate(dateStr: string): string {
    const [datePart] = dateStr.split('T')
    const [year, month, day] = datePart.split('-')
    return `${parseInt(day)}/${month}`
}

function isToday(dateStr: string): boolean {
    return dateStr.split('T')[0] === new Date().toISOString().split('T')[0]
}

function isOverdue(dateStr: string): boolean {
    return dateStr.split('T')[0] < new Date().toISOString().split('T')[0]
}

const highlightedIndex = computed(() => Math.max(0, props.window.findIndex((m) => m.isHighlighted)))
const highlighted = computed(() => (props.monthlySummary[highlightedIndex.value] ?? props.monthlySummary[0]) as MonthlySummary)

const hasData = computed(() => visibleMonthlySummary.value.some((ms) => ms.income > 0 || ms.expenses > 0))

const categoryBars = computed(() =>
    props.categoryDistribution
        .filter((d) => d.total > 0)
        .map((d) => ({ name: d.label, value: d.total, type: d.type }))
)

const categoryBarMax = computed(() => Math.max(1, ...categoryBars.value.map((d) => d.value), 1))

const hasCategoryData = computed(() => props.categoryDistribution.some((d) => d.total > 0))

const canGoBack = computed(() => {
    const first = props.window[0]
    const now = new Date()
    return !(first.year === now.getFullYear() && first.month === now.getMonth() + 1)
})

const columnTotals = computed(() => {
    return Array.from({ length: visibleCount.value }, (_, i) =>
        visibleMatrix.value.reduce((sum, row) => sum + (row.totals[i] || 0), 0)
    )
})

function goToMonth(month: number, year: number): void {
    router.get(route('dashboard', { month, year }))
}

function goBack(): void {
    if (!canGoBack.value) return
    const first = props.window[0]
    let m = first.month - 1, y = first.year
    if (m < 1) { m = 12; y-- }
    goToMonth(m, y)
}

function goForward(): void {
    const first = props.window[0]
    let m = first.month + 1, y = first.year
    if (m > 12) { m = 1; y++ }
    goToMonth(m, y)
}

function typeLabel(type: string): string {
    return { credit_card: 'Cartão', bill: 'Conta', financing: 'Financiamento', others: 'Outros' }[type] ?? type
}

function typeColor(type: string): string {
    return typeColors[type] ?? colors.background
}

function categoryColor(index: number, type: string): string {
    return typeColors[type] ?? chartPalette[index % chartPalette.length]
}
</script>

<template>
    <div class="w-full space-y-6">
        <Head title="My Wallet - Dashboard" />
        <h2 class="text-2xl font-bold">Dashboard</h2>

        <!-- Cards do mês em destaque -->
        <div class="grid gap-3 sm:grid-cols-3">
            <Card style="background-color: color-mix(in oklch, oklch(0.577 0.245 27.325) 5%, transparent); border-color: color-mix(in oklch, oklch(0.577 0.245 27.325) 20%, transparent)" class="!py-3">
                <CardHeader class="pb-1">
                    <CardTitle class="flex items-center gap-1.5 text-xs font-semibold text-muted-foreground">
                        <TrendingDown class="size-3.5 text-destructive" />
                        Despesas
                    </CardTitle>
                    <CardDescription class="text-[11px]">
                        {{ window[highlightedIndex].label }} {{ window[highlightedIndex].year }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="text-xl font-bold text-destructive">{{ formatCurrency(highlighted.expenses) }}</div>
                </CardContent>
            </Card>

            <Card style="background-color: color-mix(in oklch, oklch(0.527 0.154 150.069) 5%, transparent); border-color: color-mix(in oklch, oklch(0.527 0.154 150.069) 20%, transparent)" class="!py-3">
                <CardHeader class="pb-1">
                    <CardTitle class="flex items-center gap-1.5 text-xs font-semibold text-muted-foreground">
                        <TrendingUp class="size-3.5" style="color: oklch(0.527 0.154 150.069)" />
                        Entradas
                    </CardTitle>
                    <CardDescription class="text-[11px]">
                        {{ window[highlightedIndex].label }} {{ window[highlightedIndex].year }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="text-xl font-bold" style="color: oklch(0.527 0.154 150.069)">{{ formatCurrency(highlighted.income) }}</div>
                </CardContent>
            </Card>

            <Card class="bg-primary/5 border-primary/20 !py-3">
                <CardHeader class="pb-1">
                    <CardTitle class="flex items-center gap-1.5 text-xs font-semibold text-muted-foreground">
                        <Wallet class="size-3.5 text-primary" />
                        Saldo
                    </CardTitle>
                    <CardDescription class="text-[11px]">
                        {{ window[highlightedIndex].label }} {{ window[highlightedIndex].year }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="text-xl font-bold" :class="highlighted.balance >= 0 ? 'text-green-600' : 'text-destructive'">
                        {{ highlighted.balance >= 0 ? '+' : '' }}{{ formatCurrency(highlighted.balance) }}
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Gráfico de barras: Entradas vs Despesas -->
        <Card>
            <CardHeader class="pb-2">
                <CardTitle class="text-base font-semibold">Entradas vs Despesas</CardTitle>
            </CardHeader>
            <CardContent>
                <div v-if="!hasData" class="py-10 text-center text-sm text-muted-foreground">
                    Nenhum dado disponível para o período.
                </div>
                <div v-else>
                    <DashboardBarChart :monthly-summary="visibleMonthlySummary" :window="visibleWindow" />
                </div>
            </CardContent>
        </Card>

        <!-- Matriz de gastos -->
        <Card class="p-0 overflow-hidden">
            <CardHeader class="pb-3 px-4 pt-4">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-base font-semibold">Gastos por mês</CardTitle>
                    <div class="flex items-center gap-1">
                        <Button variant="outline" size="icon" :disabled="!canGoBack" @click="goBack">
                            <ChevronLeft class="size-4" />
                        </Button>
                        <Button variant="outline" size="icon" @click="goForward">
                            <ChevronRight class="size-4" />
                        </Button>
                    </div>
                </div>
            </CardHeader>

            <!-- Layout desktop: tabela -->
            <CardContent class="p-0 hidden md:block">
                <div class="overflow-x-auto rounded-b-xl">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-muted/40">
                                <th class="sticky left-0 z-10 bg-muted/40 pl-4 pr-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-muted-foreground rounded-tl-xl">
                                    Descrição
                                </th>
                                <th
                                    v-for="(month, index) in visibleWindow"
                                    :key="index"
                                    class="px-3 py-2.5 text-center text-[11px] cursor-pointer select-none transition-colors"
                                    :class="month.isHighlighted
                                        ? 'bg-primary/10 font-bold text-primary border-b-2 border-primary'
                                        : 'font-medium text-muted-foreground hover:bg-muted/30'"
                                    @click="goToMonth(month.month, month.year)"
                                >
                                    <div class="uppercase tracking-wider leading-tight">{{ month.label.slice(0, 3) }}</div>
                                    <div class="text-[10px] font-normal text-muted-foreground/60 leading-tight">{{ month.year }}</div>
                                    <Badge v-if="month.isCurrent && !month.isHighlighted" variant="secondary" class="mt-0.5 text-[10px] px-1 py-0 h-3.5 leading-none rounded-sm">
                                        atual
                                    </Badge>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="visibleMatrix.length === 0">
                                <td :colspan="visibleWindow.length + 1" class="py-16 text-center text-muted-foreground text-sm">
                                    Nenhuma despesa prevista para este período.
                                </td>
                            </tr>
                            <tr
                                v-for="(row, ri) in visibleMatrix"
                                :key="row.id"
                                class="transition-colors"
                                :class="ri % 2 === 0 ? 'bg-background' : 'bg-muted/20'"
                            >
                                <td class="sticky left-0 z-10 pl-4 pr-3 py-2.5 text-sm"
                                    :class="ri % 2 === 0 ? 'bg-background' : 'bg-muted/20'"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <div class="h-5 w-1 rounded-full shrink-0" :style="{ backgroundColor: typeColor(row.type) }" />
                                        <span class="font-medium truncate">{{ row.name }}</span>
                                        <Badge variant="outline" class="text-[10px] px-1.5 py-0 h-4 leading-none shrink-0 border-muted-foreground/20 text-muted-foreground">
                                            {{ typeLabel(row.type) }}
                                        </Badge>
                                    </div>
                                </td>
                                <td
                                    v-for="(total, ti) in row.totals"
                                    :key="ti"
                                    class="px-3 py-2.5 text-center text-sm tabular-nums transition-colors"
                                    :class="{
                                        'bg-primary/5 font-semibold': visibleWindow[ti]?.isHighlighted,
                                        'bg-muted/20': !visibleWindow[ti]?.isHighlighted && ri % 2 === 1,
                                    }"
                                >
                                    <span v-if="total > 0">{{ formatShortCurrency(total) }}</span>
                                    <span v-else class="text-muted-foreground/40">&mdash;</span>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 bg-muted/30">
                                <td class="sticky left-0 z-10 bg-muted/30 pl-4 pr-3 py-2.5 text-sm font-bold text-foreground">
                                    Total
                                </td>
                                <td
                                    v-for="(total, ti) in columnTotals"
                                    :key="ti"
                                    class="px-3 py-2.5 text-center text-sm font-bold tabular-nums"
                                    :class="visibleWindow[ti]?.isHighlighted ? 'bg-primary/5' : ''"
                                >
                                    {{ total > 0 ? formatShortCurrency(total) : '—' }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </CardContent>

            <!-- Layout mobile: cards -->
            <CardContent class="p-0 md:hidden">
                <div v-if="visibleMatrix.length === 0" class="py-16 text-center text-muted-foreground text-sm">
                    Nenhuma despesa prevista para este período.
                </div>
                <div v-else class="divide-y divide-muted/30">
                    <div
                        v-for="(row, ri) in visibleMatrix"
                        :key="row.id"
                        class="px-4 py-3"
                    >
                        <div class="flex items-center gap-2 mb-2">
                            <div class="h-4 w-1 rounded-full shrink-0" :style="{ backgroundColor: typeColor(row.type) }" />
                            <span class="font-medium text-sm truncate">{{ row.name }}</span>
                            <Badge variant="outline" class="text-[10px] px-1 py-0 h-3.5 leading-none shrink-0 border-muted-foreground/20 text-muted-foreground">
                                {{ typeLabel(row.type) }}
                            </Badge>
                        </div>
                        <div class="grid gap-2" :style="{ gridTemplateColumns: `repeat(${visibleWindow.length}, minmax(0, 1fr))` }">
                            <div
                                v-for="(month, mi) in visibleWindow"
                                :key="mi"
                                class="text-center rounded-md py-1.5 px-1"
                                :class="month.isHighlighted ? 'bg-primary/10 font-bold text-primary' : 'text-muted-foreground'"
                            >
                                <div class="text-[10px] font-medium mb-0.5">{{ month.label.slice(0, 3) }}</div>
                                <div class="text-xs tabular-nums font-semibold">
                                    <span v-if="row.totals[mi] > 0">{{ formatShortCurrency(row.totals[mi]) }}</span>
                                    <span v-else class="text-muted-foreground/40">&mdash;</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Total mobile -->
                    <div class="px-4 py-3 bg-muted/30">
                        <div class="font-bold text-sm mb-2">Total</div>
                        <div class="grid gap-2" :style="{ gridTemplateColumns: `repeat(${visibleWindow.length}, minmax(0, 1fr))` }">
                            <div
                                v-for="(total, ti) in columnTotals"
                                :key="ti"
                                class="text-center rounded-md py-1.5 px-1 text-xs font-bold tabular-nums"
                                :class="visibleWindow[ti]?.isHighlighted ? 'bg-primary/10 text-primary' : 'text-foreground'"
                            >
                                {{ total > 0 ? formatShortCurrency(total) : '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Próximos pagamentos + Despesas por tipo -->
        <div class="grid gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="flex items-center gap-2 text-base font-semibold">
                        <AlertCircle class="size-4" style="color: oklch(0.8 0.15 84)" />
                        Próximos pagamentos
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="upcomingPayments.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                        Nenhum pagamento próximo.
                    </div>
                    <div v-else class="space-y-2">
                        <div
                            v-for="(payment, index) in upcomingPayments"
                            :key="index"
                            class="flex items-center justify-between rounded-lg border px-3 py-2.5 transition-colors"
                            :class="{
                                'border-amber-500/40 bg-amber-500/5': isToday(payment.dueDate),
                                'border-destructive/40 bg-destructive/5': isOverdue(payment.dueDate),
                                'hover:bg-muted/50': !isToday(payment.dueDate) && !isOverdue(payment.dueDate),
                            }"
                        >
                            <div class="flex flex-col min-w-0 mr-2">
                                <span class="text-sm font-medium truncate">{{ payment.name }}</span>
                                <span class="flex items-center gap-1.5 text-xs text-muted-foreground mt-0.5 flex-wrap">
                                    {{ formatDate(payment.dueDate) }}
                                    <Badge variant="secondary" class="text-[10px] px-1.5 py-0 h-4 leading-none">{{ typeLabel(payment.type) }}</Badge>
                                    <Badge v-if="isOverdue(payment.dueDate)" variant="destructive" class="text-[10px] px-1.5 py-0 h-4 leading-none">atrasado</Badge>
                                    <Badge v-else-if="isToday(payment.dueDate)" variant="outline" class="border-amber-500 text-amber-600 text-[10px] px-1.5 py-0 h-4 leading-none">hoje</Badge>
                                </span>
                            </div>
                            <span class="text-sm font-semibold tabular-nums shrink-0">{{ formatCurrency(payment.amount) }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-base font-semibold">Despesas por tipo</CardTitle>
                    <CardDescription class="text-xs">
                        {{ window[highlightedIndex].label }} {{ window[highlightedIndex].year }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="!hasCategoryData" class="py-10 text-center text-sm text-muted-foreground">
                        Nenhuma despesa neste mês.
                    </div>
                    <div v-else class="space-y-3">
                        <div
                            v-for="(entry, i) in categoryBars"
                            :key="entry.name"
                            class="flex items-center gap-3"
                        >
                            <span class="text-xs text-muted-foreground w-[70px] shrink-0 text-right truncate" :title="entry.name">{{ entry.name }}</span>
                            <div class="flex-1 h-7 rounded-md bg-muted/50 overflow-hidden">
                                <div
                                    class="h-full rounded-md transition-all duration-300"
                                    :style="{
                                        width: `${(entry.value / categoryBarMax) * 100}%`,
                                        minWidth: entry.value > 0 ? '4px' : '0',
                                        backgroundColor: categoryColor(i, entry.type),
                                        opacity: 0.85,
                                    }"
                                />
                            </div>
                            <span class="text-sm font-semibold tabular-nums w-[85px] shrink-0" :title="formatCurrency(entry.value)">{{ formatCurrency(entry.value) }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
