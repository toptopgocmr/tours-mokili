<script setup>
import { router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

/**
 * Recherche de vols : Aller-retour / Aller simple / Multi-destinations,
 * De / Vers avec inversion, dates, "Vols directs uniquement", voyageurs et classe.
 * Envoie la recherche au moteur de billetterie (GET /vols).
 */
const props = defineProps({
    airports: { type: Array, default: () => [] },
    initial: { type: Object, default: () => ({}) },
});

const MAX_LEGS = 6;

const label = (a) => `${a.city} (${a.iata})`;
const text = (code, fallback = '') => {
    if (!code) return fallback;
    const a = props.airports.find((x) => x.iata === code);
    return a ? label(a) : code;
};

const initialLegs = Array.isArray(props.initial.legs) && props.initial.legs.length >= 2 ? props.initial.legs : null;

const trip = ref(initialLegs ? 'multi' : props.initial.depart_date && !props.initial.return_date ? 'oneway' : 'roundtrip');

const form = reactive({
    from: text(props.initial.origin, 'Brazzaville (BZV)'),
    to: text(props.initial.destination),
    depart_date: props.initial.depart_date ?? '',
    return_date: props.initial.return_date ?? '',
    adults: Number(props.initial.adults ?? 1),
    children: Number(props.initial.children ?? 0),
    infants: Number(props.initial.infants ?? 0),
    cabin: props.initial.cabin ?? 'economy',
    direct_only: ['1', 1, true, 'true'].includes(props.initial.direct_only),
});

const legs = reactive(
    (initialLegs ?? [
        { from: 'Brazzaville (BZV)', to: '', date: '' },
        { from: '', to: 'Brazzaville (BZV)', date: '' },
    ]).map((l) => ({ from: text(l.origin ?? l.from), to: text(l.destination ?? l.to), date: l.date ?? '' })),
);

const paxOpen = ref(false);
const error = ref('');
const today = new Date().toISOString().slice(0, 10);

const cabins = { economy: 'Economie', premium: 'Premium', business: 'Affaires' };

const code = (value) => {
    const v = (value || '').trim();
    const m = v.match(/\(([A-Za-z]{3})\)\s*$/);
    if (m) return m[1].toUpperCase();
    if (/^[A-Za-z]{3}$/.test(v)) return v.toUpperCase();
    const hit = props.airports.find((a) => a.city.toLowerCase() === v.toLowerCase());
    return hit ? hit.iata : '';
};

const passengersLabel = computed(() => {
    const n = form.adults + form.children + form.infants;
    return `${n} voyageur${n > 1 ? 's' : ''}`;
});

const swap = () => {
    [form.from, form.to] = [form.to, form.from];
};

const addLeg = () => {
    if (legs.length >= MAX_LEGS) return;
    const last = legs[legs.length - 1];
    legs.push({ from: last.to, to: '', date: last.date });
};
const removeLeg = (i) => {
    if (legs.length > 2) legs.splice(i, 1);
};

const bump = (key, delta, min, max) => {
    form[key] = Math.min(max, Math.max(min, form[key] + delta));
    if (form.infants > form.adults) form.infants = form.adults;
};

const submit = () => {
    error.value = '';
    const common = { adults: form.adults, children: form.children, infants: form.infants, cabin: form.cabin, ...(form.direct_only ? { direct_only: 1 } : {}) };

    if (trip.value === 'multi') {
        const payload = [];
        for (let i = 0; i < legs.length; i++) {
            const o = code(legs[i].from);
            const d = code(legs[i].to);
            if (!o || !d) return (error.value = `Vol ${i + 1} : choisissez les aeroports dans la liste.`);
            if (o === d) return (error.value = `Vol ${i + 1} : le depart et l'arrivee doivent etre differents.`);
            if (!legs[i].date) return (error.value = `Vol ${i + 1} : choisissez la date.`);
            if (i > 0 && legs[i].date < legs[i - 1].date) return (error.value = `Vol ${i + 1} : la date doit suivre celle du vol ${i}.`);
            payload.push({ origin: o, destination: d, date: legs[i].date });
        }
        return router.get('/vols', { legs: payload, ...common });
    }

    const origin = code(form.from);
    const destination = code(form.to);
    if (!origin || !destination) return (error.value = 'Choisissez un aeroport de depart et d\'arrivee dans la liste.');
    if (origin === destination) return (error.value = 'Le depart et l\'arrivee doivent etre differents.');
    if (!form.depart_date) return (error.value = 'Choisissez la date de depart.');
    if (trip.value === 'roundtrip' && !form.return_date) return (error.value = 'Choisissez la date de retour ou passez en aller simple.');

    router.get('/vols', {
        origin,
        destination,
        depart_date: form.depart_date,
        ...(trip.value === 'roundtrip' ? { return_date: form.return_date } : {}),
        ...common,
    });
};
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <datalist id="fsf-airports">
            <option v-for="a in airports" :key="a.iata" :value="label(a)">{{ a.name }}</option>
        </datalist>

        <!-- Type de voyage -->
        <div class="flex flex-wrap gap-1 border-b border-gray-200">
            <button
                v-for="t in [['roundtrip', 'Aller-retour'], ['oneway', 'Aller simple'], ['multi', 'Multi-destinations']]"
                :key="t[0]"
                type="button"
                class="-mb-px border-b-2 px-4 py-2 text-sm font-semibold transition"
                :class="trip === t[0] ? 'border-navy-900 text-navy-900' : 'border-transparent text-navy-500 hover:text-navy-900'"
                @click="trip = t[0]"
            >
                {{ t[1] }}
            </button>
        </div>

        <!-- Aller-retour / aller simple -->
        <template v-if="trip !== 'multi'">
            <div class="grid items-end gap-3 md:grid-cols-[1fr_auto_1fr]">
                <div>
                    <label class="text-[11px] font-bold uppercase text-navy-500">De</label>
                    <input v-model="form.from" list="fsf-airports" type="text" placeholder="Brazzaville (BZV)" class="mt-1 w-full rounded-xl border-gray-200 text-sm" />
                </div>
                <button type="button" class="mb-1 flex h-10 w-10 items-center justify-center justify-self-center rounded-full bg-gold-500 text-navy-900 hover:bg-gold-600" title="Inverser" @click="swap">&#8645;</button>
                <div>
                    <label class="text-[11px] font-bold uppercase text-navy-500">Vers</label>
                    <input v-model="form.to" list="fsf-airports" type="text" placeholder="Paris, Dubai, Douala..." class="mt-1 w-full rounded-xl border-gray-200 text-sm" />
                </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="text-[11px] font-bold uppercase text-navy-500">Depart</label>
                    <input v-model="form.depart_date" type="date" :min="today" class="mt-1 w-full rounded-xl border-gray-200 text-sm" />
                </div>
                <div>
                    <label class="text-[11px] font-bold uppercase text-navy-500">Retour</label>
                    <input v-model="form.return_date" type="date" :min="form.depart_date || today" :disabled="trip === 'oneway'" class="mt-1 w-full rounded-xl border-gray-200 text-sm disabled:bg-gray-50" />
                </div>
            </div>
        </template>

        <!-- Multi-destinations -->
        <template v-else>
            <div v-for="(leg, i) in legs" :key="i" class="rounded-xl border border-gray-200 p-3">
                <div class="mb-2 flex items-center justify-between">
                    <p class="text-sm font-bold text-navy-900">Vol {{ i + 1 }}</p>
                    <button v-if="legs.length > 2" type="button" class="text-xs font-semibold text-red-600 hover:underline" @click="removeLeg(i)">Supprimer</button>
                </div>
                <div class="grid gap-3 md:grid-cols-3">
                    <input v-model="leg.from" list="fsf-airports" type="text" placeholder="De (ex. Brazzaville)" class="rounded-xl border-gray-200 text-sm" />
                    <input v-model="leg.to" list="fsf-airports" type="text" placeholder="Vers (ex. Paris)" class="rounded-xl border-gray-200 text-sm" />
                    <input v-model="leg.date" type="date" :min="i > 0 && legs[i - 1].date ? legs[i - 1].date : today" class="rounded-xl border-gray-200 text-sm" />
                </div>
            </div>
            <button v-if="legs.length < 6" type="button" class="w-full rounded-xl bg-gray-100 py-2.5 text-sm font-bold text-navy-900 hover:bg-gray-200" @click="addLeg">Ajouter un vol</button>
        </template>

        <!-- Voyageurs, classe, directs -->
        <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
            <div class="relative">
                <button type="button" class="text-sm font-bold text-navy-900 hover:underline" @click="paxOpen = !paxOpen">{{ passengersLabel }} &#9662;</button>
                <div v-if="paxOpen" class="absolute z-20 mt-2 w-64 space-y-2 rounded-xl border bg-white p-4 shadow-lg">
                    <div v-for="row in [['adults', 'Adultes', 1, 9], ['children', 'Enfants (2-11 ans)', 0, 8], ['infants', 'Bebes (< 2 ans)', 0, 4]]" :key="row[0]" class="flex items-center justify-between text-sm">
                        <span>{{ row[1] }}</span>
                        <span class="flex items-center gap-3">
                            <button type="button" class="h-7 w-7 rounded-full border" @click="bump(row[0], -1, row[2], row[3])">-</button>
                            <span class="w-4 text-center font-semibold">{{ form[row[0]] }}</span>
                            <button type="button" class="h-7 w-7 rounded-full border" @click="bump(row[0], 1, row[2], row[3])">+</button>
                        </span>
                    </div>
                    <button type="button" class="btn-gold w-full !py-1.5 text-sm" @click="paxOpen = false">Valider</button>
                </div>
            </div>

            <select v-model="form.cabin" class="rounded-full border-gray-200 py-1.5 pr-9 text-sm font-bold text-navy-900">
                <option v-for="(l, k) in cabins" :key="k" :value="k">{{ l }}</option>
            </select>

            <label class="flex cursor-pointer items-center gap-2 text-sm font-bold text-navy-900">
                <span class="relative inline-flex h-6 w-11 items-center rounded-full transition" :class="form.direct_only ? 'bg-gold-500' : 'bg-gray-300'">
                    <input v-model="form.direct_only" type="checkbox" class="peer sr-only" />
                    <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition" :class="form.direct_only ? 'translate-x-5' : 'translate-x-0.5'" />
                </span>
                Vols directs uniquement
            </label>
        </div>

        <button type="submit" class="btn-gold w-full py-3 text-base sm:w-auto sm:px-14">Recherche</button>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </form>
</template>
