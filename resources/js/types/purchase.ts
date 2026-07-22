import type { Card } from './card';

export interface Purchase {
    id: number;
    user_id: number;
    card_id: number | null;
    name: string;
    type: 'credit_card' | 'bill' | 'financing' | 'others';
    payment_day: number | null;
    is_recurring: boolean;
    amount: number;
    installments_total: number | null;
    start_date: string;
    notes: string | null;
    status: string;
    paid_at: string | null;
    created_at: string;
    updated_at: string;
    card?: Card;
    notify_due?: boolean;
}

export interface PurchaseSummaryItem {
    name: string | null;
    total: number;
    dates: number[] | { closing: number; due: number };
    status?: string;
    paid_at?: string | null;
    paid_amount?: number | null;
    items: Purchase[];
}

export interface PurchaseFormData {
    name: string;
    type: string;
    payment_day: number | null;
    is_recurring: boolean;
    card_id: number | null;
    amount: number;
    installments_total: number | null;
    start_date: string;
    notes: string | null;
    mark_as_paid?: boolean;
    notify_due?: boolean;
}
