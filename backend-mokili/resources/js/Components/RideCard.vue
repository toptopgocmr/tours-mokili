<script setup>
// Trajet bus / train / canot / taxi facon BlaBlaCar : 08:00 o—2h25—o 10:25,
// ville + gare sous chaque heure, prix a droite, badge "Le plus tot et le
// plus rapide" / "Le moins cher", puis transporteur et services a bord.
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { rideTime } from '@/utils/rides';

const props = defineProps({
    offer: { type: Object, required: true },
    passengers: { type: Number, default: 1 },
    cheapest: { type: Boolean, default: false },
    fastest: { type: Boolean, default: false },
    earliest: { type: Boolean, default: false },
});

const dur = computed(() => {
    const a = props.offer.departure_at ? new Date(props.offer.departure_at) : null;
    const b = props.offer.arrival_at ? new Date(props.offer.arrival_at) : null;
    if (a && b && b > a) {
        const m = Math.round((b - a) / 60000);
        return m < 60 ? `${m} min` : `${Math.floor(m / 60)}h${String(m % 60).padStart(2, '0')}`;
    }
    return props.offer.duration_label ?? '';
});
const nextDay = computed(() => {
    const a = props.offer.departure_at?.slice(0, 10);
    const b = props.offer.arrival_at?.slice(0, 10);
    return a && b && b > a;
});
const night = computed(() => {
    const h = Number(rideTime(props.offer.departure_at).slice(0, 2));
    return h >= 21 || h < 5;
});
const price = computed(() => Math.round(Number(props.offer.discounted_price ?? props.offer.price)));
const total = computed(() => price.value * props.passengers);
const seats = computed(() => Number(props.offer.seats_available ?? 0));
const full = computed(() => seats.value === 0 || seats.value < props.passengers);
const href = computed(() => `/voyage/${props.offer.slug}${props.passengers > 1 ? `?passagers=${props.passengers}` : ''}`);
const badge = computed(() => {
    const parts = [];
    if (props.earliest) parts.push('le plus tot');
    if (props.cheapest) parts.push('le moins cher');
    if (props.fastest) parts.push('le plus rapide');
    if (!parts.length) return null;
    const txt = parts.join(' et ');
    return txt.charAt(0).toUpperCase() + txt.slice(1);
});
const AMENITIES = { wifi: 'Wi-Fi', prises: 'Prises electriques', clim: 'Climatisation', toilettes: 'Toilettes', bagages: 'Bagage inclus', collation: 'Collation', tv: 'Ecrans', gilets: 'Gilets de sauvetage' };
const money = (n) => n.toLocaleString('fr-FR');
</script>

<template>
    <Link
        :href="href"
        class="group block rounded-2xl border-2 bg-white px-5 py-4 transition hover:shadow-[0_6px_20px_rgba(5,15,33,0.10)]"
        :class="[badge ? 'border-[#2f6fe4]' : 'border-transparent shadow-[0_1px_6px_rgba(5,15,33,0.10)] hover:border-navy-100', full && 'pointer-events-none opacity-60']"
    >
        <span v-if="badge" class="mb-3 inline-block rounded-md bg-navy-900 px-2 py-1 text-xs font-bold text-white">{{ badge }}</span>

        <div class="flex items-start justify-between gap-4">
            <!-- Horaires + villes -->
            <div class="min-w-0 flex-1 sm:max-w-md">
                <div class="flex items-center gap-2">
                    <span class="text-lg font-bold tabular-nums text-navy-900">{{ rideTime(offer.departure_at) }}</span>
                    <span class="h-2.5 w-2.5 shrink-0 rounded-full border-2 border-navy-900" />
                    <span class="h-0.5 flex-1 bg-navy-900" />
                    <span class="shrink-0 text-xs font-semibold text-navy-500">{{ dur || '—' }}<span v-if="night" class="ml-1" title="Trajet de nuit">☾</span></span>
                    <span class="h-0.5 flex-1 bg-navy-900" />
                    <span class="h-2.5 w-2.5 shrink-0 rounded-full border-2 border-navy-900" />
                    <span class="text-lg font-bold tabular-nums text-navy-900">{{ rideTime(offer.arrival_at) || '--:--' }}<sup v-if="nextDay" class="ml-0.5 text-xs font-semibold text-navy-500">+1</sup></span>
                </div>
                <div class="mt-1 flex justify-between gap-4">
                    <div class="min-w-0">
                        <p class="truncate font-bold text-navy-900">{{ offer.origin_city || '—' }}</p>
                        <p v-if="offer.departure_point" class="truncate text-sm text-navy-600">{{ offer.departure_point }}</p>
                    </div>
                    <div class="min-w-0 text-right">
                        <p class="truncate font-bold text-navy-900">{{ offer.destination_city }}</p>
                        <p v-if="offer.arrival_point" class="truncate text-sm text-navy-600">{{ offer.arrival_point }}</p>
                    </div>
                </div>
            </div>

            <!-- Prix -->
            <div class="shrink-0 text-right">
                <p class="text-2xl font-extrabold tabular-nums text-navy-900">{{ money(passengers > 1 ? total : price) }}<span class="ml-1 text-sm font-bold">{{ offer.currency }}</span></p>
                <p v-if="passengers > 1" class="text-xs text-navy-500">{{ passengers }} voyageurs</p>
                <p v-if="Number(offer.discount_percent) > 0" class="mt-1 inline-block rounded bg-rose-600 px-1.5 text-[10px] font-bold text-white">-{{ offer.discount_percent }}%<span v-if="offer.promo_label"> · {{ offer.promo_label }}</span></p>
            </div>
        </div>

        <!-- Transporteur + services -->
        <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-navy-50 pt-3 text-sm">
            <svg class="h-6 w-6 shrink-0 text-navy-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <template v-if="offer.transport_mode === 'train'"><rect x="5" y="3" width="14" height="14" rx="3" /><path d="M5 11h14M9 17l-2 4M15 17l2 4" /></template>
                <template v-else-if="offer.transport_mode === 'canot' || offer.transport_mode === 'bateau'"><path d="M3 15h18l-2.5 4h-13zM6 15V9h9l3 6M10 9V5" /></template>
                <template v-else-if="offer.transport_mode === 'taxi' || offer.transport_mode === 'voiture'"><path d="M5 16V12l2-5h10l2 5v4M3 12h18" /><circle cx="7.5" cy="16.5" r="1.5" /><circle cx="16.5" cy="16.5" r="1.5" /></template>
                <template v-else><rect x="3" y="5" width="18" height="11" rx="2" /><path d="M3 11h18M7 16v2M17 16v2M8 5v6M14 5v6" /></template>
            </svg>
            <span class="rounded-lg border border-navy-100 px-3 py-1 font-bold text-navy-900">{{ offer.airline || 'Transporteur partenaire' }}</span>
            <span v-for="a in (offer.amenities ?? []).slice(0, 4)" :key="a" class="text-navy-500">{{ AMENITIES[a] ?? a }}</span>
            <span class="ml-auto text-xs font-semibold" :class="seats === 0 ? 'text-gray-500' : seats <= 3 ? 'text-amber-700' : 'text-emerald-700'">
                {{ seats === 0 ? 'Complet' : seats <= 3 ? `Plus que ${seats} place${seats > 1 ? 's' : ''}` : `${seats} places` }}
            </span>
        </div>
    </Link>
</template>
