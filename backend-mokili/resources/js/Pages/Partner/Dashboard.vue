<script setup>
// Tableau de bord partenaire : transporteurs (bus, train, canots, taxis),
// compagnies aeriennes, agences de billets, garages, hotels... Raccourcis
// adaptes a l'activite, chiffres du mois, prochains departs.
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    BuildingOfficeIcon, TicketIcon, ShoppingBagIcon, CubeIcon, MapIcon, PaperAirplaneIcon,
    ReceiptPercentIcon, WrenchScrewdriverIcon, PlusIcon, TruckIcon, SparklesIcon,
} from '@heroicons/vue/24/outline';

defineOptions({ layout: PartnerLayout });

const props = defineProps({
    company: { type: Object, default: () => ({}) },
    counts: { type: Object, required: true },
    month: { type: Object, default: () => ({}) },
    nextDepartures: { type: Array, default: () => [] },
});

const act = computed(() => props.company.activity);

// Actions rapides selon l'activite.
const QUICK = {
    transport: [
        ['/partner/transport/creer', 'Ajouter un trajet', 'Horaires, places, prix, trajet regulier'],
        ['/partner/promotions', 'Lancer une promotion', 'Remise sur plusieurs departs'],
        ['/partner/reservations', 'Voir les reservations', 'Passagers et paiements'],
    ],
    vols: [
        ['/partner/transport/creer?type=vol', 'Publier une offre vol', 'Destination, prix, dates'],
        ['/partner/transport/creer', 'Ajouter un trajet', 'Bus, train, canot... (agences)'],
        ['/partner/promotions', 'Lancer une promotion', 'Remise et date de fin'],
    ],
    hotel: [
        ['/partner/logement/creer?type=hotel', 'Ajouter une chambre / un hotel', 'Prix par nuit, capacite, equipements'],
        ['/partner/promotions', 'Lancer une promotion', 'Week-end, basse saison...'],
        ['/partner/reservations', 'Voir les reservations', 'Arrivees, nuitees, paiements'],
    ],
    appartement: [
        ['/partner/logement/creer?type=appartement', 'Ajouter un appartement', 'Prix par nuit, chambres, voyageurs'],
        ['/partner/logement/creer?type=villa', 'Ajouter une villa', 'Maison meublee, piscine...'],
        ['/partner/reservations', 'Voir les reservations', 'Sejours et paiements'],
    ],
    location: [
        ['/partner/voiture/creer?type=location', 'Ajouter une voiture a louer', 'Prix par jour, places, boite'],
        ['/partner/promotions', 'Lancer une promotion', 'Remise sur vos locations'],
        ['/partner/reservations', 'Voir les reservations', 'Locations et paiements'],
    ],
    vente: [
        ['/partner/voiture/creer?type=vente', 'Mettre une voiture en vente', 'Prix, annee, kilometrage'],
        ['/partner/promotions', 'Lancer une promotion', 'Prix barre sur vos vehicules'],
        ['/partner/entreprise', 'Mon entreprise', 'Nom, telephone de contact'],
    ],
    garage: [
        ['/partner/voiture/creer?type=mecanique', 'Ajouter une prestation', 'Vidange, diagnostic, carrosserie...'],
        ['/partner/promotions', 'Lancer une promotion', 'Remise sur vos prestations'],
        ['/partner/reservations', 'Voir les reservations', 'Rendez-vous et paiements'],
    ],
    evenements: [
        ['/partner/divertissement/creer?categorie=concert', 'Publier un concert / spectacle', 'Date, salle, prix du billet, places'],
        ['/partner/promotions', 'Lancer une promotion', 'Prevente, tarif reduit...'],
        ['/partner/reservations', 'Voir les billets vendus', 'Billets et paiements'],
    ],
    cinema: [
        ['/partner/divertissement/creer?categorie=cinema', 'Ajouter une seance', 'Film, date et heure, prix, places'],
        ['/partner/promotions', 'Lancer une promotion', 'Mardi a prix reduit...'],
        ['/partner/reservations', 'Voir les billets vendus', 'Billets et paiements'],
    ],
    parcs: [
        ['/partner/parcs/creer', 'Ajouter mon parc', 'Tarifs adulte / enfant, horaires, activites'],
        ['/partner/promotions', 'Lancer une promotion', 'Vacances scolaires, week-end...'],
        ['/partner/reservations', 'Voir les entrees vendues', 'Billets et paiements'],
    ],
};
const ACTIVITY_QUICK = {
    transport_routier: 'transport', transport_ferroviaire: 'transport', canot_rapide: 'transport', taxi_vtc: 'transport',
    compagnie_aerienne: 'vols', agence_billets: 'vols', hotel: 'hotel', appartement: 'appartement',
    location_voiture: 'location', vente_voiture: 'vente', garage: 'garage',
    organisateur_evenements: 'evenements', cinema: 'cinema', parc_loisirs: 'parcs',
};
const quick = computed(() => (QUICK[ACTIVITY_QUICK[act.value]] ?? [
    ['/partner/entreprise', 'Completer mon entreprise', 'Choisir votre activite'],
    ['/partner/transport/creer', 'Ajouter un trajet', 'Bus, train, canot, taxi'],
    ['/partner/logement/creer', 'Ajouter un logement', 'Hotel, appartement, villa'],
]).map(([href, label, hint]) => ({ href, label, hint })));

