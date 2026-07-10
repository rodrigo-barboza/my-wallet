<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })
</script>

<template>
    <div class="flex flex-1 items-center justify-center">
        <div class="text-center">
            <h2 class="text-2xl font-semibold text-foreground">Dashboard</h2>
            <p class="mt-2 text-muted-foreground">Bem-vindo! Seus dados financeiros aparecerão aqui.</p>
        </div>
    </div>
</template>
