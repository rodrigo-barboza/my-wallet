<script setup lang="ts">
import { watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useOnboarding } from '@/composables/useOnboarding'

const { shouldShowTour, startTourOnPage } = useOnboarding()

const page = usePage()

watch(() => (page as any).component as string | undefined, (component) => {
    if (!component) {
        return
    }

    if (shouldShowTour()) {
        startTourOnPage()
    }
}, { immediate: true })
</script>

<template>
    <slot />
</template>
