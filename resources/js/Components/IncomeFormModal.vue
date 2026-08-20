<script setup lang="ts">
import type { IncomeGroup } from '@/types/income';
import ResponsiveModal from '@/Components/ResponsiveModal.vue';
import IncomeForm from '@/Components/IncomeForm.vue';

defineProps<{
    open: boolean;
    groups?: IncomeGroup[];
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
        <IncomeForm
            :groups="groups"
            @success="close"
        />
    </ResponsiveModal>
</template>