<script setup>
// Recherche bus / train / canot / taxi facon SNCF Connect : onglets dans une
// pastille, barre arrondie Depart <-> Arrivee | Aller (date + heure) |
// + Ajouter le retour | Voyageurs | Rechercher, puis liens rapides.
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const mode = defineModel('mode', { type: String, default: '' });
const scope = defineModel('scope', { type: String, default: '' });
const from = defineModel('from', { type: String, default: '' });
const to = defineModel('to', { type: String, default: '' });
const date = defineModel('date', { type: String, default: '' });
const time = defineModel('time', { type: String, default: '' });
const returnDate = defineModel('returnDate', { type: String, default: '' });
const passengers = defineModel('passengers', { type: Number, default: 1 });

const emit = defineEmits(['search']);

const TABS = [
    { mode: '', scope: '', label: 'Tout rechercher', icon: 'search' },
    { mode: 'train', scope: '', label: 'Trains', icon: 'train' },
    { mode: 'bus', scope: '', label: 'Bus', icon: 'bus' },
    { mode: 'canot', scope: '', label: 'Canot rapide', icon: 'boat' },
    { mode: 'taxi', scope: '', label: 'Taxi et covoiturage', icon: 'taxi' },
    { mode: '', scope: 'urbain', label: 'Itineraires urbains', icon: 'pin' },
];
const isActive = (t) => mode.value === t.mode && scope.value === t.scope;
const pickTab = (t) => {
    mode.value = t.mode;
    scope.value = t.scope;
    emit('search');
};

// Liens rapides sous la barre, selon l'onglet (comme SNCF Connect).
const QUICK = {
    train: [['Ligne Congo-Ocean', null, 'Brazzaville|Pointe-Noire'], ['Fret & colis', '/fret'], ['Hotels', '/logement']],
    bus: [['Location de voiture', '/voiture'], ['Hotels', '/logement'], ['Fret & colis', '/fret']],
    canot: [['Brazzaville → Kinshasa', null, 'Brazzaville|Kinshasa'], ['Kinshasa → Brazzaville', null, 'Kinshasa|Brazzaville'], ['Hotels', '/logement']],
    taxi: [['Location de voiture', '/voiture'], ['Programme des vols', '/voyage/vols/horaires']],
    '': [['Location de voiture', '/voiture'], ['Hotels', '/logement'], ['Programme des vols', '/voyage/vols/horaires']],
};
// Le programme des departs est propose sur tous les onglets.
const quick = computed(() => [['Programme des departs', `/voyage/trajets/horaires${mode.value ? `?mode=${mode.value}` : ''}`], ...(QUICK[scope.value ? '' : mode.value] ?? QUICK[''])]);
const pickRoute = (r) => {
    [from.value, to.value] = r.split('|');
    emit('search');
};

const today = new Date().toISOString().slice(0, 10);
const showReturn = ref(!!returnDate.value);
const addReturn = () => { showReturn.value = true; };
const removeReturn = () => { showReturn.value = false; returnDate.value = ''; };
const swap = () => { [from.value, to.value] = [to.value, from.value]; };

