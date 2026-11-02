<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import OperatorBadge from '@/Components/OperatorBadge.vue';
import { bankCards, detectCardBrand } from '@/data/cemac';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import axios from 'axios';

defineOptions({ layout: MainLayout });

const props = defineProps({
    booking: { type: Object, required: true },
    wallet: { type: Object, default: null },
    methods: { type: Object, default: () => ({ countries: [], gateways: [] }) },
    lastPayment: { type: Object, default: null },
});

// Onglet affiche : Mobile Money (Peex / pawaPay / MaishaPay / Digitwace) ou
// carte (apercu d'interface, pas encore relie a un processeur).
const method = ref('mobile_money');

const countries = computed(() => props.methods?.countries ?? []);
const gatewayLabels = computed(() =>
    Object.fromEntries((props.methods?.gateways ?? []).map((g) => [g.key, g.label])),
);

const defaultCountry =
    countries.value.find((c) => c.code === props.wallet?.country_code)?.code ??
    countries.value.find((c) => c.code === 'CG')?.code ??
    countries.value[0]?.code ??
    'CG';

const form = useForm({
    country_code: defaultCountry,
    network: null,
    gateway: null,
    phone: props.wallet?.account_number ?? '',
});

const country = computed(() => countries.value.find((c) => c.code === form.country_code));

// Passerelle par defaut = la premiere de la liste (ordre de priorite du
// pays, ex. Congo : MaishaPay > Digitwace > Peex). Apres un echec, on
// propose automatiquement la suivante.
const resetForCountry = () => {
    const list = country.value?.gateways ?? [];
    form.network = country.value?.networks?.[0]?.key ?? null;
    form.gateway = list[0] ?? null;
    if (props.lastPayment?.status === 'failed' && props.lastPayment.country_code === form.country_code) {
        const i = list.indexOf(props.lastPayment.provider);
        if (i >= 0 && list[i + 1]) form.gateway = list[i + 1];
    }
};
resetForCountry();
watch(() => form.country_code, resetForCountry);

// Logos des operateurs (fichiers deja presents dans public/images/payments).
const OPERATOR_STYLE = {
    MTN: { color: '#FFCC00', text: '#111111', logo: '/images/payments/mtn.png' },
    AIRTEL: { color: '#E4002B', text: '#FFFFFF', logo: '/images/payments/airtel.png' },
    ORANGE: { color: '#FF6600', text: '#FFFFFF', logo: '/images/payments/orange-money.png' },
    MPESA: { color: '#E60000', text: '#FFFFFF', logo: null },
    MOOV: { color: '#0072CE', text: '#FFFFFF', logo: null },
    AFRICELL: { color: '#6B21A8', text: '#FFFFFF', logo: null },
};
const asOperator = (n) => ({ key: n.key, name: n.label, ...(OPERATOR_STYLE[n.key] ?? { color: '#0B2A5B', text: '#FFFFFF', logo: null }) });

const isPaid = computed(() => ['confirmed', 'completed'].includes(props.booking.status) || props.lastPayment?.status === 'paid');
const isPending = computed(() => ['pending', 'processing'].includes(props.lastPayment?.status) && !isPaid.value);

const pay = () => form.post(`/checkout/${props.booking.id}/payer`, { preserveScroll: true });

// Tant qu'un paiement est en attente de validation sur le telephone, on
// interroge le statut toutes les 5 s et on recharge la page au resultat.
let timer = null;
const poll = async () => {
    try {
        const { data } = await axios.get(`/checkout/${props.booking.id}/statut`);
        if (data.payment && !['pending', 'processing'].includes(data.payment.status)) {
            stopPolling();
            router.reload({ only: ['booking', 'lastPayment'] });
        }
    } catch {
        /* reseau : on reessaie au prochain tour */
    }
};
const stopPolling = () => {
    if (timer) clearInterval(timer);
    timer = null;
};
const startPolling = () => {
    if (!timer && isPending.value) timer = setInterval(poll, 5000);
};
onMounted(startPolling);
watch(isPending, (v) => (v ? startPolling() : stopPolling()));
onBeforeUnmount(stopPolling);

// Card tab (UI preview only - not connected to a payment processor yet).
const cardForm = ref({ number: '', name: '', expiry: '', cvc: '' });
const cardNotice = ref(false);
const submitCard = () => {
    cardNotice.value = true;
};
const detectedBrand = computed(() => detectCardBrand(cardForm.value.number));
</script>

