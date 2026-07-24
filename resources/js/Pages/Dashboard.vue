<script setup lang="ts">
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { router } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { ChevronLeft, ChevronRight, Wallet, TrendingUp, TrendingDown, AlertCircle } from '@lucide/vue'
import { VisXYContainer, VisGroupedBar, VisAxis, VisLine, VisDonut, VisSingleContainer } from '@unovis/vue'
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

const COLORS = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4']

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value)
}

function formatShortCurrency(value: number): string {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value)
}

function formatDate(dateStr: string): string {
    const [datePart] = dateStr.split('T')
    const [year, month, day] = datePart.split('-')
    return `${parseInt(day)}/${month}`
}

function isToday(dateStr: string): boolean {
    const [datePart] = dateStr.split('T')
    const today = new Date()
    const todayStr = today.toISOString().split('T')[0]
    return datePart === todayStr
}

function isOverdue(dateStr: string): boolean {
    const [datePart] = dateStr.split('T')
    const today = new Date()
    const todayStr = today.toISOString().split('T')[0]
    return datePart < todayStr
}

const highlightedIndex = computed(() => props.window.findIndex((m: DashboardWindowMonth) => m.isHighlighted))
const highlighted = computed(() => props.monthlySummary[highlightedIndex.value] as MonthlySummary)

const barChartData = computed(() =>
    props.monthlySummary.map((ms: MonthlySummary, i: number) => ({
        index: i,
        month: props.window[i].label.slice(0, 3),
        Entradas: ms.income,
        Despesas: ms.expenses,
    }))
)

const hasData = computed(() => barChartData.value.some((d) => d.Entradas > 0 || d.Despesas > 0))

const lineChartData = computed(() => {
    let cumulative = 0
    return props.monthlySummary.map((ms: MonthlySummary, i: number) => {
        cumulative += ms.balance
        return {
            index: i,
            month: props.window[i].label.slice(0, 3),
            Saldo: cumulative,
        }
    })
})

const pieChartData = computed(() =>
    props.categoryDistribution
        .filter((d: CategoryDistribution) => d.total > 0)
        .map((d: CategoryDistribution) => ({
            name: d.label,
            value: d.total,
        }))
)

const hasCategoryData = computed(() => props.categoryDistribution.some((d: CategoryDistribution) => d.total > 0))

const canGoBack = computed(() => {
    const first = props.window[0]
    const now = new Date()
    const currentMonth = now.getMonth() + 1
    const currentYear = now.getFullYear()
    return !(first.year === currentYear && first.month === currentMonth)
})

function goBack(): void {
    if (!canGoBack.value) return
    const first = props.window[0]
    let newMonth = first.month - 1
    let newYear = first.year
    if (newMonth < 1) {
        newMonth = 12
        newYear--
    }
    router.get(route('dashboard', { month: newMonth, year: newYear }))
}

function goForward(): void {
    const last = props.window[5]
    let newMonth = last.month + 1
    let newYear = last.year
    if (newMonth > 12) {
        newMonth = 1
        newYear++
    }
    router.get(route('dashboard', { month: newMonth, year: newYear }))
}

function typeLabel(type: string): string {
    const labels: Record<string, string> = {
        credit_card: 'Cartão',
        bill: 'Conta',
        financing: 'Financiamento',
        others: 'Outros',
    }
    return labels[type] ?? type
}

function xTickFormat(d: number): string {
    return barChartData.value[d]?.month ?? ''
}

function yTickFormat(d: number): string {
    return formatShortCurrency(d)
}

function lineXTickFormat(d: number): string {
    return lineChartData.value[d]?.month ?? ''
}

function lineYTickFormat(d: number): string {
    return formatShortCurrency(d)
}
</script>

