<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { dayLabel, dayOffset, duration, hhmm, money, stopsLabel } from '@/utils/flights';

const props = defineProps({
    offer: { type: Object, required: true },
    airports: { type: Object, default: () => ({}) },
    selectable: { type: Boolean, default: true },
});

const details = ref(false);
const rules = computed(() => props.offer.fare_rules ?? {});
const adult = computed(() => (props.offer.passengers ?? []).find((p) => p.type === 'ADT'));
const city = (code) => props.airports?.[code]?.city ?? code;
</script>

<template>
    <div class="overflow-hidden rounded-2xl border bg-white shadow-sm transition hover:shadow-md">
        <div class="grid gap-4 p-5 md:grid-cols-[1fr_auto]">
            <div class="space-y-4">
                <div v-for="(s, i) in offer.slices" :key="i" class="flex items-center gap-4">
                    <div class="w-24 shrink-0">
                        <p class="text-xs font-semibold uppercase text-navy-500">{{ i === 0 ? 'Aller' : 'Retour' }}</p>
                        <p class="text-xs text-navy-500">{{ dayLabel(s.departure_at) }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-navy-900">{{ hhmm(s.departure_at) }}</p>
                        <p class="text-xs font-semibold text-navy-500">{{ s.origin }}</p>
                    </div>
                    <div class="flex-1 text-center">
                        <p class="text-xs text-navy-500">{{ duration(s.duration_minutes) }}</p>
                        <div class="relative my-1 h-px bg-navy-200">
                            <span v-if="s.stops" class="absolute left-1/2 top-1/2 h-2 w-2 -translate-x-1/2 -translate-y-1/2 rounded-full bg-gold-600" />
                        </div>
                        <p class="text-xs" :class="s.stops ? 'text-gold-600' : 'text-green-700'">{{ stopsLabel(s.stops, s.via) }}</p>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-navy-900">
                            {{ hhmm(s.arrival_at) }}<sup v-if="dayOffset(s.departure_at, s.arrival_at) > 0" class="ml-0.5 text-xs text-gold-600">+{{ dayOffset(s.departure_at, s.arrival_at) }}</sup>
                        </p>
                        <p class="text-xs font-semibold text-navy-500">{{ s.destination }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-navy-600">
                    <span class="font-semibold text-navy-900">{{ offer.airline_name }}</span>
                    <span v-if="rules.baggage">🧳 {{ rules.baggage }}</span>
                    <span v-if="rules.cabin_baggage">👜 {{ rules.cabin_baggage }}</span>
                    <span :class="rules.refundable ? 'text-green-700' : 'text-navy-500'">{{ rules.refundable ? 'Remboursable' : 'Non remboursable' }}</span>
                    <button type="button" class="font-semibold text-gold-600 hover:underline" @click="details = !details">{{ details ? 'Masquer' : 'Details & conditions' }}</button>
                </div>
            </div>
            <div class="flex flex-col items-end justify-between gap-2 border-t pt-4 md:border-l md:border-t-0 md:pl-6 md:pt-0">
                <div class="text-right">
                    <p class="text-2xl font-extrabold text-navy-900">{{ money(offer.total_amount, offer.currency) }}</p>
                    <p class="text-xs text-navy-500">total, taxes incluses</p>
                    <p v-if="adult && (offer.passengers.length > 1 || adult.count > 1)" class="text-xs text-navy-500">{{ money(adult.unit_amount, offer.currency) }} / adulte</p>
                </div>
                <Link v-if="selectable" :href="`/voyage/vols/offres/${offer.uuid}`" class="btn-gold w-full text-center md:w-auto">Selectionner</Link>
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
