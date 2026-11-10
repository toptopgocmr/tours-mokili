<script setup>
// Resultat train / bus / canot / taxi facon SNCF Connect : heure de depart
// et d'arrivee en grand, duree sur une ligne, gares, pictogramme du moyen,
// transporteur, places, puis prix "a partir de" et bouton Choisir.
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { rideTime, RIDE_ICONS } from '@/utils/rides';

const props = defineProps({
    offer: { type: Object, required: true },
    passengers: { type: Number, default: 1 },
    cheapest: { type: Boolean, default: false },
    fastest: { type: Boolean, default: false },
});

const time = rideTime;
const dur = computed(() => {
    const a = props.offer.departure_at ? new Date(props.offer.departure_at) : null;
    const b = props.offer.arrival_at ? new Date(props.offer.arrival_at) : null;
    if (a && b && b > a) {
        const m = Math.round((b - a) / 60000);
        return m < 60 ? `${m} min` : `${Math.floor(m / 60)}h${String(m % 60).padStart(2, '0')}`;
    }
    return props.offer.duration_label ?? '';
});
// Arrivee le lendemain (+1), comme sur SNCF Connect.
const nextDay = computed(() => {
    const a = props.offer.departure_at?.slice(0, 10);
    const b = props.offer.arrival_at?.slice(0, 10);
    return a && b && b > a;
});
const price = computed(() => Math.round(Number(props.offer.discounted_price ?? props.offer.price)));
const total = computed(() => price.value * props.passengers);
const seats = computed(() => Number(props.offer.seats_available ?? 0));
const full = computed(() => seats.value === 0 || seats.value < props.passengers);
const MODE_COLORS = { train: 'bg-sky-700', bus: 'bg-emerald-600', canot: 'bg-cyan-600', bateau: 'bg-cyan-700', taxi: 'bg-amber-500' };
const href = computed(() => `/voyage/${props.offer.slug}${props.passengers > 1 ? `?passagers=${props.passengers}` : ''}`);
</script>

<template>
    <Link
        :href="href"
        class="group block rounded-2xl border border-navy-100 bg-white transition hover:border-sky-600 hover:shadow-[0_4px_16px_rgba(5,15,33,0.10)]"
        :class="full && 'pointer-events-none opacity-60'"
    >
        <div v-if="cheapest || fastest" class="flex gap-2 px-5 pt-3">
            <span v-if="cheapest" class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">Le moins cher</span>
            <span v-if="fastest" class="rounded-md bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-700">Le plus rapide</span>
        </div>
        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center">
            <!-- Horaires -->
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-3">
                    <span class="text-2xl font-bold tabular-nums text-navy-900">{{ time(offer.departure_at) }}</span>
                    <span class="flex flex-1 items-center gap-2 text-xs text-navy-500">
                        <span class="h-px flex-1 bg-navy-200" />
                        <span class="whitespace-nowrap">{{ dur || 'Duree a confirmer' }}</span>
                        <span class="h-px flex-1 bg-navy-200" />
                    </span>
                    <span class="text-2xl font-bold tabular-nums text-navy-900">{{ time(offer.arrival_at) }}<sup v-if="nextDay" class="ml-0.5 text-xs font-semibold text-rose-600">+1</sup></span>
                </div>
                <div class="mt-1 flex justify-between gap-4 text-sm text-navy-700">
                    <span class="truncate">{{ offer.departure_point || offer.origin_city || '—' }}</span>
                    <span class="truncate text-right">{{ offer.destination_city }}</span>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 font-semibold text-white" :class="MODE_COLORS[offer.transport_mode] ?? 'bg-navy-700'">
                        <span>{{ RIDE_ICONS[offer.transport_mode] ?? '🧭' }}</span>{{ offer.transport_mode_label || 'Trajet' }}
                    </span>
                    <span class="font-semibold text-navy-900">{{ offer.airline || 'Transporteur partenaire' }}</span>
                    <span class="text-navy-400">·</span>
                    <span class="text-navy-600">Direct</span>
                    <span
                        class="ml-auto rounded-full px-2 py-0.5 font-semibold sm:ml-2"
                        :class="seats === 0 ? 'bg-gray-100 text-gray-500' : seats <= 3 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'"
                    >
                        {{ seats === 0 ? 'Complet' : seats <= 3 ? `Plus que ${seats} place${seats > 1 ? 's' : ''}` : `${seats} places` }}
                    </span>
                </div>
            </div>

            <!-- Prix -->
            <div class="flex items-center justify-between gap-4 border-t border-navy-100 pt-3 sm:w-48 sm:flex-col sm:items-end sm:border-l sm:border-t-0 sm:pl-5 sm:pt-0">
                <div class="sm:text-right">
                    <p class="text-xs text-navy-500">{{ passengers > 1 ? `${passengers} voyageurs` : 'par voyageur' }}</p>
                    <p class="text-2xl font-bold text-navy-900">{{ (passengers > 1 ? total : price).toLocaleString('fr-FR') }}<span class="ml-1 text-sm font-semibold">{{ offer.currency }}</span></p>
                    <p v-if="Number(offer.discount_percent) > 0" class="inline-block rounded bg-rose-600 px-1.5 text-[10px] font-bold text-white">-{{ offer.discount_percent }}%<span v-if="offer.promo_label"> · {{ offer.promo_label }}</span></p>
                </div>
                <span class="rounded-full border-2 border-sky-700 px-5 py-1.5 text-sm font-semibold text-sky-700 transition group-hover:bg-sky-700 group-hover:text-white">
                    {{ full ? 'Complet' : 'Choisir' }}
                </span>
            </div>
        </div>
    </Link>
</template>
