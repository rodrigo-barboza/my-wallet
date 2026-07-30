<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import type { IncomeFormData } from '@/types/income';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import CurrencyInput from '@/Components/CurrencyInput.vue';

const emit = defineEmits<{
    success: [];
}>();

const monthNames = [
    'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
];

const currentMonth = new Date().getMonth() + 1;
const currentYear = new Date().getFullYear();

const form = useForm<IncomeFormData>({
    name: '',
    amount: 0,
    start_month: currentMonth,
    start_year: currentYear,
    repeat_count: 1,
});

const monthOptions = monthNames.map((name, i) => ({ value: i + 1, label: name }));

function submit(): void {
    form.post(route('incomes.store'), {
        onSuccess: () => {
            form.reset();
            emit('success');
        },
    });
}
</script>

<template>
    <form @submit.prevent="submit" class="space-y-4">
        <div class="space-y-2">
            <Label for="name">Nome</Label>
            <Input id="name" v-model="form.name" placeholder="Ex: Salário, Fulano" />
            <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
        </div>

        <div class="space-y-2">
            <Label for="amount">Valor</Label>
            <CurrencyInput id="amount" v-model="form.amount" />
            <p v-if="form.errors.amount" class="text-sm text-destructive">{{ form.errors.amount }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
                <Label for="start_month">Mês inicial</Label>
                <Select v-model="form.start_month">
                    <SelectTrigger class="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="m in monthOptions" :key="m.value" :value="m.value">
                            {{ m.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p v-if="form.errors.start_month" class="text-sm text-destructive">{{ form.errors.start_month }}</p>
            </div>

            <div class="space-y-2">
                <Label for="repeat_count">Repetir por</Label>
                <div class="flex items-center gap-2">
                    <Input id="repeat_count" v-model="form.repeat_count" type="number" min="1" max="12" />
                    <span class="text-sm text-muted-foreground">mês(es)</span>
                </div>
                <p v-if="form.errors.repeat_count" class="text-sm text-destructive">{{ form.errors.repeat_count }}</p>
            </div>
        </div>

        <Button type="submit" class="w-full" :disabled="form.processing">
            {{ form.processing ? 'Salvando...' : 'Adicionar' }}
        </Button>
    </form>
</template>
