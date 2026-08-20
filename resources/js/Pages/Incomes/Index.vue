<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ChevronLeft, ChevronRight, Copy, FolderPlus, Plus, Search, Trash2, X } from '@lucide/vue'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import AppLayout from '@/Layouts/AppLayout.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import IncomeFormModal from '@/Components/IncomeFormModal.vue'
import IncomesCardMode from '@/Pages/Incomes/Partials/IncomesCardMode.vue'
import IncomesTableMode from '@/Pages/Incomes/Partials/IncomesTableMode.vue'
import ResponsiveModal from '@/Components/ResponsiveModal.vue'
import { useIsMobile } from '@/composables/useIsMobile'
import { formatCurrency } from '@/lib/format'
import { monthAbbrs, monthNames } from '@/lib/constants'
import type { Income, IncomeEditingCell, IncomeEditingName, IncomeGroup } from '@/types/income'

defineOptions({ layout: AppLayout })

const props = defineProps<{
    incomes: Income[]
    groups: IncomeGroup[]
    year: number
}>()

const actionButtons = [
    { key: 'duplicate' as const, label: 'Duplicar entrada', icon: Copy, handler: (income: Income) => router.post(route('incomes.duplicate', income.id), { preserveScroll: true }) },
    { key: 'delete' as const, label: 'Excluir entrada', icon: Trash2, handler: (income: Income) => { deletingIncome.value = income; showDeleteDialog.value = true }, color: 'text-destructive' },
]

const isMobile = useIsMobile()
const centerMonth = ref(new Date().getMonth() + 1)
const centerYear = ref(props.year)
const sortAsc = ref(true)
const searchQuery = ref('')
const showFormModal = ref(false)
const showDeleteDialog = ref(false)
const deletingIncome = ref<Income | undefined>()
const editingCell = ref<IncomeEditingCell | null>(null)
const editingValue = ref('')
const editingName = ref<IncomeEditingName | null>(null)
const fillDialog = ref<{ incomeId: number; month: number; year: number; amount: number; open: boolean }>({
    incomeId: 0, month: 0, year: 0, amount: 0, open: false,
})
const fillCount = ref(1)
const collapsedGroups = ref<Set<number>>(new Set())
const showGroupModal = ref(false)
const groupModalMode = ref<'create' | 'attach'>('create')
const groupModalName = ref('')
const groupModalGroupId = ref<number | null>(null)
const renameTarget = ref<IncomeGroup | null>(null)
const renameModalOpen = ref(false)
const renameName = ref('')
const deleteTarget = ref<IncomeGroup | null>(null)

const visibleMonths = computed(() => {
    const months: { month: number; year: number; label: string }[] = []
    for (let i = -3; i <= 3; i++) {
        let m = centerMonth.value + i
        let y = centerYear.value
        if (m < 1) { m += 12; y-- }
        if (m > 12) { m -= 12; y++ }
        months.push({ month: m, year: y, label: monthAbbrs[m - 1] })
    }
    return months
})

const sortedIncomes = computed(() => {
    const sorted = [...props.incomes]
    sorted.sort((a, b) => a.name.localeCompare(b.name))
    if (!sortAsc.value) sorted.reverse()
    return sorted
})

const displayedIncomes = computed(() => {
    if (!searchQuery.value) return sortedIncomes.value
    const query = searchQuery.value.toLowerCase()
    return sortedIncomes.value.filter(income => income.name.toLowerCase().includes(query))
})

const totals = computed(() => {
    return visibleMonths.value.map(m => {
        let total = 0
        for (const income of props.incomes) {
            const val = income.months[m.year]?.[m.month]?.amount ?? 0
            total += val
        }
        return total
    })
})

function getAmount(income: Income, month: number, year: number): number | null {
    return income.months[year]?.[month]?.amount ?? null
}

function getMonthId(income: Income, month: number, year: number): number | null {
    return income.months[year]?.[month]?.id ?? null
}

