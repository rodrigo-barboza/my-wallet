<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import type { Card } from '@/types/card';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Toggle from '@/Components/Toggle.vue';

const props = defineProps<{
    open: boolean;
    card?: Card | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const predefinedColors: string[] = [
    '#8B5CF6', '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#EC4899',
    '#6366F1', '#14B8A6', '#F97316', '#84CC16', '#06B6D4', '#D946EF',
];

const form = useForm({
    name: '',
    color: '#8B5CF6',
    closing_day: 15,
    due_day: 25,
    notify_closing: true,
    notify_due: true,
});

const showCustomColor = ref<boolean>(false);
const customColor = ref<string>('#8B5CF6');

watch(() => props.open, (isOpen) => {
    showCustomColor.value = false;

    if (!isOpen || !props.card) {
        form.reset();
        return;
    };

    form.name = props.card.name;
    form.color = props.card.color;
    form.closing_day = props.card.closing_day;
    form.due_day = props.card.due_day;
    form.notify_closing = props.card.notify_closing;
    form.notify_due = props.card.notify_due;

    if (!predefinedColors.includes(props.card.color)) {
        showCustomColor.value = true;
        customColor.value = props.card.color;
    };
});

function submit(): void {
    if (props.card?.id) {
        form.put(route('cards.update', props.card.id), {
            onSuccess: () => emit('update:open', false),
        });
        return;
    };

    form.post(route('cards.store'), {
        onSuccess: () => emit('update:open', false),
    });
};

function selectColor(color: string): void {
    form.color = color;
    showCustomColor.value = false;
};

function selectCustomColor(): void {
    showCustomColor.value = true;
    form.color = customColor.value;
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ card ? 'Editar cartão' : 'Novo cartão' }}</DialogTitle>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="name">Nome do cartão</Label>
                    <Input id="name" v-model="form.name" placeholder="Ex: Nubank" />
                    <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                </div>

                <div class="space-y-2">
                    <Label>Cor</Label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="color in predefinedColors"
                            :key="color"
                            type="button"
                            class="size-8 rounded-full border-2 transition-all"
                            :class="form.color === color ? 'border-foreground scale-110' : 'border-transparent'"
                            :style="{ backgroundColor: color }"
                            @click="selectColor(color)"
                        />
                        <button
                            type="button"
                            class="size-8 rounded-full border-2 border-dashed border-muted-foreground transition-all"
                            :class="{ 'border-foreground scale-110': showCustomColor }"
                            @click="selectCustomColor"
                        >
                            <span class="text-xs">+</span>
                        </button>
                    </div>
                    <div v-if="showCustomColor" class="mt-2 flex items-center gap-2">
                        <input type="color" v-model="customColor" @input="form.color = customColor" class="size-8 cursor-pointer" />
                        <Input v-model="form.color" placeholder="#000000" class="w-32" />
                    </div>
                    <p v-if="form.errors.color" class="text-xs text-destructive">{{ form.errors.color }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="closing_day">Dia de fechamento</Label>
                        <Input id="closing_day" v-model="form.closing_day" type="number" min="1" max="31" />
                        <p v-if="form.errors.closing_day" class="text-xs text-destructive">{{ form.errors.closing_day }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="due_day">Dia de vencimento</Label>
                        <Input id="due_day" v-model="form.due_day" type="number" min="1" max="31" />
                        <p v-if="form.errors.due_day" class="text-xs text-destructive">{{ form.errors.due_day }}</p>
                    </div>
                </div>

                <div class="space-y-3">
                    <Label>Notificações por e-mail</Label>
                    <div class="flex items-center justify-between">
                        <Label for="notify_closing" class="text-sm text-muted-foreground">Lembrete de fechamento</Label>
                        <Toggle id="notify_closing" :checked="form.notify_closing" @update:checked="form.notify_closing = $event" />
                    </div>
                    <div class="flex items-center justify-between">
                        <Label for="notify_due" class="text-sm text-muted-foreground">Lembrete de vencimento</Label>
                        <Toggle id="notify_due" :checked="form.notify_due" @update:checked="form.notify_due = $event" />
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="emit('update:open', false)">Cancelar</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Salvando…' : 'Salvar' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
