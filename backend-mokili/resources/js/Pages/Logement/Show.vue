<script setup>
import PromoBadge from '@/Components/PromoBadge.vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import ListingImage from '@/Components/ListingImage.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: MainLayout });

// Fiche hebergement facon Booking.com : galerie, onglets, points forts,
// equipements, tableau des disponibilites, regles, encart de reservation.
const props = defineProps({
    listing: { type: Object, required: true },
    search: { type: Object, default: () => ({}) },
    amenityLabels: { type: Object, default: () => ({}) },
    bookedOnDates: { type: Boolean, default: false },
    similar: { type: Array, default: () => [] },
});

const today = new Date().toISOString().slice(0, 10);
const plusDays = (d, n) => {
    const x = new Date(d + 'T12:00:00');
    x.setDate(x.getDate() + n);
    return x.toISOString().slice(0, 10);
};

const adults = ref(Number(props.search.adults ?? props.search.guests ?? Math.min(2, props.listing.max_guests)));
const children = ref(Number(props.search.children ?? 0));
const form = useForm({
    starts_at: props.search.check_in || today,
    ends_at: props.search.check_out || plusDays(props.search.check_in || today, 1),
    guests: 1,
    notes: '',
});

const guests = computed(() => adults.value + children.value);
const tooMany = computed(() => guests.value > props.listing.max_guests);
const nights = computed(() => {
    const n = Math.round((new Date(form.ends_at) - new Date(form.starts_at)) / 86400000);
    return n > 0 ? n : 0;
});
const night = computed(() => Number(props.listing.final_price_per_night ?? props.listing.price_per_night));
const base = computed(() => Number(props.listing.price_per_night));
const total = computed(() => nights.value * night.value);
const saving = computed(() => nights.value * (base.value - night.value));
const money = (v) => Number(v ?? 0).toLocaleString('fr-FR');
const fmt = (d) => (d ? new Date(d + 'T12:00:00').toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'long', year: 'numeric' }) : '');

const amenities = computed(() => (Array.isArray(props.listing.amenities) ? props.listing.amenities : []));
const label = (a) => props.amenityLabels[a] ?? a.charAt(0).toUpperCase() + a.slice(1);
const ICONS = { wifi: '📶', climatisation: '❄️', parking: '🅿️', piscine: '🏊', 'petit-dejeuner': '🥐', 'salle de sport': '🏋️', jardin: '🌳', ascenseur: '🛗', cuisine: '🍳', television: '📺', navette: '🚐', chauffage: '🔥' };

const highlights = computed(() => {
    const h = [];
    if (amenities.value.includes('piscine')) h.push(['🏊', 'Piscine sur place']);
    if (amenities.value.includes('petit-dejeuner')) h.push(['🥐', 'Petit-dejeuner disponible']);
    if (amenities.value.includes('parking')) h.push(['🅿️', 'Parking gratuit']);
    if (amenities.value.includes('wifi')) h.push(['📶', 'Wi-Fi gratuit']);
    h.push(['👨‍👩‍👧', `Ideal jusqu'a ${props.listing.max_guests} voyageurs`]);
    h.push(['✅', 'Annulation gratuite jusqu\'a 48 h avant']);
    return h.slice(0, 5);
});

const tabs = [['apercu', 'Apercu'], ['equipements', 'Equipements'], ['disponibilites', 'Infos et tarifs'], ['regles', 'Regles de la maison']];
const go = (id) => document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });

const refresh = () => router.get(`/logement/${props.listing.slug}`, { check_in: form.starts_at, check_out: form.ends_at, adults: adults.value, children: children.value || undefined }, { preserveScroll: true, preserveState: true, only: ['bookedOnDates', 'search'] });

const submit = () => {
    form.guests = guests.value;
    if (children.value) form.notes = `${adults.value} adulte(s), ${children.value} enfant(s)` + (form.notes ? ` - ${form.notes}` : '');
    form.post(`/logement/${props.listing.slug}/reserver`);
};
</script>