function startEdit(income: Income, month: number, year: number): void {
    const val = getAmount(income, month, year)
    editingCell.value = { incomeId: income.id, month, year }
    editingValue.value = val !== null ? String(val) : ''
}

function saveCell(): void {
    if (!editingCell.value) return
    const { incomeId, month, year } = editingCell.value
    const income = props.incomes.find(i => i.id === incomeId)
    if (!income) return

    const monthId = getMonthId(income, month, year)
    const amount = parseFloat(editingValue.value.replace(',', '.'))

    if (isNaN(amount)) {
        editingCell.value = null
        return
    }

    const options = {
        preserveScroll: true,
        onSuccess: () => { editingCell.value = null },
    }

    if (monthId !== null) {
        router.patch(route('incomes.update-month', monthId), { amount }, options)
        return
    }

    router.post(route('incomes.store-month', income.id), { month, year, amount }, options)
}

function cancelEdit(): void {
    editingCell.value = null
}

function delayedCancelEdit(): void {
    setTimeout(() => {
        if (editingCell.value) {
            editingCell.value = null
        }
    }, 150)
}

function startEditName(income: Income): void {
    editingName.value = { incomeId: income.id, value: income.name }
}

function saveName(): void {
    if (!editingName.value) return
    const income = props.incomes.find(i => i.id === editingName.value!.incomeId)
    if (!income) return

    router.put(route('incomes.update', income.id), { name: editingName.value.value }, {
        preserveScroll: true,
        onSuccess: () => { editingName.value = null },
    })
}

function cancelEditName(): void {
    editingName.value = null
}

function openFill(income: Income, month: number, year: number): void {
    const val = getAmount(income, month, year)
    if (val === null) return
    fillDialog.value = { incomeId: income.id, month, year, amount: val, open: true }
    fillCount.value = 1
}

function confirmFill(): void {
    const { incomeId, month, year, amount } = fillDialog.value
    const income = props.incomes.find(i => i.id === incomeId)
    if (!income) return

    router.post(route('incomes.fill-months', income.id), {
        start_month: month, start_year: year, repeat_count: fillCount.value, amount,
    }, {
        preserveScroll: true,
        onSuccess: () => { fillDialog.value.open = false },
    })
}

function confirmDelete(): void {
    if (!deletingIncome.value) return
    router.delete(route('incomes.destroy', deletingIncome.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteDialog.value = false
            deletingIncome.value = undefined
        },
    })
}

function handleAction(income: Income, action: 'duplicate' | 'delete'): void {
    const btn = actionButtons.find(b => b.key === action)
    btn?.handler(income)
}

function updateEditingNameValue(value: string): void {
    if (!editingName.value) return
    editingName.value.value = value
}

const selectedIds = ref<Set<number>>(new Set())

function toggleSelect(id: number): void {
    const next = new Set(selectedIds.value)
    if (next.has(id)) {
        next.delete(id)
    } else {
        next.add(id)
    }
    selectedIds.value = next
}

function selectAll(): void {
    if (selectedIds.value.size === displayedIncomes.value.length) {
        selectedIds.value = new Set()
    } else {
        selectedIds.value = new Set(displayedIncomes.value.map(i => i.id))
    }
}

const selectedTotal = computed(() => {
    let total = 0
    for (const income of props.incomes) {
        if (!selectedIds.value.has(income.id)) continue
        total += income.months[centerYear.value]?.[centerMonth.value]?.amount ?? 0
    }
    return total
})

function previousMonth(): void {
    centerMonth.value--
    if (centerMonth.value < 1) {
        centerMonth.value = 12
        centerYear.value--
    }
}

function nextMonth(): void {
    centerMonth.value++
    if (centerMonth.value > 12) {
        centerMonth.value = 1
        centerYear.value++
    }
}

function toggleGroupCollapse(id: number): void {
    const next = new Set(collapsedGroups.value)
    if (next.has(id)) {
        next.delete(id)
    } else {
        next.add(id)
    }
    collapsedGroups.value = next
}

