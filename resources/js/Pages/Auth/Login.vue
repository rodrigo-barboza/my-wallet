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
                id="password"
                label="Senha"
                placeholder="Sua senha"
                :model-value="form.password"
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
