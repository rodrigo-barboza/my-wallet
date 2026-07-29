<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import { Check } from '@lucide/vue';

const props = defineProps<{
    checked?: boolean;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    'update:checked': [value: boolean];
}>();

const isChecked = computed<boolean>(() => props.checked ?? false);

function toggle(): void {
    if (props.disabled) return;
    emit('update:checked', !isChecked.value);
}
</script>

<template>
    <button
        type="button"
        role="checkbox"
        :aria-checked="isChecked"
        :data-state="isChecked ? 'checked' : 'unchecked'"
        :disabled="disabled"
        :class="cn(
            'peer size-4 shrink-0 cursor-pointer rounded-[4px] border border-primary ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
            isChecked ? 'bg-primary text-primary-foreground' : 'bg-background',
        )"
        @click="toggle"
    >
        <span class="flex items-center justify-center text-current">
            <Check v-if="isChecked" class="size-3" />
        </span>
    </button>
</template>
