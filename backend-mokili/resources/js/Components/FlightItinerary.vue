<script setup>
// Detail d'un vol facon Skyscanner : resume du voyage puis, pour chaque
// trajet, la frise des vols avec les temps de connexion et les longues attentes.
import AirlineLogo from '@/Components/AirlineLogo.vue';
import { computed, reactive } from 'vue';
import {
    dayOffset, duration, durationLong, hhmm, longDate, LONG_CONNECTION_MINUTES, minutesBetween, shortDate, sliceLabel,
} from '@/utils/flights';

const props = defineProps({
    offer: { type: Object, required: true },
    search: { type: Object, default: () => ({}) },
    airports: { type: Object, default: () => ({}) },
    cabins: { type: Object, default: () => ({}) },
});

const city = (code) => props.airports?.[code]?.city ?? code;
const slices = computed(() => props.offer.slices ?? []);
const open = reactive({});
const isOpen = (i) => open[i] ?? true;

const tripLabel = computed(() => ({ roundtrip: 'Aller-retour', oneway: 'Aller simple', multicity: 'Multi-destinations' }[props.search.trip_type] ?? ''));
const travellers = computed(() => {
    const n = (props.search.adults ?? 1) + (props.search.children ?? 0) + (props.search.infants ?? 0);
    return `${n} voyageur${n > 1 ? 's' : ''}`;
});
const dates = computed(() => {
    const list = slices.value;
    if (!list.length) return '';
    const first = shortDate(list[0].departure_at);
    const last = shortDate(list[list.length - 1].departure_at);
    return first === last ? first : `${first} – ${last}`;
});
const headline = computed(() => {
    const list = slices.value;
    if (!list.length) return '';
    if (props.search.trip_type === 'multicity') return [...list.map((s) => city(s.origin)), city(list[list.length - 1].destination)].join(' → ');
    return `Depuis ${city(list[0].origin)} vers ${city(list[0].destination)}`;
});

// Segments d'un trajet + connexion avant le segment suivant.
const legs = (s) =>
    (s.segments ?? []).map((seg, i, all) => {
        const next = all[i + 1];
        const wait = next ? minutesBetween(seg.arrival_at, next.departure_at) : 0;
        return { seg, wait, long: wait >= LONG_CONNECTION_MINUTES, last: !next };
    });
</script>

