<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = withDefaults(defineProps<{
    modelValue: number;
    class?: string;
    id?: string;
}>(), {
    modelValue: 0,
});

const emit = defineEmits<{
    'update:modelValue': [value: number];
}>();

const displayValue = computed({
    get: () => {
        if (!props.modelValue) return '';
        return props.modelValue.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    set: (val: string) => {
        const digits = val.replace(/\D/g, '');
        const value = parseFloat(digits) / 100;
        emit('update:modelValue', isNaN(value) ? 0 : value);
    },
});

function onKeydown(e: KeyboardEvent): void {
    const allowed = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End'];
    if (allowed.includes(e.key)) return;
    if (e.key === 'a' && (e.ctrlKey || e.metaKey)) return;
    if (e.key === 'c' && (e.ctrlKey || e.metaKey)) return;
    if (e.key === 'v' && (e.ctrlKey || e.metaKey)) return;
    if (/^\d$/.test(e.key)) return;
    e.preventDefault();
}

function onFocus(e: FocusEvent): void {
    const input = e.target as HTMLInputElement;
    input.setSelectionRange(input.value.length, input.value.length);
}
</script>

<template>
    <div class="relative">
        <span
            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-sm text-muted-foreground"
        >
            R$
        </span>
        <input
            :id="id"
            v-model="displayValue"
            data-slot="input"
            :class="cn(
                'dark:bg-input/30 border-input focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive dark:aria-invalid:border-destructive/50 disabled:bg-input/50 dark:disabled:bg-input/80 h-8 rounded-lg border bg-transparent py-1 text-base transition-colors file:h-6 file:text-sm file:font-medium focus-visible:ring-3 aria-invalid:ring-3 md:text-sm w-full min-w-0 outline-none file:inline-flex file:border-0 file:bg-transparent file:text-foreground placeholder:text-muted-foreground disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 pl-8',
                props.class,
            )"
            inputmode="decimal"
            placeholder="0,00"
            @keydown="onKeydown"
            @focus="onFocus"
        />
    </div>
</template>
