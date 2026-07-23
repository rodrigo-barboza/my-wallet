<script setup lang="ts">
import type { Card } from '@/types/card';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card as CardComponent, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CreditCard } from '@lucide/vue';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

defineProps<{
    cards: Card[];
}>();

const emit = defineEmits<{
    edit: [card: Card];
    delete: [card: Card];
}>();

function goToCard(card: Card): void {
    router.visit(route('cards.purchases', { card: card.id }));
}
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <CardComponent v-for="card in cards" :key="card.id" class="relative overflow-hidden cursor-pointer transition-colors hover:bg-muted/30" @click="goToCard(card)">
            <div class="absolute inset-x-0 top-0 h-2" :style="{ backgroundColor: card.color }" />
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <CreditCard class="size-5" :style="{ color: card.color }" />
                    {{ card.name }}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Fechamento:</span>
                        <span>Dia {{ card.closing_day }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Vencimento:</span>
                        <span>Dia {{ card.due_day }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Notificações:</span>
                        <TooltipProvider>
                            <div class="flex gap-1">
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <span :class="[
                                            'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                            card.notify_closing ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500',
                                        ]">
                                            Fechamento
                                        </span>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>Notificação de fechamento {{ card.notify_closing ? 'ativa' : 'inativa' }}</p>
                                    </TooltipContent>
                                </Tooltip>
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <span :class="[
                                            'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                            card.notify_due ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500',
                                        ]">
                                            Vencimento
                                        </span>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>Notificação de vencimento {{ card.notify_due ? 'ativa' : 'inativa' }}</p>
                                    </TooltipContent>
                                </Tooltip>
                            </div>
                        </TooltipProvider>
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <Button variant="outline" size="sm" @click.stop="emit('edit', card)">Editar</Button>
                    <Button variant="ghost" size="sm" @click.stop="emit('delete', card)">Excluir</Button>
                </div>
            </CardContent>
        </CardComponent>
    </div>
</template>
