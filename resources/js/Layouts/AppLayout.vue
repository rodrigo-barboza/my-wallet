<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Toaster } from '@/components/ui/sonner'
import { toast } from 'vue-sonner'
import { Menu, LayoutDashboard, ShoppingCart, CreditCard, LogOut } from '@lucide/vue'
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
    SheetClose,
} from '@/components/ui/sheet'

router.on('flash', (event) => {
    const flash = (event.detail as { flash?: { message?: string; type?: string } }).flash;
    if (flash?.message) {
        const type = flash.type ?? 'success';
        if (type === 'success') {
            toast.success(flash.message);
        } else if (type === 'error') {
            toast.error(flash.message);
        } else {
            toast(flash.message);
        }
    }
});

function isActive(name: string): boolean {
    return route().current(name);
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-muted/30">
        <Toaster position="top-right" />

        <header class="flex items-center justify-between border-b bg-background px-4 py-3 sm:px-6 lg:px-8">
            <div class="flex items-center gap-6">
                <Link :href="route('purchases.index')">
                    <img src="/images/my-wallet.png" alt="Minha Carteira" class="h-8" />
                </Link>

                <nav class="hidden items-center gap-1 sm:flex">
                    <Link :href="route('dashboard')"
                        class="rounded-md px-3 py-2 text-sm font-medium transition-colors"
                        :class="isActive('dashboard') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted hover:text-foreground'">
                        Dashboard
                    </Link>
                    <Link :href="route('purchases.index')"
                        class="rounded-md px-3 py-2 text-sm font-medium transition-colors"
                        :class="isActive('purchases*') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted hover:text-foreground'">
                        Compras
                    </Link>
                    <Link :href="route('cards.index')"
                        class="rounded-md px-3 py-2 text-sm font-medium transition-colors"
                        :class="isActive('cards*') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted hover:text-foreground'">
                        Cartões
                    </Link>
                </nav>
            </div>

            <div class="flex items-center gap-2">
                <Button variant="outline" as-child class="hidden sm:inline-flex">
                    <Link :href="route('logout')" method="post" class="cursor-pointer">Sair</Link>
                </Button>

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
                            <SheetClose as-child>
                                <Link :href="route('dashboard')"
                                    class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                                    :class="isActive('dashboard') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted hover:text-foreground'">
                                    <LayoutDashboard class="size-4" />
                                    Dashboard
                                </Link>
                            </SheetClose>
                            <SheetClose as-child>
                                <Link :href="route('purchases.index')"
                                    class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                                    :class="isActive('purchases*') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted hover:text-foreground'">
                                    <ShoppingCart class="size-4" />
                                    Compras
                                </Link>
                            </SheetClose>
                            <SheetClose as-child>
                                <Link :href="route('cards.index')"
                                    class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                                    :class="isActive('cards*') ? 'bg-primary/10 text-primary' : 'text-muted-foreground hover:bg-muted hover:text-foreground'">
                                    <CreditCard class="size-4" />
                                    Cartões
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