const modules = [
    { key: 'transport', label: 'Trajets a venir', href: '/partner/transport', icon: MapIcon, color: 'text-sky-700', bg: 'bg-sky-50', for: ['transport'] },
    { key: 'vols', label: 'Offres vols', href: '/partner/transport?vue=vols', icon: PaperAirplaneIcon, color: 'text-indigo-600', bg: 'bg-indigo-50', for: ['vols'] },
    { key: 'logement', label: 'Hotels & logements', href: '/partner/logement', icon: BuildingOfficeIcon, color: 'text-green-600', bg: 'bg-green-50', for: ['hotel', 'appartement'] },
    { key: 'location', label: 'Voitures (location / vente)', href: '/partner/voiture', icon: TruckIcon, color: 'text-orange-600', bg: 'bg-orange-50', for: ['location', 'vente'] },
    { key: 'garage', label: 'Prestations garage', href: '/partner/voiture', icon: WrenchScrewdriverIcon, color: 'text-amber-700', bg: 'bg-amber-50', for: ['garage'] },
    { key: 'divertissement', label: 'Concerts, cinema...', href: '/partner/divertissement', icon: TicketIcon, color: 'text-purple-600', bg: 'bg-purple-50', for: ['evenements', 'cinema'] },
    { key: 'parcs', label: "Parcs d'attractions", href: '/partner/parcs', icon: SparklesIcon, color: 'text-fuchsia-600', bg: 'bg-fuchsia-50', for: ['parcs'] },
    { key: 'promotions', label: 'Promotions actives', href: '/partner/promotions', icon: ReceiptPercentIcon, color: 'text-rose-600', bg: 'bg-rose-50', for: [] },
    { key: 'marketplace', label: 'Marketplace', href: '/partner/marketplace', icon: ShoppingBagIcon, color: 'text-pink-600', bg: 'bg-pink-50', for: [] },
    { key: 'fret', label: 'Fret', href: '/partner/fret', icon: CubeIcon, color: 'text-[#0972D3]', bg: 'bg-blue-50', for: [] },
];

// La rubrique principale du partenaire en premier.
const sortedModules = computed(() => [...modules].sort((x, y) => Number(y.for.includes(ACTIVITY_QUICK[act.value])) - Number(x.for.includes(ACTIVITY_QUICK[act.value]))));
const isTransport = computed(() => ['transport', 'vols'].includes(ACTIVITY_QUICK[act.value]) || !act.value || props.nextDepartures.length > 0);

const money = (v) => `${Math.round(Number(v || 0)).toLocaleString('fr-FR')} XAF`;
const when = (iso) => {
    if (!iso) return '';
    const d = new Date(`${iso}:00`);
    return `${d.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' })} · ${iso.slice(11, 16)}`;
};
</script>

