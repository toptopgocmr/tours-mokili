<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import FlightItinerary from '@/Components/FlightItinerary.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { money, PAX_LABELS } from '@/utils/flights';

defineOptions({ layout: MainLayout });

const props = defineProps({
    offer: { type: Object, required: true },
    search: { type: Object, required: true },
    airports: { type: Object, default: () => ({}) },
    international: { type: Boolean, default: true },
    contact: { type: Object, default: () => ({}) },
    cabins: { type: Object, default: () => ({}) },
});

const COUNTRIES = [
    ['CG', 'Congo'], ['CD', 'RD Congo'], ['CM', 'Cameroun'], ['GA', 'Gabon'], ['CF', 'Centrafrique'], ['TD', 'Tchad'],
    ['GQ', 'Guinee equatoriale'], ['AO', 'Angola'], ['CI', "Cote d'Ivoire"], ['SN', 'Senegal'], ['ML', 'Mali'],
    ['BF', 'Burkina Faso'], ['BJ', 'Benin'], ['TG', 'Togo'], ['NG', 'Nigeria'], ['GH', 'Ghana'], ['RW', 'Rwanda'],
    ['MA', 'Maroc'], ['TN', 'Tunisie'], ['FR', 'France'], ['BE', 'Belgique'], ['CH', 'Suisse'], ['CA', 'Canada'],
    ['US', 'Etats-Unis'], ['CN', 'Chine'], ['LB', 'Liban'], ['TR', 'Turquie'], ['AE', 'Emirats arabes unis'],
];

const blank = (type) => ({
    type, title: type === 'ADT' ? 'M.' : 'Enfant', first_name: '', last_name: '', gender: '', birth_date: '',
    nationality: 'CG', document_type: 'passport', document_number: '', document_expiry: '', document_country: 'CG',
});

const passengers = [
    ...Array.from({ length: props.search.adults }, () => blank('ADT')),
    ...Array.from({ length: props.search.children }, () => blank('CHD')),
    ...Array.from({ length: props.search.infants }, () => blank('INF')),
];

const form = useForm({
    contact_name: props.contact.name ?? '',
    contact_email: props.contact.email ?? '',
    contact_phone: props.contact.phone ?? '',
    passengers,
    accept_conditions: false,
    expected_total: props.offer.total_amount,
});

// Prix revalide par le serveur : si le prix change, la page se recharge
// avec le nouveau prix et le client doit le confirmer en renvoyant.
const priceChanged = ref(false);
watch(() => props.offer.total_amount, (v, old) => {
    if (old !== undefined && Number(v) !== Number(old)) priceChanged.value = true;
});

const submit = () => {
    form.transform((d) => ({ ...d, expected_total: props.offer.total_amount }))
        .post(`/voyage/vols/offres/${props.offer.uuid}/reserver`, { preserveScroll: true, preserveState: true });
};

const err = (i, field) => form.errors[`passengers.${i}.${field}`];
const paxIndex = (i) => {
    const type = form.passengers[i].type;
    return form.passengers.slice(0, i + 1).filter((p) => p.type === type).length;
};
const titles = (type) => (type === 'ADT' ? ['M.', 'Mme', 'Mlle'] : ['Enfant']);
const rules = computed(() => props.offer.fare_rules ?? {});
</script>

