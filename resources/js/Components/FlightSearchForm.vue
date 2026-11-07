<script setup>
import { router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

/**
 * Formulaire de recherche de vols : aller-retour / aller simple, classe,
 * De / Vers (suggestions d'aeroports + inversion), dates, passagers.
 * Envoie la recherche au moteur de billetterie (GET /vols).
 */
const props = defineProps({
    airports: { type: Array, default: () => [] },
    initial: { type: Object, default: () => ({}) },
});

const label = (a) => `${a.city} (${a.iata})`;
const byIata = (code) => props.airports.find((a) => a.iata === code);

const text = (code, fallback = '') => {
    if (!code) return fallback;
    const a = byIata(code);
    return a ? label(a) : code;
};

const trip = ref(props.initial.return_date ? 'roundtrip' : 'oneway');
if (!props.initial.depart_date) trip.value = 'roundtrip';

const form = reactive({
    from: text(props.initial.origin, 'Brazzaville (BZV)'),
    to: text(props.initial.destination),
    depart_date: props.initial.depart_date ?? '',
    return_date: props.initial.return_date ?? '',
    adults: Number(props.initial.adults ?? 1),
    children: Number(props.initial.children ?? 0),
    infants: Number(props.initial.infants ?? 0),
    cabin: props.initial.cabin ?? 'economy',
});

const paxOpen = ref(false);
const error = ref('');
const today = new Date().toISOString().slice(0, 10);

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
    return `${n} passager${n > 1 ? 's' : ''}`;
});

const swap = () => {
    [form.from, form.to] = [form.to, form.from];
};

const submit = () => {
    const origin = code(form.from);
    const destination = code(form.to);
    error.value = '';
    if (!origin || !destination) return (error.value = 'Choisissez un aeroport de depart et d\'arrivee dans la liste.');
    if (origin === destination) return (error.value = 'Le depart et l\'arrivee doivent etre differents.');
    if (!form.depart_date) return (error.value = 'Choisissez la date de depart.');
    if (trip.value === 'roundtrip' && !form.return_date) return (error.value = 'Choisissez la date de retour ou passez en aller simple.');

    router.get('/vols', {
        origin,
        destination,
        depart_date: form.depart_date,
        ...(trip.value === 'roundtrip' ? { return_date: form.return_date } : {}),
        adults: form.adults,
        children: form.children,
        infants: form.infants,
        cabin: form.cabin,
    });
};

const bump = (key, delta, min, max) => {
    form[key] = Math.min(max, Math.max(min, form[key] + delta));
    if (form.infants > form.adults) form.infants = form.adults;
};
</script>

<template>
    <form class="space-y-3" @submit.prevent="submit">
        <datalist id="fsf-airports">
            <option v-for="a in airports" :key="a.iata" :value="label(a)">{{ a.name }}</option>
        </datalist>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex gap-5 text-sm font-semibold text-navy-900">
                <label class="flex cursor-pointer items-center gap-2"><input v-model="trip" type="radio" value="roundtrip" class="text-navy-900" /> Aller-retour</label>
                <label class="flex cursor-pointer items-center gap-2"><input v-model="trip" type="radio" value="oneway" class="text-navy-900" /> Aller simple</label>
            </div>
            <select v-model="form.cabin" class="rounded-full border-gray-200 py-1.5 pr-9 text-sm">
                <option value="economy">Economique</option>
                <option value="premium">Premium</option>
                <option value="business">Affaires</option>
            </select>
        </div>

        <div class="grid items-end gap-3 md:grid-cols-[1fr_auto_1fr]">
            <div>
                <label class="text-[11px] font-bold uppercase text-navy-500">De</label>
                <input v-model="form.from" list="fsf-airports" type="text" placeholder="Brazzaville (BZV)" class="mt-1 w-full rounded-xl border-gray-200 text-sm" />
            </div>
            <button type="button" class="mb-1 hidden h-10 w-10 items-center justify-center rounded-full border text-navy-700 hover:bg-navy-50 md:flex" title="Inverser" @click="swap">&#8646;</button>
            <div>
                <label class="text-[11px] font-bold uppercase text-navy-500">Vers</label>
                <input v-model="form.to" list="fsf-airports" type="text" placeholder="Paris, Dubai, Douala..." class="mt-1 w-full rounded-xl border-gray-200 text-sm" />
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_auto]">
            <div>
                <label class="text-[11px] font-bold uppercase text-navy-500">Depart</label>
                <input v-model="form.depart_date" type="date" :min="today" class="mt-1 w-full rounded-xl border-gray-200 text-sm" />
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-navy-500">Retour</label>
                <input v-model="form.return_date" type="date" :min="form.depart_date || today" :disabled="trip === 'oneway'" class="mt-1 w-full rounded-xl border-gray-200 text-sm disabled:bg-gray-50" />
            </div>
            <div class="relative">
                <label class="text-[11px] font-bold uppercase text-navy-500">Passagers</label>
                <button type="button" class="mt-1 w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-left text-sm" @click="paxOpen = !paxOpen">{{ passengersLabel }}</button>
                <div v-if="paxOpen" class="absolute z-20 mt-1 w-64 space-y-2 rounded-xl border bg-white p-4 shadow-lg">
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
            <button type="submit" class="btn-gold self-end px-8">Rechercher des vols</button>
        </div>

        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </form>
</template>
