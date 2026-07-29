<script setup lang="ts">
import { ref } from 'vue'
import { Link, Head, useForm } from '@inertiajs/vue3'
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
    <AuthCard title="Entrar" description="Acesse sua conta">
        <Head title="My Wallet - Entrar" />

        <form @submit.prevent="submit" class="space-y-4">
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
                placeholder="Sua senha"
                :error="form.errors.password"
                @update:model-value="form.password = $event"
            />

            <div class="flex items-center gap-2">
                <Checkbox
                    :checked="form.remember"
                    @update:checked="(v: boolean) => form.remember = v"
                />
                <Label for="remember" class="text-sm text-muted-foreground">Lembrar de mim</Label>
            </div>

            <Button type="submit" class="w-full" :disabled="form.processing">
                {{ form.processing ? 'Entrando…' : 'Entrar' }}
            </Button>

            <p class="text-center text-xs text-muted-foreground">
                Ainda não tem conta?
                <Link :href="route('register')" class="font-medium text-primary hover:underline">
                    Criar conta
                </Link>
            </p>
        </form>
    </AuthCard>
</template>