<template>
    <Head title="Passagers - billet d'avion" />

    <div class="mx-auto max-w-5xl px-6 py-10">
        <Link :href="`/voyage/vols/resultats/${search.uuid}`" class="text-sm text-navy-500 hover:text-navy-900">&larr; Retour aux resultats</Link>
        <h1 class="mt-2 text-2xl font-bold text-navy-900">Vos informations de voyage</h1>
        <p class="mt-1 text-sm text-navy-600">Saisissez les noms exactement comme sur le passeport ou la piece d'identite.</p>

        <div class="mt-6">
            <FlightItinerary :offer="offer" :search="search" :airports="airports" :cabins="cabins" />
        </div>

        <div v-if="priceChanged" class="mt-4 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            Le prix a ete mis a jour par la compagnie : <strong>{{ money(offer.total_amount, offer.currency) }}</strong>. Verifiez puis confirmez pour continuer.
        </div>

        <form class="mt-8 grid gap-8 lg:grid-cols-[1fr_300px]" @submit.prevent="submit">
            <div class="space-y-6">
                <section v-for="(p, i) in form.passengers" :key="i" class="rounded-2xl border p-5">
                    <h2 class="font-semibold text-navy-900">Passager {{ i + 1 }} · {{ PAX_LABELS[p.type] }} {{ paxIndex(i) }}</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-6">
                        <div class="sm:col-span-1">
                            <label class="text-xs font-medium text-navy-600">Civilite</label>
                            <select v-model="p.title" class="mt-1 w-full rounded-lg border-gray-200 text-sm">
                                <option v-for="t in titles(p.type)" :key="t" :value="t">{{ t }}</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-xs font-medium text-navy-600">Prenom(s)</label>
                            <input v-model="p.first_name" type="text" required class="mt-1 w-full rounded-lg border-gray-200 text-sm" />
                            <p v-if="err(i, 'first_name')" class="mt-1 text-xs text-red-600">{{ err(i, 'first_name') }}</p>
                        </div>
                        <div class="sm:col-span-3">
                            <label class="text-xs font-medium text-navy-600">Nom de famille</label>
                            <input v-model="p.last_name" type="text" required class="mt-1 w-full rounded-lg border-gray-200 text-sm uppercase" />
                            <p v-if="err(i, 'last_name')" class="mt-1 text-xs text-red-600">{{ err(i, 'last_name') }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-xs font-medium text-navy-600">Sexe</label>
                            <select v-model="p.gender" class="mt-1 w-full rounded-lg border-gray-200 text-sm">
                                <option value="">-</option><option value="M">Masculin</option><option value="F">Feminin</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-xs font-medium text-navy-600">Date de naissance</label>
                            <input v-model="p.birth_date" type="date" required class="mt-1 w-full rounded-lg border-gray-200 text-sm" />
                            <p v-if="err(i, 'birth_date')" class="mt-1 text-xs text-red-600">{{ err(i, 'birth_date') }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-xs font-medium text-navy-600">Nationalite</label>
                            <select v-model="p.nationality" class="mt-1 w-full rounded-lg border-gray-200 text-sm">
                                <option v-for="[code, name] in COUNTRIES" :key="code" :value="code">{{ name }}</option>
                            </select>
                        </div>
                        <template v-if="international || p.document_number">
                            <div class="sm:col-span-2">
                                <label class="text-xs font-medium text-navy-600">N° de passeport <span v-if="international" class="text-red-600">*</span></label>
                                <input v-model="p.document_number" type="text" :required="international" autocomplete="off" class="mt-1 w-full rounded-lg border-gray-200 text-sm uppercase" />
                                <p v-if="err(i, 'document_number')" class="mt-1 text-xs text-red-600">{{ err(i, 'document_number') }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-xs font-medium text-navy-600">Expire le</label>
                                <input v-model="p.document_expiry" type="date" :required="international" class="mt-1 w-full rounded-lg border-gray-200 text-sm" />
                                <p v-if="err(i, 'document_expiry')" class="mt-1 text-xs text-red-600">{{ err(i, 'document_expiry') }}</p>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-xs font-medium text-navy-600">Pays de delivrance</label>
                                <select v-model="p.document_country" class="mt-1 w-full rounded-lg border-gray-200 text-sm">
                                    <option v-for="[code, name] in COUNTRIES" :key="code" :value="code">{{ name }}</option>
                                </select>
                            </div>
                        </template>
                    </div>
                </section>

                <section class="rounded-2xl border p-5">
                    <h2 class="font-semibold text-navy-900">Contact pour le dossier</h2>
                    <p class="text-xs text-navy-500">Confirmation, billet electronique et alertes envoyes a ces coordonnees.</p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <div>
                            <label class="text-xs font-medium text-navy-600">Nom</label>
                            <input v-model="form.contact_name" type="text" required class="mt-1 w-full rounded-lg border-gray-200 text-sm" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-navy-600">E-mail</label>
                            <input v-model="form.contact_email" type="email" required class="mt-1 w-full rounded-lg border-gray-200 text-sm" />
                            <p v-if="form.errors.contact_email" class="mt-1 text-xs text-red-600">{{ form.errors.contact_email }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-navy-600">Telephone (WhatsApp)</label>
                            <input v-model="form.contact_phone" type="tel" required placeholder="+242 06 000 0000" class="mt-1 w-full rounded-lg border-gray-200 text-sm" />
                        </div>
                    </div>
                </section>
            </div>

            <aside class="h-fit space-y-4 rounded-2xl border p-5 shadow-sm lg:sticky lg:top-24">
                <p class="font-semibold text-navy-900">Recapitulatif</p>
                <ul class="space-y-1 text-sm text-navy-700">
                    <li v-for="p in offer.passengers" :key="p.type" class="flex justify-between">
                        <span>{{ p.count }} × {{ PAX_LABELS[p.type] }}</span><span>{{ money(p.unit_amount * p.count, offer.currency) }}</span>
                    </li>
                    <li v-if="Number(offer.service_fee)" class="flex justify-between"><span>Frais de service</span><span>{{ money(offer.service_fee, offer.currency) }}</span></li>
                </ul>
                <p class="flex justify-between border-t pt-3 text-lg font-bold text-navy-900"><span>Total</span><span>{{ money(offer.total_amount, offer.currency) }}</span></p>
                <p class="text-xs text-navy-500">
                    {{ rules.refundable ? 'Billet remboursable' : 'Billet non remboursable' }}<span v-if="rules.cancel_fee != null && rules.refundable"> (frais {{ money(rules.cancel_fee, offer.currency) }})</span>.
                    {{ rules.changeable === false ? 'Non modifiable.' : (rules.change_fee != null ? `Modification : ${money(rules.change_fee, offer.currency)} + difference tarifaire.` : '') }}
                </p>
                <label class="flex items-start gap-2 text-xs text-navy-700">
                    <input v-model="form.accept_conditions" type="checkbox" class="mt-0.5" />
                    <span>J'ai verifie les noms des passagers et j'accepte les conditions tarifaires, d'annulation et de remboursement.</span>
                </label>
                <p v-if="form.errors.accept_conditions" class="text-xs text-red-600">{{ form.errors.accept_conditions }}</p>
                <button type="submit" class="btn-gold w-full" :disabled="form.processing">
                    {{ form.processing ? 'Verification du prix...' : 'Confirmer le prix et payer' }}
                </button>
                <p class="text-center text-xs text-navy-500">Le prix est revalide aupres de la compagnie avant le paiement.</p>
            </aside>
        </form>
    </div>
</template>
