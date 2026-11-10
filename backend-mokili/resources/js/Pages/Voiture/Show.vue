<script setup>
import PromoBadge from '@/Components/PromoBadge.vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import ListingImage from '@/Components/ListingImage.vue';
import ContactButtons from '@/Components/ContactButtons.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import axios from 'axios';

defineOptions({ layout: MainLayout });

// Fiche vehicule : location (calendrier des disponibilites, verification
// en direct, reservation puis paiement Mobile Money), vente et mecanique
// (contact direct).
const props = defineProps({
    vehicle: { type: Object, required: true },
    similar: { type: Array, default: () => [] },
    busyDays: { type: Array, default: () => [] },
    pickupModes: { type: Object, default: () => ({}) },
    search: { type: Object, default: () => ({}) },
});

const v = computed(() => props.vehicle);
const isRental = computed(() => v.value.offer_type === 'location' && !!v.value.price_per_day);
const money = (x) => Number(x ?? 0).toLocaleString('fr-FR');
const user = computed(() => usePage().props.auth?.user);

const price = computed(() => (v.value.display_price
    ? `${v.value.display_price.suffix === 'a partir de' ? 'A partir de ' : ''}${money(v.value.display_price.amount)} ${v.value.currency}`
    : 'Prix sur demande'));

const cat = computed(() => usePage().props.catalogue?.voiture ?? {});
const specs = computed(() => [
    v.value.condition && ['Etat', cat.value.conditions?.[v.value.condition] ?? v.value.condition],
    v.value.year && ['Annee', v.value.year],
    v.value.seats && ['Places', `${v.value.seats} places`],
    v.value.offer_type !== 'mecanique' && v.value.transmission && ['Boite', v.value.transmission === 'manuelle' ? 'Manuelle' : 'Automatique'],
    v.value.fuel && ['Carburant', cat.value.fuels?.[v.value.fuel] ?? v.value.fuel],
    v.value.color && ['Couleur', cat.value.colors?.[v.value.color]?.[0] ?? v.value.color],
    v.value.doors && ['Portes', `${v.value.doors} portes`],
    v.value.engine && ['Moteur', v.value.engine],
    v.value.offer_type === 'vente' && ['Premiere main', v.value.first_owner ? 'Oui' : 'Non'],
    v.value.mileage_km && ['Kilometrage', `${money(v.value.mileage_km)} km`],
    v.value.category && ['Categorie', cat.value.categories?.[v.value.category] ?? v.value.category],
    v.value.city && ['Ville', v.value.city],
].filter(Boolean));

// ---------- Location ----------
const iso = (d) => {
    const x = new Date(d);
    return `${x.getFullYear()}-${String(x.getMonth() + 1).padStart(2, '0')}-${String(x.getDate()).padStart(2, '0')}`;
};
const today = iso(new Date());
const addDays = (d, n) => {
    const x = new Date(d + 'T12:00:00');
    x.setDate(x.getDate() + n);
    return iso(x);
};
const busy = computed(() => new Set(props.busyDays));

const form = useForm({
    starts_at: props.search.start || addDays(today, 1),
    ends_at: props.search.end || addDays(today, 3),
    pickup_time: props.search.start_time || '10:00',
    return_time: props.search.end_time || props.search.start_time || '10:00',
    return_elsewhere: !!props.search.return_city,
    return_place: props.search.return_city || '',
    driver_age: props.search.age || '30+',
    pickup_mode: 'agence',
    pickup_address: '',
    with_driver: false,
    notes: '',
});

