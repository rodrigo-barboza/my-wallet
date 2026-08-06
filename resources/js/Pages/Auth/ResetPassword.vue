<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import AuthCard from '@/Components/AuthCard.vue'
import PasswordInput from '@/Components/PasswordInput.vue'

const props = defineProps<{
    token: string
    email?: string
}>()

const form = useForm({
    token: props.token,
    email: props.email ?? '',
    password: '',
    password_confirmation: '',
})

function submit() {
    form.post(route('password.store'))
}
</script>

<template>
    <AuthCard
        title="Redefinir senha"
        description="Digite sua nova senha abaixo"
    >
        <Head title="My Wallet - Redefinir senha" />

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
                label="Nova senha"
                placeholder="Mínimo 12 caracteres"
                :error="form.errors.password"
                @update:model-value="form.password = $event"
            />

            <PasswordInput
                v-model="form.password_confirmation"
                id="password_confirmation"
                label="Confirmar senha"
                placeholder="Repita a senha"
                @update:model-value="form.password_confirmation = $event"
            />

            <Button
                class="w-full"
                type="submit"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Redefinindo…' : 'Redefinir senha' }}
            </Button>
        </form>
    </AuthCard>
</template>
