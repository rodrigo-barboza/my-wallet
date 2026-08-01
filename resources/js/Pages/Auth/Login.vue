<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AuthCard from '@/Components/AuthCard.vue'
import Checkbox from '@/Components/Checkbox.vue'
import PasswordInput from '@/Components/PasswordInput.vue'

const form = useForm({
    email: '',
    password: '',
    remember: true,
})

function submit() {
    form.post(route('login.store'))
}
</script>

<template>
    <AuthCard
        title="Entrar"
        description="Acesse sua conta"
    >
        <Head title="My Wallet - Entrar" />

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

            <PasswordInput
                v-model="form.password"
                id="password"
                label="Senha"
                placeholder="Sua senha"
                :error="form.errors.password"
                @update:model-value="form.password = $event"
            />

            <div class="flex items-center gap-2">
                <Checkbox
                    :checked="form.remember"
                    @update:checked="(v: boolean) => form.remember = v"
                />
                <Label
                    class="text-sm text-muted-foreground"
                    for="remember"
                >
                    Lembrar de mim
                </Label>
            </div>

            <Button
                class="w-full"
                type="submit"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Entrando…' : 'Entrar' }}
            </Button>

            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <span class="w-full border-t" />
                </div>
                <div class="relative flex justify-center text-xs uppercase">
                    <span class="bg-card px-2 text-muted-foreground">ou</span>
                </div>
            </div>

            <a
                class="inline-flex w-full items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium text-foreground shadow-sm transition-colors hover:bg-muted"
                :href="route('google.redirect')"
            >
                <svg
                    class="mr-2 size-4"
                    viewBox="0 0 24 24"
                ><path
                    fill="currentColor"
                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"
                /><path
                    fill="currentColor"
                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                /><path
                    fill="currentColor"
                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                /><path
                    fill="currentColor"
                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                /></svg>
                Entrar com Google
            </a>

            <p class="text-center text-xs text-muted-foreground">
                Ainda não tem conta?
                <Link
                    class="font-medium text-primary hover:underline"
                    :href="route('register')"
                >
                    Criar conta
                </Link>
            </p>
        </form>
    </AuthCard>
</template>
