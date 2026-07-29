import { router } from '@inertiajs/vue3'

export function useMonthNavigation(routeName: string) {
    function goToMonth(month: number, year: number): void {
        router.get(route(routeName, { month, year }))
    }

    function previousMonth(month: number, year: number): void {
        let newMonth = month - 1
        let newYear = year
        if (newMonth < 1) {
            newMonth = 12
            newYear--
        }
        goToMonth(newMonth, newYear)
    }

    function nextMonth(month: number, year: number): void {
        let newMonth = month + 1
        let newYear = year
        if (newMonth > 12) {
            newMonth = 1
            newYear++
        }
        goToMonth(newMonth, newYear)
    }

    return { goToMonth, previousMonth, nextMonth }
}
