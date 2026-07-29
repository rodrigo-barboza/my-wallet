<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'

defineProps<{
    title: string
    description?: string
    icon?: any
    iconColor?: string
    value: string
    valueColor?: string
    bgColor?: string
    borderColor?: string
}>()
</script>

<template>
    <Card :style="{ backgroundColor: bgColor, borderColor: borderColor }">
        <CardHeader class="pb-2">
            <CardTitle class="flex items-center gap-2 text-sm font-semibold text-muted-foreground">
                <component :is="icon" v-if="icon" class="size-4" :style="{ color: iconColor }" />
                {{ title }}
            </CardTitle>
            <CardDescription v-if="description" class="text-xs">
                {{ description }}
            </CardDescription>
        </CardHeader>
        <CardContent>
            <div class="text-2xl font-bold" :class="valueColor">{{ value }}</div>
        </CardContent>
    </Card>
</template>
