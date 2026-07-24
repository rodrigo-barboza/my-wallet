<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Nova entrada</DialogTitle>
                <DialogDescription>Adicione um recebimento mensal</DialogDescription>
            </DialogHeader>
            <IncomeForm @success="close" />
        </DialogContent>
    </Dialog>
</template>
