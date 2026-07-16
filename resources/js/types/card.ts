export interface Card {
    id: number;
    name: string;
    color: string;
    closing_day: number;
    due_day: number;
    notify_closing: boolean;
    notify_due: boolean;
};
