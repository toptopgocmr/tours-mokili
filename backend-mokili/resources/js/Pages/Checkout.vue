<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import OperatorBadge from '@/Components/OperatorBadge.vue';
import CountryPicker from '@/Components/CountryPicker.vue';
import 'flag-icons/css/flag-icons.min.css';
import { bankCards, detectCardBrand } from '@/data/cemac';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import axios from 'axios';
import { dayHeading, rideTime, RIDE_ICONS } from '@/utils/rides';

defineOptions({ layout: MainLayout });

const props = defineProps({
    booking: { type: Object, required: true },
    wallet: { type: Object, default: null },
    methods: { type: Object, default: () => ({ countries: [], gateways: [] }) },
    lastPayment: { type: Object, default: null },
    receiptUrl: { type: String, default: null },
});

// Onglet affiche : Mobile Money (Peex / pawaPay / MaishaPay / Digitwace) ou
// carte (apercu d'interface, pas encore relie a un processeur).
const method = ref('mobile_money');

// Trajet train / bus / canot / taxi : recapitulatif facon SNCF Connect.
const ride = computed(() => (props.booking.bookable?.type === 'circuit' ? props.booking.bookable : null));
const STATUS_LABELS = { pending: 'En attente', awaiting_payment: 'En attente de paiement', confirmed: 'Confirmee', paid: 'Payee', cancelled: 'Annulee', failed: 'Echec', refunded: 'Remboursee', completed: 'Terminee' };
const statusLabel = computed(() => STATUS_LABELS[String(props.booking.status).toLowerCase()] ?? props.booking.status);

const countries = computed(() => props.methods?.countries ?? []);
const flagClass = (code) => (code ? `fi fi-${String(code).toLowerCase()}` : null);
const imgBroken = ref(false);
const money = (v) => Number(v ?? 0).toLocaleString('fr-FR');
const fmtDate = (d) => (d ? new Date(d).toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'long', year: 'numeric' }) : '');
// Recapitulatif generique (logement, voiture, evenement, parc...).
const item = computed(() => {
    const b = props.booking.bookable ?? {};
    return {
        title: b.title ?? b.name ?? props.booking.meta?.park ?? 'Votre reservation',
        place: [b.city ?? props.booking.meta?.city, b.country].filter(Boolean).join(', '),
        image: b.image_url ?? null,
        isLodging: String(props.booking.bookable_type ?? '').endsWith('LodgingListing'),
        isVehicle: String(props.booking.bookable_type ?? '').endsWith('Vehicle'),
    };
});
const STATUS_STYLE = { awaiting_payment: 'bg-amber-100 text-amber-800', pending: 'bg-amber-100 text-amber-800', confirmed: 'bg-green-100 text-green-800', paid: 'bg-green-100 text-green-800', completed: 'bg-green-100 text-green-800', cancelled: 'bg-red-100 text-red-700', failed: 'bg-red-100 text-red-700' };
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
const payableCountries = computed(() => countries.value.filter((c) => c.gateways?.length));
const soonCountries = computed(() => countries.value.filter((c) => !c.gateways?.length));
const countryPayable = computed(() => (country.value?.gateways?.length ?? 0) > 0);

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