function saveGroupsCollapsedPreference(collapsed: boolean): void {
    fetch(route('preferences.update'), {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            key: 'income_groups_collapsed',
            value: collapsed,
        }),
    })
}

function collapseAllGroups(): void {
    collapsedGroups.value = new Set(props.groups.map(g => g.id))
    saveGroupsCollapsedPreference(true)
}

function expandAllGroups(): void {
    collapsedGroups.value = new Set()
    saveGroupsCollapsedPreference(false)
}

onMounted(() => {
    const prefs = (usePage().props.preferences as Record<string, any>) ?? {}
    if (prefs.income_groups_collapsed) {
        collapsedGroups.value = new Set(props.groups.map(g => g.id))
    }
})

function openGroupModal(mode: 'create' | 'attach'): void {
    groupModalMode.value = mode
    groupModalName.value = ''
    groupModalGroupId.value = null
    showGroupModal.value = true
}

function clearSelection(): void {
    selectedIds.value = new Set()
    showGroupModal.value = false
}

function submitGroup(): void {
    const incomeIds = Array.from(selectedIds.value)

    if (groupModalMode.value === 'create') {
        router.post(route('incomes.groups.store'), {
            name: groupModalName.value,
            income_ids: incomeIds,
        }, {
            onSuccess: clearSelection,
        })
        return
    }

    if (groupModalGroupId.value !== null) {
        router.post(route('incomes.groups.attach', groupModalGroupId.value), {
            income_ids: incomeIds,
        }, {
            onSuccess: clearSelection,
        })
    }
}

function openRenameGroup(group: IncomeGroup): void {
    renameTarget.value = group
    renameName.value = group.name
    renameModalOpen.value = true
}

function submitRenameGroup(): void {
    if (!renameTarget.value) return
    router.patch(route('incomes.groups.update', renameTarget.value.id), {
        name: renameName.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            renameTarget.value = null
            renameModalOpen.value = false
        },
    })
}

function closeRenameGroup(): void {
    renameTarget.value = null
    renameModalOpen.value = false
}

function openDeleteGroup(group: IncomeGroup): void {
    deleteTarget.value = group
}

function submitDeleteGroup(): void {
    if (!deleteTarget.value) return
    router.delete(route('incomes.groups.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => { deleteTarget.value = null },
    })
}

function detachIncome(income: Income): void {
    router.delete(route('incomes.group-detach', income.id), {
        preserveScroll: true,
    })
}
</script>