<template>
    <Head title="Tableau de bord partenaire" />

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ company.activity_label || 'Partenaire MOKILI TOUR' }}</p>
            <h1 class="text-xl font-bold text-slate-900">{{ company.name }}</h1>
        </div>
        <Link href="/partner/entreprise" class="btn-console-secondary">Mon entreprise</Link>
    </div>

    <div v-if="!company.verified" class="mb-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <p class="font-semibold">Compte en cours de validation</p>
        <p>Vous pouvez deja preparer vos trajets, offres et promotions. Ils seront mis en ligne des que l'equipe MOKILI TOUR aura valide votre societe.</p>
    </div>
    <div v-else-if="!company.activity" class="mb-5 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
        Indiquez votre activite (compagnie de bus, train, canots, agence, garage...) dans <Link href="/partner/entreprise" class="font-semibold underline">Mon entreprise</Link> pour un espace adapte.
    </div>

    <!-- Chiffres du mois -->
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
        <div class="console-stat">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Ventes du mois</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ money(month.revenue) }}</p>
        </div>
        <div class="console-stat">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Places / billets vendus (mois)</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ month.seats ?? 0 }}</p>
        </div>
        <div class="console-stat">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">En attente de paiement</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ month.awaiting ?? 0 }}</p>
        </div>
        <div class="console-stat">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Promotions actives</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ counts.promotions }}</p>
        </div>
    </div>

    <!-- Actions rapides -->
    <div class="mt-5 grid gap-3 md:grid-cols-3">
        <Link v-for="q in quick" :key="q.href + q.label" :href="q.href" class="console-panel group flex items-center gap-3 transition hover:border-[#0972D3] hover:shadow-md">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#0F1B2A] text-white"><PlusIcon class="h-5 w-5" /></span>
            <span>
                <span class="block font-semibold text-slate-900 group-hover:text-[#0972D3]">{{ q.label }}</span>
                <span class="block text-xs text-slate-500">{{ q.hint }}</span>
            </span>
        </Link>
    </div>

    <div class="mt-5 grid gap-5" :class="isTransport && 'lg:grid-cols-[1fr_340px]'">
        <!-- Prochains departs (transporteurs) -->
        <div v-if="isTransport" class="console-panel">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-semibold text-slate-900">Prochains departs</h2>
                <Link href="/partner/transport" class="text-sm font-semibold text-[#0972D3] hover:underline">Tous les trajets →</Link>
            </div>
            <p v-if="!nextDepartures.length" class="py-6 text-center text-sm text-slate-400">
                Aucun depart programme. <Link href="/partner/transport/creer" class="font-semibold text-[#0972D3]">Ajouter un trajet</Link>
            </p>
            <ul v-else class="divide-y divide-slate-100">
                <li v-for="d in nextDepartures" :key="d.id" class="flex items-center gap-3 py-2.5">
                    <div class="w-28 shrink-0 text-xs font-semibold text-slate-500">{{ when(d.departure_at) }}</div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-slate-900">{{ d.route }}</p>
                        <p class="text-xs text-slate-500">{{ d.mode }}<span v-if="d.promo"> · <span class="font-semibold text-rose-600">-{{ d.promo }} %</span></span><span v-if="!d.active"> · <span class="text-amber-700">non publie</span></span></p>
                    </div>
                    <div class="text-right text-xs">
                        <p class="font-semibold text-slate-900">{{ d.sold }} vendue{{ d.sold > 1 ? 's' : '' }}</p>
                        <p class="text-slate-500">{{ d.seats }} restante{{ d.seats > 1 ? 's' : '' }}</p>
                    </div>
                    <Link :href="`/partner/transport/${d.id}/passagers`" class="text-xs font-semibold text-[#0972D3] hover:underline">Passagers</Link>
                </li>
            </ul>
        </div>

        <div class="console-panel">
            <h2 class="font-semibold text-slate-900">Bon a savoir</h2>
            <ul class="mt-3 space-y-2 text-sm text-slate-600">
                <li v-if="isTransport">&bull; Creez un <strong>trajet regulier</strong> en une fois : cochez les jours (ex. lundi, mercredi, vendredi) et la date de fin.</li>
                <li v-if="isTransport">&bull; Vos departs apparaissent dans la recherche et le <strong>Programme des departs</strong> du site et de l'application.</li>
                <li v-else>&bull; Ajoutez de belles photos et une description precise : elles s'affichent sur le site et dans l'application.</li>
                <li>&bull; Vos nouvelles annonces (logements, voitures, evenements) sont verifiees par MOKILI TOUR avant publication.</li>
                <li>&bull; Une <strong>promotion</strong> s'arrete toute seule a sa date de fin.</li>
                <li>&bull; Les clients paient par Mobile Money ; la place est confirmee au paiement.</li>
            </ul>
        </div>
    </div>

    <!-- Modules -->
    <h2 class="mb-3 mt-6 font-semibold text-slate-900">Mes rubriques</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Link v-for="m in sortedModules" :key="m.key" :href="m.href" class="console-panel group transition hover:border-[#0972D3] hover:shadow-md">
            <div :class="['inline-flex h-10 w-10 items-center justify-center rounded-lg', m.bg]">
                <component :is="m.icon" :class="['h-5 w-5', m.color]" />
            </div>
            <p class="mt-3 text-2xl font-bold text-slate-900">{{ counts[m.key] ?? 0 }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ m.label }}</p>
        </Link>
    </div>
</template>
