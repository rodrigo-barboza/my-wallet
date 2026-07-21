<script setup lang="ts">
import { type Ref, ref, watch } from 'vue';
import { CalendarDate, parseDate, getLocalTimeZone, today } from '@internationalized/date';
import { CalendarIcon } from '@lucide/vue';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';

const props = withDefaults(defineProps<{
    modelValue?: string;
    class?: string;
    id?: string;
}>(), {
    modelValue: '',
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const defaultPlaceholder = today(getLocalTimeZone());

const date = ref<CalendarDate | undefined>(
    props.modelValue ? parseDate(props.modelValue) : undefined,
) as Ref<CalendarDate | undefined>;

watch(date, (val) => {
    emit('update:modelValue', val ? val.toString() : '');
});

watch(() => props.modelValue, (val) => {
    if (!val) {
        date.value = undefined;
    } else if (!date.value || val !== date.value.toString()) {
        date.value = parseDate(val);
    }
});

function formatDate(val: CalendarDate | undefined): string {
    if (!val) return '';
    return val.toDate(getLocalTimeZone()).toLocaleDateString('pt-BR');
}
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                :id="id"
                :class="cn(
                    'w-full justify-start text-left font-normal cursor-pointer',
                    !date && 'text-muted-foreground',
                    props.class,
                )"
            >
                <CalendarIcon class="mr-2 size-4 shrink-0" />
                {{ date ? formatDate(date) : 'Selecione uma data' }}
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0" align="start">
            <Calendar
                v-model="date"
                locale="pt-BR"
                :default-placeholder="defaultPlaceholder"
                layout="month-and-year"
                initial-focus
            />
        </PopoverContent>
    </Popover>
</template>
