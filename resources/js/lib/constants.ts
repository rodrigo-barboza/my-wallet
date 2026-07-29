import { Banknote, Calendar, CreditCard, ShoppingCart } from '@lucide/vue'

export const monthNames = [
    'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
]

export const monthAbbrs = [
    'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun',
    'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez',
]

export const typeIcons: Record<string, any> = {
    credit_card: CreditCard,
    bill: Calendar,
    financing: Banknote,
    others: ShoppingCart,
}

export const typeLabels: Record<string, string> = {
    credit_card: 'Cartão de crédito',
    bill: 'Conta',
    financing: 'Financiamento',
    others: 'Outros',
}
