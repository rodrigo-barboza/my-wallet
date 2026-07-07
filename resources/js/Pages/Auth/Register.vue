<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

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
    <div class="flex min-h-screen items-center justify-center bg-background p-4">
        <Card class="w-full max-w-sm">
            <CardHeader class="text-center">
                <CardTitle class="text-2xl">Criar conta</CardTitle>
                <CardDescription>Preencha o formulário para começar</CardDescription>
            </CardHeader>

            <CardContent>
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="name">Nome</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="Seu nome"
                        />
                        <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="email">E-mail</Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            placeholder="email@exemplo.com"
                        />
                        <p v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="password">Senha</Label>
                        <Input
                            id="password"
                            v-model="form.password"
                            type="password"
                            placeholder="Mínimo de 8 caracteres"
                        />
                        <p v-if="form.errors.password" class="text-sm text-destructive">{{ form.errors.password }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="password_confirmation">Confirmar senha</Label>
                        <Input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            placeholder="Repita sua senha"
                        />
                    </div>

                    <Button type="submit" class="w-full" :disabled="form.processing">
                        {{ form.processing ? 'Criando conta…' : 'Criar conta' }}
                    </Button>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
