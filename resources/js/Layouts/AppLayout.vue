<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
</script>

<template>
    <div class="flex min-h-screen flex-col bg-muted/30">
        <header class="flex items-center justify-between border-b bg-background px-4 py-3 sm:px-6 lg:px-8">
            <div class="flex items-center gap-4">
                <h1 class="text-lg font-semibold">Minha Carteira</h1>
                <Link :href="route('cards.index')" class="text-sm font-medium text-muted-foreground hover:text-foreground">
                    Cartões
                </Link>
            </div>
            <Button variant="outline" as-child>
                <Link :href="route('logout')" method="post">Sair</Link>
            </Button>
        </header>

        <main class="flex flex-1 p-4 sm:p-6 lg:p-8">
            <slot />
        </main>
    </div>
</template>
