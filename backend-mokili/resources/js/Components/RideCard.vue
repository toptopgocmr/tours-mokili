<script setup>
// Trajet bus / train / taxi (presentation BlaBlaCar) : heures et villes sur
// une frise, prix par passager, transporteur et places restantes.
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { rideTime, RIDE_ICONS } from '@/utils/rides';

const props = defineProps({
    offer: { type: Object, required: true },
    passengers: { type: Number, default: 1 },
});

const time = rideTime;
const dur = computed(() => {
    const a = props.offer.departure_at ? new Date(props.offer.departure_at) : null;
    const b = props.offer.arrival_at ? new Date(props.offer.arrival_at) : null;
    if (a && b && b > a) {
        const m = Math.round((b - a) / 60000);
        return `${Math.floor(m / 60)}h${String(m % 60).padStart(2, '0')}`;
    }
    return props.offer.duration_label ?? '';
});
const price = computed(() => Math.round(Number(props.offer.discounted_price ?? props.offer.price)));
const seats = computed(() => Number(props.offer.seats_available ?? 0));
</script>

<template>
    <Link
        :href="`/voyage/${offer.slug}${passengers > 1 ? `?passagers=${passengers}` : ''}`"
        class="block rounded-2xl bg-white p-4 shadow-[0_1px_6px_rgba(5,15,33,0.12)] transition hover:shadow-[0_4px_14px_rgba(5,15,33,0.18)] sm:p-5"
        :class="seats === 0 && 'pointer-events-none opacity-50'"
    >
        <div class="flex gap-4">
            <!-- Frise depart / arrivee -->
            <div class="grid flex-1 grid-cols-[46px_14px_1fr] gap-x-2">
                <p class="text-base font-extrabold text-navy-900">{{ time(offer.departure_at) }}</p>
                <div class="flex flex-col items-center pt-1.5">
                    <span class="h-3 w-3 rounded-full border-2 border-navy-900 bg-white" />
                    <span class="w-1 flex-1 rounded bg-navy-900" />
                </div>
                <div class="pb-5">
                    <p class="font-bold text-navy-900">{{ offer.origin_city || '—' }}</p>
                    <p v-if="offer.departure_point" class="text-xs text-navy-500">{{ offer.departure_point }}</p>
                </div>

                <p class="text-[11px] font-semibold text-navy-400">{{ dur }}</p>
                <div />
                <div />

                <p class="text-base font-extrabold text-navy-900">{{ time(offer.arrival_at) }}</p>
                <div class="flex justify-center"><span class="h-3 w-3 rounded-full border-2 border-navy-900 bg-white" /></div>
                <p class="font-bold text-navy-900">{{ offer.destination_city }}</p>
            </div>
            <div class="text-right">
                <p class="text-xl font-extrabold text-navy-900">{{ price.toLocaleString('fr-FR') }}<span class="text-sm"> {{ offer.currency }}</span></p>
                <p class="text-[11px] text-navy-500">par passager</p>
                <p v-if="Number(offer.discount_percent) > 0" class="mt-1 inline-block rounded bg-rose-600 px-1.5 text-[10px] font-bold text-white">-{{ offer.discount_percent }}%</p>
            </div>
        </div>
        <div class="mt-3 flex items-center gap-3 border-t pt-3 text-sm">
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-navy-50 text-lg">{{ RIDE_ICONS[offer.transport_mode] ?? '🧭' }}</span>
            <div class="min-w-0 flex-1">
                <p class="truncate font-semibold text-navy-900">{{ offer.airline || offer.transport_mode_label || 'Transporteur' }}</p>
                <p class="text-xs text-navy-500">{{ offer.transport_mode_label }}</p>
            </div>
            <span class="rounded-full px-2.5 py-1 text-xs font-bold" :class="seats === 0 ? 'bg-gray-100 text-gray-500' : seats <= 3 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'">
                {{ seats === 0 ? 'Complet' : `${seats} place${seats > 1 ? 's' : ''}` }}
            </span>
        </div>
    </Link>
</template>
