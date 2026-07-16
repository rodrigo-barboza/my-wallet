<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import { CheckIcon } from '@lucide/vue';

const props = defineProps<{
    checked?: boolean;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    'update:checked': [value: boolean];
}>();

const isChecked = computed<boolean>(() => props.checked ?? false);

function toggle(): void {
    if (props.disabled) {
        return;
    };

    emit('update:checked', !isChecked.value);
};
</script>

<template>
    <button
        type="button"
        role="checkbox"
        :aria-checked="isChecked"
        :data-state="isChecked ? 'checked' : 'unchecked'"
        :disabled="disabled"
        :class="cn(
            'border-input dark:bg-input/30 peer size-4 shrink-0 rounded-[4px] border transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-50',
            isChecked ? 'bg-primary text-primary-foreground border-primary' : 'bg-transparent',
        )"
        @click="toggle"
    >
        <span class="flex size-4 items-center justify-center" v-if="isChecked">
            <CheckIcon class="size-3.5" />
        </span>
    </button>
</template>
