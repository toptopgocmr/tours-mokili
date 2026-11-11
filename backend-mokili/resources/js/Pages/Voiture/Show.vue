<script setup>
import PromoBadge from '@/Components/PromoBadge.vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import ListingImage from '@/Components/ListingImage.vue';
import ContactButtons from '@/Components/ContactButtons.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import { useCarShortlist, monthly } from '@/composables/useCarShortlist';

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
    sale: { type: Object, default: null },
    idTypes: { type: Object, default: () => ({}) },
    dealers: { type: Array, default: () => [] },
});

// ---------- Galerie ----------
const photos = computed(() => (props.vehicle.gallery_urls?.length ? props.vehicle.gallery_urls : [props.vehicle.image_url].filter(Boolean)));
const photo = ref(0);
const go = (d) => (photo.value = (photo.value + d + photos.value.length) % photos.value.length);
const zoom = ref(false);

// ---------- Vente : favoris, partage, financement, reservation ----------
const { isFav, toggleFav, inCompare, toggleCompare, compare } = useCarShortlist();
const shared = ref(false);
const share = async () => {
    const url = window.location.href;
    try {
        if (navigator.share) await navigator.share({ title: props.vehicle.title, url });
        else {
            await navigator.clipboard.writeText(url);
            shared.value = true;
            setTimeout(() => (shared.value = false), 2000);
        }
    } catch {
        /* partage annule */
    }
};
const fin = ref({ ...(props.sale?.finance ?? { down_payment_percent: 20, months: 36, annual_rate: 12 }) });
const salePrice = computed(() => Number(props.vehicle.display_price?.amount ?? 0));
const downPayment = computed(() => Math.round(salePrice.value * fin.value.down_payment_percent / 100));
const perMonth = computed(() => monthly(salePrice.value, fin.value));
const totalCredit = computed(() => perMonth.value * fin.value.months);
const reserveForm = useForm({ phone: '', message: '' });
const reserve = () => reserveForm.post(`/voiture/${props.vehicle.slug}/reserver-achat`, { preserveScroll: true });
const tradeIn = ref({ brand: '', model: '', year: '', km: '' });
const tradeInMsg = computed(() => `Bonjour, je suis interesse(e) par votre ${props.vehicle.title} (MOKILI TOUR) et je souhaite faire estimer ma voiture en reprise : ${tradeIn.value.brand} ${tradeIn.value.model} ${tradeIn.value.year}, ${tradeIn.value.km} km.`);
const printSheet = () => window.print();
const goTo = (id) => document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });

const v = computed(() => props.vehicle);
const isRental = computed(() => v.value.offer_type === 'location' && !!v.value.price_per_day);
const money = (x) => Number(x ?? 0).toLocaleString('fr-FR');
const user = computed(() => usePage().props.auth?.user);

