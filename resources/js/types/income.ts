import type { Component } from 'vue'

export interface Income {
    id: number;
    name: string;
    months: Record<number, Record<number, { id: number; amount: number }>>;
}

export interface IncomeFormData {
    name: string;
    amount: number;
    start_month: number;
    start_year: number;
    repeat_count: number;
}

export interface IncomeEditingCell {
    incomeId: number
    month: number
    year: number
}

export interface IncomeEditingName {
    incomeId: number
    value: string
}

export interface IncomeActionButton {
    key: 'duplicate' | 'delete'
    label: string
    icon: Component
    handler: (income: Income) => void
    color?: string
}
