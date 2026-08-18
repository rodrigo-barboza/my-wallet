<script setup lang="ts">
import { computed } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { AlertCircle, Calendar, ChevronLeft, ChevronRight, TrendingDown, TrendingUp, Wallet } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import DashboardBarChart from '@/Components/DashboardBarChart.vue'
import SummaryCard from '@/Components/SummaryCard.vue'
import { useIsMobile } from '@/composables/useIsMobile'
import { chartPalette, colors, typeColors } from '@/lib/colors'
import { formatCurrency, formatDate } from '@/lib/format'
import type { CategoryDistribution, DashboardMatrixItem, DashboardWindowMonth, MonthlySummary, UpcomingPayment } from '@/types/dashboard'

defineOptions({ layout: AppLayout })

const props = defineProps<{
    window: DashboardWindowMonth[]
    matrix: DashboardMatrixItem[]
    monthlySummary: MonthlySummary[]
    categoryDistribution: CategoryDistribution[]
    upcomingPayments: UpcomingPayment[]
}>()

const isMobile = useIsMobile()
const visibleCount = computed(() => isMobile.value ? 3 : 6)

const visibleWindow = computed(() => props.window.slice(0, visibleCount.value))
const visibleMonthlySummary = computed(() => props.monthlySummary.slice(0, visibleCount.value))

const highlightedIndex = computed(() => Math.max(0, props.window.findIndex((m) => m.isHighlighted)))
const highlighted = computed(() => (props.monthlySummary[highlightedIndex.value] ?? props.monthlySummary[0]) as MonthlySummary)

const highlightedExpenses = computed(() =>
    props.matrix.map((row) => ({ ...row, total: row.totals[highlightedIndex.value] ?? 0 }))
)
const highlightedColumnTotal = computed(() =>
    highlightedExpenses.value.reduce((sum, row) => sum + row.total, 0)
)

const hasData = computed(() => visibleMonthlySummary.value.some((ms) => ms.income > 0 || ms.expenses > 0))

const categoryBars = computed(() =>
    props.categoryDistribution
        .filter((d) => d.total > 0)
        .map((d) => ({ name: d.label, value: d.total, type: d.type }))
)

const categoryBarMax = computed(() => Math.max(1, ...categoryBars.value.map((d) => d.value), 1))
const hasCategoryData = computed(() => props.categoryDistribution.some((d) => d.total > 0))

const canGoBack = computed(() => true)

const isCurrentMonth = computed(() => {
    const first = props.window[0]
    const now = new Date()
    return first.year === now.getFullYear() && first.month === now.getMonth() + 1
})

function isToday(dateStr: string): boolean {
    const now = new Date()
    const todayStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`
    return dateStr === todayStr
}

function isTomorrow(dateStr: string): boolean {
    const now = new Date()
    now.setDate(now.getDate() + 1)
    const tomorrowStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`
    return dateStr === tomorrowStr
}

