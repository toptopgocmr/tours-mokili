<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import DestinationImage from '@/Components/DestinationImage.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { dayHeading, rideTime, RIDE_ICONS } from '@/utils/rides';

defineOptions({ layout: MainLayout });

const props = defineProps({
    offer: { type: Object, required: true },
    flightSearch: { type: Object, default: null },
});

const searchFlights = () => router.get('/voyage/vols/recherche', props.flightSearch);

const isRide = computed(() => props.offer.type === 'circuit');
const seats = computed(() => Number(props.offer.seats_available ?? 0));
const fromQuery = Number(new URLSearchParams(usePage().url.split('?')[1] ?? '').get('passagers')) || 1;
const form = useForm({ quantity: Math.max(1, Math.min(fromQuery, seats.value || 1)), notes: '' });
const unit = computed(() => Number(props.offer.discounted_price ?? props.offer.price));
const total = computed(() => Math.round(unit.value * (form.quantity || 1)));
const time = rideTime;
const duration = computed(() => {
    const a = props.offer.departure_at ? new Date(props.offer.departure_at) : null;
    const b = props.offer.arrival_at ? new Date(props.offer.arrival_at) : null;
    if (a && b && b > a) {
        const m = Math.round((b - a) / 60000);
        return `${Math.floor(m / 60)}h${String(m % 60).padStart(2, '0')}`;
    }
    return props.offer.duration_label ?? '';
});

const submit = () => form.post(`/voyage/${props.offer.slug}/reserver`);

const MODE_COLORS = { train: 'bg-sky-700', bus: 'bg-emerald-600', canot: 'bg-cyan-600', bateau: 'bg-cyan-700', taxi: 'bg-amber-500' };
const backHref = computed(() => {
    const q = new URLSearchParams({ type: 'circuit' });
    if (props.offer.transport_mode) q.set('mode', props.offer.transport_mode);
    if (props.offer.origin_city) q.set('from', props.offer.origin_city);
    if (props.offer.destination_city) q.set('destination', props.offer.destination_city);
    return `/voyage?${q}`;
});
const where = computed(() => props.offer.departure_point ? ` a ${props.offer.departure_point}` : '');
const kinshasa = computed(() => /kinshasa/i.test(`${props.offer.origin_city} ${props.offer.destination_city}`));
const tips = computed(() => {
    const common = [['🎫', 'Votre reservation est confirmee des le paiement : presentez le recu (ecran ou SMS) au depart.']];
    const byMode = {
        train: [
            ['⏰', `Presentez-vous en gare au moins 45 minutes avant le depart${where.value}.`],
            ['🪪', "Gardez une piece d'identite sur vous : le billet est nominatif."],
            ['🧳', 'Bagages : etiquetez-les a votre nom. Les colis volumineux peuvent etre factures par le transporteur.'],
        ],
        bus: [
            ['⏰', `Presentez-vous a l'agence au moins 30 minutes avant le depart${where.value}.`],
            ['🧳', 'Bagages en soute selon les conditions du transporteur : signalez les bagages volumineux dans votre message.'],
            ['🪪', "Gardez une piece d'identite sur vous pour les controles routiers."],
        ],
        canot: [
            ['⏰', `Presentez-vous au port au moins 1 heure avant le depart${where.value}.`],
            ...(kinshasa.value ? [['🛂', 'Traversee Brazzaville - Kinshasa : munissez-vous de votre passeport et des documents de voyage exiges pour les formalites de frontiere.']] : []),
            ['🦺', 'Le gilet de sauvetage est obligatoire a bord.'],
            ['🧳', 'Protegez vos bagages et vos appareils de l\'eau.'],
        ],
        taxi: [
            ['⏰', `Soyez au point de depart 15 minutes avant l'heure${where.value} : le taxi part a l'heure.`],
            ['📞', 'Le chauffeur peut vous appeler avant le depart : gardez votre telephone allume.'],
        ],
    };
    return [...(byMode[props.offer.transport_mode] ?? [['⏰', `Presentez-vous 30 minutes avant le depart${where.value}.`]]), ...common];
});
const notePlaceholder = computed(() => ({
    canot: 'Bagages, nombre de colis...',
    taxi: 'Point de prise en charge, bagages...',
}[props.offer.transport_mode] ?? 'Bagages volumineux, besoins particuliers...'));
</script>

