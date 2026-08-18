<script setup lang="ts">
import { Button } from '@/components/ui/button';
import ResponsiveModal from '@/Components/ResponsiveModal.vue';

defineProps<{
    open: boolean;
    title: string;
    description: string;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [];
}>();
</script>

<template>
    <ResponsiveModal
        :open="open"
        :title="title"
        :description="description"
        @update:open="emit('update:open', $event)"
    >
        <template #footer>
            <div class="flex gap-2">
                <Button variant="outline" class="flex-1" @click="emit('update:open', false)">Cancelar</Button>
                <Button variant="destructive" class="flex-1" @click="emit('confirm')">Confirmar</Button>
            </div>
        </template>
    </ResponsiveModal>
</template>