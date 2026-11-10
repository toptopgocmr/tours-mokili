<script setup>
// Formulaire de recherche de vols (aller-retour / aller simple /
// multi-destinations, passagers, classe). Soumis en GET -> le serveur
// interroge les fournisseurs puis redirige vers la page de resultats.
import AirportInput from '@/Components/AirportInput.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';

const MAX_LEGS = 6;

const props = defineProps({
    initial: { type: Object, default: () => ({}) },
    labels: { type: Object, default: () => ({}) },
    cabins: { type: Object, default: () => ({ economy: 'Economique', business: 'Affaires' }) },
    suggestions: { type: Array, default: () => [] },
    // Aeroports connus, indexes par code IATA (pour afficher les villes des trajets).
    airports: { type: Object, default: () => ({}) },
});

const page = usePage();
const errors = computed(() => page.props.errors ?? {});
const today = new Date().toISOString().slice(0, 10);

const form = reactive({
    trip_type: props.initial.trip_type ?? 'roundtrip',
    origin: props.initial.origin ?? 'BZV',
    destination: props.initial.destination ?? '',
    depart_date: props.initial.depart_date?.slice?.(0, 10) ?? '',
    return_date: props.initial.return_date?.slice?.(0, 10) ?? '',
    adults: Number(props.initial.adults ?? 1),
    children: Number(props.initial.children ?? 0),
    infants: Number(props.initial.infants ?? 0),
    cabin: props.initial.cabin ?? 'economy',
    direct_only: ['1', 1, true, 'true'].includes(props.initial.direct_only),
});

// --- Multi-destinations : liste des vols (De -> Vers + date) ---
let legSeq = 0;
const airportLabel = (code) => {
    if (!code) return '';
    const a = props.airports?.[code];
    return a?.city ? `${a.city} (${code})` : code;
};
const makeLeg = (origin = '', destination = '', date = '', originLabel = '', destinationLabel = '') => ({
    key: ++legSeq,
    origin,
    destination,
    date,
    originLabel: originLabel || airportLabel(origin),
    destinationLabel: destinationLabel || airportLabel(destination),
});

const legs = reactive(
    Array.isArray(props.initial.legs) && props.initial.legs.length >= 2
        ? props.initial.legs.map((l) => makeLeg(l.origin, l.destination, String(l.date ?? '').slice(0, 10)))
        : [
              makeLeg(form.origin, form.destination, form.depart_date, props.labels.origin ?? (form.origin === 'BZV' ? 'Brazzaville (BZV)' : ''), props.labels.destination ?? ''),
              makeLeg(form.destination, '', '', props.labels.destination ?? ''),
          ],
);

const addLeg = () => {
    if (legs.length >= MAX_LEGS) return;
    const last = legs[legs.length - 1];
    // Le vol suivant part de la ville d'arrivee du precedent.
    legs.push(makeLeg(last.destination, '', '', last.destinationLabel));
};
const removeLeg = (i) => {
    if (legs.length > 2) legs.splice(i, 1);
};
const pickedLeg = (leg, side, a) => {
    leg[`${side}Label`] = `${a.city} (${a.iata_code})`;
};
const legMin = (i) => (i > 0 && legs[i - 1].date ? legs[i - 1].date : today);
const legError = (i, field) => errors.value[`legs.${i}.${field}`];

const originRef = ref(null);
const destRef = ref(null);

// En passant en multi-destinations, on reprend le trajet deja saisi comme premier vol.
watch(
    () => form.trip_type,
    (type, previous) => {
        if (type !== 'multicity' || previous === 'multicity') return;
        const first = legs[0];
        if (form.origin && !first.destination) {
            first.origin = form.origin;
            first.originLabel = originRef.value?.text || airportLabel(form.origin);
        }
        if (form.destination && !first.destination) {
            first.destination = form.destination;
            first.destinationLabel = destRef.value?.text || airportLabel(form.destination);
            if (!legs[1].origin) {
                legs[1].origin = form.destination;
                legs[1].originLabel = first.destinationLabel;
            }
        }
        if (form.depart_date && !first.date) first.date = form.depart_date;
    },
);
const paxOpen = ref(false);
const loading = ref(false);
const localError = ref('');

