import { computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

export function useWallet() {
    const page = usePage()
    const walletBalance = computed(() => Number((page.props as any).wallet_balance ?? 0))
    const walletHidden = ref<boolean>(!!(page.props as any).wallet_hidden)
    const editingWallet = ref(false)
    const walletInput = ref('')

    function toggleWalletHidden(): void {
        walletHidden.value = !walletHidden.value
        fetch(route('preferences.update'), {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ key: 'wallet_hidden', value: walletHidden.value }),
        })
    }

    function startEditWallet(): void {
        walletInput.value = String(walletBalance.value)
        editingWallet.value = true
    }

    function saveWallet(): void {
        const balance = parseFloat(walletInput.value.replace(',', '.'))
        if (isNaN(balance)) {
            editingWallet.value = false
            return
        }

        router.patch(route('wallet.update'), { balance }, {
            preserveScroll: true,
            onSuccess: () => { editingWallet.value = false },
        })
    }

    return {
        walletBalance,
        walletHidden,
        editingWallet,
        walletInput,
        toggleWalletHidden,
        startEditWallet,
        saveWallet,
    }
}