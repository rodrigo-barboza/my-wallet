<script setup lang="ts">
import { ref } from 'vue'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Eye, EyeOff } from '@lucide/vue'

const model = defineModel();

const props = defineProps<{
    id: string
    label: string
    modelValue: string
    placeholder?: string
    error?: string
}>()

const showPassword = ref(false)
</script>

<template>
    <div class="space-y-2">
        <Label
            class="text-muted-foreground"
            :for="id"
        >
            {{ label }}
        </Label>
        <div class="relative">
            <Input
                v-model="model"
                :id="id"
                :value="modelValue"
                :type="showPassword ? 'text' : 'password'"
                :placeholder="placeholder"
            />
            <button
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                type="button"
                tabindex="-1"
                @click="showPassword = !showPassword"
            >
                <component
                    :is="showPassword ? EyeOff : Eye"
                    class="size-4"
                />
            </button>
        </div>
        <p
            v-if="error"
            class="text-xs text-destructive"
        >
            {{ error }}
        </p>
    </div>
</template>
