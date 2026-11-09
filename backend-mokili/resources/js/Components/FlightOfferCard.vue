<script setup>
// Carte d'un vol dans les resultats (presentation inspiree de Skyscanner) :
// compagnies, une ligne par trajet (horaires, aeroports, escales, duree), prix.
import AirlineLogo from '@/Components/AirlineLogo.vue';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { airlinesLabel, dayLabel, dayOffset, duration, hhmm, money, sliceLabel } from '@/utils/flights';

const props = defineProps({
    offer: { type: Object, required: true },
    airports: { type: Object, default: () => ({}) },
    selectable: { type: Boolean, default: true },
    // Etiquettes affichees en haut de la carte ("Le moins cher", "Le plus rapide"...).
    badges: { type: Array, default: () => [] },
});

const details = ref(false);
const rules = computed(() => props.offer.fare_rules ?? {});
const adult = computed(() => (props.offer.passengers ?? []).find((p) => p.type === 'ADT'));
const city = (code) => props.airports?.[code]?.city ?? code;
const stopsText = (n) => (!n ? 'Direct' : `${n} escale${n > 1 ? 's' : ''}`);
const sliceCode = (s) => s.segments?.[0]?.airline_code ?? props.offer.validating_airline;
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:border-navy-200 hover:shadow-md">
        <div class="p-4 sm:p-5">
            <div class="mb-3 flex flex-wrap items-center gap-2">
                <p class="font-bold text-navy-900">{{ airlinesLabel(offer) }}</p>
                <span v-for="b in badges" :key="b" class="rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-bold text-sky-800">{{ b }}</span>
            </div>

            <div class="grid gap-4 md:grid-cols-[1fr_auto] md:items-center">
                <div class="space-y-3">
                    <div v-for="(s, i) in offer.slices" :key="i" class="flex items-center gap-3">
                        <AirlineLogo :code="sliceCode(s)" />
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-navy-400">{{ sliceLabel(offer.slices, i) }} · {{ dayLabel(s.departure_at) }}</p>
                            <p class="text-base font-bold text-navy-900">
                                {{ hhmm(s.departure_at) }} – {{ hhmm(s.arrival_at) }}<sup v-if="dayOffset(s.departure_at, s.arrival_at) > 0" class="ml-0.5 text-[10px] font-bold text-navy-500">+{{ dayOffset(s.departure_at, s.arrival_at) }}</sup>
                            </p>
                            <p class="truncate text-xs text-navy-500">{{ s.origin }} - {{ s.destination }} <span class="hidden sm:inline">· {{ city(s.origin) }} → {{ city(s.destination) }}</span></p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold" :class="s.stops ? 'text-rose-600' : 'text-emerald-600'">{{ stopsText(s.stops) }}</p>
                            <p class="text-xs text-navy-500">{{ duration(s.duration_minutes) }}</p>
                            <p v-if="s.stops && s.via" class="text-[11px] text-navy-400">via {{ s.via }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-end justify-between gap-3 border-t pt-3 md:min-w-[190px] md:flex-col md:items-end md:border-l md:border-t-0 md:pl-5 md:pt-0">
                    <div class="md:text-right">
                        <p class="whitespace-nowrap text-xl font-extrabold text-navy-900 sm:text-2xl">{{ money(offer.total_amount, offer.currency) }}</p>
                        <p class="text-xs text-navy-500">total, taxes incluses</p>
                        <p v-if="adult && (offer.passengers.length > 1 || adult.count > 1)" class="text-xs text-navy-500">{{ money(adult.unit_amount, offer.currency) }} / adulte</p>
                    </div>
                    <Link v-if="selectable" :href="`/voyage/vols/offres/${offer.uuid}`" class="btn-gold whitespace-nowrap text-center">Selectionner →</Link>
                </div>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-dashed pt-3 text-xs text-navy-600">
                <span v-if="rules.cabin_baggage">👜 Cabine {{ rules.cabin_baggage }}</span>
                <span v-if="rules.baggage">🧳 Soute {{ rules.baggage }}</span>
                <span :class="rules.refundable ? 'text-emerald-700' : 'text-navy-500'">{{ rules.refundable ? 'Remboursable' : 'Non remboursable' }}</span>
                <button type="button" class="ml-auto font-semibold text-navy-900 underline-offset-2 hover:underline" @click="details = !details">{{ details ? 'Masquer les details' : 'Afficher les details' }} ›</button>
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
                <p class="mt-2 text-xs text-navy-500">Detail du prix : <span v-for="p in offer.passengers" :key="p.type">{{ p.count }} × {{ money(p.unit_amount, offer.currency) }} ({{ { ADT: 'adulte', CHD: 'enfant', INF: 'bebe' }[p.type] }}) </span><span v-if="Number(offer.service_fee)">+ frais de service {{ money(offer.service_fee, offer.currency) }}</span></p>
            </div>
        </div>
    </div>
</template>
