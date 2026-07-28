<script setup lang="ts">
import { useForm, usePage, Head } from '@inertiajs/vue3'
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const page = usePage()

const status = computed(() => page.props.status as string | undefined)

const form = useForm({})

function resend() {
    form.post(route('verification.send'))
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-muted/30 p-4 sm:p-8">
        <Head title="My Wallet - Verificar e-mail" />
        <Card class="w-full max-w-md">
            <CardHeader class="items-center text-center">
                <img src="/images/my-wallet.png" alt="Minha Carteira" class="h-10 mx-auto mb-1" />
                <CardTitle class="text-xl">Verifique seu e-mail</CardTitle>
                <CardDescription>
                    Obrigado por se cadastrar! Antes de começar, verifique seu e-mail clicando no link que enviamos.
                    Se você não recebeu o e-mail, enviaremos outro.
                </CardDescription>
            </CardHeader>

            <CardContent class="space-y-4">
                <div
                    v-if="status === 'verification-link-sent'"
                    class="rounded-md bg-primary/10 px-4 py-3 text-sm text-primary"
                >
                    Um novo link de verificação foi enviado para o e-mail informado no cadastro.
                </div>

                <form @submit.prevent="resend" class="flex justify-center">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Enviando…' : 'Reenviar e-mail de verificação' }}
                    </Button>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
