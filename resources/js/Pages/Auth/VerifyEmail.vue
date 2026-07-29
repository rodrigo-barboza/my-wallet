<script setup lang="ts">
import { computed } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import AppLayout from '@/Layouts/AppLayout.vue'
import AuthCard from '@/Components/AuthCard.vue'

defineOptions({ layout: AppLayout })

const page = usePage()

const status = computed(() => page.props.status as string | undefined)

const form = useForm({})

function resend() {
    form.post(route('verification.send'))
}
</script>

<template>
    <AuthCard title="Verifique seu e-mail">
        <Head title="My Wallet - Verificar e-mail" />

        <div class="space-y-4">
            <p class="text-center text-sm text-muted-foreground">
                Obrigado por se cadastrar! Antes de começar, verifique seu e-mail clicando no link que enviamos.
                Se você não recebeu o e-mail, enviaremos outro.
            </p>

            <div
                v-if="status === 'verification-link-sent'"
                class="rounded-md bg-primary/10 px-4 py-3 text-sm text-primary"
            >
                Um novo link de verificação foi enviado para o e-mail informado no cadastro.
            </div>

            <form
                class="flex justify-center"
                @submit.prevent="resend"
            >
                <Button
                    type="submit"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Enviando…' : 'Reenviar e-mail de verificação' }}
                </Button>
            </form>
        </div>
    </AuthCard>
</template>
