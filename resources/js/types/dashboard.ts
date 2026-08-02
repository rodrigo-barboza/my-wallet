export interface DashboardWindowMonth {
    label: string
    month: number
    year: number
    isCurrent: boolean
    isHighlighted: boolean
}

export interface DashboardMatrixItem {
    id: string
    name: string
    type: string
    totals: number[]
}

export interface MonthlySummary {
    income: number
    expenses: number
    paid: number
    balance: number
    month: number
    year: number
}

export interface CategoryDistribution {
    type: string
    label: string
    total: number
}

export interface UpcomingPayment {
    name: string
    dueDate: string
    amount: number
    type: string
}
