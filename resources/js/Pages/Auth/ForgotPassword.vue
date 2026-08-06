<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AuthCard from '@/Components/AuthCard.vue'

const props = defineProps<{
    status?: string
}>()

const form = useForm({
    email: '',
})

function submit() {
    form.post(route('password.email'))
}
</script>

<template>
    <AuthCard
        title="Esqueceu a senha?"
        description="Informe seu e-mail para receber um link de redefinição"
    >
        <Head title="My Wallet - Esqueceu a senha" />

        <div
            v-if="status"
            class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-400"
        >
            {{ status }}
        </div>

        <form
            class="space-y-4"
            @submit.prevent="submit"
        >
            <div class="space-y-2">
                <Label
                    class="text-muted-foreground"
                    for="email"
                >
                    E-mail
                </Label>
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    placeholder="email@exemplo.com"
                />
                <p
                    v-if="form.errors.email"
                    class="text-xs text-destructive"
                >
                    {{ form.errors.email }}
                </p>
            </div>

            <Button
                class="w-full"
                type="submit"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Enviando…' : 'Enviar link de redefinição' }}
            </Button>

            <p class="text-center text-xs text-muted-foreground">
                Lembrou a senha?
                <Link
                    class="font-medium text-primary hover:underline"
                    :href="route('login')"
                >
                    Voltar ao login
                </Link>
            </p>
        </form>
    </AuthCard>
</template>
