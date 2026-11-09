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
</script>

<template>
    <Head :title="offer.title" />

    <!-- TRAJET bus / train / taxi : page facon BlaBlaCar -->
    <div v-if="isRide" class="bg-navy-50/40">
        <div class="mx-auto grid max-w-5xl gap-8 px-6 py-10 md:grid-cols-[1fr_340px]">
            <div class="space-y-5">
                <Link href="/voyage?type=circuit" class="text-sm text-navy-500 hover:text-navy-900">&larr; Tous les trajets</Link>
                <h1 class="text-3xl font-extrabold text-navy-900">{{ dayHeading(offer.departure_at) }}</h1>

                <div class="rounded-2xl bg-white p-6 shadow-sm">
                    <div class="grid grid-cols-[56px_16px_1fr] gap-x-3">
                        <p class="text-lg font-extrabold text-navy-900">{{ time(offer.departure_at) }}</p>
                        <div class="flex flex-col items-center pt-2">
                            <span class="h-3.5 w-3.5 rounded-full border-2 border-navy-900 bg-white" />
                            <span class="w-1 flex-1 rounded bg-navy-900" />
                        </div>
                        <div class="pb-6">
                            <p class="text-lg font-bold text-navy-900">{{ offer.origin_city }}</p>
                            <p v-if="offer.departure_point" class="text-sm text-navy-500">{{ offer.departure_point }}</p>
                        </div>
                        <p class="text-xs font-semibold text-navy-400">{{ duration }}</p>
                        <div />
                        <div />
                        <p class="text-lg font-extrabold text-navy-900">{{ time(offer.arrival_at) }}</p>
                        <div class="flex justify-center"><span class="h-3.5 w-3.5 rounded-full border-2 border-navy-900 bg-white" /></div>
                        <p class="text-lg font-bold text-navy-900">{{ offer.destination_city }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-4 rounded-2xl bg-white p-5 shadow-sm">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-navy-50 text-2xl">{{ RIDE_ICONS[offer.transport_mode] ?? '🧭' }}</span>
                    <div class="flex-1">
                        <p class="font-bold text-navy-900">{{ offer.airline || 'Transporteur partenaire' }}</p>
                        <p class="text-sm text-navy-500">{{ offer.transport_mode_label }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-sm font-bold" :class="seats === 0 ? 'bg-gray-100 text-gray-500' : 'bg-emerald-50 text-emerald-700'">
                        {{ seats === 0 ? 'Complet' : `${seats} place${seats > 1 ? 's' : ''} restante${seats > 1 ? 's' : ''}` }}
                    </span>
                </div>

                <div v-if="offer.description" class="rounded-2xl bg-white p-5 text-sm text-navy-700 shadow-sm">
                    <p class="mb-1 font-bold text-navy-900">Infos du trajet</p>
                    <p class="whitespace-pre-line">{{ offer.description }}</p>
                </div>
                <ul class="space-y-2 rounded-2xl bg-white p-5 text-sm text-navy-700 shadow-sm">
                    <li>✅ Paiement Mobile Money securise (MaishaPay, Digitwace, Peex, pawaPay)</li>
                    <li>🎫 Reservation confirmee des le paiement, a presenter au depart</li>
                    <li>⏰ Presentez-vous 30 minutes avant le depart{{ offer.departure_point ? ` a : ${offer.departure_point}` : '' }}</li>
                </ul>
            </div>

            <form class="h-fit space-y-4 rounded-2xl bg-white p-6 shadow-sm md:sticky md:top-24" @submit.prevent="submit">
                <div class="flex items-baseline justify-between">
                    <span class="text-sm text-navy-600">Prix par passager</span>
                    <span class="text-xl font-extrabold text-navy-900">{{ Math.round(unit).toLocaleString('fr-FR') }} {{ offer.currency }}</span>
                </div>
                <div>
                    <label class="text-sm font-semibold text-navy-900">Passagers</label>
                    <div class="mt-1 flex items-center gap-3">
                        <button type="button" class="h-9 w-9 rounded-full border text-lg" :disabled="form.quantity <= 1" @click="form.quantity--">−</button>
                        <span class="w-6 text-center text-lg font-bold">{{ form.quantity }}</span>
                        <button type="button" class="h-9 w-9 rounded-full border text-lg" :disabled="form.quantity >= seats" @click="form.quantity++">+</button>
                    </div>
                    <p v-if="form.errors.quantity" class="mt-1 text-xs text-red-600">{{ form.errors.quantity }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-navy-900">Message au transporteur (optionnel)</label>
                    <textarea v-model="form.notes" rows="2" placeholder="Bagages volumineux, point de prise en charge..." class="mt-1 w-full rounded-lg border-gray-300 text-sm"></textarea>
                </div>
                <div class="flex items-baseline justify-between border-t pt-3">
                    <span class="font-semibold text-navy-900">Total</span>
                    <span class="text-2xl font-extrabold text-navy-900">{{ total.toLocaleString('fr-FR') }} {{ offer.currency }}</span>
                </div>
                <button type="submit" class="w-full rounded-full bg-sky-500 py-3 font-bold text-white hover:bg-sky-600 disabled:opacity-50" :disabled="form.processing || seats === 0">
                    {{ seats === 0 ? 'Complet' : 'Reserver et payer' }}
                </button>
                <p class="text-center text-xs text-navy-500">Vous payez sur la page suivante par Mobile Money.</p>
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
                {{ Number(offer.discounted_price ?? offer.price).toLocaleString('fr-FR') }} {{ offer.currency }}
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
                    Paiement Mobile Money securise : Peex, pawaPay, MaishaPay ou Digitwace.
                </p>
            </form>
        </div>
    </div>
</template>