<template>
    <Head :title="offer.title" />

    <!-- TRAJET train / bus / canot / taxi : page facon SNCF Connect -->
    <div v-if="isRide" class="min-h-screen bg-[#f3f6fb]">
        <!-- Etapes -->
        <div class="border-b border-navy-100 bg-white">
            <div class="mx-auto flex max-w-5xl items-center gap-2 overflow-x-auto px-6 py-3 text-sm">
                <template v-for="(st, i) in ['Trajet', 'Recapitulatif', 'Paiement']" :key="st">
                    <span class="flex shrink-0 items-center gap-2" :class="i === 1 ? 'font-semibold text-navy-900' : i === 0 ? 'text-emerald-700' : 'text-navy-400'">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold" :class="i === 1 ? 'bg-sky-700 text-white' : i === 0 ? 'bg-emerald-600 text-white' : 'bg-navy-100 text-navy-500'">{{ i === 0 ? '✓' : i + 1 }}</span>
                        {{ st }}
                    </span>
                    <span v-if="i < 2" class="h-px w-8 shrink-0 bg-navy-200" />
                </template>
            </div>
        </div>

        <div class="mx-auto grid max-w-5xl gap-6 px-6 py-8 md:grid-cols-[1fr_340px]">
            <div class="space-y-5">
                <Link :href="backHref" class="inline-flex items-center gap-2 text-sm font-medium text-sky-700 hover:underline">&larr; Modifier la recherche</Link>

                <!-- Aller -->
                <section class="rounded-2xl border border-navy-100 bg-white">
                    <div class="flex items-center justify-between border-b border-navy-100 px-6 py-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Aller</p>
                            <h1 class="text-xl font-bold text-navy-900">{{ dayHeading(offer.departure_at) }}</h1>
                        </div>
                        <span class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold text-white" :class="MODE_COLORS[offer.transport_mode] ?? 'bg-navy-700'">
                            {{ RIDE_ICONS[offer.transport_mode] ?? '🧭' }} {{ offer.transport_mode_label || 'Trajet' }}
                        </span>
                    </div>
                    <div class="grid grid-cols-[64px_18px_1fr] gap-x-3 px-6 py-5">
                        <p class="text-xl font-bold tabular-nums text-navy-900">{{ time(offer.departure_at) }}</p>
                        <div class="flex flex-col items-center pt-2">
                            <span class="h-3.5 w-3.5 rounded-full border-[3px] border-sky-700 bg-white" />
                            <span class="w-0.5 flex-1 bg-sky-700" />
                        </div>
                        <div class="pb-5">
                            <p class="text-lg font-semibold text-navy-900">{{ offer.origin_city }}</p>
                            <p v-if="offer.departure_point" class="text-sm text-navy-500">{{ offer.departure_point }}</p>
                        </div>

                        <p class="text-xs text-navy-500">{{ duration }}</p>
                        <div class="flex justify-center"><span class="w-0.5 bg-sky-700" /></div>
                        <div class="mb-5 flex items-center gap-3 rounded-xl bg-navy-50/70 px-4 py-3 text-sm">
                            <span class="text-2xl">{{ RIDE_ICONS[offer.transport_mode] ?? '🧭' }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-navy-900">{{ offer.airline || 'Transporteur partenaire' }}</p>
                                <p class="text-navy-600">{{ offer.transport_mode_label }} · Direct{{ duration ? ` · ${duration}` : '' }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold" :class="seats === 0 ? 'bg-gray-100 text-gray-500' : seats <= 3 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'">
                                {{ seats === 0 ? 'Complet' : `${seats} place${seats > 1 ? 's' : ''}` }}
                            </span>
                        </div>

                        <p class="text-xl font-bold tabular-nums text-navy-900">{{ time(offer.arrival_at) }}</p>
                        <div class="flex justify-center"><span class="h-3.5 w-3.5 rounded-full border-[3px] border-sky-700 bg-white" /></div>
                        <p class="text-lg font-semibold text-navy-900">{{ offer.destination_city }}</p>
                    </div>
                </section>

                <!-- Bon a savoir (selon le moyen) -->
                <section class="rounded-2xl border border-navy-100 bg-white px-6 py-5">
                    <h2 class="mb-3 font-bold text-navy-900">Bon a savoir</h2>
                    <ul class="space-y-2.5 text-sm text-navy-700">
                        <li v-for="t in tips" :key="t[1]" class="flex gap-3"><span class="w-5 shrink-0 text-center">{{ t[0] }}</span><span>{{ t[1] }}</span></li>
                    </ul>
                </section>

                <section v-if="offer.description" class="rounded-2xl border border-navy-100 bg-white px-6 py-5 text-sm text-navy-700">
                    <h2 class="mb-1 font-bold text-navy-900">Infos du transporteur</h2>
                    <p class="whitespace-pre-line">{{ offer.description }}</p>
                </section>
            </div>

            <!-- Recapitulatif -->
            <form class="h-fit rounded-2xl border border-navy-100 bg-white md:sticky md:top-24" @submit.prevent="submit">
                <div class="border-b border-navy-100 px-6 py-4">
                    <h2 class="font-bold text-navy-900">Recapitulatif</h2>
                    <p class="text-sm text-navy-600">{{ offer.origin_city }} → {{ offer.destination_city }}</p>
                    <p class="text-xs text-navy-500">{{ dayHeading(offer.departure_at) }} · {{ time(offer.departure_at) }}</p>
                </div>
                <div class="space-y-4 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-navy-900">Voyageurs</p>
                            <p class="text-xs text-navy-500">{{ Math.round(unit).toLocaleString('fr-FR') }} {{ $cur(offer.currency) }} / voyageur</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="button" class="h-9 w-9 rounded-full border border-navy-200 text-lg disabled:opacity-30" :disabled="form.quantity <= 1" @click="form.quantity--">−</button>
                            <span class="w-5 text-center text-lg font-bold">{{ form.quantity }}</span>
                            <button type="button" class="h-9 w-9 rounded-full border border-navy-200 text-lg disabled:opacity-30" :disabled="form.quantity >= seats" @click="form.quantity++">+</button>
                        </div>
                    </div>
                    <p v-if="form.errors.quantity" class="text-xs text-red-600">{{ form.errors.quantity }}</p>
                    <div>
                        <label class="text-sm font-semibold text-navy-900">Message au transporteur <span class="font-normal text-navy-500">(optionnel)</span></label>
                        <textarea v-model="form.notes" rows="2" :placeholder="notePlaceholder" class="mt-1 w-full rounded-lg border-navy-200 text-sm focus:border-sky-600 focus:ring-sky-600"></textarea>
                    </div>
                </div>
                <div class="border-t border-navy-100 px-6 py-4">
                    <div class="flex items-baseline justify-between">
                        <span class="font-semibold text-navy-900">Total</span>
                        <span class="text-2xl font-bold text-navy-900">{{ total.toLocaleString('fr-FR') }} <span class="text-sm">{{ $cur(offer.currency) }}</span></span>
                    </div>
                    <button type="submit" class="mt-4 w-full rounded-full bg-sky-700 py-3 font-semibold text-white hover:bg-sky-800 disabled:opacity-50" :disabled="form.processing || seats === 0">
                        {{ seats === 0 ? 'Complet' : 'Continuer vers le paiement' }}
                    </button>
                    <p class="mt-3 text-center text-xs text-navy-500">Paiement Mobile Money securise : MTN MoMo, Airtel Money, Orange Money, M-Pesa…</p>
                </div>
            </form>
        </div>
    </div>

    <div v-else class="mx-auto grid max-w-5xl gap-10 px-6 py-12 md:grid-cols-2">
        <div>
            <div class="relative h-64 overflow-hidden rounded-2xl">
                <DestinationImage :city="offer.destination_city" :image="offer.image_url" class="h-full w-full" />
                <div class="absolute inset-0 bg-gradient-to-t from-navy-900/70 to-transparent" />
                <span class="absolute bottom-4 left-5 text-2xl font-semibold text-white drop-shadow">{{ offer.destination_city }}</span>
            </div>
            <h1 class="mt-6 text-3xl font-bold text-navy-900">{{ offer.title }}</h1>
            <p class="mt-2 text-navy-700">{{ offer.description }}</p>

            <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-navy-500">Type</dt><dd class="font-medium capitalize">{{ offer.type }}</dd></div>
                <div v-if="offer.transport_mode_label && offer.type !== 'vol'"><dt class="text-navy-500">Transport</dt><dd class="font-medium">{{ offer.transport_mode_label }}</dd></div>
                <div v-if="offer.airline"><dt class="text-navy-500">Compagnie / transporteur</dt><dd class="font-medium">{{ offer.airline }}</dd></div>
                <div v-if="offer.departure_point"><dt class="text-navy-500">Embarquement</dt><dd class="font-medium">{{ offer.departure_point }}</dd></div>
                <div v-if="offer.duration_label"><dt class="text-navy-500">Duree</dt><dd class="font-medium">{{ offer.duration_label }}</dd></div>
                <div><dt class="text-navy-500">Depart</dt><dd class="font-medium">{{ offer.origin_city }}</dd></div>
                <div><dt class="text-navy-500">Destination</dt><dd class="font-medium">{{ offer.destination_city }}</dd></div>
                <div><dt class="text-navy-500">Places disponibles</dt><dd class="font-medium">{{ offer.seats_available }}</dd></div>
            </dl>
        </div>

        <div class="h-fit rounded-2xl border p-6 shadow-sm">
            <p class="text-2xl font-bold text-gold-600">
                {{ Number(offer.discounted_price ?? offer.price).toLocaleString('fr-FR') }} {{ $cur(offer.currency) }}
                <span class="text-sm font-normal text-navy-500">/ personne</span>
            </p>

            <div v-if="offer.type === 'vol'" class="mt-6 space-y-3">
                <p class="text-sm text-navy-700">
                    Le billet d'avion se reserve via notre billetterie : choix des horaires, saisie des passagers
                    (nom comme sur le passeport), prix confirme aupres de la compagnie puis emission du billet electronique.
                </p>
                <button v-if="flightSearch" type="button" class="btn-gold w-full" @click="searchFlights">Voir les vols et reserver</button>
                <Link v-else href="/voyage?type=vol" class="btn-gold block w-full text-center">Rechercher un vol</Link>
            </div>
            <form v-else class="mt-6 space-y-4" @submit.prevent="submit">
                <div>
                    <label class="text-sm font-medium text-navy-900">Nombre de voyageurs</label>
                    <input v-model.number="form.quantity" type="number" min="1" max="20" class="mt-1 w-full rounded-lg border-gray-300" />
                </div>
                <div>
                    <label class="text-sm font-medium text-navy-900">Notes (optionnel)</label>
                    <textarea v-model="form.notes" rows="3" class="mt-1 w-full rounded-lg border-gray-300"></textarea>
                </div>
                <button type="submit" class="btn-gold w-full" :disabled="form.processing">Reserver maintenant</button>
                <p class="text-center text-xs text-navy-500">
                    Paiement Mobile Money securise : MTN MoMo, Airtel Money, Orange Money, M-Pesa…
                </p>
            </form>
        </div>
    </div>
</template>