<template>
    <div class="w-full space-y-6">
        <Head title="My Wallet - Entradas" />

        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold">Entradas</h2>
            <div class="flex items-center gap-2">
                <Button id="onboarding-incomes-add" @click="showFormModal = true">
                    <Plus class="mr-2 size-4" />
                    Nova entrada
                </Button>
            </div>
        </div>

        <div id="onboarding-incomes-month" class="flex items-center justify-center gap-4">
            <Button
                variant="outline"
                size="icon"
                @click="previousMonth"
            >
                <ChevronLeft class="size-4" />
            </Button>
            <div v-if="!isMobile" class="text-sm text-muted-foreground">
                <span class="text-base font-semibold text-foreground">{{ monthAbbrs[visibleMonths[0].month - 1] }}/{{ visibleMonths[0].year }}</span>
                <span class="mx-1">até</span>
                <span class="text-base font-semibold text-foreground">{{ monthAbbrs[visibleMonths[6].month - 1] }}/{{ visibleMonths[6].year }}</span>
            </div>
            <div v-else class="text-base font-semibold text-foreground">
                {{ monthNames[centerMonth - 1] }} {{ centerYear }}
            </div>
            <Button
                variant="outline"
                size="icon"
                @click="nextMonth"
            >
                <ChevronRight class="size-4" />
            </Button>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-5 text-muted-foreground" />
                <Input
                    v-model="searchQuery"
                    placeholder="Buscar entrada..."
                    class="h-11 pl-10 text-base"
                />
            </div>
            <Button
                id="onboarding-incomes-groups"
                variant="outline"
                class="h-11 shrink-0"
                @click="collapsedGroups.size > 0 ? expandAllGroups() : collapseAllGroups()"
            >
                {{ collapsedGroups.size > 0 ? 'Abrir todos' : 'Fechar todos' }}
            </Button>
        </div>

        <div
            v-if="selectedIds.size > 0"
            class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2"
        >
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm text-muted-foreground">{{ selectedIds.size }} selecionado(s)</span>
                <Button
                    variant="outline"
                    size="sm"
                    @click="openGroupModal('create')"
                >
                    <FolderPlus class="mr-2 size-4" />
                    Agrupar
                </Button>
                <Button
                    variant="ghost"
                    size="sm"
                    @click="selectedIds = new Set()"
                >
                    <X class="mr-2 size-4" />
                    Limpar seleção
                </Button>
            </div>
            <span class="text-sm text-muted-foreground">
                Total {{ monthAbbrs[centerMonth - 1] }}/{{ centerYear }}
                <span class="font-semibold text-foreground">
                    {{ formatCurrency(selectedTotal) }}
                </span>
            </span>
        </div>

        <IncomesTableMode
            v-if="!isMobile"
            :incomes="displayedIncomes"
            :groups="groups"
            :collapsed-groups="collapsedGroups"
            :visible-months="visibleMonths"
            :totals="totals"
            :selected-ids="selectedIds"
            :center-month="centerMonth"
            :center-year="centerYear"
            :sort-asc="sortAsc"
            :editing-cell="editingCell"
            :editing-value="editingValue"
            :editing-name="editingName"
            :action-buttons="actionButtons"
            @toggle-select="toggleSelect"
            @select-all="selectAll"
            @sort-toggle="sortAsc = !sortAsc"
            @start-edit="startEdit"
            @save-cell="saveCell"
            @cancel-edit="cancelEdit"
            @delayed-cancel-edit="delayedCancelEdit"
            @start-edit-name="startEditName"
            @save-name="saveName"
            @cancel-edit-name="cancelEditName"
            @open-fill="openFill"
            @action="handleAction"
            @update:editing-value="editingValue = $event"
            @update:editing-name-value="updateEditingNameValue"
            @toggle-group-collapse="toggleGroupCollapse"
            @rename-group="openRenameGroup"
            @delete-group="openDeleteGroup"
            @detach-income="detachIncome"
        />

        <IncomesCardMode
            v-else
            :incomes="displayedIncomes"
            :groups="groups"
            :collapsed-groups="collapsedGroups"
            :selected-ids="selectedIds"
            :center-month="centerMonth"
            :center-year="centerYear"
            :editing-cell="editingCell"
            :editing-value="editingValue"
            :editing-name="editingName"
            :action-buttons="actionButtons"
            @toggle-select="toggleSelect"
            @start-edit="startEdit"
            @save-cell="saveCell"
            @cancel-edit="cancelEdit"
            @delayed-cancel-edit="delayedCancelEdit"
            @start-edit-name="startEditName"
            @save-name="saveName"
            @cancel-edit-name="cancelEditName"
            @open-fill="openFill"
            @action="handleAction"
            @update:editing-value="editingValue = $event"
            @update:editing-name-value="updateEditingNameValue"
            @toggle-group-collapse="toggleGroupCollapse"
            @rename-group="openRenameGroup"
            @delete-group="openDeleteGroup"
            @detach-income="detachIncome"
        />

        <div
            v-if="!isMobile && props.incomes.length > 0"
            class="text-sm text-muted-foreground text-center"
        >
            Clique em uma célula para editar o valor. Use o ícone
            <span class="font-bold text-xs">→</span>
            para preencher os próximos meses com o mesmo valor.
        </div>

        <IncomeFormModal
            :open="showFormModal"
            :groups="groups"
            @update:open="showFormModal = $event"
        />

        <ConfirmDialog
            v-model:open="showDeleteDialog"
            title="Excluir entrada"
            description="Tem certeza que deseja excluir esta entrada? Esta ação não pode ser desfeita."
            confirm-text="Excluir"
            @confirm="confirmDelete"
        />
    </div>

    <ResponsiveModal
        :open="fillDialog.open"
        title="Preencher meses"
        @update:open="fillDialog.open = $event"
    >
        <div class="space-y-4">
            <p class="text-sm text-muted-foreground">
                Preencher <span class="font-semibold">{{ formatCurrency(fillDialog.amount) }}</span> a partir de
                {{ monthAbbrs[fillDialog.month - 1] }}/{{ fillDialog.year }} por quantos meses?
            </p>
            <div class="flex items-center gap-2">
                <Input
                    v-model="fillCount"
                    type="number"
                    min="1"
                    max="12"
                    class="w-20"
                />
                <span class="text-sm text-muted-foreground">mês(es)</span>
            </div>
            <Button
                class="w-full"
                @click="confirmFill"
            >
                Preencher
            </Button>
        </div>
    </ResponsiveModal>

    <ResponsiveModal
        :open="showGroupModal"
        :title="groupModalMode === 'create' ? 'Criar grupo' : 'Adicionar ao grupo'"
        @update:open="showGroupModal = $event"
    >
        <div class="space-y-4">
            <div v-if="groupModalMode === 'create'" class="space-y-2">
                <Label for="group_name">Nome do grupo</Label>
                <Input
                    id="group_name"
                    v-model="groupModalName"
                    placeholder="Ex: Renda fixa, Projetos..."
                />
            </div>
            <div v-else class="space-y-2">
                <Label for="group_select">Grupo</Label>
                <Select v-model="groupModalGroupId">
                    <SelectTrigger class="w-full">
                        <SelectValue placeholder="Selecione um grupo" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="g in groups"
                            :key="g.id"
                            :value="g.id"
                        >
                            {{ g.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <p class="text-sm text-muted-foreground">
                {{ selectedIds.size }} {{ selectedIds.size === 1 ? 'item selecionado' : 'itens selecionados' }}
            </p>
        </div>
        <template #footer>
            <div class="flex w-full gap-2">
                <Button
                    variant="outline"
                    class="flex-1"
                    @click="showGroupModal = false"
                >
                    Cancelar
                </Button>
                <Button
                    class="flex-1"
                    :disabled="groupModalMode === 'create' ? !groupModalName : groupModalGroupId === null"
                    @click="submitGroup"
                >
                    Confirmar
                </Button>
            </div>
        </template>
    </ResponsiveModal>

    <ResponsiveModal
        :open="renameModalOpen"
        title="Renomear grupo"
        @update:open="open => { if (!open) closeRenameGroup() }"
    >
        <div class="space-y-2">
            <Label for="rename_group">Nome do grupo</Label>
            <Input
                id="rename_group"
                v-model="renameName"
                @keydown.enter="submitRenameGroup"
            />
        </div>
        <template #footer>
            <div class="flex w-full gap-2">
                <Button
                    variant="outline"
                    class="flex-1"
                    @click="closeRenameGroup"
                >
                    Cancelar
                </Button>
                <Button
                    class="flex-1"
                    @click="submitRenameGroup"
                >
                    Renomear
                </Button>
            </div>
        </template>
    </ResponsiveModal>

    <ConfirmDialog
        :open="deleteTarget !== null"
        title="Excluir grupo"
        description="Os itens do grupo voltarão a ficar sem grupo. Esta ação não pode ser desfeita."
        confirm-text="Excluir"
        @update:open="open => { if (!open) deleteTarget = null }"
        @confirm="submitDeleteGroup"
    />
</template>