const price = computed(() => (v.value.display_price
    ? `${v.value.display_price.suffix === 'a partir de' ? 'A partir de ' : ''}${money(v.value.display_price.amount)} ${v.value.currency === 'XAF' ? 'FCFA' : v.value.currency}`
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
    id_type: 'cni',
    id_number: '',
    id_document: null,
    license_number: '',
    license_date: '',
    license_document: null,
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
const canBook = computed(() => check.value.available === true && !check.value.loading && (form.pickup_mode !== 'livraison' || form.pickup_address) && (!form.return_elsewhere || form.return_place) && docsOk.value);
const isSale = computed(() => v.value.offer_type === 'vente');
const saleMsg = (what) => `Bonjour, je suis interesse(e) par votre ${v.value.title} vu sur MOKILI TOUR. ${what}`;
const SAFETY = ['Rencontrez le vendeur dans un lieu public et de jour.', 'Verifiez la carte grise, le controle technique et le numero de chassis.', 'Faites un essai routier et, si possible, un diagnostic dans un garage.', "Ne versez jamais d'acompte avant d'avoir vu le vehicule."];
const docName = ref({ id_document: '', license_document: '' });
const onDoc = (field, e) => {
    const f = e.target.files[0];
    form[field] = f ?? null;
    docName.value[field] = f?.name ?? '';
};
const docsOk = computed(() => form.id_number && form.id_document && (form.with_driver || (form.license_number && form.license_date && form.license_document)));
const submit = () => form.post(`/voiture/${v.value.slug}/reserver`, { preserveScroll: true, forceFormData: true });

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
                <div class="relative overflow-hidden rounded-2xl bg-slate-100">
                    <button type="button" class="block w-full" @click="photos.length && (zoom = true)">
                        <ListingImage :src="photos[photo] ?? null" fallback-class="bg-navy-800 text-xl font-semibold" class="h-64 sm:h-[26rem]">{{ v.brand }} {{ v.model }}</ListingImage>
                    </button>
                    <template v-if="photos.length > 1">
                        <button type="button" class="absolute left-3 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-xl text-navy-900 shadow hover:bg-white" aria-label="Photo precedente" @click="go(-1)">‹</button>
                        <button type="button" class="absolute right-3 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-xl text-navy-900 shadow hover:bg-white" aria-label="Photo suivante" @click="go(1)">›</button>
                    </template>
                    <span v-if="photos.length" class="absolute bottom-3 right-3 rounded-full bg-black/60 px-3 py-1 text-xs font-semibold text-white">{{ photo + 1 }} / {{ photos.length }}</span>
                    <span v-if="sale?.reserved" class="absolute left-3 top-3 rounded-md bg-red-600 px-3 py-1 text-xs font-bold uppercase text-white">Reserve</span>
                    <span v-if="v.image_is_illustration" class="absolute bottom-3 left-3 rounded-full bg-black/60 px-3 py-1 text-xs font-semibold text-white">Photo d'illustration {{ v.brand }}</span>
                </div>
                <div v-if="photos.length > 1" class="mt-2 flex gap-2 overflow-x-auto pb-1">
                    <button v-for="(u, i) in photos" :key="u" type="button" class="h-16 w-24 shrink-0 overflow-hidden rounded-lg ring-2 transition" :class="i === photo ? 'ring-navy-900' : 'ring-transparent opacity-70 hover:opacity-100'" @click="photo = i">
                        <img :src="u" alt="" class="h-full w-full object-cover" loading="lazy" />
                    </button>
                </div>
                <div v-if="zoom" class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4" @click.self="zoom = false">
                    <img :src="photos[photo]" alt="" class="max-h-[85vh] max-w-full rounded-lg object-contain" />
                    <button type="button" class="absolute right-5 top-5 text-3xl text-white" aria-label="Fermer" @click="zoom = false">×</button>
                    <template v-if="photos.length > 1">
                        <button type="button" class="absolute left-4 text-5xl text-white" @click="go(-1)">‹</button>
                        <button type="button" class="absolute right-4 text-5xl text-white" @click="go(1)">›</button>
                    </template>
                </div>

                <!-- Onglets (vente) -->
                <div v-if="isSale" class="sticky top-16 z-10 mt-6 flex gap-2 overflow-x-auto bg-white/95 py-2 backdrop-blur">
                    <button v-for="[id, t] in [['caracteristiques', 'Caracteristiques'], ['equipements', 'Equipements'], ['financement', 'Financement'], ['reprise', 'Reprise'], ['vendeur', 'Vendeur']]" :key="id" type="button" class="whitespace-nowrap rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:border-navy-900 hover:text-navy-900" @click="goTo(id)">{{ t }}</button>
                </div>

                <!-- Caracteristiques -->
                <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div v-for="[label, val] in specs.slice(0, 8)" :key="label" class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ label }}</p>
                        <p class="mt-0.5 font-bold text-slate-900">{{ val }}</p>
                    </div>
                </div>

                <section v-if="isSale" id="caracteristiques" class="mt-8 scroll-mt-32">
                    <h2 class="text-xl font-extrabold text-slate-900">Fiche technique</h2>
                    <dl class="mt-3 divide-y rounded-2xl border border-slate-200 bg-white text-sm">
                        <div v-for="[k, val] in [['Marque', v.brand], ['Modele', v.model], ...specs]" :key="k" class="flex justify-between px-4 py-2.5"><dt class="text-slate-500">{{ k }}</dt><dd class="font-semibold text-slate-900">{{ val || '-' }}</dd></div>
                    </dl>
                </section>

                <section v-if="v.description" class="mt-8">
                    <h2 class="text-xl font-extrabold text-slate-900">Description</h2>
                    <p class="mt-2 whitespace-pre-line leading-relaxed text-slate-700">{{ v.description }}</p>
                </section>

                <section v-if="v.options?.length" id="equipements" class="mt-8 scroll-mt-32">
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

                <template v-if="isSale">
                    <section id="financement" class="mt-8 scroll-mt-32">
                        <h2 class="text-xl font-extrabold text-slate-900">Simuler mon financement</h2>
                        <div class="mt-3 grid gap-5 rounded-2xl border border-slate-200 bg-white p-5 sm:grid-cols-2">
                            <div class="space-y-4 text-sm">
                                <label class="block">
                                    <span class="flex justify-between font-semibold text-slate-800"><span>Apport</span><span>{{ fin.down_payment_percent }} % · {{ money(downPayment) }} {{ $cur(v.currency) }}</span></span>
                                    <input v-model.number="fin.down_payment_percent" type="range" min="0" max="70" step="5" class="mt-2 w-full accent-navy-900" />
                                </label>
                                <label class="block">
                                    <span class="flex justify-between font-semibold text-slate-800"><span>Duree</span><span>{{ fin.months }} mois</span></span>
                                    <input v-model.number="fin.months" type="range" min="6" max="72" step="6" class="mt-2 w-full accent-navy-900" />
                                </label>
                                <label class="block">
                                    <span class="flex justify-between font-semibold text-slate-800"><span>Taux annuel estime</span><span>{{ fin.annual_rate }} %</span></span>
                                    <input v-model.number="fin.annual_rate" type="range" min="0" max="25" step="0.5" class="mt-2 w-full accent-navy-900" />
                                </label>
                            </div>
                            <div class="flex flex-col justify-center rounded-xl bg-navy-900 p-5 text-white">
                                <p class="text-sm text-white/70">Mensualite estimee</p>
                                <p class="text-3xl font-extrabold text-gold-400">{{ money(perMonth) }} {{ $cur(v.currency) }}<span class="text-base font-semibold text-white"> /mois</span></p>
                                <p class="mt-2 text-xs text-white/70">Montant finance : {{ money(salePrice - downPayment) }} {{ $cur(v.currency) }} · Cout total : {{ money(totalCredit + downPayment) }} {{ $cur(v.currency) }}</p>
                            </div>
                            <p class="text-[11px] text-slate-400 sm:col-span-2">Simulation indicative, sans valeur d'offre. Un credit vous engage et doit etre rembourse : verifiez vos capacites de remboursement avant de vous engager. Renseignez-vous aupres de votre banque ou d'un etablissement de microfinance.</p>
                        </div>
                    </section>

                    <section id="reprise" class="mt-8 scroll-mt-32">
                        <h2 class="text-xl font-extrabold text-slate-900">Faire reprendre mon vehicule</h2>
                        <p class="mt-1 text-sm text-slate-500">Proposez votre voiture actuelle en reprise : le vendeur vous repond avec une estimation.</p>
                        <div class="mt-3 grid gap-3 rounded-2xl border border-slate-200 bg-white p-5 sm:grid-cols-4">
                            <input v-model="tradeIn.brand" type="text" placeholder="Marque" class="rounded-lg border-slate-300 text-sm" />
                            <input v-model="tradeIn.model" type="text" placeholder="Modele" class="rounded-lg border-slate-300 text-sm" />
                            <input v-model="tradeIn.year" type="number" placeholder="Annee" class="rounded-lg border-slate-300 text-sm" />
                            <input v-model="tradeIn.km" type="number" placeholder="Kilometrage" class="rounded-lg border-slate-300 text-sm" />
                            <a v-if="v.phone" :href="`https://wa.me/${String(v.phone).replace(/\D/g, '')}?text=${encodeURIComponent(tradeInMsg)}`" target="_blank" rel="noopener" class="rounded-xl bg-navy-900 py-2.5 text-center text-sm font-semibold text-white sm:col-span-4" :class="!tradeIn.brand && 'pointer-events-none opacity-50'">Demander une estimation au vendeur</a>
                            <p v-else class="text-sm text-slate-500 sm:col-span-4">Le vendeur n'a pas indique de telephone.</p>
                        </div>
                    </section>

                    <section id="vendeur" class="mt-8 scroll-mt-32">
                        <h2 class="text-xl font-extrabold text-slate-900">Le vendeur</h2>
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5">
                            <div>
                                <p class="font-bold text-slate-900">{{ sale?.seller?.name ?? 'Vendeur MOKILI TOUR' }}</p>
                                <p class="text-sm text-slate-500">{{ v.city }}<span v-if="sale?.seller?.since"> · sur MOKILI TOUR depuis {{ sale.seller.since }}</span></p>
                                <p v-if="sale?.seller?.verified" class="mt-1 text-xs font-semibold text-green-700">✓ Professionnel verifie par MOKILI TOUR</p>
                            </div>
                            <ContactButtons v-if="v.phone" :phone="v.phone" :message="saleMsg('Est-il toujours disponible ?')" />
                        </div>
                    </section>
                </template>

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
                        {{ money(v.display_price?.amount) }} {{ $cur(v.currency) }}
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

                    <!-- Conducteur : piece d'identite + permis -->
                    <div class="mt-4 border-t border-slate-100 pt-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-700">Conducteur</p>
                        <label class="mt-2 block text-sm">
                            <span class="text-slate-600">Type de piece d'identite</span>
                            <select v-model="form.id_type" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                <option v-for="(label, key) in idTypes" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </label>
                        <input v-model="form.id_number" type="text" placeholder="Numero de la piece d'identite" class="mt-2 w-full rounded-lg border-slate-300 text-sm" />
                        <p v-if="form.errors.id_number" class="mt-1 text-xs text-red-600">{{ form.errors.id_number }}</p>
                        <label class="mt-2 flex cursor-pointer items-center gap-3 rounded-lg border border-dashed px-3 py-2.5 text-sm" :class="form.id_document ? 'border-green-500 bg-green-50 text-green-800' : 'border-slate-300 text-slate-600 hover:border-slate-500'">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-white text-lg ring-1 ring-slate-200">+</span>
                            <span class="min-w-0 truncate">{{ docName.id_document || "Photo de la piece d'identite (recto)" }}</span>
                            <input type="file" accept="image/*,application/pdf" class="hidden" @change="onDoc('id_document', $event)" />
                        </label>
                        <p v-if="form.errors.id_document" class="mt-1 text-xs text-red-600">{{ form.errors.id_document }}</p>

                        <template v-if="!form.with_driver">
                            <p class="mt-4 text-sm font-semibold text-slate-700">Permis de conduire</p>
                            <div class="mt-2 grid grid-cols-2 gap-2">
                                <input v-model="form.license_number" type="text" placeholder="Numero du permis" class="rounded-lg border-slate-300 text-sm" />
                                <label class="block">
                                    <input v-model="form.license_date" type="date" :max="today" class="w-full rounded-lg border-slate-300 text-sm" title="Date d'obtention" />
                                </label>
                            </div>
                            <p class="mt-1 text-[11px] text-slate-500">Date d'obtention du permis a droite.</p>
                            <p v-if="form.errors.license_number || form.errors.license_date" class="mt-1 text-xs text-red-600">{{ form.errors.license_number || form.errors.license_date }}</p>
                            <label class="mt-2 flex cursor-pointer items-center gap-3 rounded-lg border border-dashed px-3 py-2.5 text-sm" :class="form.license_document ? 'border-green-500 bg-green-50 text-green-800' : 'border-slate-300 text-slate-600 hover:border-slate-500'">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-white text-lg ring-1 ring-slate-200">+</span>
                                <span class="min-w-0 truncate">{{ docName.license_document || 'Photo du permis de conduire' }}</span>
                                <input type="file" accept="image/*,application/pdf" class="hidden" @change="onDoc('license_document', $event)" />
                            </label>
                            <p v-if="form.errors.license_document" class="mt-1 text-xs text-red-600">{{ form.errors.license_document }}</p>
                        </template>
                        <p v-else class="mt-2 text-xs text-slate-500">Avec chauffeur : le permis de conduire n'est pas demande.</p>
                        <p class="mt-2 text-[11px] text-slate-400">Vos documents sont transmis uniquement au loueur et a MOKILI TOUR pour verifier la location.</p>
                    </div>

                    <div v-if="check.days" class="mt-4 rounded-xl bg-slate-50 p-3 text-sm">
                        <div class="flex justify-between"><span>{{ money(check.unit_price) }} {{ $cur(v.currency) }} × {{ check.days }} jour(s)</span><span>{{ money(check.total) }}</span></div>
                        <div class="mt-2 flex items-end justify-between border-t border-slate-200 pt-2">
                            <span class="font-semibold">Total</span>
                            <span class="text-2xl font-extrabold text-slate-900">{{ money(check.total) }} {{ $cur(v.currency) }}</span>
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
                        <div v-if="isSale" class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-1">
                                <button type="button" class="rounded-full p-2 hover:bg-slate-100" aria-label="Partager" @click="share">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3" /><circle cx="6" cy="12" r="3" /><circle cx="18" cy="19" r="3" /><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4" /></svg>
                                </button>
                                <button type="button" class="rounded-full p-2 hover:bg-slate-100" :aria-label="isFav(v.id) ? 'Retirer des favoris' : 'Ajouter aux favoris'" @click="toggleFav(v.id)">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" :fill="isFav(v.id) ? '#f43f5e' : 'none'" :stroke="isFav(v.id) ? '#f43f5e' : 'currentColor'" stroke-width="2"><path d="M12 21s-7.5-4.6-9.5-9.2C1.2 8.6 3.3 5 6.9 5c2 0 3.4 1.1 4.1 2.2C11.7 6.1 13.1 5 15.1 5c3.6 0 5.7 3.6 4.4 6.8C19.5 16.4 12 21 12 21z" /></svg>
                                </button>
                                <span v-if="shared" class="text-xs text-green-700">Lien copie</span>
                            </div>
                            <button type="button" class="text-xs font-semibold underline" @click="printSheet">Imprimer la fiche</button>
                        </div>
                        <div v-if="isSale" class="mt-1 flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xl font-extrabold text-slate-900">{{ v.brand }} {{ v.model }}</p>
                                <p class="text-sm text-slate-500">{{ v.version || v.title }}</p>
                            </div>
                            <p class="shrink-0 text-xs text-slate-500">Vu {{ money(v.views) }} fois</p>
                        </div>
                        <div v-if="isSale" class="mt-3 flex flex-wrap gap-1.5 text-xs font-medium text-slate-700">
                            <span v-if="v.fuel" class="rounded-md bg-slate-100 px-2 py-1">{{ cat.fuels?.[v.fuel] ?? v.fuel }}</span>
                            <span v-if="v.transmission" class="rounded-md bg-slate-100 px-2 py-1">{{ v.transmission === 'manuelle' ? 'Manuelle' : 'Automatique' }}</span>
                            <span v-if="v.year" class="rounded-md bg-slate-100 px-2 py-1">{{ v.year }}</span>
                            <span v-if="v.mileage_km" class="rounded-md bg-slate-100 px-2 py-1">{{ money(v.mileage_km) }} km</span>
                            <span v-if="v.warranty_months" class="rounded-md bg-navy-900 px-2 py-1 text-white">Garantie {{ v.warranty_months }} mois</span>
                        </div>
                        <div class="mt-4 border-t border-slate-100 pt-4">
                            <p v-if="v.display_price?.original" class="text-base text-red-500 line-through">{{ money(v.display_price.original) }} {{ $cur(v.currency) }}</p>
                            <p class="text-3xl font-extrabold text-navy-900">{{ price }}</p>
                            <p v-if="isSale && salePrice" class="mt-1 text-sm text-slate-600">ou environ <b class="text-lg text-navy-900">{{ money(perMonth) }} {{ $cur(v.currency) }}</b> /mois*
                                <button type="button" class="ml-1 text-xs font-semibold text-[#006ce4] underline" @click="goTo('financement')">Personnaliser</button></p>
                        </div>

                        <div v-if="isSale && v.is_demo" class="mt-4 rounded-xl bg-slate-100 p-3 text-sm text-slate-700">
                            <p class="font-semibold">Annonce d'exemple</p>
                            <p class="mt-0.5">Ce vehicule illustre le catalogue MOKILI TOUR (prix indicatif du marche). Il n'est pas reservable : consultez les annonces des vendeurs ou contactez notre service client.</p>
                        </div>
                        <template v-if="isSale && !v.is_demo">
                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <a v-if="v.phone" :href="`https://wa.me/${String(v.phone).replace(/\D/g, '')}?text=${encodeURIComponent(saleMsg('Est-il toujours disponible ?'))}`" target="_blank" rel="noopener" class="flex items-center justify-center rounded-full bg-navy-900 px-3 py-3 text-center text-sm font-bold text-white hover:bg-navy-800">Se renseigner</a>
                                <button v-if="user" type="button" class="rounded-full bg-gold-500 px-3 py-3 text-sm font-bold text-navy-900 hover:bg-gold-400 disabled:cursor-not-allowed disabled:opacity-50" :class="!v.phone && 'col-span-2'" :disabled="sale?.reserved || reserveForm.processing" @click="reserve">
                                    {{ sale?.reserved ? 'Deja reserve' : 'Reserver ce vehicule' }}
                                </button>
                                <Link v-else href="/login" class="rounded-full bg-gold-500 px-3 py-3 text-center text-sm font-bold text-navy-900">Reserver ce vehicule</Link>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">Reservation par un acompte de <b>{{ money(sale?.deposit) }} {{ $cur(v.currency) }}</b> en Mobile Money, deduit du prix chez le vendeur. Le vehicule est retire de la vente pour vous.</p>
                            <p v-if="reserveForm.errors.reserve" class="mt-1 text-xs font-semibold text-red-600">{{ reserveForm.errors.reserve }}</p>
                            <div v-if="v.phone" class="mt-3 grid grid-cols-2 gap-2">
                                <a :href="`https://wa.me/${String(v.phone).replace(/\D/g, '')}?text=${encodeURIComponent(saleMsg('Je souhaiterais faire un essai, quand seriez-vous disponible ?'))}`" target="_blank" rel="noopener" class="rounded-xl border border-slate-300 px-3 py-2 text-center text-sm font-semibold text-navy-900 hover:bg-slate-50">Demander un essai</a>
                                <a :href="`https://wa.me/${String(v.phone).replace(/\D/g, '')}?text=${encodeURIComponent(saleMsg('Le prix est-il negociable ?'))}`" target="_blank" rel="noopener" class="rounded-xl border border-slate-300 px-3 py-2 text-center text-sm font-semibold text-navy-900 hover:bg-slate-50">Faire une offre</a>
                                <a :href="`tel:${v.phone}`" class="col-span-2 rounded-xl border border-slate-300 px-3 py-2 text-center text-sm font-semibold text-navy-900 hover:bg-slate-50">Appeler le vendeur</a>
                            </div>
                        </template>
                        <template v-if="isSale">
                            <button type="button" class="mt-3 w-full rounded-xl border px-3 py-2 text-sm font-semibold" :class="inCompare(v.id) ? 'border-navy-900 bg-navy-50 text-navy-900' : 'border-slate-300 text-slate-700'" @click="toggleCompare(v.id)">
                                {{ inCompare(v.id) ? '✓ Ajoute au comparateur' : 'Ajouter au comparateur' }}
                            </button>
                            <Link v-if="compare.length >= 2" :href="`/voiture/comparer?ids=${compare.join(',')}`" class="mt-2 block text-center text-xs font-semibold text-[#006ce4] underline">Comparer {{ compare.length }} vehicules</Link>
                            <div v-if="dealers.length" class="mt-4 rounded-xl border border-slate-200 p-3">
                                <p class="text-sm font-bold text-navy-900">Acheter une {{ v.brand }} au Congo</p>
                                <p class="text-xs text-slate-500">Vendeurs et concessionnaires partenaires</p>
                                <Link v-for="d in dealers" :key="d.id" :href="`/partenaires/${d.slug}`" class="mt-2 flex items-center justify-between gap-2 rounded-lg bg-slate-50 px-3 py-2 text-sm hover:bg-slate-100">
                                    <span><b class="text-navy-900">{{ d.name }}</b><span class="block text-xs text-slate-500">{{ d.city }}<template v-if="d.phone"> · {{ d.phone }}</template></span></span>
                                    <span class="text-[#006ce4]">›</span>
                                </Link>
                            </div>
                        </template>
                        <div v-else class="mt-4">
                            <ContactButtons v-if="v.phone" :phone="v.phone" :message="saleMsg('Pouvez-vous me donner plus d\'informations ?')" />
                            <p v-else class="text-sm text-slate-500">Le vendeur n'a pas encore indique de telephone. Contactez le service client MOKILI TOUR.</p>
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
                        <p v-if="s.display_price" class="mt-1 text-sm font-semibold text-slate-900">{{ money(s.display_price.amount) }} {{ $cur(s.currency) }} <span class="font-normal text-slate-500">{{ s.display_price.suffix }}</span></p>
                    </div>
                </Link>
            </div>
        </section>
    </div>
</template>
