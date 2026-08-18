<script setup lang="ts">
import type { Purchase } from '@/types/purchase';
import type { Card } from '@/types/card';
import ResponsiveModal from '@/Components/ResponsiveModal.vue';
import PurchaseForm from '@/Pages/Purchases/Partials/PurchaseForm.vue';

const props = defineProps<{
    open: boolean;
    purchase?: Purchase;
    cards: Card[];
    defaultCardId?: number | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();
</script>

<template>
    <ResponsiveModal
        :open="open"
        :title="purchase ? 'Editar compra' : 'Nova compra'"
        :description="purchase ? 'Edite os dados da compra.' : 'Preencha os dados para criar uma nova compra.'"
        @update:open="emit('update:open', $event)"
    >
        <PurchaseForm
            :purchase="purchase"
            :cards="cards"
            :default-card-id="defaultCardId"
            @success="emit('update:open', false)"
        />
    </ResponsiveModal>
</template>