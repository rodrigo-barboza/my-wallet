<script setup lang="ts">
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'

defineProps<{
    notifyClosing: boolean
    notifyDue: boolean
}>()
</script>

<template>
    <TooltipProvider>
        <div class="flex gap-1">
            <Tooltip>
                <TooltipTrigger as-child>
                    <span
                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                        :class="notifyClosing ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                    >
                        Fechamento
                    </span>
                </TooltipTrigger>
                <TooltipContent>
                    <p>Notificação de fechamento {{ notifyClosing ? 'ativa' : 'inativa' }}</p>
                </TooltipContent>
            </Tooltip>
            <Tooltip>
                <TooltipTrigger as-child>
                    <span
                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                        :class="notifyDue ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                    >
                        Vencimento
                    </span>
                </TooltipTrigger>
                <TooltipContent>
                    <p>Notificação de vencimento {{ notifyDue ? 'ativa' : 'inativa' }}</p>
                </TooltipContent>
            </Tooltip>
        </div>
    </TooltipProvider>
</template>
