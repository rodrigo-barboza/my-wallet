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