// Billetterie aerienne : paiement confirme ne veut pas dire billet emis.
const isFlight = computed(() => props.booking.bookable_type?.endsWith('FlightBooking') && !!props.booking.bookable);
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
            router.reload({ only: ['booking', 'lastPayment', 'receiptUrl'] });
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

    <div v-if="ride" class="border-b border-navy-100 bg-white">
        <div class="mx-auto flex max-w-4xl items-center gap-2 overflow-x-auto px-6 py-3 text-sm">
            <template v-for="(st, i) in ['Trajet', 'Recapitulatif', 'Paiement']" :key="st">
                <span class="flex shrink-0 items-center gap-2" :class="i === 2 ? 'font-semibold text-navy-900' : 'text-emerald-700'">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold text-white" :class="i === 2 ? 'bg-sky-700' : 'bg-emerald-600'">{{ i === 2 ? 3 : '✓' }}</span>
                    {{ st }}
                </span>
                <span v-if="i < 2" class="h-px w-8 shrink-0 bg-navy-200" />
            </template>
        </div>
    </div>
    <div class="mx-auto grid max-w-4xl gap-10 px-6 py-12 md:grid-cols-2">
        <div>
            <div v-if="ride" class="mb-4 rounded-2xl border border-navy-100 bg-white">
                <div class="border-b border-navy-100 px-5 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Aller</p>
                    <p class="font-bold text-navy-900">{{ dayHeading(ride.departure_at) }}</p>
                </div>
                <div class="grid grid-cols-[56px_16px_1fr] gap-x-3 px-5 py-4 text-sm">
                    <p class="text-lg font-bold tabular-nums text-navy-900">{{ rideTime(ride.departure_at) }}</p>
                    <div class="flex flex-col items-center pt-1.5"><span class="h-3 w-3 rounded-full border-[3px] border-sky-700" /><span class="w-0.5 flex-1 bg-sky-700" /></div>
                    <div class="pb-4"><p class="font-semibold text-navy-900">{{ ride.origin_city }}</p><p v-if="ride.departure_point" class="text-xs text-navy-500">{{ ride.departure_point }}</p></div>
                    <p class="text-lg font-bold tabular-nums text-navy-900">{{ rideTime(ride.arrival_at) }}</p>
                    <div class="flex justify-center"><span class="h-3 w-3 rounded-full border-[3px] border-sky-700" /></div>
                    <p class="font-semibold text-navy-900">{{ ride.destination_city }}</p>
                </div>
                <div class="flex items-center gap-3 border-t border-navy-100 px-5 py-3 text-sm">
                    <span class="text-xl">{{ RIDE_ICONS[ride.transport_mode] ?? '🧭' }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-navy-900">{{ ride.airline || 'Transporteur partenaire' }}</p>
                        <p class="text-xs text-navy-500">{{ ride.transport_mode_label }}</p>
                    </div>
                    <span class="shrink-0 whitespace-nowrap rounded-full bg-navy-50 px-2.5 py-1 text-xs font-semibold text-navy-700">{{ booking.quantity }} voyageur{{ booking.quantity > 1 ? 's' : '' }}</span>
                </div>
            </div>
            <h1 class="text-2xl font-extrabold text-navy-900">Recapitulatif</h1>
            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div v-if="!ride && !isFlight" class="flex gap-4 border-b border-slate-100 p-4">
                    <img v-if="item.image && !imgBroken" :src="item.image" alt="" class="h-20 w-24 shrink-0 rounded-xl object-cover" @error="imgBroken = true" />
                    <div class="min-w-0">
                        <p class="font-bold leading-tight text-navy-900">{{ item.title }}</p>
                        <p v-if="item.place" class="mt-0.5 text-sm text-navy-500">{{ item.place }}</p>
                    </div>
                </div>
                <dl class="divide-y divide-slate-100 text-sm">
                    <div class="flex items-center justify-between px-4 py-3"><dt class="text-navy-500">Reference</dt><dd class="font-mono font-semibold text-navy-900">{{ booking.reference }}</dd></div>
                    <div class="flex items-center justify-between px-4 py-3">
                        <dt class="text-navy-500">Statut</dt>
                        <dd><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="STATUS_STYLE[booking.status] ?? 'bg-slate-100 text-slate-700'">{{ statusLabel }}</span></dd>
                    </div>
                    <template v-if="isFlight">
                        <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-navy-500">Dossier</dt><dd class="font-semibold">{{ booking.bookable.reference }}</dd></div>
                        <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-navy-500">Vol</dt><dd class="text-right">{{ booking.bookable.title }}</dd></div>
                        <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-navy-500">Depart</dt><dd>{{ fmtDate(booking.bookable.depart_date) }}</dd></div>
                        <div v-if="booking.bookable.return_date" class="flex justify-between gap-4 px-4 py-3"><dt class="text-navy-500">Retour</dt><dd>{{ fmtDate(booking.bookable.return_date) }}</dd></div>
                        <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-navy-500">Passagers</dt><dd>{{ booking.bookable.passenger_count }}</dd></div>
                    </template>
                    <template v-else-if="item.isVehicle && booking.starts_at">
                        <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-navy-500">Prise en charge</dt><dd class="text-right font-semibold">{{ fmtDate(booking.starts_at) }} a {{ new Date(booking.starts_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }}</dd></div>
                        <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-navy-500">Restitution</dt><dd class="text-right font-semibold">{{ fmtDate(booking.ends_at) }}</dd></div>
                        <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-navy-500">Duree</dt><dd>{{ booking.quantity }} jour(s)</dd></div>
                        <div v-if="booking.meta?.pickup_label" class="flex justify-between gap-4 px-4 py-3"><dt class="text-navy-500">Remise</dt><dd class="text-right">{{ booking.meta.pickup_label }}<span v-if="booking.meta.pickup_address" class="block text-xs text-navy-500">{{ booking.meta.pickup_address }}</span></dd></div>
                        <div v-if="booking.meta?.with_driver" class="flex justify-between gap-4 px-4 py-3"><dt class="text-navy-500">Chauffeur</dt><dd>Demande (tarif confirme par le loueur)</dd></div>
                    </template>
                    <template v-else-if="item.isLodging && booking.starts_at">
                        <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-navy-500">Arrivee</dt><dd class="font-semibold">{{ fmtDate(booking.starts_at) }}</dd></div>
                        <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-navy-500">Depart</dt><dd class="font-semibold">{{ fmtDate(booking.ends_at) }}</dd></div>
                        <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-navy-500">Sejour</dt><dd>{{ booking.quantity }} nuit(s){{ booking.meta?.guests ? ` · ${booking.meta.guests} voyageur(s)` : '' }}</dd></div>
                    </template>
                    <template v-else>
                        <div v-if="booking.meta?.park && booking.starts_at" class="flex justify-between gap-4 px-4 py-3"><dt class="text-navy-500">Visite le</dt><dd>{{ fmtDate(booking.starts_at) }}</dd></div>
                        <div v-if="booking.meta?.adults !== undefined" class="flex justify-between gap-4 px-4 py-3"><dt class="text-navy-500">Billets</dt><dd>{{ booking.meta.adults }} adulte(s), {{ booking.meta.children ?? 0 }} enfant(s)</dd></div>
                        <div v-else-if="!ride" class="flex justify-between gap-4 px-4 py-3"><dt class="text-navy-500">Quantite</dt><dd>{{ booking.quantity }}</dd></div>
                    </template>
                    <div v-if="Number(booking.unit_price) && booking.quantity > 1" class="flex justify-between gap-4 px-4 py-3 text-navy-600"><dt>{{ money(booking.unit_price) }} {{ booking.currency }} × {{ booking.quantity }}</dt><dd>{{ money(booking.total_amount) }} {{ booking.currency }}</dd></div>
                </dl>
                <div class="flex items-end justify-between bg-navy-900 px-4 py-4 text-white">
                    <span class="text-sm text-white/80">Total a payer</span>
                    <span class="text-2xl font-extrabold text-gold-400">{{ money(booking.total_amount) }} {{ booking.currency }}</span>
                </div>
            </div>
            <p v-if="isFlight" class="mt-3 rounded-xl bg-navy-50/70 p-3 text-xs text-navy-700">Le prix a ete confirme aupres de la compagnie. Apres le paiement, la reservation puis l'emission du billet sont suivies dans votre dossier.</p>
            <ul class="mt-4 space-y-1.5 text-xs text-navy-600">
                <li class="flex items-center gap-2"><span class="text-green-600">✓</span> Paiement securise, confirme sur votre telephone</li>
                <li class="flex items-center gap-2"><span class="text-green-600">✓</span> Aucun frais cache : le montant affiche est le montant debite</li>
                <li class="flex items-center gap-2"><span class="text-green-600">✓</span> Confirmation immediate apres validation</li>
            </ul>
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
                    <p v-if="isFlight" class="mt-1 text-sm">
                        Votre paiement est confirme. La reservation de votre vol et l'emission du billet sont en cours :
                        votre billet electronique vous sera envoye des son emission.
                        <Link :href="`/voyage/vols/dossiers/${booking.bookable.reference}`" class="font-semibold underline">Suivre mon dossier</Link>
                    </p>
                    <p v-else class="mt-1 text-sm">Votre reservation {{ booking.reference }} est confirmee. Merci d'avoir choisi MOKILI TOUR !</p>
                    <a v-if="receiptUrl" :href="receiptUrl" target="_blank" rel="noopener" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-green-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-800">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a1 1 0 011 1v7.59l2.3-2.3a1 1 0 111.4 1.42l-4 4a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.42L9 10.59V3a1 1 0 011-1zM4 15a1 1 0 011 1v1h10v-1a1 1 0 112 0v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2a1 1 0 011-1z" /></svg>
                        Voir et telecharger mon recu
                    </a>
                </div>

                <div v-else-if="isPending" class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-900">
                    <div class="flex items-center gap-3">
                        <span class="h-5 w-5 animate-spin rounded-full border-2 border-amber-400 border-t-transparent" />
                        <p class="font-semibold">En attente de votre validation</p>
                    </div>
                    <p class="mt-2 text-sm">
                        Une demande de {{ Number(lastPayment.amount).toLocaleString('fr-FR') }} {{ lastPayment.currency }} a ete envoyee au
                        <strong>+{{ lastPayment.phone }}</strong>.
                        Composez votre code secret Mobile Money pour confirmer. Cette page se met a jour automatiquement.
                    </p>
                </div>

                <form v-else class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="pay">
                    <div v-if="lastPayment?.status === 'failed'" class="rounded-lg bg-red-50 p-3 text-sm text-red-700">
                        Dernier essai refuse : {{ lastPayment.failure_reason ?? 'paiement non abouti' }}. Vous pouvez reessayer.
                    </div>

                    <div v-if="!countries.length" class="rounded-lg bg-slate-50 p-3 text-sm text-navy-700">
                        Le paiement en ligne est momentanement indisponible. Contactez le service client MOKILI TOUR.
                    </div>

                    <template v-else>
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wide text-navy-700">1. Pays</label>
                            <CountryPicker v-model="form.country_code" :countries="countries" class="mt-1.5" />
                        </div>

                        <div v-if="countryPayable && country?.networks?.length">
                            <label class="text-xs font-bold uppercase tracking-wide text-navy-700">2. Operateur</label>
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
                            <label class="text-xs font-bold uppercase tracking-wide text-navy-700">{{ countryPayable && country?.networks?.length ? 3 : 2 }}. Numero Mobile Money</label>
                            <div class="mt-1.5 flex overflow-hidden rounded-xl border border-slate-300 bg-white focus-within:border-gold-400 focus-within:ring-2 focus-within:ring-gold-400">
                                <span class="flex shrink-0 items-center gap-2 border-r border-slate-200 bg-slate-50 px-3 text-sm font-semibold tabular-nums text-navy-800">
                                    <span v-if="flagClass(country?.code)" :class="flagClass(country?.code)" class="!h-4 !w-6 rounded-sm bg-cover ring-1 ring-black/10" />
                                    +{{ country?.dial_code }}
                                </span>
                                <input v-model="form.phone" type="tel" inputmode="tel" placeholder="Votre numero Mobile Money" autocomplete="tel-national" class="w-full border-0 px-3 py-2.5 text-sm tracking-wide focus:ring-0" required />
                            </div>
                            <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600">{{ form.errors.phone }}</p>
                        </div>

                        <!-- La passerelle (agregateur) est choisie automatiquement : elle n'est pas affichee au client. -->
                        <p v-if="form.errors.gateway" class="text-xs text-red-600">{{ form.errors.gateway }}</p>
                        <div v-if="!countryPayable" class="rounded-lg bg-amber-50 p-3 text-sm text-amber-900">
                            Le paiement Mobile Money depuis <strong>{{ country?.name }}</strong> arrive bientot.
                            Utilisez un numero Mobile Money d'un pays disponible, ou contactez le service client MOKILI TOUR.
                        </div>

                        <button type="submit" class="btn-gold w-full py-3.5 text-base disabled:cursor-not-allowed disabled:opacity-50" :disabled="form.processing || !countryPayable || !form.gateway || !form.phone">
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