const check = ref({ loading: false, available: null, days: 0, total: 0, next_free: null });
let t = null;
const runCheck = () => {
    clearTimeout(t);
    if (!isRental.value || !form.starts_at || !form.ends_at || form.ends_at < form.starts_at) return;
    check.value.loading = true;
    t = setTimeout(async () => {
        try {
            const { data } = await axios.get(`/voiture/${v.value.slug}/disponibilite`, { params: { start: form.starts_at, end: form.ends_at } });
            check.value = { loading: false, ...data };
        } catch {
            check.value = { loading: false, available: null, days: 0, total: 0, next_free: null };
        }
    }, 250);
};
watch(() => [form.starts_at, form.ends_at], () => {
    if (form.ends_at < form.starts_at) form.ends_at = form.starts_at;
    runCheck();
}, { immediate: true });

const useNextFree = () => {
    const len = Math.max(0, Math.round((new Date(form.ends_at) - new Date(form.starts_at)) / 86400000));
    form.starts_at = check.value.next_free;
    form.ends_at = addDays(check.value.next_free, len);
};

// Calendrier (2 mois) : jours pris en rouge, periode choisie en bleu.
const monthOffset = ref(0);
const months = computed(() => [0, 1].map((k) => {
    const first = new Date();
    first.setDate(1);
    first.setMonth(first.getMonth() + monthOffset.value + k);
    const label = first.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
    const lead = (first.getDay() + 6) % 7; // lundi en premier
    const count = new Date(first.getFullYear(), first.getMonth() + 1, 0).getDate();
    const cells = Array.from({ length: lead }, () => null);
    for (let d = 1; d <= count; d++) cells.push(iso(new Date(first.getFullYear(), first.getMonth(), d)));
    return { label, cells };
}));
const picking = ref('start');
const pickDay = (d) => {
    if (!d || d < today || busy.value.has(d)) return;
    if (picking.value === 'start' || d < form.starts_at) {
        form.starts_at = d;
        if (form.ends_at < d) form.ends_at = d;
        picking.value = 'end';
    } else {
        form.ends_at = d;
        picking.value = 'start';
    }
};
const dayClass = (d) => {
    if (!d) return '';
    if (d < today) return 'text-slate-300 cursor-default';
    if (busy.value.has(d)) return 'bg-red-50 text-red-400 line-through cursor-not-allowed';
    if (d === form.starts_at || d === form.ends_at) return 'bg-[#006ce4] text-white font-bold';
    if (d > form.starts_at && d < form.ends_at) return 'bg-blue-100 text-[#003b95]';
    return 'hover:bg-slate-100 text-slate-800';
};