<template>
    <Head :title="listing.title" />

    <div class="bg-white">
        <div class="mx-auto max-w-6xl px-4 py-5">
            <!-- Fil d'ariane -->
            <nav class="text-xs text-[#006ce4]">
                <Link href="/" class="hover:underline">Accueil</Link> ›
                <Link href="/logement" class="hover:underline">Hebergements</Link> ›
                <Link :href="`/logement?city=${encodeURIComponent(listing.city)}`" class="hover:underline">{{ listing.city }}</Link> ›
                <span class="text-slate-500">{{ listing.title }}</span>
            </nav>

            <!-- Onglets -->
            <div class="mt-4 flex gap-1 overflow-x-auto border-b">
                <button v-for="[id, t] in tabs" :key="id" type="button" class="whitespace-nowrap border-b-2 border-transparent px-4 py-3 text-sm font-medium text-slate-700 hover:border-[#006ce4] hover:text-[#006ce4]" @click="go(id)">{{ t }}</button>
            </div>

            <!-- Titre -->
            <div id="apercu" class="mt-5 flex scroll-mt-24 flex-wrap items-start justify-between gap-4">
                <div>
                    <p v-if="listing.property_type_label" class="inline-block rounded bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">{{ listing.property_type_label }}</p>
                    <h1 class="mt-1 text-2xl font-extrabold text-slate-900 sm:text-3xl">{{ listing.title }}</h1>
                    <p class="mt-1 text-sm text-slate-700">📍 {{ [listing.address, listing.city, listing.country].filter(Boolean).join(', ') }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <PromoBadge :promo="listing.promo" show-end />
                    <button type="button" class="rounded-md bg-[#006ce4] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#0057b8]" @click="go('disponibilites')">Reserver</button>
                </div>
            </div>

            <!-- Galerie -->
            <div class="mt-4 grid gap-2 overflow-hidden rounded-xl md:grid-cols-[2fr_1fr]">
                <ListingImage :src="listing.image_url" fallback-class="bg-[#003b95] text-2xl font-semibold" class="h-72 md:h-[420px]">{{ listing.city }}</ListingImage>
                <div class="hidden md:block">
                    <div class="flex h-full flex-col justify-center rounded-md bg-blue-50 p-6">
                        <p class="text-sm font-bold text-slate-900">Points forts de l'etablissement</p>
                        <ul class="mt-2 space-y-1.5 text-sm text-slate-700">
                            <li v-for="[i, t] in highlights" :key="t">{{ i }} {{ t }}</li>
                        </ul>
                        <button type="button" class="mt-5 w-full rounded-md bg-[#006ce4] py-2.5 text-sm font-semibold text-white hover:bg-[#0057b8]" @click="go('disponibilites')">Reserver</button>
                    </div>
                </div>
            </div>

            <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_340px]">
                <div class="min-w-0">
                    <!-- Description -->
                    <section>
                        <h2 class="text-xl font-extrabold text-slate-900">A propos de cet etablissement</h2>
                        <p class="mt-3 whitespace-pre-line leading-relaxed text-slate-700">{{ listing.description || 'Le partenaire n\'a pas encore ajoute de description.' }}</p>
                        <div class="mt-4 grid grid-cols-3 gap-3 text-center">
                            <div class="rounded-lg border p-3"><p class="text-2xl">🛏️</p><p class="text-sm font-semibold">{{ listing.bedrooms }} chambre(s)</p></div>
                            <div class="rounded-lg border p-3"><p class="text-2xl">🛁</p><p class="text-sm font-semibold">{{ listing.bathrooms }} salle(s) de bain</p></div>
                            <div class="rounded-lg border p-3"><p class="text-2xl">👥</p><p class="text-sm font-semibold">{{ listing.max_guests }} voyageurs max.</p></div>
                        </div>
                    </section>

                    <!-- Equipements -->
                    <section id="equipements" class="mt-8 scroll-mt-24">
                        <h2 class="text-xl font-extrabold text-slate-900">Equipements les plus populaires</h2>
                        <div v-if="amenities.length" class="mt-3 flex flex-wrap gap-x-6 gap-y-3">
                            <span v-for="a in amenities" :key="a" class="flex items-center gap-2 text-sm text-slate-800"><span class="text-lg">{{ ICONS[a] ?? '✔️' }}</span>{{ label(a) }}</span>
                        </div>
                        <p v-else class="mt-2 text-sm text-slate-500">Equipements communiques a la reservation.</p>
                    </section>

                    <!-- Disponibilites -->
                    <section id="disponibilites" class="mt-8 scroll-mt-24">
                        <h2 class="text-xl font-extrabold text-slate-900">Disponibilites</h2>
                        <div class="mt-3 grid gap-1 rounded-xl bg-[#ffb700] p-1 sm:grid-cols-[1fr_1fr_1fr_auto]">
                            <label class="rounded-lg bg-white px-3 py-2">
                                <span class="block text-[11px] text-slate-500">Arrivee</span>
                                <input v-model="form.starts_at" type="date" :min="today" class="w-full border-0 p-0 text-sm font-semibold focus:ring-0" @change="form.ends_at <= form.starts_at && (form.ends_at = plusDays(form.starts_at, 1))" />
                            </label>
                            <label class="rounded-lg bg-white px-3 py-2">
                                <span class="block text-[11px] text-slate-500">Depart</span>
                                <input v-model="form.ends_at" type="date" :min="plusDays(form.starts_at || today, 1)" class="w-full border-0 p-0 text-sm font-semibold focus:ring-0" />
                            </label>
                            <div class="flex items-center justify-between gap-2 rounded-lg bg-white px-3 py-2 text-sm">
                                <span class="flex items-center gap-1">Adultes
                                    <button type="button" class="h-6 w-6 rounded border text-[#006ce4]" @click="adults = Math.max(1, adults - 1)">−</button><b>{{ adults }}</b>
                                    <button type="button" class="h-6 w-6 rounded border text-[#006ce4]" @click="adults++">+</button></span>
                                <span class="flex items-center gap-1">Enf.
                                    <button type="button" class="h-6 w-6 rounded border text-[#006ce4]" @click="children = Math.max(0, children - 1)">−</button><b>{{ children }}</b>
                                    <button type="button" class="h-6 w-6 rounded border text-[#006ce4]" @click="children++">+</button></span>
                            </div>
                            <button type="button" class="rounded-lg bg-[#006ce4] px-5 py-2 text-sm font-semibold text-white hover:bg-[#0057b8]" @click="refresh">Modifier la recherche</button>
                        </div>

                        <div class="mt-4 overflow-x-auto rounded-xl border">
                            <table class="w-full min-w-[640px] text-sm">
                                <thead class="bg-[#003b95] text-left text-white">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">Type d'hebergement</th>
                                        <th class="px-4 py-3 font-semibold">Nombre de personnes</th>
                                        <th class="px-4 py-3 font-semibold">Tarif pour {{ nights || 1 }} nuit(s)</th>
                                        <th class="px-4 py-3 font-semibold">Vos options</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="align-top">
                                        <td class="border-r px-4 py-4">
                                            <p class="font-bold text-[#006ce4] underline">{{ listing.property_type_label || 'Logement entier' }} · {{ listing.bedrooms }} chambre(s)</p>
                                            <p class="mt-1 text-xs text-slate-600">{{ listing.bathrooms }} salle(s) de bain</p>
                                            <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-600">
                                                <span v-for="a in amenities.slice(0, 4)" :key="a">✓ {{ label(a) }}</span>
                                            </div>
                                        </td>
                                        <td class="border-r px-4 py-4 text-lg">
                                            <span :title="`${listing.max_guests} voyageurs max.`">{{ '👤'.repeat(Math.min(listing.max_guests, 6)) }}</span>
                                            <p v-if="tooMany" class="mt-1 text-xs font-semibold text-red-600">Maximum {{ listing.max_guests }} voyageurs</p>
                                        </td>
                                        <td class="border-r px-4 py-4">
                                            <p v-if="listing.promo" class="text-sm text-red-600 line-through">{{ money(base * (nights || 1)) }} {{ $cur(listing.currency) }}</p>
                                            <p class="text-lg font-bold text-slate-900">{{ money(night * (nights || 1)) }} {{ $cur(listing.currency) }}</p>
                                            <p class="text-[11px] text-slate-500">Taxes et frais compris</p>
                                            <PromoBadge :promo="listing.promo" class="mt-1" />
                                        </td>
                                        <td class="px-4 py-4 text-xs text-slate-700">
                                            <p class="font-semibold text-green-700">✓ Annulation gratuite jusqu'a 48 h avant</p>
                                            <p class="mt-1 font-semibold text-green-700">✓ Aucun prepaiement par carte</p>
                                            <p class="mt-1">• Paiement par Mobile Money</p>
                                            <p v-if="amenities.includes('petit-dejeuner')" class="mt-1">• Petit-dejeuner disponible</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- Regles -->
                    <section id="regles" class="mt-8 scroll-mt-24">
                        <h2 class="text-xl font-extrabold text-slate-900">Regles de la maison</h2>
                        <div class="mt-3 divide-y rounded-xl border text-sm">
                            <div class="grid gap-2 p-4 sm:grid-cols-[200px_1fr]"><p class="font-semibold">🕑 Arrivee</p><p>A partir de 14 h 00. Une piece d'identite est demandee a l'arrivee.</p></div>
                            <div class="grid gap-2 p-4 sm:grid-cols-[200px_1fr]"><p class="font-semibold">🕛 Depart</p><p>Jusqu'a 12 h 00.</p></div>
                            <div class="grid gap-2 p-4 sm:grid-cols-[200px_1fr]"><p class="font-semibold">ℹ️ Annulation</p><p>Gratuite jusqu'a 48 h avant l'arrivee. Passe ce delai, la premiere nuit reste due.</p></div>
                            <div class="grid gap-2 p-4 sm:grid-cols-[200px_1fr]"><p class="font-semibold">👶 Enfants</p><p>Les enfants sont les bienvenus, dans la limite de {{ listing.max_guests }} voyageurs au total.</p></div>
                            <div class="grid gap-2 p-4 sm:grid-cols-[200px_1fr]"><p class="font-semibold">💳 Paiement</p><p>MTN Mobile Money, Airtel Money, Orange Money, M-Pesa... La reservation est confirmee des le paiement.</p></div>
                        </div>
                    </section>
                </div>

                <!-- Encart de reservation -->
                <aside class="h-fit lg:sticky lg:top-24">
                    <form class="rounded-xl border p-5 shadow-sm" @submit.prevent="submit">
                        <p class="text-lg font-extrabold text-slate-900">Votre reservation</p>
                        <div class="mt-3 grid grid-cols-2 gap-3 border-b pb-3 text-sm">
                            <div><p class="text-xs text-slate-500">Arrivee</p><p class="font-bold">{{ fmt(form.starts_at) }}</p><p class="text-xs text-slate-500">a partir de 14 h</p></div>
                            <div><p class="text-xs text-slate-500">Depart</p><p class="font-bold">{{ fmt(form.ends_at) }}</p><p class="text-xs text-slate-500">jusqu'a 12 h</p></div>
                        </div>
                        <p class="mt-3 text-sm text-slate-600">Duree du sejour : <b class="text-slate-900">{{ nights }} nuit(s)</b></p>
                        <p class="text-sm text-slate-600">Voyageurs : <b class="text-slate-900">{{ adults }} adulte(s){{ children ? `, ${children} enfant(s)` : '' }}</b></p>
                        <p v-if="form.errors.starts_at || form.errors.ends_at" class="mt-1 text-xs text-red-600">{{ form.errors.starts_at || form.errors.ends_at }}</p>
                        <p v-if="form.errors.guests" class="mt-1 text-xs text-red-600">{{ form.errors.guests }}</p>

                        <div class="mt-4 rounded-lg bg-blue-50 p-3 text-sm">
                            <div class="flex justify-between"><span>{{ money(night) }} {{ $cur(listing.currency) }} × {{ nights }} nuit(s)</span><span>{{ money(total) }}</span></div>
                            <div v-if="saving > 0" class="mt-1 flex justify-between text-green-700"><span>Promotion</span><span>− {{ money(saving) }}</span></div>
                            <div class="mt-2 flex items-end justify-between border-t border-blue-100 pt-2">
                                <span class="font-semibold">Total</span>
                                <span class="text-2xl font-extrabold text-slate-900">{{ money(total) }} {{ $cur(listing.currency) }}</span>
                            </div>
                        </div>

                        <label class="mt-3 block text-xs font-semibold text-slate-700">Demandes particulieres (optionnel)</label>
                        <textarea v-model="form.notes" rows="2" placeholder="Heure d'arrivee, lit bebe..." class="mt-1 w-full rounded-md border-slate-300 text-sm"></textarea>

                        <p v-if="bookedOnDates" class="mt-3 rounded-md bg-red-50 p-2 text-xs font-semibold text-red-700">Ce logement est deja reserve sur une partie de ces dates. Choisissez d'autres dates.</p>
                        <button type="submit" class="mt-4 w-full rounded-md bg-[#006ce4] py-3 font-semibold text-white hover:bg-[#0057b8] disabled:opacity-50" :disabled="form.processing || !nights || tooMany || bookedOnDates">
                            {{ form.processing ? 'Reservation...' : 'Je reserve' }}
                        </button>
                        <p class="mt-2 text-center text-xs text-slate-500">Vous ne serez debite qu'a l'etape suivante, par Mobile Money.</p>
                    </form>
                </aside>
            </div>

            <!-- Logements similaires -->
            <section v-if="similar.length" class="mt-12">
                <h2 class="text-xl font-extrabold text-slate-900">Autres hebergements qui pourraient vous plaire</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Link v-for="l in similar" :key="l.id" :href="`/logement/${l.slug}`" class="overflow-hidden rounded-xl border bg-white transition hover:shadow-md">
                        <ListingImage :src="l.image_url" fallback-class="bg-[#003b95]" class="h-40">{{ l.city }}</ListingImage>
                        <div class="p-3">
                            <p class="truncate font-bold text-slate-900">{{ l.title }}</p>
                            <p class="text-xs text-slate-500">{{ l.city }} · {{ l.max_guests }} voyageurs</p>
                            <p class="mt-2 text-sm">A partir de <b>{{ money(l.final_price_per_night ?? l.price_per_night) }} {{ $cur(l.currency) }}</b> / nuit</p>
                        </div>
                    </Link>
                </div>
            </section>
        </div>
    </div>
</template>