<template>
    <div class="space-y-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-xl font-extrabold text-navy-900">{{ headline }}</h2>
            <p class="mt-1 text-sm text-navy-600">
                {{ dates }} · {{ travellers }}<span v-if="tripLabel"> · {{ tripLabel }}</span> · {{ cabins[search.cabin] ?? search.cabin }}
            </p>

            <div class="mt-4 divide-y">
                <div v-for="(s, i) in slices" :key="i" class="py-3 first:pt-0 last:pb-0">
                    <p class="text-xs text-navy-500">{{ sliceLabel(slices, i) }} · Depuis {{ city(s.origin) }} vers {{ city(s.destination) }}</p>
                    <div class="mt-1 flex items-center gap-3">
                        <AirlineLogo :code="s.segments?.[0]?.airline_code ?? offer.validating_airline" size="h-9 w-9 text-[10px]" />
                        <div class="flex-1">
                            <p class="font-bold text-navy-900">
                                {{ hhmm(s.departure_at) }} – {{ hhmm(s.arrival_at) }}<sup v-if="dayOffset(s.departure_at, s.arrival_at) > 0" class="ml-0.5 text-[10px]">+{{ dayOffset(s.departure_at, s.arrival_at) }}</sup>
                            </p>
                            <p class="text-xs text-navy-500">{{ s.segments?.[0]?.airline_name ?? offer.airline_name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold" :class="s.stops ? 'text-rose-600' : 'text-emerald-600'">{{ s.stops ? `${s.stops} escale${s.stops > 1 ? 's' : ''}` : 'Direct' }}</p>
                            <p class="text-xs text-navy-500">{{ duration(s.duration_minutes) }}</p>
                        </div>
                    </div>
                    <p v-if="legs(s).some((l) => l.long)" class="mt-1 pl-12 text-xs font-semibold text-amber-700">Long delai d'attente</p>
                </div>
            </div>
        </div>

        <!-- Frise detaillee de chaque trajet -->
        <section v-for="(s, i) in slices" :key="`t${i}`" class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <button type="button" class="flex w-full items-center justify-between gap-3 p-5 text-left" @click="open[i] = !isOpen(i)">
                <div>
                    <p class="text-lg font-extrabold text-navy-900">{{ city(s.destination) }}</p>
                    <p class="text-sm text-navy-500">depuis {{ city(s.origin) }}</p>
                    <p class="mt-2 inline-block rounded-md bg-navy-600 px-2 py-0.5 text-xs font-semibold text-white">{{ longDate(s.departure_at) }}</p>
                    <p class="mt-2 text-xs text-navy-600">
                        🕓 {{ durationLong(s.duration_minutes) }}
                        <span class="ml-2" :class="s.stops ? 'text-rose-600' : 'text-emerald-600'">● {{ s.stops ? `${s.stops} escale${s.stops > 1 ? 's' : ''}` : 'Direct' }}</span>
                        <span v-if="s.stops && s.via && (s.segments ?? []).length < 2" class="text-navy-500"> via {{ city(s.via) }}</span>
                    </p>
                </div>
                <span class="text-xl text-navy-400">{{ isOpen(i) ? '▴' : '▾' }}</span>
            </button>

            <div v-if="isOpen(i)" class="space-y-0 border-t px-5 pb-5 pt-4">
                <template v-for="(l, k) in legs(s)" :key="k">
                    <div class="rounded-xl border border-gray-200">
                        <div class="flex items-center justify-between gap-3 border-b px-4 py-3">
                            <div>
                                <p class="font-semibold text-navy-900">{{ l.seg.airline_name ?? offer.airline_name }}</p>
                                <p class="text-xs text-navy-500">{{ l.seg.flight_number }}</p>
                            </div>
                            <AirlineLogo :code="l.seg.airline_code ?? offer.validating_airline" />
                        </div>
                        <div class="relative px-4 py-3 pl-9">
                            <span class="absolute bottom-6 left-[1.15rem] top-6 w-px bg-navy-200" />
                            <span class="absolute left-4 top-[1.1rem] h-2 w-2 rounded-full bg-navy-900" />
                            <span class="absolute bottom-[1.1rem] left-4 h-2 w-2 rounded-full bg-navy-900" />
                            <p class="text-sm"><strong>{{ hhmm(l.seg.departure_at) }}</strong> {{ city(l.seg.origin) }} <span class="text-xs text-navy-400">({{ l.seg.origin }})</span></p>
                            <p class="my-2 border-y border-dashed py-2 text-xs text-navy-500">
                                <template v-if="(s.segments ?? []).length === 1 && s.duration_minutes">🕓 {{ durationLong(s.duration_minutes) }}</template>
                                <template v-else>✈ Vol {{ l.seg.flight_number }}</template>
                                <span v-if="l.seg.cabin || search.cabin" class="ml-2">· {{ cabins[l.seg.cabin ?? search.cabin] ?? '' }}</span>
                                <span v-if="(s.segments ?? []).length === 1 && s.stops && s.via" class="ml-2">· escale a {{ city(s.via) }}</span>
                            </p>
                            <p class="text-sm">
                                <strong>{{ hhmm(l.seg.arrival_at) }}</strong><sup v-if="dayOffset(l.seg.departure_at, l.seg.arrival_at) > 0" class="ml-0.5 text-[10px]">+{{ dayOffset(l.seg.departure_at, l.seg.arrival_at) }}</sup>
                                {{ city(l.seg.destination) }} <span class="text-xs text-navy-400">({{ l.seg.destination }})</span>
                            </p>
                        </div>
                    </div>
                    <div v-if="!l.last" class="flex items-start gap-3 px-2 py-3 text-sm text-navy-700">
                        <span class="mt-0.5">🕓</span>
                        <div>
                            <p v-if="l.long" class="mb-1 inline-block rounded bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">Long delai d'attente</p>
                            <p><strong>{{ durationLong(l.wait) }}</strong> de connexion a {{ city(l.seg.destination) }}</p>
                        </div>
                    </div>
                </template>
            </div>
        </section>
    </div>
</template>
