import { computed, nextTick } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { driver, type DriveStep, type Config } from 'driver.js'
import 'driver.js/dist/driver.css'

const TOUR_STORAGE_KEY = 'my-wallet-onboarding-tour'

interface TourState {
    active: boolean
    visitedPages: string[]
}

function getTourState(): TourState | null {
    const raw = sessionStorage.getItem(TOUR_STORAGE_KEY)

    if (!raw) {
        return null
    }

    try {
        return JSON.parse(raw)
    } catch {
        return null
    }
}

function setTourState(state: TourState | null): void {
    if (state) {
        sessionStorage.setItem(TOUR_STORAGE_KEY, JSON.stringify(state))
    } else {
        sessionStorage.removeItem(TOUR_STORAGE_KEY)
    }
}

const PAGE_TOURS: Record<string, () => DriveStep[]> = {
    'Dashboard': () => [
        {
            element: '#onboarding-logo',
            popover: {
                title: 'Minha Carteira',
                description: 'Clique no logo a qualquer momento para voltar ao dashboard.',
                side: 'bottom',
                align: 'start',
            },
        },
        {
            element: '#onboarding-nav',
            popover: {
                title: 'Navegação',
                description: 'Use estes links para acessar Compras, Entradas e Cartões.',
                side: 'bottom',
            },
        },
        {
            element: '#onboarding-summary-cards',
            popover: {
                title: 'Resumo do Mês',
                description: 'Cards com as despesas, entradas e saldo do mês selecionado.',
                side: 'bottom',
            },
        },
        {
            element: '#onboarding-progress',
            popover: {
                title: 'Progresso de Pagamentos',
                description: 'Barra mostrando quanto já foi pago vs. o total de despesas do mês.',
                side: 'top',
            },
        },
        {
            element: '#onboarding-month-nav',
            popover: {
                title: 'Navegação de Meses',
                description: 'Use as setas para voltar ou avançar meses. Clique em "Mês atual" para voltar ao mês corrente.',
                side: 'bottom',
            },
        },
        {
            element: '#onboarding-upcoming',
            popover: {
                title: 'Próximos Pagamentos',
                description: 'Pagamentos que vencem em breve. Itens atrasados aparecem em vermelho, hoje em amanhã.',
                side: 'top',
            },
        },
    ],
    'Purchases/Index': () => [
        {
            element: '#onboarding-purchases-viewmode',
            popover: {
                title: 'Visualização',
                description: 'Alterne entre visualização em cards (arrastável com drag and drop) ou tabela.',
                side: 'bottom',
            },
        },
        {
            element: '#onboarding-purchases-month',
            popover: {
                title: 'Seletor de Mês',
                description: 'Navegue entre os meses usando as setas ou clique em "Mês atual" para voltar ao mês corrente.',
                side: 'bottom',
            },
        },
        {
            element: '#onboarding-purchases-tabs',
            popover: {
                title: 'Abas',
                description: 'Alterne entre a visão geral de compras e o histórico de pagamentos realizados.',
                side: 'bottom',
            },
        },
        {
            element: '#onboarding-purchases-summary',
            popover: {
                title: 'Resumo',
                description: 'Veja o total do mês, receitas vs. gastos e saldo.',
                side: 'top',
            },
        },
        {
            element: '#onboarding-purchases-add',
            popover: {
                title: 'Nova Compra',
                description: 'Adicione uma nova compra clicando neste botão.',
                side: 'bottom',
            },
        },
        {
            element: '#onboarding-purchases-filters',
            popover: {
                title: 'Busca',
                description: 'Pesquise compras por nome.',
                side: 'bottom',
            },
        },
        {
            element: '#onboarding-purchases-list',
            popover: {
                title: 'Suas Compras',
                description: 'Na visão card, arraste para reordenar. Clique em uma compra para editar ou marcar como paga.',
                side: 'top',
            },
        },
    ],
    'Incomes/Index': () => [
        {
            element: '#onboarding-incomes-add',
            popover: {
                title: 'Nova Entrada',
                description: 'Adicione uma nova fonte de renda mensal.',
                side: 'bottom',
            },
        },
        {
            element: '#onboarding-incomes-month',
            popover: {
                title: 'Seletor de Mês',
                description: 'Navegue entre os meses para ver as entradas de cada período.',
                side: 'bottom',
            },
        },
        {
            element: '#onboarding-incomes-list',
            popover: {
                title: 'Suas Entradas',
                description: 'Tabela com todas as fontes de renda. Marque com checkbox para selecionar. Clique no nome da coluna para ordenar.',
                side: 'top',
            },
        },
    ],
    'Cards/Index': () => [
        {
            element: '#onboarding-cards-viewmode',
            popover: {
                title: 'Visualização',
                description: 'Alterne entre visualização em grid (cards visuais) ou tabela (lista detalhada).',
                side: 'bottom',
            },
        },
        {
            element: '#onboarding-cards-add',
            popover: {
                title: 'Novo Cartão',
                description: 'Adicione um novo cartão de crédito ou débito.',
                side: 'bottom',
            },
        },
        {
            element: '#onboarding-cards-list',
            popover: {
                title: 'Seus Cartões',
                description: 'Todos os seus cartões cadastrados. Clique em um para ver faturas ou editar.',
                side: 'top',
            },
        },
    ],
}