const paxSummary = computed(() => {
    const parts = [`${form.adults} adulte${form.adults > 1 ? 's' : ''}`];
    if (form.children) parts.push(`${form.children} enfant${form.children > 1 ? 's' : ''}`);
    if (form.infants) parts.push(`${form.infants} bebe${form.infants > 1 ? 's' : ''}`);
    return parts.join(', ');
});

const step = (key, delta) => {
    const next = form[key] + delta;
    const min = key === 'adults' ? 1 : 0;
    if (next < min) return;
    if (key !== 'infants' && form.adults + form.children + delta > 9) return;
    if (key === 'infants' && next > form.adults) return;
    if (key === 'adults' && form.infants > next) form.infants = next;
    form[key] = next;
};

const swap = () => {
    const o = form.origin;
    form.origin = form.destination;
    form.destination = o;
    const ot = originRef.value?.text;
    originRef.value?.setText(destRef.value?.text ?? '');
    destRef.value?.setText(ot ?? '');
};

const send = (params) =>
    router.get('/voyage/vols/recherche', params, {
        onStart: () => (loading.value = true),
        onFinish: () => (loading.value = false),
    });

const submit = () => {
    localError.value = '';
    const pax = {
        adults: form.adults, children: form.children, infants: form.infants, cabin: form.cabin,
        ...(form.direct_only ? { direct_only: 1 } : {}),
    };

    if (form.trip_type === 'multicity') {
        for (let i = 0; i < legs.length; i++) {
            const l = legs[i];
            if (!l.origin || !l.destination) {
                localError.value = `Vol ${i + 1} : choisissez les aeroports de depart et d'arrivee dans la liste.`;
                return;
            }
            if (l.origin === l.destination) {
                localError.value = `Vol ${i + 1} : le depart et l'arrivee doivent etre differents.`;
                return;
            }
            if (!l.date) {
                localError.value = `Vol ${i + 1} : indiquez la date.`;
                return;
            }
            if (i > 0 && legs[i - 1].date && l.date < legs[i - 1].date) {
                localError.value = `Vol ${i + 1} : la date doit etre posterieure a celle du vol ${i}.`;
                return;
            }
        }
        send({
            trip_type: 'multicity',
            legs: legs.map((l) => ({ origin: l.origin, destination: l.destination, date: l.date })),
            ...pax,
        });
        return;
    }

    if (!form.origin || !form.destination) {
        localError.value = 'Choisissez les aeroports de depart et d\'arrivee dans la liste.';
        return;
    }
    if (form.trip_type === 'roundtrip' && !form.return_date) {
        localError.value = 'Indiquez la date de retour.';
        return;
    }
    const params = {
        trip_type: form.trip_type,
        origin: form.origin,
        destination: form.destination,
        depart_date: form.depart_date,
        ...pax,
    };
    if (form.trip_type === 'roundtrip') params.return_date = form.return_date;
    send(params);
};
</script>

