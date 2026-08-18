<script setup lang="ts">
import { computed } from 'vue'
import { Bar } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js'
import ChartDataLabels from 'chartjs-plugin-datalabels'
import { formatCurrency } from '@/lib/format'
import type { MonthlySummary, DashboardWindowMonth } from '@/types/dashboard'
import { colors } from '@/lib/colors'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend, ChartDataLabels)

const props = withDefaults(defineProps<{
    monthlySummary: MonthlySummary[]
    window: DashboardWindowMonth[]
    isMobile?: boolean
}>(), {
    isMobile: false,
})

const chartData = computed(() => ({
    labels: props.window.map((m) => m.label.slice(0, 3)),
    datasets: [
        {
            label: 'Entradas',
            data: props.monthlySummary.map((ms) => ms.income),
            backgroundColor: colors.income,
            borderRadius: 4,
            borderSkipped: false,
        },
        {
            label: 'Despesas',
            data: props.monthlySummary.map((ms) => ms.expenses),
            backgroundColor: colors.expense,
            borderRadius: 4,
            borderSkipped: false,
        },
    ],
}))

const chartOptions = computed(() => {
    const mobile = props.isMobile
    return ({
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top' as const,
                labels: {
                    usePointStyle: true,
                    padding: mobile ? 8 : 16,
                    font: { size: mobile ? 11 : 12 },
                },
            },
            tooltip: {
                callbacks: {
                    label: (ctx: any) => `${ctx.dataset.label}: ${formatCurrency(ctx.raw)}`,
                },
            },
            datalabels: {
                anchor: 'end' as const,
                align: 'end' as const,
                offset: mobile ? 1 : 2,
                font: { weight: 'bold' as const, size: mobile ? 8 : 10 },
                formatter: (value: number) => value > 0 ? formatCurrency(value) : '',
                color: (ctx: any) => ctx.datasetIndex === 0 ? '#065f46' : '#991b1b',
            },
        },
        scales: {
            x: { grid: { display: false } },
            y: { display: false },
        },
    })
})
</script>

<template>
    <div :class="props.isMobile ? 'h-[220px] w-full' : 'h-[320px] w-full'">
        <Bar :data="chartData" :options="chartOptions" />
    </div>
</template>
