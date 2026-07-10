<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3'
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
    <div class="flex flex-1 items-center justify-center">
        <Card class="w-full max-w-md">
            <CardHeader class="text-center">
                <CardTitle class="text-2xl">Verifique seu e-mail</CardTitle>
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
