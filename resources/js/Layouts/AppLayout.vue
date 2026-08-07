<script setup lang="ts">
import { watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Menu, LayoutDashboard, Banknote, ShoppingCart, CreditCard, User, LogOut } from '@lucide/vue'
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
    SheetClose,
} from '@/components/ui/sheet'
import NavLink from '@/Components/NavLink.vue'
import OnboardingProvider from '@/Components/OnboardingProvider.vue'
import ToastContainer from '@/Components/ToastContainer.vue'
import UserDropdown from '@/Components/UserDropdown.vue'
import { useToast } from '@/composables/useToast'

const { show } = useToast()

const page = usePage()

watch(() => (page as any).flash?.toast, (toastData: any) => {
    if (toastData?.message) {
        show(toastData.message, toastData.type ?? 'success')
    }
}, { immediate: true })

function isActive(name: string): boolean {
    return route().current(name);
}

const navLinks = [
    { name: 'Dashboard', route: 'dashboard', pattern: 'dashboard', icon: LayoutDashboard },
    { name: 'Compras', route: 'purchases.index', pattern: 'purchases*', icon: ShoppingCart },
    { name: 'Entradas', route: 'incomes.index', pattern: 'incomes*', icon: Banknote },
    { name: 'Cartões', route: 'cards.index', pattern: 'cards*', icon: CreditCard },
]
</script>

<template>
    <div class="flex min-h-screen flex-col bg-muted/30">
        <OnboardingProvider />
        <ToastContainer />

        <header class="flex items-center justify-between border-b bg-background px-4 py-3 sm:px-6 lg:px-8">
            <div class="flex items-center gap-6">
                <Link id="onboarding-logo" :href="route('dashboard')">
                    <img src="/images/my-wallet.png" alt="Minha Carteira" class="h-8" />
                </Link>

                <nav id="onboarding-nav" class="hidden items-center gap-1 sm:flex">
                    <NavLink
                        v-for="link in navLinks"
                        :key="link.route"
                        :href="route(link.route)"
                        :active="isActive(link.pattern)"
                    >
                        {{ link.name }}
                    </NavLink>
                </nav>
            </div>

            <div class="flex items-center gap-2">
            <div class="hidden sm:inline-flex">
                <UserDropdown />
            </div>

                <Sheet>
                    <SheetTrigger as-child>
                        <Button variant="ghost" size="icon" class="sm:hidden">
                            <Menu class="size-5" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="left" class="w-64">
                        <SheetHeader>
                            <SheetTitle>
                                <img src="/images/my-wallet.png" alt="Minha Carteira" class="h-8" />
                            </SheetTitle>
                        </SheetHeader>
                        <nav class="mt-8 flex flex-col gap-1">
                            <SheetClose as-child v-for="link in navLinks" :key="link.route">
                                <Link
                                    :href="route(link.route)"
                                    class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                                    :class="isActive(link.pattern) ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted hover:text-foreground'"
                                >
                                    <component :is="link.icon" class="size-4" />
                                    {{ link.name }}
                                </Link>
                            </SheetClose>
                            <SheetClose as-child>
                                <Link
                                    class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors text-muted-foreground hover:bg-muted hover:text-foreground"
                                    :href="route('profile')"
                                >
                                    <User class="size-4" />
                                    Perfil
                                </Link>
                            </SheetClose>
                            <SheetClose as-child>
                                <Link :href="route('logout')" method="post"
                                    class="mt-4 flex cursor-pointer items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground">
                                    <LogOut class="size-4" />
                                    Sair
                                </Link>
                            </SheetClose>
                        </nav>
                    </SheetContent>
                </Sheet>
            </div>
        </header>

        <main class="flex flex-1 p-4 sm:p-6 lg:p-8">
            <slot />
        </main>
    </div>
</template>
