<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    status: string;
}>();

const statusConfig: Record<string, { label: string; class: string }> = {
    aberta: {
        label: 'Aberta',
        class: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    },
    fechada: {
        label: 'Fechada',
        class: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300',
    },
    paga: {
        label: 'Paga',
        class: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    },
    parcialmente_paga: {
        label: 'Parcial',
        class: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300',
    },
    atrasada: {
        label: 'Atrasada',
        class: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    },
};

const config = computed(() => statusConfig[props.status] ?? statusConfig.aberta);
</script>

<template>
    <span
        :class="cn(
            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
            config.class,
        )"
    >
        {{ config.label }}
    </span>
</template>
