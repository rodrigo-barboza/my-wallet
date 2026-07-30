<script setup lang="ts">
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover'
import { User, LogOut } from '@lucide/vue'

const isOpen = ref(false)

const page = usePage()
const auth = computed(() => (page.props as any).auth ?? null)
const user = computed(() => (auth.value as any)?.user ?? null)

const initials = computed(() => {
    const name = user.value?.name ?? ''
    return name.split(' ').map((n: string) => n[0]).join('').toUpperCase().slice(0, 2)
})
</script>

<template>
    <Popover v-model:open="isOpen">
        <PopoverTrigger as-child>
            <button
                class="cursor-pointer flex items-center gap-2 rounded-full p-1 transition-colors hover:bg-muted"
            >
                <Avatar class="size-8">
                    <AvatarImage
                        v-if="user?.avatar"
                        :src="user.avatar"
                        :alt="user?.name"
                    />
                    <AvatarFallback class="bg-primary text-primary-foreground text-xs font-semibold">
                        {{ initials }}
                    </AvatarFallback>
                </Avatar>
            </button>
        </PopoverTrigger>
        <PopoverContent
            class="w-48"
            align="end"
            side="bottom"
        >
            <div class="flex flex-col gap-1">
                <Link
                    class="flex items-center gap-2 rounded-md px-3 py-2 text-sm transition-colors hover:bg-muted"
                    :href="route('profile')"
                    @click="isOpen = false"
                >
                    <User class="size-4" />
                    Perfil
                </Link>
                <Link
                    class="flex items-center gap-2 rounded-md px-3 py-2 text-sm transition-colors hover:bg-muted"
                    :href="route('logout')"
                    method="post"
                    as="button"
                    @click="isOpen = false"
                >
                    <LogOut class="size-4" />
                    Sair
                </Link>
            </div>
        </PopoverContent>
    </Popover>
</template>
