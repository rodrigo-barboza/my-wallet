<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import { statusColors } from '@/lib/colors';

const props = defineProps<{
    status: string;
}>();

const statusLabels: Record<string, string> = {
    aberta: 'Aberta',
    fechada: 'Fechada',
    paga: 'Paga',
    parcialmente_paga: 'Parcialmente Paga',
    atrasada: 'Atrasada',
};

const config = computed(() => ({
    label: statusLabels[props.status] ?? statusLabels.aberta,
    color: statusColors[props.status] ?? statusColors.aberta,
}));
</script>

<template>
    <span
        :class="cn('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium')"
        :style="{
            backgroundColor: `${config.color}18`,
            color: config.color,
        }"
    >
        {{ config.label }}
    </span>
</template>
