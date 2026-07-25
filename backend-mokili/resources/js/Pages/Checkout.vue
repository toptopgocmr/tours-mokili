<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import axios from 'axios';

defineOptions({ layout: MainLayout });

const props = defineProps({
    booking: { type: Object, required: true },
    wallet: { type: Object, default: null },
});

const isVerified = computed(() => !!props.wallet?.peex_verified_at);

// Step 1: verify the customer's Peex wallet (mobile money / bank).
const walletForm = useForm({ country_code: props.wallet?.country_code ?? 'CD', account_number: props.wallet?.account_number ?? '' });
const verifying = ref(false);
const verifyResult = ref(null);

const verifyWallet = async () => {
    verifying.value = true;
    verifyResult.value = null;
    try {
        const { data } = await axios.post('/api/wallet/verify', walletForm.data());
        verifyResult.value = { ok: true, message: data.message };
        router.reload({ only: ['wallet'] });
    } catch (e) {
        verifyResult.value = { ok: false, message: e.response?.data?.message ?? 'Verification echouee.' };
    } finally {
        verifying.value = false;
    }
};

// Step 2: pay via Peex (server calls clients/request_payment).
const payForm = useForm({});
const pay = () => payForm.post(`/checkout/${props.booking.id}/payer`);
</script>

<template>
    <Head title="Paiement" />

    <div class="mx-auto grid max-w-4xl gap-10 px-6 py-12 md:grid-cols-2">
        <div>
            <h1 class="text-2xl font-bold text-navy-900">Recapitulatif de la reservation</h1>
            <div class="mt-4 space-y-2 rounded-2xl border p-5 text-sm">
                <p><span class="text-navy-500">Reference :</span> <span class="font-semibold">{{ booking.reference }}</span></p>
                <p><span class="text-navy-500">Statut :</span> <span class="font-semibold capitalize">{{ booking.status }}</span></p>
                <p><span class="text-navy-500">Quantite :</span> {{ booking.quantity }}</p>
                <p class="text-lg font-bold text-gold-600">
                    Total : {{ Number(booking.total_amount).toLocaleString('fr-FR') }} {{ booking.currency }}
                </p>
            </div>
        </div>

        <div class="space-y-6">
            <!-- Step 1: Peex wallet verification -->
            <div class="rounded-2xl border p-5">
                <h2 class="font-semibold text-navy-900">1. Verification du portefeuille Peex</h2>
                <p class="mt-1 text-xs text-navy-500">
                    Nous verifions que votre numero mobile money / bancaire peut recevoir des transactions
                    (<a href="https://peex-api-docs.peexit.com/verify-wallet" target="_blank" class="underline">clients/verify-wallet</a>).
                </p>

                <div v-if="isVerified" class="mt-3 rounded-lg bg-green-50 p-3 text-sm text-green-700">
                    Portefeuille verifie : {{ wallet.account_name ?? wallet.account_number }} ({{ wallet.operator }})
                </div>

                <form v-else class="mt-3 space-y-3" @submit.prevent="verifyWallet">
                    <div class="flex gap-2">
                        <select v-model="walletForm.country_code" class="w-28 rounded-lg border-gray-300 text-sm">
                            <option value="CD">CD</option>
                            <option value="CM">CM</option>
                            <option value="GA">GA</option>
                        </select>
                        <input
                            v-model="walletForm.account_number"
                            type="text"
                            placeholder="Numero mobile money / compte"
                            class="flex-1 rounded-lg border-gray-300 text-sm"
                            required
                        />
                    </div>
                    <button type="submit" class="btn-outline w-full !py-2 text-sm" :disabled="verifying">
                        {{ verifying ? 'Verification...' : 'Verifier mon portefeuille' }}
                    </button>
                    <p v-if="verifyResult" :class="verifyResult.ok ? 'text-green-700' : 'text-red-600'" class="text-xs">
                        {{ verifyResult.message }}
                    </p>
                </form>
            </div>

            <!-- Step 2: pay -->
            <div class="rounded-2xl border p-5">
                <h2 class="font-semibold text-navy-900">2. Paiement</h2>
                <p class="mt-1 text-xs text-navy-500">Le paiement est traite via Peex (clients/request_payment).</p>
                <button
                    class="btn-gold mt-3 w-full"
                    :disabled="!isVerified || payForm.processing || booking.status === 'confirmed'"
                    @click="pay"
                >
                    {{ booking.status === 'confirmed' ? 'Reservation confirmee' : 'Payer maintenant' }}
                </button>
            </div>
        </div>
    </div>
</template>
