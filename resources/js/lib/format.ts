export function formatCurrency(value: number): string {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value)
}

export function formatShortCurrency(value: number): string {
    if (value >= 1000) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(value)
    }
    return formatCurrency(value)
}

export function formatDate(dateStr: string): string {
    const [datePart] = dateStr.split('T')
    const [year, month, day] = datePart.split('-')
    return `${parseInt(day)}/${month}`
}

export function formatDateTime(dateStr: string): string {
    const [datePart, timePart] = dateStr.split('T')
    const [year, month, day] = datePart.split('-')
    const [hour, minute] = timePart.split(':')
    return `${parseInt(day)}/${month} ${hour}:${minute}`
}

export function formatDateRange(closing: number, due: number): string {
    return `Fechamento: ${closing} / Vencimento: ${due}`
}

export function toTitleCase(str: string): string {
    return str.split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}