function isOverdue(dateStr: string): boolean {
    const now = new Date()
    const todayStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`
    return dateStr < todayStr
}

function paymentDateLabel(dateStr: string): string {
    return formatDate(dateStr)
}

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

function goToCurrentMonth(): void {
    const now = new Date()
    goToMonth(now.getMonth() + 1, now.getFullYear())
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

        <div id="onboarding-summary-cards" class="grid gap-3 sm:grid-cols-3 items-start">
            <SummaryCard
                title="Despesas"
                :description="`${window[highlightedIndex].label} ${window[highlightedIndex].year}`"
                :icon="TrendingDown"
                icon-color="hsl(var(--destructive))"
                :value="formatCurrency(highlighted.expenses)"
                value-color="text-destructive"
                bg-color="color-mix(in oklch, oklch(0.577 0.245 27.325) 5%, transparent)"
                border-color="color-mix(in oklch, oklch(0.577 0.245 27.325) 20%, transparent)"
            />

            <SummaryCard
                title="Entradas"
                :description="`${window[highlightedIndex].label} ${window[highlightedIndex].year}`"
                :icon="TrendingUp"
                icon-color="#10B981"
                :value="formatCurrency(highlighted.income)"
                value-color="text-green-600"
                bg-color="color-mix(in oklch, oklch(0.527 0.154 150.069) 5%, transparent)"
                border-color="color-mix(in oklch, oklch(0.527 0.154 150.069) 20%, transparent)"
            />

            <SummaryCard
                title="Saldo"
                :description="`${window[highlightedIndex].label} ${window[highlightedIndex].year}`"
                :icon="Wallet"
                icon-color="hsl(var(--primary))"
                :value="`${highlighted.balance >= 0 ? '+' : ''}${formatCurrency(highlighted.balance)}`"
                :value-color="highlighted.balance >= 0 ? 'text-green-600' : 'text-destructive'"
                bg-color="hsl(var(--primary) / 0.05)"
                border-color="hsl(var(--primary) / 0.2)"
            />
        </div>

        <Card id="onboarding-progress">
            <CardContent class="py-4">
                <div class="space-y-3">
                    <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                        <div
                            class="h-full rounded-full bg-green-500 transition-all"
                            :style="{ width: highlighted.expenses > 0 ? `${(highlighted.paid / highlighted.expenses) * 100}%` : '0%' }"
                        />
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-4">
                            <span class="text-muted-foreground">Despesas: <span class="font-semibold text-foreground">{{ formatCurrency(highlighted.expenses) }}</span></span>
                            <span class="text-muted-foreground">Pago: <span class="font-semibold text-green-600">{{ formatCurrency(highlighted.paid) }}</span></span>
                        </div>
                        <span
                            class="font-semibold"
                            :class="highlighted.expenses - highlighted.paid > 0 ? 'text-amber-500' : 'text-green-600'"
                        >
                            {{ highlighted.expenses - highlighted.paid > 0 ? `Faltam ${formatCurrency(highlighted.expenses - highlighted.paid)}` : 'Tudo pago' }}
                        </span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="pb-2">
                <CardTitle class="text-base font-semibold">Entradas vs Despesas</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="!hasData"
                    class="py-10 text-center text-sm text-muted-foreground"
                >
                    Nenhum dado disponível para o período.
                </div>
                <div v-else>
                    <DashboardBarChart
                        :monthly-summary="visibleMonthlySummary"
                        :window="visibleWindow"
                        :is-mobile="isMobile"
                    />
                </div>
            </CardContent>
        </Card>

        <Card class="p-0 overflow-hidden">
            <CardHeader class="pb-3 px-4 pt-4">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-base font-semibold">Gastos por mês</CardTitle>
                    <div class="flex items-center gap-1" id="onboarding-month-nav">
                        <Button
                            v-if="!isCurrentMonth"
                            variant="outline"
                            size="sm"
                            class="h-8 gap-1.5 text-xs mr-1"
                            @click="goToCurrentMonth"
                        >
                            <Calendar class="size-3" />
                            Mês atual
                        </Button>
                        <Button
                            variant="outline"
                            size="icon"
                            :disabled="!canGoBack"
                            @click="goBack"
                        >
                            <ChevronLeft class="size-4" />
                        </Button>
                        <Button
                            variant="outline"
                            size="icon"
                            @click="goForward"
                        >
                            <ChevronRight class="size-4" />
                        </Button>
                    </div>
                </div>
            </CardHeader>

            <CardContent class="p-0">
                <div
                    v-if="highlightedExpenses.length === 0"
                    class="py-16 text-center text-muted-foreground text-sm"
                >
                    Nenhuma despesa prevista para este período.
                </div>
                <div
                    v-else
                    class="divide-y divide-muted/30"
                >
                    <div
                        v-for="row in highlightedExpenses"
                        :key="row.id"
                        class="flex items-center justify-between gap-3 px-4 py-3"
                    >
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div
                                class="h-4 w-1 rounded-full shrink-0"
                                :style="{ backgroundColor: typeColor(row.type) }"
                            />
                            <span class="font-medium text-sm truncate">{{ row.name }}</span>
                            <Badge
                                variant="outline"
                                class="text-[10px] px-1.5 py-0 h-4 leading-none shrink-0 border-muted-foreground/20 text-muted-foreground"
                            >
                                {{ typeLabel(row.type) }}
                            </Badge>
                        </div>
                        <span class="text-sm font-semibold tabular-nums shrink-0">
                            <span v-if="row.total > 0">{{ formatCurrency(row.total) }}</span>
                            <span
                                v-else
                                class="text-muted-foreground/40"
                            >&mdash;</span>
                        </span>
                    </div>

                    <div class="flex items-center justify-between gap-3 px-4 py-3 bg-muted/30">
                        <span class="font-bold text-sm">Total</span>
                        <span class="font-bold text-sm tabular-nums">
                            {{ highlightedColumnTotal > 0 ? formatCurrency(highlightedColumnTotal) : '—' }}
                        </span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card id="onboarding-upcoming">
                <CardHeader class="pb-2">
                    <CardTitle class="flex items-center gap-2 text-base font-semibold">
                        <AlertCircle
                            class="size-4"
                            style="color: oklch(0.8 0.15 84)"
                        />
                        Próximos pagamentos
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="upcomingPayments.length === 0"
                        class="min-h-[140px] flex items-center justify-center text-sm text-muted-foreground"
                    >
                        Nenhum pagamento próximo.
                    </div>
                    <div
                        v-else
                        class="space-y-2"
                    >
                        <div
                            v-for="(payment, index) in upcomingPayments"
                            :key="index"
                            class="flex items-center justify-between rounded-lg border px-3 py-2.5 transition-colors"
                            :class="{
                                'border-amber-500/40 bg-amber-500/5': isToday(payment.dueDate),
                                'border-blue-500/40 bg-blue-500/5': isTomorrow(payment.dueDate),
                                'border-destructive/40 bg-destructive/5': isOverdue(payment.dueDate),
                                'hover:bg-muted/50': !isToday(payment.dueDate) && !isTomorrow(payment.dueDate) && !isOverdue(payment.dueDate),
                            }"
                        >
                            <div class="flex flex-col min-w-0 mr-2">
                                <span class="text-sm font-medium truncate">{{ payment.name }}</span>
                                <span class="flex items-center gap-1.5 text-xs text-muted-foreground mt-0.5 flex-wrap">
                                    {{ paymentDateLabel(payment.dueDate) }}
                                    <Badge
                                        variant="secondary"
                                        class="text-[10px] px-1.5 py-0 h-4 leading-none"
                                    >
                                        {{ typeLabel(payment.type) }}
                                    </Badge>
                                    <Badge
                                        v-if="isOverdue(payment.dueDate)"
                                        variant="destructive"
                                        class="text-[10px] px-1.5 py-0 h-4 leading-none"
                                    >
                                        atrasado
                                    </Badge>
                                    <Badge
                                        v-else-if="isToday(payment.dueDate)"
                                        variant="outline"
                                        class="border-amber-500 text-amber-600 text-[10px] px-1.5 py-0 h-4 leading-none"
                                    >
                                        hoje
                                    </Badge>
                                    <Badge
                                        v-else-if="isTomorrow(payment.dueDate)"
                                        variant="outline"
                                        class="border-blue-500 text-blue-600 text-[10px] px-1.5 py-0 h-4 leading-none"
                                    >
                                        amanhã
                                    </Badge>
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
                    <div
                        v-if="!hasCategoryData"
                        class="min-h-[140px] flex items-center justify-center text-sm text-muted-foreground"
                    >
                        Nenhuma despesa neste mês.
                    </div>
                    <div
                        v-else
                        class="space-y-3"
                    >
                        <div
                            v-for="(entry, i) in categoryBars"
                            :key="entry.name"
                            class="flex items-center gap-3"
                        >
                            <span
                                class="text-xs text-muted-foreground shrink-0 text-right truncate"
                                :class="isMobile ? 'w-[56px]' : 'w-[70px]'"
                                :title="entry.name"
                            >
                                {{ entry.name }}
                            </span>
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
                            <span
                                class="text-sm font-semibold tabular-nums shrink-0"
                                :class="isMobile ? 'w-[70px]' : 'w-[85px]'"
                                :title="formatCurrency(entry.value)"
                            >
                                {{ formatCurrency(entry.value) }}
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
