<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { CheckCircle, Lock, Mail, Shield, Trash2, User } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import PasswordInput from '@/Components/PasswordInput.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import { useToast } from '@/composables/useToast'

defineOptions({ layout: AppLayout })

const props = defineProps<{
    user: {
        name: string
        email: string
        email_verified_at: string | null
        avatar: string | null
        provider: string | null
    }
}>()

const { show: showToast } = useToast()

const showDeleteDialog = ref(false)

const nameForm = useForm({ name: props.user.name })
const emailForm = useForm({ email: props.user.email })
const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' })

const emailVerified = computed(() => !!props.user.email_verified_at)
const isGoogleUser = computed(() => props.user.provider === 'google')

function updateName(): void {
    nameForm.patch(route('profile.update-name'), {
        onSuccess: () => showToast('Nome atualizado!', 'success'),
        onError: () => showToast('Erro ao atualizar nome.', 'error'),
    })
}

function updateEmail(): void {
    emailForm.patch(route('profile.update-email'), {
        onSuccess: () => showToast('E-mail atualizado! Verifique seu novo endereço.', 'success'),
        onError: () => showToast('Erro ao atualizar e-mail.', 'error'),
    })
}

function updatePassword(): void {
    passwordForm.patch(route('profile.update-password'), {
        onSuccess: () => { passwordForm.reset(); showToast('Senha atualizada com sucesso!', 'success') },
        onError: () => showToast('Erro ao atualizar senha.', 'error'),
    })
}

function deleteAccount(): void {
    router.post(route('profile.destroy'), {}, {
        onSuccess: () => showToast('E-mail de confirmação enviado!', 'success'),
        onError: () => showToast('Erro ao enviar confirmação.', 'error'),
    })
    showDeleteDialog.value = false
}
</script>

<template>
    <div class="w-full max-w-2xl space-y-6">
        <Head title="My Wallet - Meu Perfil" />
        <h2 class="text-2xl font-bold">Meu Perfil</h2>

        <Card>
            <CardHeader class="pb-2">
                <CardTitle class="flex items-center gap-2 text-base font-semibold">
                    <User class="size-4" />Dados pessoais
                </CardTitle>
            </CardHeader>
            <CardContent>
                <form class="space-y-3" @submit.prevent="updateName">
                    <div class="space-y-1">
                        <Label for="name" class="text-muted-foreground">Nome</Label>
                        <Input id="name" v-model="nameForm.name" placeholder="Seu nome" />
                        <p v-if="nameForm.errors.name" class="text-xs text-destructive">{{ nameForm.errors.name }}</p>
                    </div>
                    <Button type="submit" variant="outline" size="sm" :disabled="nameForm.processing">Salvar nome</Button>
                </form>
            </CardContent>
        </Card>

        <Card v-if="!isGoogleUser">
            <CardHeader class="pb-2">
                <CardTitle class="flex items-center gap-2 text-base font-semibold">
                    <Mail class="size-4" />E-mail
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div v-if="emailVerified" class="flex items-center gap-2 rounded-md bg-emerald-500/10 px-3 py-2 text-sm text-emerald-600">
                    <CheckCircle class="size-4" />E-mail verificado
                </div>
                <form class="space-y-3" @submit.prevent="updateEmail">
                    <div class="space-y-1">
                        <Label for="email" class="text-muted-foreground">E-mail</Label>
                        <Input id="email" v-model="emailForm.email" type="email" placeholder="email@exemplo.com" />
                        <p v-if="emailForm.errors.email" class="text-xs text-destructive">{{ emailForm.errors.email }}</p>
                    </div>
                    <Button type="submit" variant="outline" size="sm" :disabled="emailForm.processing">Salvar e-mail</Button>
                </form>
            </CardContent>
        </Card>

        <Card v-if="isGoogleUser">
            <CardHeader class="pb-2">
                <CardTitle class="flex items-center gap-2 text-base font-semibold">
                    <Mail class="size-4" />E-mail
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="flex items-center gap-2 rounded-md bg-blue-500/10 px-3 py-2 text-sm text-blue-600">
                    <Shield class="size-4 shrink-0" />
                    Vinculado ao Google: <span class="font-medium">{{ user.email }}</span>
                </div>
                <p class="mt-2 text-xs text-muted-foreground">O e-mail é gerenciado pela sua conta Google.</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="pb-2">
                <CardTitle class="flex items-center gap-2 text-base font-semibold">
                    <Lock class="size-4" />{{ isGoogleUser ? 'Definir senha' : 'Senha' }}
                </CardTitle>
                <p v-if="isGoogleUser" class="text-xs text-muted-foreground">
                    Defina uma senha para acessar também com e-mail + senha.
                </p>
            </CardHeader>
            <CardContent>
                <form class="space-y-3" @submit.prevent="updatePassword">
                    <PasswordInput
                        v-if="!isGoogleUser"
                        id="current_password"
                        label="Senha atual"
                        :model-value="passwordForm.current_password"
                        placeholder="Sua senha atual"
                        :error="passwordForm.errors.current_password"
                        @update:model-value="passwordForm.current_password = $event"
                    />
                    <PasswordInput
                        id="password"
                        label="Nova senha"
                        :model-value="passwordForm.password"
                        placeholder="Mínimo 12 caracteres, com letra e número"
                        :error="passwordForm.errors.password"
                        @update:model-value="passwordForm.password = $event"
                    />
                    <PasswordInput
                        id="password_confirmation"
                        label="Confirmar nova senha"
                        :model-value="passwordForm.password_confirmation"
                        placeholder="Repita a nova senha"
                        @update:model-value="passwordForm.password_confirmation = $event"
                    />
                    <Button type="submit" variant="outline" size="sm" :disabled="passwordForm.processing">
                        {{ isGoogleUser ? 'Definir senha' : 'Atualizar senha' }}
                    </Button>
                </form>
            </CardContent>
        </Card>

        <Card class="border-destructive/30">
            <CardHeader class="pb-2">
                <CardTitle class="flex items-center gap-2 text-base font-semibold text-destructive">
                    <Trash2 class="size-4" />Zona de perigo
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
                <p class="text-sm text-muted-foreground">
                    Excluir sua conta remove permanentemente todos os seus dados (cartões, compras, entradas, etc.).
                </p>
                <Button variant="destructive" size="sm" @click="showDeleteDialog = true">Excluir conta</Button>
            </CardContent>
        </Card>

        <ConfirmDialog
            v-model:open="showDeleteDialog"
            title="Excluir conta"
            description="Tem certeza? Enviaremos um e-mail de confirmação para excluir todos os seus dados permanentemente."
            confirm-text="Enviar confirmação"
            @confirm="deleteAccount"
        />
    </div>
</template>
