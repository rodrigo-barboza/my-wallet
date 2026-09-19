<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Check, Eye, EyeOff } from '@lucide/vue'
import { useWallet } from '@/composables/useWallet'
import { formatCurrency } from '@/lib/format'

const props = withDefaults(defineProps<{
    editable?: boolean
    variant?: 'navbar' | 'block'
}>(), {
    editable: false,
    variant: 'navbar',
})

const {
    walletBalance,
    walletHidden,
    editingWallet,
    walletInput,
    toggleWalletHidden,
    startEditWallet,
    saveWallet,
} = useWallet()
</script>

<template>
    <div v-if="variant === 'navbar'" class="flex items-end gap-1.5">
        <div class="flex flex-col items-end leading-none">
            <span class="text-[10px] font-medium text-muted-foreground">Saldo atual</span>
            <div v-if="editingWallet" class="flex items-center">
                <Input
                    v-model="walletInput"
                    type="text"
                    inputmode="decimal"
                    class="h-8 w-32 text-right text-sm font-semibold tabular-nums sm:w-40"
                    @keydown.enter="saveWallet"
                    @keydown.esc="editingWallet = false"
                    @blur="saveWallet"
                />
                <Button
                    variant="ghost"
                    size="icon"
                    class="size-7"
                    @mousedown.prevent="saveWallet"
                >
                    <Check class="size-4" />
                </Button>
            </div>
            <button
                v-else-if="editable"
                type="button"
                class="mt-0.5 inline-block w-32 truncate text-right text-base font-bold tabular-nums cursor-pointer hover:text-primary sm:w-40"
                @click="startEditWallet"
            >
                {{ walletHidden ? '••••' : formatCurrency(walletBalance) }}
            </button>
            <span
                v-else
                class="mt-0.5 inline-block w-32 truncate text-right text-base font-bold tabular-nums sm:w-40"
            >
                {{ walletHidden ? '••••' : formatCurrency(walletBalance) }}
            </span>
        </div>
        <Button
            variant="ghost"
            size="icon"
            class="size-7 shrink-0"
            @click="toggleWalletHidden"
        >
            <EyeOff v-if="walletHidden" class="size-[18px]" :stroke-width="2.5" />
            <Eye v-else class="size-[18px]" :stroke-width="2.5" />
        </Button>
    </div>

    <div v-else class="flex items-center justify-between gap-3 rounded-lg border bg-muted/30 px-3 py-2.5">
        <div class="min-w-0">
            <div class="text-[10px] font-medium text-muted-foreground">Saldo atual</div>
            <div v-if="editingWallet" class="flex items-center">
                <Input
                    v-model="walletInput"
                    type="text"
                    inputmode="decimal"
                    class="h-8 w-28 text-right text-sm font-semibold tabular-nums"
                    @keydown.enter="saveWallet"
                    @keydown.esc="editingWallet = false"
                    @blur="saveWallet"
                />
                <Button
                    variant="ghost"
                    size="icon"
                    class="size-7"
                    @mousedown.prevent="saveWallet"
                >
                    <Check class="size-4" />
                </Button>
            </div>
            <button
                v-else-if="editable"
                type="button"
                class="mt-0.5 block w-full truncate text-lg font-bold tabular-nums cursor-pointer hover:text-primary"
                @click="startEditWallet"
            >
                {{ walletHidden ? '••••' : formatCurrency(walletBalance) }}
            </button>
            <span
                v-else
                class="mt-0.5 block w-full truncate text-lg font-bold tabular-nums"
            >
                {{ walletHidden ? '••••' : formatCurrency(walletBalance) }}
            </span>
        </div>
        <Button
            variant="ghost"
            size="icon"
            class="size-8 shrink-0"
            @click="toggleWalletHidden"
        >
            <EyeOff v-if="walletHidden" class="size-5" />
            <Eye v-else class="size-5" />
        </Button>
    </div>
</template>