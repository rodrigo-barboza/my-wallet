<script setup lang="ts">
import { ref, watch } from 'vue';
import type { Card } from '@/types/card';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card as CardComponent, CardContent } from '@/components/ui/card';
import { CreditCard, Plus, LayoutGrid, List } from '@lucide/vue';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import CardsSectionGridMode from './Partials/CardsSectionGridMode.vue';
import CardsSectionTableMode from './Partials/CardsSectionTableMode.vue';
import CardFormModal from '@/Components/CardFormModal.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    cards: Card[];
}>();

const storedViewMode = localStorage.getItem('cards_view_mode') as 'grid' | 'table' | null;
const viewMode = ref<'grid' | 'table'>(storedViewMode ?? 'table');
watch(viewMode, (mode) => localStorage.setItem('cards_view_mode', mode));
const showModal = ref<boolean>(false);
const editingCard = ref<Card | null>(null);

const showConfirm = ref<boolean>(false);
const confirmTitle = ref<string>('');
const confirmDescription = ref<string>('');
let confirmAction: (() => void) | null = null;

function openCreateModal(): void {
    editingCard.value = null;
    showModal.value = true;
};

function openEditModal(card: Card): void {
    editingCard.value = card;
    showModal.value = true;
};

function openConfirm(title: string, description: string, action: () => void): void {
    confirmTitle.value = title;
    confirmDescription.value = description;
    confirmAction = action;
    showConfirm.value = true;
};

function handleConfirm(): void {
    confirmAction?.();
    showConfirm.value = false;
    confirmAction = null;
};

function handleDelete(card: Card): void {
    openConfirm(
        'Excluir cartão',
        `Deseja realmente excluir o cartão "${card.name}"?`,
        () => router.delete(route('cards.destroy', card)),
    );
};

function handleBulkDelete(ids: number[]): void {
    openConfirm(
        'Excluir cartões',
        `Deseja realmente excluir ${ids.length} cartão(s)?`,
        () => router.post(route('cards.bulk-destroy'), { ids }),
    );
};
</script>

<template>
    <div class="w-full space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold">Cartões</h2>
            <div class="flex items-center gap-2">
                <TooltipProvider>
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button variant="outline" size="icon"
                                :class="{ 'bg-primary text-primary-foreground': viewMode === 'grid' }" @click="viewMode = 'grid'">
                                <LayoutGrid class="size-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>Visualização em grade</p>
                        </TooltipContent>
                    </Tooltip>
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button variant="outline" size="icon"
                                :class="{ 'bg-primary text-primary-foreground': viewMode === 'table' }" @click="viewMode = 'table'">
                                <List class="size-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>Visualização em tabela</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
                <Button @click="openCreateModal">
                    <Plus class="mr-2 size-4" />
                    Novo cartão
                </Button>
            </div>
        </div>

        <CardComponent v-if="cards.length === 0" class="mx-auto max-w-md">
            <CardContent class="flex flex-col items-center justify-center py-12 text-center">
                <div class="mb-4 flex size-16 items-center justify-center rounded-full bg-muted">
                    <CreditCard class="size-8 text-muted-foreground" />
                </div>
                <h3 class="mb-2 text-lg font-semibold">Nenhum cartão cadastrado</h3>
                <p class="mb-6 text-sm text-muted-foreground">
                    Cadastre seus cartões de crédito para acompanhar faturas e receber lembretes de pagamento.
                </p>
                <Button @click="openCreateModal">
                    <Plus class="mr-2 size-4" />
                    Criar primeiro cartão
                </Button>
            </CardContent>
        </CardComponent>

        <CardsSectionGridMode v-else-if="viewMode === 'grid'" :cards="cards" @edit="openEditModal"
            @delete="handleDelete" />

        <CardsSectionTableMode v-else :cards="cards" @edit="openEditModal" @delete="handleDelete"
            @bulk-delete="handleBulkDelete" />

        <CardFormModal :open="showModal" :card="editingCard" @update:open="showModal = $event" />

        <ConfirmDialog :open="showConfirm" :title="confirmTitle" :description="confirmDescription"
            @update:open="showConfirm = $event" @confirm="handleConfirm" />
    </div>
</template>
