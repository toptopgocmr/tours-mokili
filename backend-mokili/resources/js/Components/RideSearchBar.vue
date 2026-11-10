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

        <!-- Barre de recherche -->
        <form
            class="mt-5 grid items-stretch gap-0 rounded-3xl border border-navy-100 bg-white shadow-[0_2px_10px_rgba(5,15,33,0.08)] lg:flex lg:rounded-full"
            @submit.prevent="submit"
        >
            <div class="relative flex flex-1 items-stretch border-b border-navy-100 lg:border-b-0">
                <label class="flex min-w-0 flex-1 cursor-text flex-col justify-center px-5 py-3 lg:px-6 lg:py-4">
                    <span class="text-sm text-navy-900">Depart</span>
                    <input v-model="from" type="text" placeholder="Gare, ville, lieu..." class="w-full border-0 p-0 text-base text-navy-900 placeholder:text-navy-400 focus:ring-0" />
                </label>
                <button
                    type="button"
                    class="absolute left-1/2 top-1/2 z-10 flex h-10 w-10 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-navy-50 text-navy-700 hover:bg-navy-100"
                    title="Inverser depart et arrivee"
                    @click="swap"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 7h12l-3-3M17 17H5l3 3" /></svg>
                </button>
                <label class="flex min-w-0 flex-1 cursor-text flex-col justify-center py-3 pl-8 pr-4 lg:py-4 lg:pl-9 lg:pr-6">
                    <span class="text-sm text-navy-900">Arrivee</span>
                    <input v-model="to" type="text" placeholder="Gare, ville, lieu..." class="w-full border-0 p-0 text-base text-navy-900 placeholder:text-navy-400 focus:ring-0" />
                </label>
            </div>

            <div class="flex min-w-0 flex-wrap items-stretch border-b border-navy-100 lg:flex-nowrap lg:border-b-0">
                <div class="my-3 hidden w-px bg-navy-200 lg:block" />
                <label class="relative flex min-w-0 cursor-pointer flex-col justify-center px-6 py-3 lg:py-4">
                    <span class="text-sm text-navy-900">Aller</span>
                    <span class="whitespace-nowrap text-base text-navy-900">{{ departLabel }}</span>
                    <div class="mt-1 flex flex-wrap gap-2">
                        <input v-model="date" type="date" :min="today" class="rounded-md border-navy-200 py-1 text-xs text-navy-900 focus:border-sky-500 focus:ring-sky-500" aria-label="Date de depart" />
                        <select v-model="time" class="rounded-md border-navy-200 py-1 pr-7 text-xs text-navy-900 focus:border-sky-500 focus:ring-sky-500" aria-label="Partir a partir de">
                            <option value="">Toute heure</option>
                            <option v-for="h in HOURS" :key="h" :value="String(h)">des {{ h }}h</option>
                        </select>
                    </div>
                </label>
                <div v-if="!showReturn" class="flex items-center gap-2 px-6 pb-3 lg:px-0 lg:pb-0 lg:pr-6">
                    <button type="button" class="text-left text-sm font-medium leading-tight text-sky-700 hover:underline" @click="addReturn">Ajouter<br class="hidden lg:inline" /> le retour</button>
                    <button type="button" class="flex h-6 w-6 items-center justify-center rounded-full bg-sky-700 text-lg leading-none text-white" aria-label="Ajouter le retour" @click="addReturn">+</button>
                </div>
                <label v-else class="flex flex-col justify-center px-6 py-3 lg:px-0 lg:py-4 lg:pr-6">
                    <span class="flex items-center gap-2 text-sm text-navy-900">Retour
                        <button type="button" class="text-xs text-navy-400 hover:text-rose-600" aria-label="Retirer le retour" @click="removeReturn">✕</button>
                    </span>
                    <span class="whitespace-nowrap text-base text-navy-900">{{ dateLabel(returnDate) }}</span>
                    <input v-model="returnDate" type="date" :min="date || today" class="mt-1 rounded-md border-navy-200 py-1 text-xs text-navy-900 focus:border-sky-500 focus:ring-sky-500" aria-label="Date de retour" />
                </label>
            </div>

            <div class="flex min-w-0 items-center justify-between gap-3 px-3 py-3 lg:py-2 lg:pl-0">
                <div class="my-1 hidden w-px self-stretch bg-navy-200 lg:block" />
                <div class="relative">
                    <button type="button" class="flex flex-col px-3 text-left" @click="openPax = !openPax">
                        <span class="text-sm text-navy-900">Voyageurs</span>
                        <span class="whitespace-nowrap text-base text-navy-900">{{ passengers }} voyageur{{ passengers > 1 ? 's' : '' }}</span>
                    </button>
                    <div v-if="openPax" class="absolute right-0 top-full z-20 mt-3 w-60 rounded-2xl border border-navy-100 bg-white p-4 shadow-xl">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-navy-900">Voyageurs</span>
                            <div class="flex items-center gap-3">
                                <button type="button" class="h-8 w-8 rounded-full border border-navy-200 text-lg disabled:opacity-30" :disabled="passengers <= 1" @click="passengers--">−</button>
                                <span class="w-4 text-center font-bold">{{ passengers }}</span>
                                <button type="button" class="h-8 w-8 rounded-full border border-navy-200 text-lg disabled:opacity-30" :disabled="passengers >= 8" @click="passengers++">+</button>
                            </div>
                        </div>
                        <button type="button" class="mt-3 w-full rounded-full bg-navy-900 py-2 text-sm font-semibold text-white" @click="openPax = false">OK</button>
                    </div>
                </div>
                <button type="submit" class="shrink-0 rounded-full border-2 border-sky-700 px-5 py-2.5 text-base font-semibold text-sky-700 transition hover:bg-sky-700 hover:text-white">
                    Rechercher
                </button>
            </div>
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