const ALL_PAGES = ['Dashboard', 'Purchases/Index', 'Incomes/Index', 'Cards/Index']

export function useOnboarding() {
    const page = usePage()
    const onboardingCompleted = computed(() => (page.props as any).onboarding_completed as boolean)

    function getPageName(): string {
        return (page as any).component as string
    }

    function isPageVisited(pageName: string): boolean {
        const state = getTourState()
        return state?.visitedPages.includes(pageName) ?? false
    }

    function shouldShowTour(): boolean {
        if (onboardingCompleted.value) {
            return false
        }

        const state = getTourState()

        if (state?.active) {
            const currentPage = getPageName()
            return !state.visitedPages.includes(currentPage)
        }

        return true
    }

    let driverInstance: ReturnType<typeof driver> | null = null

    function destroyDriver(): void {
        if (driverInstance) {
            try {
                driverInstance.destroy()
            } catch {
                // ignore
            }
            driverInstance = null
        }
    }

    function completeTour(): void {
        setTourState(null)
        destroyDriver()

        fetch(route('onboarding.complete'), {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '',
                ),
            },
        })

        router.reload({ only: ['onboarding_completed'] })
    }

    function markPageVisited(pageName: string): void {
        const state = getTourState()

        if (!state) {
            return
        }

        const visitedPages = [...state.visitedPages]

        if (!visitedPages.includes(pageName)) {
            visitedPages.push(pageName)
        }

        const allVisited = ALL_PAGES.every((p) => visitedPages.includes(p))

        if (allVisited) {
            completeTour()
            return
        }

        setTourState({ active: true, visitedPages })
    }

    function getConfig(): Config {
        return {
            animate: true,
            allowClose: true,
            overlayColor: 'rgba(0, 0, 0, 0.5)',
            stagePadding: 5,
            stageRadius: 8,
            showProgress: true,
            showButtons: ['next', 'previous', 'close'],
            progressText: 'Passo {{current}} de {{total}}',
            nextBtnText: 'Próximo',
            prevBtnText: 'Anterior',
            doneBtnText: 'Concluir',
            onDestroyStarted: () => {
                destroyDriver()
                markPageVisited(getPageName())
            },
            onDoneClick: () => {
                destroyDriver()
                markPageVisited(getPageName())
            },
            onNextClick: () => {
                if (driverInstance?.hasNextStep()) {
                    driverInstance.moveNext()
                }
            },
        }
    }

    async function startTourOnPage(): Promise<void> {
        if (!shouldShowTour()) {
            return
        }

        const currentPage = getPageName()

        if (!getTourState()) {
            setTourState({ active: true, visitedPages: [] })
        }

        const steps = PAGE_TOURS[currentPage]?.()

        if (!steps || steps.length === 0) {
            markPageVisited(currentPage)
            return
        }

        await nextTick()

        setTimeout(() => {
            destroyDriver()
            driverInstance = driver(getConfig())
            driverInstance.setSteps(steps)
            driverInstance.drive()
        }, 300)
    }

    function startTour(): void {
        setTourState({
            active: true,
            visitedPages: [],
        })

        if (getPageName() === 'Dashboard') {
            startTourOnPage()
        } else {
            router.visit(route('dashboard'), {
                preserveScroll: true,
                onSuccess: () => {
                    nextTick(() => startTourOnPage())
                },
            })
        }
    }

    function skipTour(): void {
        completeTour()
    }

    function resetTour(): void {
        destroyDriver()

        fetch(route('onboarding.reset'), {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '',
                ),
            },
        }).finally(() => {
            router.reload({
                only: ['onboarding_completed'],
                onSuccess: () => {
                    setTourState({
                        active: true,
                        visitedPages: [],
                    })

                    if (getPageName() === 'Dashboard') {
                        startTourOnPage()
                    } else {
                        router.visit(route('dashboard'), {
                            preserveScroll: true,
                            onSuccess: () => {
                                nextTick(() => startTourOnPage())
                            },
                        })
                    }
                },
            })
        })
    }

    return {
        onboardingCompleted,
        shouldShowTour,
        startTour,
        startTourOnPage,
        skipTour,
        resetTour,
        completeTour,
    }
}
