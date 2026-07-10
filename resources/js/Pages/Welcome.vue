<script setup lang="ts">
import { Button } from '@/components/ui/button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'

</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-background p-4 sm:p-8">
        <Card class="w-full max-w-sm text-center">
            <CardHeader>
                <CardTitle class="text-3xl">Minha Carteira</CardTitle>
                <CardDescription>Gerencie suas finanças pessoais</CardDescription>
            </CardHeader>
            <CardContent class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                <Button as-child>
                    <a :href="route('login')">Entrar</a>
                </Button>
                <Button variant="outline" as-child>
                    <a :href="route('register')">Criar conta</a>
                </Button>
            </CardContent>
        </Card>
    </div>
</template>
