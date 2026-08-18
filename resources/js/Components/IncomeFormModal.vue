<script setup lang="ts">
import ResponsiveModal from '@/Components/ResponsiveModal.vue';
import IncomeForm from '@/Components/IncomeForm.vue';

defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

function close(): void {
    emit('update:open', false);
}
</script>

<template>
    <ResponsiveModal
        :open="open"
        title="Nova entrada"
        description="Adicione um recebimento mensal"
        @update:open="emit('update:open', $event)"
    >
        <IncomeForm @success="close" />
    </ResponsiveModal>
</template>