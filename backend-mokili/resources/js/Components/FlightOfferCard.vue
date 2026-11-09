<script setup>
// Carte d'un vol (mise en page Skyscanner) : a gauche un trajet par ligne
// (pastille compagnie, heure + aeroport, duree sur une ligne ponctuee des
// escales, codes des escales, arrivee +1), a droite le prix et "Selectionner".
import AirlineLogo from '@/Components/AirlineLogo.vue';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { airlinesLabel, dayLabel, dayOffset, duration, hhmm, money, stopCodes } from '@/utils/flights';

const props = defineProps({
    offer: { type: Object, required: true },
    airports: { type: Object, default: () => ({}) },
    selectable: { type: Boolean, default: true },
    badges: { type: Array, default: () => [] },
    favorite: { type: Boolean, default: false },
});
defineEmits(['favorite']);

const details = ref(false);
const rules = computed(() => props.offer.fare_rules ?? {});
const adult = computed(() => (props.offer.passengers ?? []).find((p) => p.type === 'ADT'));
const city = (code) => props.airports?.[code]?.city ?? code;
const operatedBy = computed(() => {
    const names = [...new Set((props.offer.slices ?? []).flatMap((s) => (s.segments ?? []).map((g) => g.operated_by)).filter(Boolean))];
    return names.length ? `Opere par ${names.join(', ')}` : '';
});
const stopsText = (s) => {
    const n = Number(s.stops) || 0;
    return n ? `${n} escale${n > 1 ? 's' : ''}` : 'Direct';
};
const sliceCode = (s) => s.segments?.[0]?.airline_code ?? props.offer.validating_airline;
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
        <div class="grid md:grid-cols-[1fr_210px]">
            <!-- Trajets -->
            <div class="p-4 sm:p-5">
                <div class="mb-2 flex flex-wrap items-center gap-x-2 gap-y-1 pr-8 md:pr-0">
                    <p class="text-xs font-bold text-navy-900">{{ airlinesLabel(offer) }}<span v-if="operatedBy" class="font-normal text-navy-500"> · {{ operatedBy }}</span></p>
                    <span v-for="b in badges" :key="b" class="rounded bg-sky-100 px-1.5 py-0.5 text-[10px] font-bold text-sky-800">{{ b }}</span>
                </div>

                <div v-for="(s, i) in offer.slices" :key="i" class="grid grid-cols-[40px_auto_1fr_auto] items-center gap-x-3 py-2 sm:gap-x-5">
                    <AirlineLogo :code="sliceCode(s)" size="h-9 w-9 text-[10px]" />
                    <div class="text-right">
                        <p class="text-lg font-bold leading-tight text-navy-900 sm:text-xl">{{ hhmm(s.departure_at) }}</p>
                        <p class="text-sm text-navy-500">{{ s.origin }}</p>
                    </div>
                    <div class="min-w-0 text-center">
                        <p class="text-xs text-navy-500">{{ duration(s.duration_minutes) }}</p>
                        <div class="relative my-1.5 flex items-center">
                            <div class="h-px flex-1 bg-navy-300" />
                            <span class="-ml-0.5 text-[10px] leading-none text-navy-400">✈</span>
                            <div class="absolute inset-x-0 top-1/2 flex -translate-y-1/2 justify-center gap-3 pr-3">
                                <span v-for="(c, k) in stopCodes(s)" :key="k" class="h-1.5 w-1.5 rounded-full bg-rose-600 ring-2 ring-white" />
                            </div>
                        </div>
                        <p class="truncate text-xs">
                            <span :class="s.stops ? 'text-rose-600' : 'font-semibold text-emerald-600'">{{ stopsText(s) }}</span>
                            <span v-if="stopCodes(s).length" class="text-navy-500">{{ ' ' + stopCodes(s).join(', ') }}</span>
                        </p>
                    </div>
                    <div>
                        <p class="text-lg font-bold leading-tight text-navy-900 sm:text-xl">
                            {{ hhmm(s.arrival_at) }}<sup v-if="dayOffset(s.departure_at, s.arrival_at) > 0" class="ml-0.5 text-[10px] font-bold">+{{ dayOffset(s.departure_at, s.arrival_at) }}</sup>
                        </p>
                        <p class="text-sm text-navy-500">{{ s.destination }}</p>
                    </div>
                </div>

                <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px] text-navy-600">
                    <span v-if="rules.cabin_baggage" class="rounded bg-navy-50 px-2 py-0.5">👜 {{ rules.cabin_baggage }}</span>
                    <span v-if="rules.baggage" class="rounded bg-navy-50 px-2 py-0.5">🧳 {{ rules.baggage }}</span>
                    <span class="rounded px-2 py-0.5" :class="rules.refundable ? 'bg-emerald-50 text-emerald-700' : 'bg-navy-50 text-navy-500'">{{ rules.refundable ? 'Remboursable' : 'Non remboursable' }}</span>
                    <button type="button" class="ml-auto font-semibold text-navy-900 hover:underline" @click="details = !details">{{ details ? 'Masquer' : 'Details' }} ›</button>
                </div>
            </div>

            <!-- Prix -->
            <div class="relative flex flex-row items-end justify-between gap-3 border-t p-4 md:flex-col md:items-stretch md:justify-end md:border-l md:border-t-0 sm:p-5">
                <button
                    type="button"
                    class="absolute right-3 top-3 text-xl leading-none transition"
                    :class="favorite ? 'text-rose-600' : 'text-navy-900 hover:text-rose-600'"
                    :title="favorite ? 'Retirer des favoris' : 'Ajouter aux favoris'"
                    @click="$emit('favorite', offer)"
                >
                    {{ favorite ? '♥' : '♡' }}
                </button>
                <div>
                    <p class="text-xs text-navy-500">{{ adult && (offer.passengers.length > 1 || adult.count > 1) ? `${money(adult.unit_amount, offer.currency)} / adulte` : 'Total, taxes incluses' }}</p>
                    <p class="whitespace-nowrap text-xl font-extrabold text-navy-900 sm:text-2xl">{{ money(offer.total_amount, offer.currency) }}</p>
                </div>
                <Link v-if="selectable" :href="`/voyage/vols/offres/${offer.uuid}`" class="inline-flex items-center justify-center gap-2 rounded-lg bg-navy-900 px-5 py-2.5 text-sm font-bold text-white hover:bg-navy-700">
                    Selectionner <span aria-hidden="true">→</span>
                </Link>
            </div>
        </div>

        <div v-if="details" class="grid gap-4 border-t bg-navy-50/40 p-5 text-sm md:grid-cols-2">
            <div>
                <p class="font-semibold text-navy-900">Vols</p>
                <ul class="mt-2 space-y-1 text-navy-700">
                    <li v-for="(s, i) in offer.slices" :key="i">
                        <span v-for="seg in s.segments" :key="seg.flight_number + seg.departure_at" class="block">
                            {{ seg.airline_name }} {{ seg.flight_number }} · {{ city(seg.origin) }} → {{ city(seg.destination) }} · {{ dayLabel(seg.departure_at) }} {{ hhmm(seg.departure_at) }}
                        </span>
                    </li>
                </ul>
            </div>
            <div>
                <p class="font-semibold text-navy-900">Conditions tarifaires</p>
                <ul class="mt-2 space-y-1 text-navy-700">
                    <li>Modification : {{ rules.changeable === false ? 'non autorisee' : (rules.change_fee != null ? 'frais ' + money(rules.change_fee, offer.currency) + ' + difference tarifaire' : 'selon conditions compagnie') }}</li>
                    <li>Annulation : {{ rules.refundable ? (rules.cancel_fee != null ? 'remboursable, frais ' + money(rules.cancel_fee, offer.currency) : 'remboursable') : 'non remboursable (taxes remboursables sur demande)' }}</li>
                    <li v-if="rules.conditions" class="whitespace-pre-line text-xs">{{ rules.conditions }}</li>
                </ul>
            </div>
        </div>
    </div>
</template>
