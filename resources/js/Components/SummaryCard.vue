<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

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
        <CardHeader class="pb-1">
            <CardTitle class="flex items-center gap-1.5 text-xs font-semibold text-muted-foreground">
                <component :is="icon" v-if="icon" class="size-3.5" :style="{ color: iconColor }" />
                {{ title }}
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-0.5">
            <div class="text-xl font-bold leading-tight" :class="valueColor">{{ value }}</div>
            <div v-if="description" class="text-[11px] text-muted-foreground">{{ description }}</div>
        </CardContent>
    </Card>
</template>