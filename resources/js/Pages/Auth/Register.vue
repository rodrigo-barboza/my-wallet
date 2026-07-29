<script setup lang="ts">
import { Link, Head, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AuthCard from '@/Components/AuthCard.vue'
import PasswordInput from '@/Components/PasswordInput.vue'

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
})

function submit() {
    form.post(route('register.store'))
}
</script>

<template>
    <AuthCard title="Criar conta" description="Preencha os dados para começar">
        <Head title="My Wallet - Criar conta" />

        <form @submit.prevent="submit" class="space-y-4">
            <div class="space-y-2">
                <Label for="name" class="text-muted-foreground">Nome</Label>
                <Input
                    id="name"
                    v-model="form.name"
                    type="text"
                    placeholder="Seu nome"
                />
                <p v-if="form.errors.name" class="text-xs text-destructive">
                    {{ form.errors.name }}
                </p>
            </div>

            <div class="space-y-2">
                <Label for="email" class="text-muted-foreground">E-mail</Label>
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    placeholder="email@exemplo.com"
                />
                <p v-if="form.errors.email" class="text-xs text-destructive">
                    {{ form.errors.email }}
                </p>
            </div>

            <PasswordInput
                id="password"
                label="Senha"
                :model-value="form.password"
                placeholder="Mínimo de 8 caracteres"
                :error="form.errors.password"
                @update:model-value="form.password = $event"
            />

            <PasswordInput
                id="password_confirmation"
                label="Confirmar senha"
                :model-value="form.password_confirmation"
                placeholder="Repita sua senha"
                @update:model-value="form.password_confirmation = $event"
            />

            <Button type="submit" class="w-full" :disabled="form.processing">
                {{ form.processing ? 'Criando conta…' : 'Criar conta' }}
            </Button>

            <p class="text-center text-xs text-muted-foreground">
                Já tem uma conta?
                <Link :href="route('login')" class="font-medium text-primary hover:underline">
                    Faça login
                </Link>
            </p>
        </form>
    </AuthCard>
</template>