<template>
    <Head title="Paiement" />

    <div class="mx-auto grid max-w-4xl gap-10 px-6 py-12 md:grid-cols-2">
        <div>
            <h1 class="text-2xl font-bold text-navy-900">Recapitulatif de la reservation</h1>
            <div class="mt-4 space-y-2 rounded-2xl border p-5 text-sm">
                <p><span class="text-navy-500">Reference :</span> <span class="font-semibold">{{ booking.reference }}</span></p>
                <p><span class="text-navy-500">Statut :</span> <span class="font-semibold capitalize">{{ booking.status }}</span></p>
                <p v-if="booking.meta?.park"><span class="text-navy-500">Parc :</span> {{ booking.meta.park }} ({{ booking.meta.city }})</p>
                <p v-if="booking.meta?.park && booking.starts_at"><span class="text-navy-500">Visite le :</span> {{ new Date(booking.starts_at).toLocaleDateString('fr-FR') }}</p>
                <p v-if="booking.meta?.adults !== undefined">
                    <span class="text-navy-500">Billets :</span> {{ booking.meta.adults }} adulte(s), {{ booking.meta.children ?? 0 }} enfant(s)
                </p>
                <p v-else><span class="text-navy-500">Quantite :</span> {{ booking.quantity }}</p>
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

            <!-- Mobile Money multi-passerelles -->
            <template v-if="method === 'mobile_money'">
                <div v-if="isPaid" class="rounded-2xl border border-green-200 bg-green-50 p-5 text-green-800">
                    <p class="font-semibold">Paiement recu ✓</p>
                    <p class="mt-1 text-sm">Votre reservation {{ booking.reference }} est confirmee. Merci d'avoir choisi MOKILI TOUR !</p>
                </div>

                <div v-else-if="isPending" class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-900">
                    <div class="flex items-center gap-3">
                        <span class="h-5 w-5 animate-spin rounded-full border-2 border-amber-400 border-t-transparent" />
                        <p class="font-semibold">En attente de votre validation</p>
                    </div>
                    <p class="mt-2 text-sm">
                        Une demande de {{ Number(lastPayment.amount).toLocaleString('fr-FR') }} {{ lastPayment.currency }} a ete envoyee au
                        <strong>+{{ lastPayment.phone }}</strong> via {{ gatewayLabels[lastPayment.provider] ?? lastPayment.provider }}.
                        Composez votre code secret Mobile Money pour confirmer. Cette page se met a jour automatiquement.
                    </p>
                </div>

                <form v-else class="space-y-5 rounded-2xl border p-5" @submit.prevent="pay">
                    <div v-if="lastPayment?.status === 'failed'" class="rounded-lg bg-red-50 p-3 text-sm text-red-700">
                        Dernier essai refuse : {{ lastPayment.failure_reason ?? 'paiement non abouti' }}. Vous pouvez reessayer.
                    </div>

                    <div v-if="!countries.length" class="rounded-lg bg-slate-50 p-3 text-sm text-navy-700">
                        Le paiement en ligne est momentanement indisponible. Contactez le service client MOKILI TOUR.
                    </div>

                    <template v-else>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-navy-500">1. Pays</label>
                            <select v-model="form.country_code" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                                <option v-for="c in countries" :key="c.code" :value="c.code">{{ c.name }} (+{{ c.dial_code }})</option>
                            </select>
                        </div>

                        <div v-if="country?.networks?.length">
                            <label class="text-xs font-semibold uppercase tracking-wide text-navy-500">2. Operateur</label>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <OperatorBadge
                                    v-for="n in country.networks"
                                    :key="n.key"
                                    :operator="asOperator(n)"
                                    clickable
                                    :selected="form.network === n.key"
                                    @click="form.network = n.key"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-navy-500">3. Numero Mobile Money</label>
                            <div class="mt-1 flex overflow-hidden rounded-lg border border-gray-300 focus-within:ring-2 focus-within:ring-gold-400">
                                <span class="flex items-center bg-navy-50 px-3 text-sm text-navy-700">+{{ country?.dial_code }}</span>
                                <input v-model="form.phone" type="tel" inputmode="tel" placeholder="06 123 45 67" class="w-full border-0 text-sm focus:ring-0" required />
                            </div>
                            <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600">{{ form.errors.phone }}</p>
                        </div>

                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-navy-500">4. Payer avec</label>
                            <div class="mt-2 grid grid-cols-2 gap-2">
                                <button
                                    v-for="g in country?.gateways ?? []"
                                    :key="g"
                                    type="button"
                                    class="relative rounded-xl border px-3 py-2.5 text-left text-sm font-semibold transition"
                                    :class="form.gateway === g ? 'border-navy-900 bg-navy-900 text-white' : 'border-gray-200 text-navy-900 hover:border-navy-400'"
                                    @click="form.gateway = g"
                                >
                                    {{ gatewayLabels[g] ?? g }}
                                    <span v-if="g === country.gateways[0]" class="mt-0.5 block text-[10px] font-medium uppercase tracking-wide" :class="form.gateway === g ? 'text-gold-400' : 'text-gold-600'">Recommande</span>
                                </button>
                            </div>
                            <p v-if="form.errors.gateway" class="mt-1 text-xs text-red-600">{{ form.errors.gateway }}</p>
                        </div>

                        <button type="submit" class="btn-gold w-full" :disabled="form.processing || !form.gateway || !form.phone">
                            {{ form.processing ? 'Envoi de la demande...' : `Payer ${Number(booking.total_amount).toLocaleString('fr-FR')} ${booking.currency}` }}
                        </button>
                        <p class="text-center text-xs text-navy-500">
                            Vous recevrez une notification sur votre telephone pour valider avec votre code secret.
                        </p>
                    </template>
                </form>
            </template>

            <!-- Bank card (interface preview only, not yet connected to a processor) -->
            <template v-else>
                <div class="rounded-2xl border p-5">
                    <div class="flex items-center justify-between">
                        <h2 class="font-semibold text-navy-900">Paiement par carte bancaire</h2>
                        <div class="flex gap-2">
                            <OperatorBadge
                                v-for="c in bankCards"
                                :key="c.key"
                                :operator="c"
                                size="sm"
                                :selected="detectedBrand === c.key"
                                :dimmed="!!detectedBrand && detectedBrand !== c.key"
                            />
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
