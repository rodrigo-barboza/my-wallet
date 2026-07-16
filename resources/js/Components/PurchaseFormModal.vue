<script setup lang="ts">
import type { Purchase } from '@/types/purchase';
import type { Card } from '@/types/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import PurchaseForm from '@/Pages/Purchases/Partials/PurchaseForm.vue';

const props = defineProps<{
    open: boolean;
    purchase?: Purchase;
    cards: Card[];
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ purchase ? 'Editar compra' : 'Nova compra' }}</DialogTitle>
                <DialogDescription>
                    {{ purchase ? 'Edite os dados da compra.' : 'Preencha os dados para criar uma nova compra.' }}
                </DialogDescription>
            </DialogHeader>
            <PurchaseForm
                :purchase="purchase"
                :cards="cards"
                @success="emit('update:open', false)"
            />
        </DialogContent>
    </Dialog>
</template>
