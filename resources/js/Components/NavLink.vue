<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { cn } from '@/lib/utils'

const props = defineProps<{
    href: string
    active?: boolean
    showDot?: boolean
}>()
</script>

<template>
    <Link
        :href="href"
        :class="cn(
            'flex items-center gap-1.5 rounded-md px-3 py-2 text-sm font-medium transition-colors',
            active
                ? 'bg-primary/10 text-primary'
                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
        )"
    >
        <slot />
        <span
            v-if="showDot"
            class="relative flex size-2 shrink-0"
            data-testid="nav-dot"
        >
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary opacity-60" />
            <span class="relative inline-flex size-2 rounded-full bg-primary" />
        </span>
    </Link>
</template>
