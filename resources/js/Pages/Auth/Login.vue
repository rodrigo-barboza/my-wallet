<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
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
import { Wallet, Eye, EyeOff } from '@lucide/vue'

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const showPassword = ref(false)

function submit() {
    form.post(route('login.store'))
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-muted/30 p-4 sm:p-8">
        <Card class="w-full max-w-md">
            <CardHeader class="items-center text-center">
                <div class="mx-auto mb-1 flex size-12 items-center justify-center rounded-lg bg-primary">
                    <Wallet class="size-6 text-primary-foreground" />
                </div>
                <CardTitle class="text-xl">Entrar</CardTitle>
                <CardDescription>Acesse sua conta</CardDescription>
            </CardHeader>

            <CardContent>
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

                    <div class="space-y-2">
                        <Label for="password" class="text-muted-foreground">Senha</Label>
                        <div class="relative">
                            <Input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                placeholder="Sua senha"
                            />
                            <button
                                type="button"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                @click="showPassword = !showPassword"
                                tabindex="-1"
                            >
                                <component :is="showPassword ? EyeOff : Eye" class="size-4" />
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <Input
                            id="remember"
                            v-model="form.remember"
                            type="checkbox"
                            class="size-4"
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
            </CardContent>
        </Card>
    </div>
</template>
