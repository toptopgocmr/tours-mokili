<script setup>
// Formulaire de recherche de vols (aller simple / aller-retour, passagers,
// classe). Soumis en GET -> le serveur interroge les fournisseurs puis
// redirige vers la page de resultats.
import AirportInput from '@/Components/AirportInput.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    initial: { type: Object, default: () => ({}) },
    labels: { type: Object, default: () => ({}) },
    cabins: { type: Object, default: () => ({ economy: 'Economique', business: 'Affaires' }) },
    suggestions: { type: Array, default: () => [] },
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
});

const originRef = ref(null);
const destRef = ref(null);
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

const submit = () => {
    localError.value = '';
    if (!form.origin || !form.destination) {
        localError.value = 'Choisissez les aeroports de depart et d\'arrivee dans la liste.';
        return;
    }
    if (form.trip_type === 'roundtrip' && !form.return_date) {
        localError.value = 'Indiquez la date de retour.';
        return;
    }
    const params = { ...form };
    if (form.trip_type === 'oneway') delete params.return_date;
    router.get('/voyage/vols/recherche', params, {
        onStart: () => (loading.value = true),
        onFinish: () => (loading.value = false),
    });
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
            <select v-model="form.cabin" class="ml-auto rounded-full border-gray-200 py-1 text-sm focus:border-gold-600 focus:ring-gold-600">
                <option v-for="(label, key) in cabins" :key="key" :value="key">{{ label }}</option>
            </select>
        </div>

        <div class="grid gap-2 md:grid-cols-[1fr_auto_1fr]">
            <AirportInput ref="originRef" v-model="form.origin" label="De" :initial-label="labels.origin ?? 'Brazzaville (BZV)'" :suggestions="suggestions" :error="errors.origin" />
            <button type="button" class="mx-auto mt-5 hidden h-9 w-9 items-center justify-center rounded-full border text-navy-700 hover:bg-navy-50 md:flex" title="Inverser" @click="swap">⇄</button>
            <AirportInput ref="destRef" v-model="form.destination" label="Vers" placeholder="Paris, Dubai, Douala..." :initial-label="labels.destination ?? ''" :suggestions="suggestions" :error="errors.destination" />
        </div>

        <div class="grid gap-2 sm:grid-cols-2 md:grid-cols-[1fr_1fr_1.3fr_auto]">
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
        <p v-if="localError" class="text-sm text-red-600">{{ localError }}</p>
    </form>
</template>
