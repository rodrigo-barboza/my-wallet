<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Card as CardComponent, CardContent } from '@/components/ui/card'
import { CreditCard, LayoutGrid, List, Plus } from '@lucide/vue'
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import { useIsMobile } from '@/composables/useIsMobile'
import { useLocalStorage } from '@/composables/useLocalStorage'
import AppLayout from '@/Layouts/AppLayout.vue'
import CardFormModal from '@/Components/CardFormModal.vue'
import CardsSectionGridMode from '@/Pages/Cards/Partials/CardsSectionGridMode.vue'
import CardsSectionTableMode from '@/Pages/Cards/Partials/CardsSectionTableMode.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import type { Card } from '@/types/card'

defineOptions({ layout: AppLayout })

defineProps<{
    cards: Card[]
}>()

let confirmAction: (() => void) | null = null

const viewModes = [
    { key: 'grid' as const, icon: LayoutGrid, label: 'Visualização em grade' },
    { key: 'table' as const, icon: List, label: 'Visualização em tabela' },
]

const isMobile = useIsMobile()
const rawViewMode = useLocalStorage<'grid' | 'table'>('cards_view_mode', 'table')
const viewMode = computed<'grid' | 'table'>(() => isMobile.value ? 'grid' : rawViewMode.value)

function setViewMode(mode: 'grid' | 'table'): void {
    rawViewMode.value = mode
}

const showModal = ref(false)
const editingCard = ref<Card | null>(null)
const showConfirm = ref(false)
const confirmTitle = ref('')
const confirmDescription = ref('')

function openCreateModal(): void {
    editingCard.value = null
    showModal.value = true
}

function openEditModal(card: Card): void {
    editingCard.value = card
    showModal.value = true
}

function openConfirm(title: string, description: string, action: () => void): void {
    confirmTitle.value = title
    confirmDescription.value = description
    confirmAction = action
    showConfirm.value = true
}

function handleConfirm(): void {
    confirmAction?.()
    showConfirm.value = false
    confirmAction = null
}

function handleDelete(card: Card): void {
    openConfirm('Excluir cartão', `Deseja realmente excluir o cartão "${card.name}"?`, () => router.delete(route('cards.destroy', card.id)))
}

function handleBulkDelete(ids: number[]): void {
    openConfirm('Excluir cartões', `Deseja realmente excluir ${ids.length} cartão(s)?`, () => router.post(route('cards.bulk-destroy'), { ids }))
}
</script>

<template>
    <div class="w-full space-y-6">
        <Head title="My Wallet - Cartões" />

        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold">Cartões</h2>
            <div id="onboarding-cards-viewmode" class="flex items-center gap-2">
                <TooltipProvider v-if="!isMobile">
                    <Tooltip
                        v-for="mode in viewModes"
                        :key="mode.key"
                    >
                        <TooltipTrigger as-child>
                            <Button
                                :class="viewMode === mode.key ? 'bg-primary text-primary-foreground' : ''"
                                variant="outline"
                                size="icon"
                                @click="setViewMode(mode.key)"
                            >
                                <component
                                    :is="mode.icon"
                                    class="size-4"
                                />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p>{{ mode.label }}</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
                <Button id="onboarding-cards-add" @click="openCreateModal">
                    <Plus class="mr-2 size-4" />
                    Novo cartão
                </Button>
            </div>
        </div>

        <CardComponent
            v-if="cards.length === 0"
            class="mx-auto max-w-md"
        >
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

        <CardsSectionGridMode
            v-else-if="viewMode === 'grid'"
            id="onboarding-cards-list"
            :cards="cards"
            @delete="handleDelete"
            @edit="openEditModal"
        />

        <CardsSectionTableMode
            v-else
            id="onboarding-cards-list"
            :cards="cards"
            @bulk-delete="handleBulkDelete"
            @delete="handleDelete"
            @edit="openEditModal"
        />

        <CardFormModal
            :open="showModal"
            :card="editingCard"
            @update:open="showModal = $event"
        />

        <ConfirmDialog
            :open="showConfirm"
            :title="confirmTitle"
            :description="confirmDescription"
            @update:open="showConfirm = $event"
            @confirm="handleConfirm"
        />
    </div>
</template>
