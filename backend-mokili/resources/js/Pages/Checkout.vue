<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import OperatorBadge from '@/Components/OperatorBadge.vue';
import { cemacCountries, bankCards, operatorsForCountry } from '@/data/cemac';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import axios from 'axios';

defineOptions({ layout: MainLayout });

const props = defineProps({
    booking: { type: Object, required: true },
    wallet: { type: Object, default: null },
});

const isVerified = computed(() => !!props.wallet?.peex_verified_at);

// Which payment method the customer is looking at. Only "mobile_money" is
// actually wired up to Peex today - "card" is an interface preview (no
// Stripe account connected yet), per product decision.
const method = ref('mobile_money');

const currentOperators = computed(() => operatorsForCountry(walletForm.country_code));

// Step 1: verify the customer's Peex wallet (mobile money / bank).
const walletForm = useForm({ country_code: props.wallet?.country_code ?? 'CM', account_number: props.wallet?.account_number ?? '' });
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

// Card tab (UI preview only - not connected to a payment processor yet).
const cardForm = ref({ number: '', name: '', expiry: '', cvc: '' });
const cardNotice = ref(false);
const submitCard = () => {
    cardNotice.value = true;
};
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
            <!-- Payment method tabs -->
            <div class="flex gap-2 rounded-xl bg-navy-50/60 p-1">
                <button
                    type="button"
                    class="flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition"
                    :class="method === 'mobile_money' ? 'bg-white text-navy-900 shadow-sm' : 'text-navy-500 hover:text-navy-700'"
                    @click="method = 'mobile_money'"
                >
                    Mobile Money
                </button>
                <button
                    type="button"
                    class="flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition"
                    :class="method === 'card' ? 'bg-white text-navy-900 shadow-sm' : 'text-navy-500 hover:text-navy-700'"
                    @click="method = 'card'"
                >
                    Carte bancaire
                </button>
            </div>

            <!-- Mobile Money (Peex) -->
            <template v-if="method === 'mobile_money'">
                <div class="rounded-2xl border p-5">
                    <h2 class="font-semibold text-navy-900">1. Verification du portefeuille Peex</h2>
                    <p class="mt-1 text-xs text-navy-500">
                        Nous verifions que votre numero mobile money peut recevoir des transactions
                        (<a href="https://peex-api-docs.peexit.com/verify-wallet" target="_blank" class="underline">clients/verify-wallet</a>).
                    </p>

                    <div v-if="isVerified" class="mt-3 rounded-lg bg-green-50 p-3 text-sm text-green-700">
                        Portefeuille verifie : {{ wallet.account_name ?? wallet.account_number }} ({{ wallet.operator }})
                    </div>

                    <form v-else class="mt-3 space-y-3" @submit.prevent="verifyWallet">
                        <div>
                            <label class="text-xs font-medium text-navy-500">Pays (zone CEMAC)</label>
                            <select v-model="walletForm.country_code" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                                <option v-for="c in cemacCountries" :key="c.code" :value="c.code">{{ c.name }}</option>
                            </select>
                        </div>

                        <div v-if="currentOperators.length" class="flex flex-wrap items-center gap-2">
                            <span class="text-xs text-navy-500">Operateurs disponibles :</span>
                            <OperatorBadge v-for="op in currentOperators" :key="op.key" :operator="op" size="sm" />
                        </div>

                        <input
                            v-model="walletForm.account_number"
                            type="text"
                            placeholder="Numero mobile money"
                            class="w-full rounded-lg border-gray-300 text-sm"
                            required
                        />

                        <button type="submit" class="btn-outline w-full !py-2 text-sm" :disabled="verifying">
                            {{ verifying ? 'Verification...' : 'Verifier mon portefeuille' }}
                        </button>
                        <p v-if="verifyResult" :class="verifyResult.ok ? 'text-green-700' : 'text-red-600'" class="text-xs">
                            {{ verifyResult.message }}
                        </p>
                    </form>
                </div>

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
            </template>

            <!-- Bank card (interface preview only, not yet connected to a processor) -->
            <template v-else>
                <div class="rounded-2xl border p-5">
                    <div class="flex items-center justify-between">
                        <h2 class="font-semibold text-navy-900">Paiement par carte bancaire</h2>
                        <div class="flex gap-2">
                            <OperatorBadge v-for="c in bankCards" :key="c.key" :operator="c" size="sm" />
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-navy-500">Visa et Mastercard - bientot disponible.</p>

                    <form class="mt-4 space-y-3" @submit.prevent="submitCard">
                        <div>
                            <label class="text-xs font-medium text-navy-500">Numero de carte</label>
                            <input v-model="cardForm.number" type="text" inputmode="numeric" maxlength="19" placeholder="1234 5678 9012 3456" class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-navy-500">Nom sur la carte</label>
                            <input v-model="cardForm.name" type="text" placeholder="J. DUPONT" class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div class="flex gap-3">
                            <div class="flex-1">
                                <label class="text-xs font-medium text-navy-500">Expiration</label>
                                <input v-model="cardForm.expiry" type="text" placeholder="MM/AA" maxlength="5" class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                            </div>
                            <div class="flex-1">
                                <label class="text-xs font-medium text-navy-500">CVC</label>
                                <input v-model="cardForm.cvc" type="text" placeholder="123" maxlength="4" class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                            </div>
                        </div>
                        <button type="submit" class="btn-gold w-full">Payer par carte</button>
                        <p v-if="cardNotice" class="text-xs text-navy-500">
                            Le paiement par carte bancaire arrive bientot. Utilisez Mobile Money pour finaliser votre reservation des maintenant.
                        </p>
                    </form>
                </div>
            </template>
        </div>
    </div>
</template>