const dateLabel = (iso) => (iso ? new Date(`${iso}T12:00:00`).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' }) : 'Choisir');
const departLabel = computed(() => {
    if (!date.value) return time.value ? `Aujourd'hui, ${Number(time.value)}h` : 'Toutes les dates';
    return `${dateLabel(date.value)}${time.value !== '' ? `, ${Number(time.value)}h` : ''}`;
});
const HOURS = Array.from({ length: 24 }, (_, h) => h);
const openPax = ref(false);

const submit = () => {
    if (returnDate.value && date.value && returnDate.value < date.value) returnDate.value = date.value;
    emit('search');
};
</script>

<template>
    <div>
        <!-- Onglets -->
        <div class="mx-auto flex w-fit max-w-full gap-1 overflow-x-auto rounded-2xl border border-white/40 bg-white/90 p-1.5 shadow-sm backdrop-blur">
            <button
                v-for="t in TABS"
                :key="t.label"
                type="button"
                class="flex shrink-0 items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition"
                :class="isActive(t) ? 'bg-white text-sky-700 shadow' : 'text-navy-800 hover:bg-white/70'"
                @click="pickTab(t)"
            >
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <template v-if="t.icon === 'search'"><circle cx="11" cy="11" r="6.5" /><path d="m20 20-4.2-4.2" /></template>
                    <template v-else-if="t.icon === 'train'"><rect x="5" y="3" width="14" height="14" rx="3" /><path d="M5 11h14M9 17l-2 4M15 17l2 4" /><circle cx="9" cy="14" r=".8" /><circle cx="15" cy="14" r=".8" /></template>
                    <template v-else-if="t.icon === 'bus'"><rect x="3" y="5" width="18" height="11" rx="2" /><path d="M3 11h18M7 16v2M17 16v2M8 5v6M14 5v6" /></template>
                    <template v-else-if="t.icon === 'boat'"><path d="M3 15h18l-2.5 4h-13zM6 15V9h9l3 6M10 9V5" /></template>
                    <template v-else-if="t.icon === 'taxi'"><path d="M5 16V12l2-5h10l2 5v4M3 12h18M9 7V5h6v2" /><circle cx="7.5" cy="16.5" r="1.5" /><circle cx="16.5" cy="16.5" r="1.5" /></template>
                    <template v-else><path d="M12 21s-6-5.6-6-11a6 6 0 1 1 12 0c0 5.4-6 11-6 11z" /><circle cx="12" cy="10" r="2.2" /></template>
                </svg>
                {{ t.label }}
            </button>
        </div>

        <!-- Barre de recherche facon BlaBlaCar : De <-> Vers | Depart | Retour | Passagers | Rechercher -->
        <form class="mt-5 overflow-visible rounded-2xl border-2 border-[#2f6fe4] bg-white shadow-[0_4px_20px_rgba(5,15,33,0.15)] lg:flex" @submit.prevent="submit">
            <div class="relative flex items-stretch border-b border-navy-100 lg:w-[44%] lg:border-b-0">
                <label class="flex min-w-0 flex-1 cursor-text flex-col justify-center px-4 py-3">
                    <span class="text-xs font-semibold text-navy-500">De</span>
                    <input v-model="from" type="text" placeholder="Ville, gare, lieu" class="w-full border-0 p-0 text-base font-semibold text-navy-900 placeholder:font-normal placeholder:text-navy-400 focus:ring-0" />
                </label>
                <button type="button" class="flex w-10 shrink-0 items-center justify-center text-[#2f6fe4] hover:text-navy-900" title="Inverser" @click="swap">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 7h12l-3-3M17 17H5l3 3" /></svg>
                </button>
                <div class="my-3 w-px bg-navy-200" />
                <label class="flex min-w-0 flex-1 cursor-text flex-col justify-center px-4 py-3">
                    <span class="text-xs font-semibold text-navy-500">Vers</span>
                    <input v-model="to" type="text" placeholder="Ville, gare, lieu" class="w-full border-0 p-0 text-base font-semibold text-navy-900 placeholder:font-normal placeholder:text-navy-400 focus:ring-0" />
                </label>
            </div>
            <div class="grid grid-cols-2 border-b border-navy-100 lg:flex lg:flex-1 lg:border-b-0">
                <div class="my-3 hidden w-px bg-navy-200 lg:block" />
                <label class="flex min-w-0 flex-col justify-center px-4 py-3 lg:flex-1">
                    <span class="text-xs font-semibold text-navy-500">Depart</span>
                    <input v-model="date" type="date" :min="today" class="w-full border-0 p-0 text-base font-semibold text-navy-900 focus:ring-0" />
                    <select v-model="time" class="mt-0.5 w-full border-0 p-0 text-xs text-navy-500 focus:ring-0" aria-label="Partir a partir de">
                        <option value="">Toute la journee</option>
                        <option v-for="h in HOURS" :key="h" :value="String(h)">a partir de {{ h }}h</option>
                    </select>
                </label>
                <div class="my-3 hidden w-px bg-navy-200 lg:block" />
                <label class="flex min-w-0 flex-col justify-center border-l border-navy-100 px-4 py-3 lg:flex-1 lg:border-l-0">
                    <span class="flex items-center justify-between text-xs font-semibold text-navy-500">Retour
                        <button v-if="returnDate" type="button" class="text-navy-400 hover:text-rose-600" aria-label="Retirer le retour" @click="removeReturn">✕</button>
                    </span>
                    <input v-model="returnDate" type="date" :min="date || today" class="w-full border-0 p-0 text-base font-semibold text-navy-900 focus:ring-0" :class="!returnDate && 'text-navy-400'" />
                </label>
                <div class="my-3 hidden w-px bg-navy-200 lg:block" />
                <div class="relative col-span-2 flex flex-col justify-center border-t border-navy-100 px-4 py-3 lg:flex-1 lg:border-t-0">
                    <button type="button" class="text-left" @click="openPax = !openPax">
                        <span class="block text-xs font-semibold text-navy-500">Passagers</span>
                        <span class="block whitespace-nowrap text-base font-semibold text-navy-900">{{ passengers }} {{ passengers > 1 ? 'adultes' : 'adulte' }}</span>
                    </button>
                    <div v-if="openPax" class="absolute right-0 top-full z-30 mt-2 w-60 rounded-2xl border border-navy-100 bg-white p-4 shadow-xl">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-navy-900">Passagers</span>
                            <div class="flex items-center gap-3">
                                <button type="button" class="h-8 w-8 rounded-full border border-[#2f6fe4] text-lg text-[#2f6fe4] disabled:opacity-30" :disabled="passengers <= 1" @click="passengers--">−</button>
                                <span class="w-4 text-center font-bold">{{ passengers }}</span>
                                <button type="button" class="h-8 w-8 rounded-full border border-[#2f6fe4] text-lg text-[#2f6fe4] disabled:opacity-30" :disabled="passengers >= 8" @click="passengers++">+</button>
                            </div>
                        </div>
                        <button type="button" class="mt-3 w-full rounded-full bg-[#2f6fe4] py-2 text-sm font-semibold text-white" @click="openPax = false">OK</button>
                    </div>
                </div>
            </div>
            <button type="submit" class="w-full bg-[#2f6fe4] px-10 py-4 text-lg font-bold text-white transition hover:bg-[#1f5ad0] lg:w-auto lg:rounded-r-xl">Rechercher</button>
        </form>

        <!-- Liens rapides -->
        <div class="mt-4 flex flex-wrap justify-center gap-x-8 gap-y-2 text-sm">
            <template v-for="q in quick" :key="q[0]">
                <Link v-if="q[1]" :href="q[1]" class="inline-flex items-center gap-2 text-white drop-shadow hover:underline">
                    {{ q[0] }}
                    <span class="flex h-4 w-4 items-center justify-center rounded-full bg-gold-500 text-[10px] text-navy-900">›</span>
                </Link>
                <button v-else type="button" class="inline-flex items-center gap-2 text-white drop-shadow hover:underline" @click="pickRoute(q[2])">
                    {{ q[0] }}
                    <span class="flex h-4 w-4 items-center justify-center rounded-full bg-gold-500 text-[10px] text-navy-900">›</span>
                </button>
            </template>
        </div>
    </div>
</template>