const fmt = (d) => (d ? new Date(d + 'T12:00:00').toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' }) : '');
const canBook = computed(() => check.value.available === true && !check.value.loading && (form.pickup_mode !== 'livraison' || form.pickup_address) && (!form.return_elsewhere || form.return_place));
const isSale = computed(() => v.value.offer_type === 'vente');
const saleMsg = (what) => `Bonjour, je suis interesse(e) par votre ${v.value.title} vu sur MOKILI TOUR. ${what}`;
const SAFETY = ['Rencontrez le vendeur dans un lieu public et de jour.', 'Verifiez la carte grise, le controle technique et le numero de chassis.', 'Faites un essai routier et, si possible, un diagnostic dans un garage.', "Ne versez jamais d'acompte avant d'avoir vu le vehicule."];
const submit = () => form.post(`/voiture/${v.value.slug}/reserver`, { preserveScroll: true });

const CONDITIONS = [
    ['Permis de conduire', 'Permis valide depuis au moins 2 ans (sauf location avec chauffeur).'],
    ["Piece d'identite", "CNI ou passeport presente a la prise en charge."],
    ['Carburant', 'Le vehicule est rendu avec le meme niveau de carburant.'],
    ['Annulation', "Gratuite jusqu'a 24 h avant la prise en charge."],
];
</script>

<template>
    <Head :title="vehicle.title" />

    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6">
        <nav class="text-xs text-[#006ce4]">
            <Link href="/" class="hover:underline">Accueil</Link> ›
            <Link href="/voiture" class="hover:underline">Voitures</Link> ›
            <Link :href="`/voiture?type=${v.offer_type}`" class="hover:underline">{{ v.offer_type_label }}</Link> ›
            <span class="text-slate-500">{{ v.title }}</span>
        </nav>

        <div class="mt-4 flex flex-wrap items-start justify-between gap-3">
            <div>
                <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">{{ v.offer_type_label }}</span>
                <h1 class="mt-1 text-2xl font-extrabold text-slate-900 sm:text-3xl">{{ v.title }}</h1>
                <p class="mt-1 text-sm text-slate-600">{{ [v.brand, v.model].filter(Boolean).join(' ') }}<span v-if="v.city"> · {{ v.city }}</span></p>
            </div>
            <PromoBadge :promo="v.promo" show-end />
        </div>

        <div class="mt-5 grid gap-8 lg:grid-cols-[1fr_380px]">
            <div class="min-w-0">
                <div class="overflow-hidden rounded-2xl bg-slate-100">
                    <ListingImage :src="v.image_url" fallback-class="bg-orange-600 text-xl font-semibold" class="h-64 sm:h-96">{{ v.brand }} {{ v.model }}</ListingImage>
                </div>

                <!-- Caracteristiques -->
                <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div v-for="[label, val] in specs.slice(0, 8)" :key="label" class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ label }}</p>
                        <p class="mt-0.5 font-bold text-slate-900">{{ val }}</p>
                    </div>
                </div>

                <section v-if="isSale" class="mt-8">
                    <h2 class="text-xl font-extrabold text-slate-900">Fiche technique</h2>
                    <dl class="mt-3 divide-y rounded-2xl border border-slate-200 bg-white text-sm">
                        <div v-for="[k, val] in [['Marque', v.brand], ['Modele', v.model], ...specs]" :key="k" class="flex justify-between px-4 py-2.5"><dt class="text-slate-500">{{ k }}</dt><dd class="font-semibold text-slate-900">{{ val || '-' }}</dd></div>
                    </dl>
                </section>

                <section v-if="v.description" class="mt-8">
                    <h2 class="text-xl font-extrabold text-slate-900">Description</h2>
                    <p class="mt-2 whitespace-pre-line leading-relaxed text-slate-700">{{ v.description }}</p>
                </section>

                <section v-if="v.options?.length" class="mt-8">
                    <h2 class="text-xl font-extrabold text-slate-900">Equipements</h2>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <span v-for="o in v.options" :key="o" class="flex items-center gap-2 text-sm text-slate-700"><span class="text-green-600">✓</span>{{ o }}</span>
                    </div>
                </section>

                <section v-if="v.services?.length" class="mt-8">
                    <h2 class="text-xl font-extrabold text-slate-900">{{ v.offer_type === 'mecanique' ? 'Prestations' : 'Inclus' }}</h2>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span v-for="s in v.services" :key="s" class="rounded-full bg-green-50 px-3 py-1 text-sm font-medium text-green-800">✓ {{ s }}</span>
                    </div>
                </section>

                <!-- Disponibilites -->
                <section v-if="isRental" id="disponibilites" class="mt-8">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-extrabold text-slate-900">Disponibilites</h2>
                        <div class="flex gap-1">
                            <button type="button" class="h-8 w-8 rounded-full border text-slate-600 disabled:opacity-30" :disabled="monthOffset === 0" @click="monthOffset--">‹</button>
                            <button type="button" class="h-8 w-8 rounded-full border text-slate-600 disabled:opacity-30" :disabled="monthOffset >= 2" @click="monthOffset++">›</button>
                        </div>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">Cliquez sur le jour de prise en charge, puis sur le jour de restitution.</p>
                    <div class="mt-4 grid gap-6 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2">
                        <div v-for="m in months" :key="m.label">
                            <p class="mb-2 text-center text-sm font-bold capitalize text-slate-900">{{ m.label }}</p>
                            <div class="grid grid-cols-7 gap-1 text-center text-[11px] font-semibold text-slate-400">
                                <span v-for="(d, i) in ['L', 'M', 'M', 'J', 'V', 'S', 'D']" :key="i">{{ d }}</span>
                            </div>
                            <div class="mt-1 grid grid-cols-7 gap-1">
                                <button
                                    v-for="(d, i) in m.cells"
                                    :key="i"
                                    type="button"
                                    class="h-9 rounded-md text-sm tabular-nums transition"
                                    :class="dayClass(d)"
                                    :disabled="!d"
                                    @click="pickDay(d)"
                                >{{ d ? Number(d.slice(8)) : '' }}</button>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-4 text-xs text-slate-600 sm:col-span-2">
                            <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-[#006ce4]" />Vos dates</span>
                            <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-red-100" />Deja reserve</span>
                            <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded border" />Disponible</span>
                        </div>
                    </div>
                </section>

                <section v-if="isRental" class="mt-8">
                    <h2 class="text-xl font-extrabold text-slate-900">Conditions de location</h2>
                    <div class="mt-3 divide-y rounded-2xl border border-slate-200 bg-white text-sm">
                        <div v-for="[k, txt] in CONDITIONS" :key="k" class="grid gap-1 px-4 py-3 sm:grid-cols-[180px_1fr]">
                            <p class="font-semibold text-slate-900">{{ k }}</p>
                            <p class="text-slate-600">{{ txt }}</p>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Encart reservation / contact -->
            <aside class="h-fit lg:sticky lg:top-24">
                <form v-if="isRental" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="submit">
                    <p class="text-2xl font-extrabold text-slate-900">
                        <span v-if="v.display_price?.original" class="mr-1 text-base font-normal text-red-500 line-through">{{ money(v.display_price.original) }}</span>
                        {{ money(v.display_price?.amount) }} {{ v.currency }}
                        <span class="text-sm font-normal text-slate-500">/ jour</span>
                    </p>

                    <div class="mt-4 grid grid-cols-2 overflow-hidden rounded-xl border border-slate-300">
                        <label class="border-r border-slate-200 px-3 py-2" :class="picking === 'start' && 'bg-blue-50'">
                            <span class="block text-[11px] font-semibold text-slate-500">Prise en charge</span>
                            <input v-model="form.starts_at" type="date" :min="today" class="w-full border-0 p-0 text-sm font-semibold focus:ring-0" />
                        </label>
                        <label class="px-3 py-2" :class="picking === 'end' && 'bg-blue-50'">
                            <span class="block text-[11px] font-semibold text-slate-500">Restitution</span>
                            <input v-model="form.ends_at" type="date" :min="form.starts_at || today" class="w-full border-0 p-0 text-sm font-semibold focus:ring-0" />
                        </label>
                        <label class="border-r border-t border-slate-200 px-3 py-2">
                            <span class="block text-[11px] font-semibold text-slate-500">Heure de depart</span>
                            <input v-model="form.pickup_time" type="time" step="1800" class="w-full border-0 p-0 text-sm font-semibold focus:ring-0" />
                        </label>
                        <label class="border-t border-slate-200 px-3 py-2">
                            <span class="block text-[11px] font-semibold text-slate-500">Heure de retour</span>
                            <input v-model="form.return_time" type="time" step="1800" class="w-full border-0 p-0 text-sm font-semibold focus:ring-0" />
                        </label>
                    </div>

                    <!-- Etat de disponibilite -->
                    <div class="mt-3 rounded-xl px-3 py-2.5 text-sm font-semibold" :class="check.loading ? 'bg-slate-50 text-slate-500' : check.available ? 'bg-green-50 text-green-800' : check.available === false ? 'bg-red-50 text-red-700' : 'bg-slate-50 text-slate-500'">
                        <template v-if="check.loading">Verification des disponibilites...</template>
                        <template v-else-if="check.available">✓ Disponible du {{ fmt(form.starts_at) }} au {{ fmt(form.ends_at) }}</template>
                        <template v-else-if="check.available === false">
                            ✕ Deja reserve sur ces dates.
                            <button v-if="check.next_free" type="button" class="mt-1 block text-left text-xs font-semibold text-[#006ce4] underline" @click="useNextFree">Prochaine disponibilite : a partir du {{ fmt(check.next_free) }}</button>
                        </template>
                        <template v-else>Choisissez vos dates.</template>
                    </div>

                    <div class="mt-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-700">Remise du vehicule</p>
                        <div class="mt-2 space-y-1.5">
                            <label v-for="(label, key) in pickupModes" :key="key" class="flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm" :class="form.pickup_mode === key ? 'border-[#006ce4] bg-blue-50' : 'border-slate-200'">
                                <input v-model="form.pickup_mode" type="radio" :value="key" class="text-[#006ce4]" /> {{ label }}
                            </label>
                        </div>
                        <input v-if="form.pickup_mode === 'livraison'" v-model="form.pickup_address" type="text" placeholder="Adresse de livraison (quartier, rue...)" class="mt-2 w-full rounded-lg border-slate-300 text-sm" />
                        <p v-if="form.errors.pickup_address" class="mt-1 text-xs text-red-600">{{ form.errors.pickup_address }}</p>
                        <label class="mt-3 flex items-center gap-2 text-sm text-slate-700">
                            <input v-model="form.return_elsewhere" type="checkbox" class="rounded text-[#006ce4]" /> Retour dans un autre lieu
                        </label>
                        <input v-if="form.return_elsewhere" v-model="form.return_place" type="text" placeholder="Lieu de retour (ville, aeroport...)" class="mt-2 w-full rounded-lg border-slate-300 text-sm" />
                        <p v-if="form.errors.return_place" class="mt-1 text-xs text-red-600">{{ form.errors.return_place }}</p>
                        <label class="mt-2 flex items-center gap-2 text-sm text-slate-700">
                            <input v-model="form.with_driver" type="checkbox" class="rounded text-[#006ce4]" /> Je souhaite un chauffeur (tarif confirme par le loueur)
                        </label>
                        <label class="mt-3 flex items-center justify-between gap-2 text-sm text-slate-700">
                            <span>Age du conducteur</span>
                            <select v-model="form.driver_age" class="rounded-lg border-slate-300 py-1 text-sm font-semibold">
                                <option value="18-24">18 - 24 ans</option>
                                <option value="25-29">25 - 29 ans</option>
                                <option value="30+">30 ans et plus</option>
                            </select>
                        </label>
                        <p v-if="form.driver_age === '18-24' && !form.with_driver" class="mt-1 text-xs text-amber-700">Jeune conducteur : le loueur peut demander une caution plus elevee.</p>
                    </div>

                    <div v-if="check.days" class="mt-4 rounded-xl bg-slate-50 p-3 text-sm">
                        <div class="flex justify-between"><span>{{ money(check.unit_price) }} {{ v.currency }} × {{ check.days }} jour(s)</span><span>{{ money(check.total) }}</span></div>
                        <div class="mt-2 flex items-end justify-between border-t border-slate-200 pt-2">
                            <span class="font-semibold">Total</span>
                            <span class="text-2xl font-extrabold text-slate-900">{{ money(check.total) }} {{ v.currency }}</span>
                        </div>
                    </div>

                    <p v-if="form.errors.starts_at || form.errors.ends_at" class="mt-2 text-xs font-semibold text-red-600">{{ form.errors.starts_at || form.errors.ends_at }}</p>

                    <button v-if="user" type="submit" class="mt-4 w-full rounded-xl bg-[#006ce4] py-3 font-semibold text-white transition hover:bg-[#0057b8] disabled:cursor-not-allowed disabled:opacity-50" :disabled="!canBook || form.processing">
                        {{ form.processing ? 'Reservation...' : 'Reserver et payer' }}
                    </button>
                    <Link v-else :href="`/login`" class="mt-4 block w-full rounded-xl bg-[#006ce4] py-3 text-center font-semibold text-white hover:bg-[#0057b8]">Se connecter pour reserver</Link>
                    <p class="mt-2 text-center text-xs text-slate-500">Paiement Mobile Money a l'etape suivante. Le vehicule vous est reserve des le paiement.</p>

                    <div v-if="v.phone" class="mt-4 border-t pt-4">
                        <p class="mb-2 text-xs font-semibold text-slate-500">Une question au loueur ?</p>
                        <ContactButtons :phone="v.phone" :message="`Bonjour, je suis interesse(e) par votre location MOKILI TOUR : ${v.title}`" />
                    </div>
                </form>

                <div v-else class="space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p v-if="isSale" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Prix de vente</p>
                        <p class="text-3xl font-extrabold text-navy-900">
                            <span v-if="v.display_price?.original" class="mr-1 block text-base font-normal text-red-500 line-through">{{ money(v.display_price.original) }} {{ v.currency }}</span>{{ price }}
                        </p>
                        <p v-if="isSale && v.year" class="mt-1 text-sm text-slate-500">{{ [v.year, v.mileage_km ? `${money(v.mileage_km)} km` : null, v.fuel, v.transmission].filter(Boolean).join(' · ') }}</p>
                        <div class="mt-4">
                            <ContactButtons v-if="v.phone" :phone="v.phone" :message="saleMsg(isSale ? 'Est-il toujours disponible ?' : 'Pouvez-vous me donner plus d\'informations ?')" />
                            <p v-else class="text-sm text-slate-500">Le vendeur n'a pas encore indique de telephone. Contactez le service client MOKILI TOUR.</p>
                        </div>
                        <div v-if="isSale && v.phone" class="mt-3 grid grid-cols-2 gap-2">
                            <a :href="`https://wa.me/${String(v.phone).replace(/\D/g, '')}?text=${encodeURIComponent(saleMsg('Je souhaiterais faire un essai, quand seriez-vous disponible ?'))}`" target="_blank" rel="noopener" class="rounded-xl border border-slate-300 px-3 py-2.5 text-center text-sm font-semibold text-navy-900 hover:bg-slate-50">Demander un essai</a>
                            <a :href="`https://wa.me/${String(v.phone).replace(/\D/g, '')}?text=${encodeURIComponent(saleMsg('Le prix est-il negociable ?'))}`" target="_blank" rel="noopener" class="rounded-xl border border-slate-300 px-3 py-2.5 text-center text-sm font-semibold text-navy-900 hover:bg-slate-50">Faire une offre</a>
                        </div>
                    </div>
                    <div v-if="isSale" class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                        <p class="font-bold text-amber-900">Conseils pour acheter en securite</p>
                        <ul class="mt-2 space-y-1.5 text-sm text-amber-900/90">
                            <li v-for="t in SAFETY" :key="t" class="flex gap-2"><span>•</span>{{ t }}</li>
                        </ul>
                    </div>
                </div>
            </aside>
        </div>

        <section v-if="similar.length" class="mt-12">
            <h2 class="text-xl font-extrabold text-slate-900">Vous aimerez aussi</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Link v-for="s in similar" :key="s.id" :href="`/voiture/${s.slug}`" class="overflow-hidden rounded-2xl border bg-white transition hover:shadow-md">
                    <ListingImage :src="s.image_url" fallback-class="bg-orange-600" class="h-36">{{ s.brand }}</ListingImage>
                    <div class="p-3">
                        <p class="truncate font-bold text-slate-900">{{ s.title }}</p>
                        <p class="text-xs text-slate-500">{{ s.city }}</p>
                        <p v-if="s.display_price" class="mt-1 text-sm font-semibold text-slate-900">{{ money(s.display_price.amount) }} {{ s.currency }} <span class="font-normal text-slate-500">{{ s.display_price.suffix }}</span></p>
                    </div>
                </Link>
            </div>
        </section>
    </div>
</template>
