export const colors = {
    primary: '#3B82F6',
    income: '#10B981',
    expense: '#EF4444',
    pending: '#F59E0B',
    background: '#6B7280',
} as const

export const statusColors: Record<string, string> = {
    aberta: colors.primary,
    fechada: colors.background,
    paga: colors.income,
    parcialmente_paga: colors.pending,
    atrasada: colors.expense,
}

export const typeColors: Record<string, string> = {
    credit_card: '#3B82F6',
    bill: '#06B6D4',
    financing: '#8B5CF6',
    others: '#F59E0B',
}

export const chartPalette = [
    '#3B82F6', '#06B6D4', '#8B5CF6', '#F59E0B', '#10B981', '#EF4444', '#EC4899',
]
