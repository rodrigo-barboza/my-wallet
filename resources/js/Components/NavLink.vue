<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { cn } from '@/lib/utils'

const props = defineProps<{
    href: string
    active?: boolean
}>()
</script>

<template>
    <Link
        :href="href"
        :class="cn(
            'rounded-md px-3 py-2 text-sm font-medium transition-colors',
            active
                ? 'bg-primary/10 text-primary'
                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
        )"
    >
        <slot />
    </Link>
</template>