<template>
    <div class="w-full space-y-6">
        <h2 class="text-2xl font-bold">Dashboard</h2>

        <!-- Feedback do mês em destaque -->
        <div class="grid gap-4 sm:grid-cols-3">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="flex items-center gap-2 text-sm font-semibold text-muted-foreground">
                        <TrendingDown class="size-4" />
                        Despesas
                    </CardTitle>
                    <CardDescription class="text-xs">
                        {{ window[highlightedIndex].label }} {{ window[highlightedIndex].year }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-destructive">{{ formatCurrency(highlighted.expenses) }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="flex items-center gap-2 text-sm font-semibold text-muted-foreground">
                        <TrendingUp class="size-4" />
                        Entradas
                    </CardTitle>
                    <CardDescription class="text-xs">
                        {{ window[highlightedIndex].label }} {{ window[highlightedIndex].year }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-green-600">{{ formatCurrency(highlighted.income) }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="flex items-center gap-2 text-sm font-semibold text-muted-foreground">
                        <Wallet class="size-4" />
                        Saldo
                    </CardTitle>
                    <CardDescription class="text-xs">
                        {{ window[highlightedIndex].label }} {{ window[highlightedIndex].year }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        class="text-2xl font-bold"
                        :class="highlighted.balance >= 0 ? 'text-green-600' : 'text-destructive'"
                    >
                        {{ highlighted.balance >= 0 ? '+' : '' }}{{ formatCurrency(highlighted.balance) }}
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Matriz -->
        <Card>
            <CardHeader class="pb-2">
                <div class="flex items-center justify-between">
                    <CardTitle class="text-base font-semibold">Próximos meses</CardTitle>
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
            <CardContent>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[600px]">
                        <thead>
                            <tr>
                                <th class="pr-4 text-left text-sm font-semibold text-muted-foreground w-[180px]"></th>
                                <th
                                    v-for="(month, index) in window"
                                    :key="index"
                                    class="px-2 py-2 text-center text-sm"
                                    :class="{
                                        'font-bold bg-primary/10 rounded-md': month.isHighlighted,
                                        'text-muted-foreground font-medium': !month.isHighlighted,
                                    }"
                                >
                                    <div>{{ month.label }}</div>
                                    <div class="text-xs text-muted-foreground">{{ month.year }}</div>
                                    <Badge v-if="month.isCurrent && !month.isHighlighted" variant="outline" class="mt-1 text-[10px] px-1 py-0 h-4">
                                        atual
                                    </Badge>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="matrix.length === 0">
                                <td :colspan="7" class="py-10 text-center text-muted-foreground text-sm">
                                    Nenhuma despesa prevista para este período.
                                </td>
                            </tr>
                            <tr
                                v-for="row in matrix"
                                :key="row.id"
                                class="border-b border-muted/50"
                            >
                                <td class="py-2 pr-4 text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        {{ row.name }}
                                        <Badge variant="secondary" class="text-[10px] px-1.5 py-0 h-4">
                                            {{ typeLabel(row.type) }}
                                        </Badge>
                                    </div>
                                </td>
                                <td
                                    v-for="(total, ti) in row.totals"
                                    :key="ti"
                                    class="px-2 py-2 text-center text-sm"
                                    :class="{
                                        'bg-primary/10': window[ti]?.isHighlighted,
                                        'rounded-md': window[ti]?.isHighlighted,
                                    }"
                                >
                                    <span v-if="total > 0" class="font-mono tabular-nums">{{ formatShortCurrency(total) }}</span>
                                    <span v-else class="text-muted-foreground">&mdash;</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <!-- Próximos pagamentos -->
        <div class="grid gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="flex items-center gap-2 text-base font-semibold">
                        <AlertCircle class="size-4 text-amber-500" />
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
                            class="flex items-center justify-between rounded-md border px-3 py-2"
                            :class="{
                                'border-amber-500/50 bg-amber-500/5': isToday(payment.dueDate),
                                'border-destructive/50 bg-destructive/5': isOverdue(payment.dueDate),
                            }"
                        >
                            <div class="flex flex-col">
                                <span class="text-sm font-medium">{{ payment.name }}</span>
                                <span class="flex items-center gap-1 text-xs text-muted-foreground">
                                    {{ formatDate(payment.dueDate) }}
                                    <Badge variant="secondary" class="text-[10px] px-1.5 py-0 h-4">
                                        {{ typeLabel(payment.type) }}
                                    </Badge>
                                    <Badge
                                        v-if="isOverdue(payment.dueDate)"
                                        variant="destructive"
                                        class="text-[10px] px-1.5 py-0 h-4"
                                    >
                                        atrasado
                                    </Badge>
                                    <Badge
                                        v-else-if="isToday(payment.dueDate)"
                                        variant="outline"
                                        class="border-amber-500 text-amber-600 text-[10px] px-1.5 py-0 h-4"
                                    >
                                        hoje
                                    </Badge>
                                </span>
                            </div>
                            <span class="text-sm font-semibold tabular-nums">{{ formatCurrency(payment.amount) }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="hidden lg:block" />
        </div>

        <!-- Gráficos -->
        <div class="space-y-6">
            <!-- Barras: Entradas vs Despesas -->
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-base font-semibold">Entradas vs Despesas</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="!hasData" class="py-10 text-center text-sm text-muted-foreground">
                        Nenhum dado disponível para o período.
                    </div>
                    <div v-else class="h-[300px] w-full">
                        <VisXYContainer :data="barChartData" :scale-by-domain="true">
                            <VisGroupedBar
                                :x="(d: any) => d.index"
                                :y="[(d: any) => d.Entradas, (d: any) => d.Despesas]"
                                :color="['#10B981', '#EF4444']"
                                :rounded-corners="4"
                                bar-padding="0.1"
                                group-padding="0.2"
                            />
                            <VisAxis type="x" position="bottom" :tick-format="xTickFormat" :num-ticks="6" />
                            <VisAxis type="y" position="left" :tick-format="yTickFormat" />
                        </VisXYContainer>
                    </div>
                </CardContent>
            </Card>

            <!-- Distribuição por tipo -->
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
                    <div v-else class="flex flex-col items-center gap-6 sm:flex-row">
                        <div class="h-[200px] w-full sm:w-1/2">
                            <VisSingleContainer :data="pieChartData">
                                <VisDonut
                                    :value="(d: any) => d.value"
                                    :color="(d: any, i: number) => COLORS[i % COLORS.length]"
                                    :arc-width="24"
                                />
                            </VisSingleContainer>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div
                                v-for="(entry, index) in pieChartData"
                                :key="entry.name"
                                class="flex items-center gap-2"
                            >
                                <div
                                    class="h-3 w-3 rounded-full"
                                    :style="{ backgroundColor: COLORS[index % COLORS.length] }"
                                />
                                <span class="text-muted-foreground">{{ entry.name }}</span>
                                <span class="font-semibold tabular-nums">{{ formatCurrency(entry.value) }}</span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Linha: Saldo acumulado -->
            <Card v-if="hasData">
                <CardHeader class="pb-2">
                    <CardTitle class="text-base font-semibold">Saldo acumulado</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="h-[250px] w-full">
                        <VisXYContainer :data="lineChartData" :scale-by-domain="true">
                            <VisLine
                                :x="(d: any) => d.index"
                                :y="(d: any) => d.Saldo"
                                :color="lineChartData[lineChartData.length - 1]?.Saldo >= 0 ? '#10B981' : '#EF4444'"
                                :line-width="2"
                            />
                            <VisAxis type="x" position="bottom" :tick-format="lineXTickFormat" :num-ticks="6" />
                            <VisAxis type="y" position="left" :tick-format="lineYTickFormat" />
                        </VisXYContainer>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
