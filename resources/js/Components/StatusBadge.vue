<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import { statusColors } from '@/lib/colors';

const props = defineProps<{
    status: string;
    baseStatus?: string;
}>();

const statusLabels: Record<string, string> = {
    aberta: 'Aberta',
    fechada: 'Fechada',
    paga: 'Paga',
    parcialmente_paga: 'Parcialmente Paga',
    atrasada: 'Atrasada',
};

const showDualBadges = computed(() =>
    props.status === 'parcialmente_paga' && props.baseStatus,
);

const baseConfig = computed(() => ({
    label: statusLabels[props.baseStatus!] ?? statusLabels.aberta,
    color: statusColors[props.baseStatus!] ?? statusColors.aberta,
}));

const paymentConfig = computed(() => ({
    label: statusLabels[props.status] ?? statusLabels.aberta,
    color: statusColors[props.status] ?? statusColors.aberta,
}));

const singleConfig = computed(() => ({
    label: statusLabels[props.status] ?? statusLabels.aberta,
    color: statusColors[props.status] ?? statusColors.aberta,
}));
</script>

<template>
    <template v-if="showDualBadges">
        <span
            :class="cn('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium')"
            :style="{
                backgroundColor: `${baseConfig.color}18`,
                color: baseConfig.color,
            }"
        >
            {{ baseConfig.label }}
        </span>
        <span
            :class="cn('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium')"
            :style="{
                backgroundColor: `${paymentConfig.color}18`,
                color: paymentConfig.color,
            }"
        >
            {{ paymentConfig.label }}
        </span>
    </template>
    <span
        v-else
        :class="cn('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium')"
        :style="{
            backgroundColor: `${singleConfig.color}18`,
            color: singleConfig.color,
        }"
    >
        {{ singleConfig.label }}
    </span>
</template>
