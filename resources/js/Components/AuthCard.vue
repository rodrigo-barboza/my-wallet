<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

defineProps<{
    title: string
    description?: string
}>()
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-muted/30 p-4 sm:p-8">
        <Card class="w-full max-w-md">
            <CardHeader class="items-center text-center">
                <img src="/images/my-wallet.png" alt="Minha Carteira" class="h-10 mx-auto mb-1" />
                <CardTitle class="text-xl">{{ title }}</CardTitle>
                <CardDescription v-if="description">{{ description }}</CardDescription>
            </CardHeader>
            <CardContent>
                <slot />
            </CardContent>
        </Card>
    </div>
</template>
