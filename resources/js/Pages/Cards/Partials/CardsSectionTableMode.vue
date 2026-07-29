<script setup lang="ts">
import { computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import type { Card } from '@/types/card'
import { Button } from '@/components/ui/button'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { CreditCard, Trash2 } from '@lucide/vue'
import Checkbox from '@/Components/Checkbox.vue'
import NotificationBadges from '@/Pages/Cards/Partials/NotificationBadges.vue'
import { useTableSort } from '@/composables/useTableSort'

const props = defineProps<{
    cards: Card[];
}>()

const emit = defineEmits<{
    edit: [card: Card]
    delete: [card: Card]
    bulkDelete: [ids: number[]]
}>()

const selectedIds = ref<number[]>([])

const page = usePage()
const initialPrefs = (page.props.preferences as Record<string, any>) ?? {}
const storedSort = initialPrefs.cards_table_sort ?? null

const { sortKey, sortDir, toggleSort, sortIcon } = useTableSort(
    storedSort?.key ?? null,
    storedSort?.dir ?? 'asc',
    'cards_table_sort'
)

const sortedCards = computed(() => {
    if (!sortKey.value) return props.cards

    return [...props.cards].sort((a, b) => {
        const cmp = a.name.localeCompare(b.name)
        return sortDir.value === 'asc' ? cmp : -cmp
    })
})

const allSelected = computed(() =>
    selectedIds.value.length === props.cards.length && props.cards.length > 0
)

function toggleSelectAll(): void {
    selectedIds.value = allSelected.value ? [] : props.cards.map((c) => c.id)
}

function toggleSelect(id: number): void {
    const index = selectedIds.value.indexOf(id)
    selectedIds.value = index === -1
        ? [...selectedIds.value, id]
        : selectedIds.value.filter((_, i) => i !== index)
}

function handleBulkDelete(): void {
    if (selectedIds.value.length === 0) return
    emit('bulkDelete', selectedIds.value)
    selectedIds.value = []
}
</script>

<template>
    <div class="space-y-4">
        <div
            v-if="selectedIds.length > 0"
            class="flex items-center gap-2"
        >
            <span class="text-sm text-muted-foreground">{{ selectedIds.length }} selecionado(s)</span>
            <Button
                variant="destructive"
                size="sm"
                @click="handleBulkDelete"
            >
                <Trash2 class="mr-2 size-4" />
                Excluir selecionados
            </Button>
        </div>

        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead class="w-12">
                        <Checkbox
                            :checked="allSelected"
                            @update:checked="toggleSelectAll"
                        />
                    </TableHead>
                    <TableHead class="w-8 text-xs text-muted-foreground">
                        #
                    </TableHead>
                    <TableHead>Cor</TableHead>
                    <TableHead
                        class="cursor-pointer select-none"
                        @click="toggleSort('name')"
                    >
                        Nome<span class="text-muted-foreground">{{ sortIcon('name') }}</span>
                    </TableHead>
                    <TableHead>Fechamento</TableHead>
                    <TableHead>Vencimento</TableHead>
                    <TableHead>Notificações</TableHead>
                    <TableHead class="text-right">Ações</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow
                    v-for="(card, index) in sortedCards"
                    :key="card.id"
                    class="cursor-pointer"
                    @click="router.visit(route('cards.purchases', { card: card.id }))"
                >
                    <TableCell>
                        <Checkbox
                            :checked="selectedIds.includes(card.id)"
                            @update:checked="toggleSelect(card.id)"
                        />
                    </TableCell>
                    <TableCell class="text-xs text-muted-foreground tabular-nums">
                        {{ index + 1 }}
                    </TableCell>
                    <TableCell>
                        <div class="flex items-center gap-2">
                            <div
                                class="size-4 rounded-full"
                                :style="{ backgroundColor: card.color }"
                            />
                            <CreditCard
                                class="size-4"
                                :style="{ color: card.color }"
                            />
                        </div>
                    </TableCell>
                    <TableCell class="font-medium">
                        {{ card.name }}
                    </TableCell>
                    <TableCell>Dia {{ card.closing_day }}</TableCell>
                    <TableCell>Dia {{ card.due_day }}</TableCell>
                    <TableCell>
                        <NotificationBadges
                            :notify-closing="card.notify_closing"
                            :notify-due="card.notify_due"
                        />
                    </TableCell>
                    <TableCell class="text-right">
                        <Button
                            variant="ghost"
                            size="sm"
                            @click.stop="emit('edit', card)"
                        >
                            Editar
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click.stop="emit('delete', card)"
                        >
                            Excluir
                        </Button>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>
</template>