<template>
    <form class="space-y-3" @submit.prevent="submit">
        <div class="flex flex-wrap items-center gap-4 text-sm">
            <label class="flex items-center gap-1.5 font-medium text-navy-700">
                <input v-model="form.trip_type" type="radio" value="roundtrip" class="text-navy-900 focus:ring-gold-600" /> Aller-retour
            </label>
            <label class="flex items-center gap-1.5 font-medium text-navy-700">
                <input v-model="form.trip_type" type="radio" value="oneway" class="text-navy-900 focus:ring-gold-600" /> Aller simple
            </label>
            <label class="flex items-center gap-1.5 font-medium text-navy-700">
                <input v-model="form.trip_type" type="radio" value="multicity" class="text-navy-900 focus:ring-gold-600" /> Multi-destinations
            </label>
            <select v-model="form.cabin" class="ml-auto rounded-full border-gray-200 py-1 text-sm focus:border-gold-600 focus:ring-gold-600">
                <option v-for="(label, key) in cabins" :key="key" :value="key">{{ label }}</option>
            </select>
        </div>

        <!-- ALLER-RETOUR / ALLER SIMPLE -->
        <template v-if="form.trip_type !== 'multicity'">
            <div class="grid gap-2 md:grid-cols-[1fr_auto_1fr]">
                <AirportInput ref="originRef" v-model="form.origin" label="De" :initial-label="labels.origin ?? 'Brazzaville (BZV)'" :suggestions="suggestions" :error="errors.origin" />
                <button type="button" class="mx-auto mt-5 hidden h-9 w-9 items-center justify-center rounded-full border text-navy-700 hover:bg-navy-50 md:flex" title="Inverser" @click="swap">⇄</button>
                <AirportInput ref="destRef" v-model="form.destination" label="Vers" placeholder="Paris, Dubai, Douala..." :initial-label="labels.destination ?? ''" :suggestions="suggestions" :error="errors.destination" />
            </div>
        </template>

        <!-- MULTI-DESTINATIONS -->
        <template v-else>
            <div v-for="(leg, i) in legs" :key="leg.key" class="grid gap-2 rounded-xl border border-gray-100 bg-navy-50/30 p-3 md:grid-cols-[auto_1fr_1fr_0.8fr_auto] md:items-start">
                <p class="text-xs font-bold uppercase tracking-wide text-navy-900 md:mt-8 md:w-12">Vol {{ i + 1 }}</p>
                <AirportInput
                    v-model="leg.origin"
                    label="De"
                    :initial-label="leg.originLabel"
                    :suggestions="suggestions"
                    :error="legError(i, 'origin')"
                    @picked="(a) => pickedLeg(leg, 'origin', a)"
                />
                <AirportInput
                    v-model="leg.destination"
                    label="Vers"
                    placeholder="Paris, Dubai, Douala..."
                    :initial-label="leg.destinationLabel"
                    :suggestions="suggestions"
                    :error="legError(i, 'destination')"
                    @picked="(a) => pickedLeg(leg, 'destination', a)"
                />
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-navy-500">Date</label>
                    <input v-model="leg.date" type="date" :min="legMin(i)" class="w-full rounded-xl border-gray-200 text-sm focus:border-gold-600 focus:ring-gold-600" />
                    <p v-if="legError(i, 'date')" class="mt-1 text-xs text-red-600">{{ legError(i, 'date') }}</p>
                </div>
                <button
                    type="button"
                    class="h-9 w-9 justify-self-end rounded-full text-lg leading-none text-navy-500 hover:bg-red-50 hover:text-red-600 disabled:invisible md:mt-6"
                    :disabled="legs.length <= 2"
                    title="Supprimer ce vol"
                    @click="removeLeg(i)"
                >
                    ✕
                </button>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <button
                    v-if="legs.length < MAX_LEGS"
                    type="button"
                    class="rounded-full border border-dashed border-navy-300 px-4 py-1.5 text-sm font-semibold text-navy-900 hover:bg-navy-50"
                    @click="addLeg"
                >
                    + Ajouter un vol
                </button>
                <p class="text-xs text-navy-500">{{ legs.length }} / {{ MAX_LEGS }} vols</p>
            </div>
            <p v-if="errors.legs" class="text-xs text-red-600">{{ errors.legs }}</p>
        </template>

        <div class="grid gap-2 sm:grid-cols-2" :class="form.trip_type === 'multicity' ? 'md:grid-cols-[1.3fr_auto]' : 'md:grid-cols-[1fr_1fr_1.3fr_auto]'">
            <template v-if="form.trip_type !== 'multicity'">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-navy-500">Depart</label>
                    <input v-model="form.depart_date" type="date" :min="today" required class="w-full rounded-xl border-gray-200 text-sm focus:border-gold-600 focus:ring-gold-600" />
                    <p v-if="errors.depart_date" class="mt-1 text-xs text-red-600">{{ errors.depart_date }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-navy-500">Retour</label>
                    <input
                        v-model="form.return_date"
                        type="date"
                        :min="form.depart_date || today"
                        :disabled="form.trip_type === 'oneway'"
                        class="w-full rounded-xl border-gray-200 text-sm focus:border-gold-600 focus:ring-gold-600 disabled:bg-gray-50 disabled:text-gray-400"
                    />
                    <p v-if="errors.return_date" class="mt-1 text-xs text-red-600">{{ errors.return_date }}</p>
                </div>
            </template>
            <div class="relative">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-navy-500">Passagers</label>
                <button type="button" class="w-full truncate rounded-xl border border-gray-200 px-3 py-2 text-left text-sm" @click="paxOpen = !paxOpen">{{ paxSummary }}</button>
                <div v-if="paxOpen" class="absolute z-30 mt-1 w-72 rounded-xl border bg-white p-4 shadow-lg">
                    <div v-for="row in [
                        { key: 'adults', label: 'Adultes', hint: '12 ans et +' },
                        { key: 'children', label: 'Enfants', hint: '2 a 11 ans' },
                        { key: 'infants', label: 'Bebes', hint: 'moins de 2 ans, sur les genoux' },
                    ]" :key="row.key" class="flex items-center justify-between py-2">
                        <div>
                            <p class="text-sm font-semibold text-navy-900">{{ row.label }}</p>
                            <p class="text-xs text-navy-500">{{ row.hint }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="button" class="h-8 w-8 rounded-full border text-lg leading-none" @click="step(row.key, -1)">−</button>
                            <span class="w-4 text-center font-semibold">{{ form[row.key] }}</span>
                            <button type="button" class="h-8 w-8 rounded-full border text-lg leading-none" @click="step(row.key, 1)">+</button>
                        </div>
                    </div>
                    <button type="button" class="btn-gold mt-2 w-full !py-2" @click="paxOpen = false">OK</button>
                </div>
                <p v-if="errors.adults || errors.infants" class="mt-1 text-xs text-red-600">{{ errors.adults || errors.infants }}</p>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn-gold w-full whitespace-nowrap sm:px-8" :disabled="loading">
                    {{ loading ? 'Recherche...' : 'Rechercher des vols' }}
                </button>
            </div>
        </div>
        <!-- Vols directs uniquement + lien optionnel a droite (slot "extra") -->
        <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-3">
        <label class="inline-flex cursor-pointer select-none items-center gap-3 text-sm font-semibold text-navy-900">
            <input v-model="form.direct_only" type="checkbox" class="peer sr-only" />
            <span class="relative inline-flex h-6 w-11 items-center rounded-full border-2 transition" :class="form.direct_only ? 'border-navy-900 bg-navy-900' : 'border-navy-300 bg-white'">
                <span class="inline-block h-4 w-4 rounded-full shadow transition" :class="form.direct_only ? 'translate-x-5 bg-gold-500' : 'translate-x-0.5 bg-navy-300'" />
            </span>
            Vols directs uniquement
        </label>
        <slot name="extra" />
        </div>

        <p v-if="localError" class="text-sm text-red-600">{{ localError }}</p>

        <!-- Recherche en cours : ecran d'attente avec cartes fantomes (comme Skyscanner) -->
        <Teleport to="body">
            <div v-if="loading" class="fixed inset-0 z-[100] overflow-hidden bg-navy-50/95 backdrop-blur-sm">
                <div class="h-1 w-full overflow-hidden bg-navy-100"><div class="h-full w-1/3 animate-[mk-progress_1.4s_ease-in-out_infinite] rounded-full bg-gold-500" /></div>
                <div class="mx-auto max-w-3xl px-4 py-8">
                    <p class="text-center text-lg font-bold text-navy-900">Recherche des meilleurs vols…</p>
                    <p class="mb-6 text-center text-sm text-navy-500">Nous comparons les compagnies et les tarifs disponibles.</p>
                    <div v-for="n in 3" :key="n" class="mb-4 animate-pulse rounded-2xl bg-white p-5 shadow-sm">
                        <div v-for="m in 2" :key="m" class="flex items-center gap-4 py-2">
                            <div class="h-10 w-10 rounded-lg bg-navy-100" />
                            <div class="flex-1 space-y-2"><div class="h-3 w-1/3 rounded bg-navy-100" /><div class="h-3 w-1/5 rounded bg-navy-50" /></div>
                            <div class="space-y-2"><div class="h-3 w-16 rounded bg-navy-100" /><div class="h-3 w-12 rounded bg-navy-50" /></div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </form>
</template>
