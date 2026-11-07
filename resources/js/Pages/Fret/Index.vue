<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import ListingImage from '@/Components/ListingImage.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

defineOptions({ layout: MainLayout });
const props = defineProps({
    offers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    packageTypes: { type: Object, default: () => ({}) },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

const modeLabel = { air: 'Aerien', mer: 'Maritime', route: 'Routier' };
const modeIcon = { air: '✈️', mer: '🚢', route: '🚛' };

/* ---------- Widget d'action (tarif / suivi / expedition) ---------- */
const tab = ref('rate');
const tabs = [
    { key: 'rate', label: 'Obtenir un tarif' },
    { key: 'track', label: 'Suivre un envoi' },
    { key: 'ship', label: 'Creer une expedition' },
];

const goRate = () => {
    tab.value = 'rate';
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const trackingCode = ref('');
const track = () => {
    if (!trackingCode.value.trim()) return;
    router.get(`/fret/suivi/${encodeURIComponent(trackingCode.value.trim())}`);
};

const parcelRates = {
    document: { label: 'Documents', perKg: 1500, base: 2000 },
    colis: { label: 'Colis standard', perKg: 2500, base: 3000 },
    express: { label: 'Colis express', perKg: 4000, base: 5000 },
};
const parcelType = ref('colis');
const parcelWeight = ref(1);
const estimate = computed(() => {
    const r = parcelRates[parcelType.value];
    return Math.round(r.base + r.perKg * (Number(parcelWeight.value) || 0));
});

/* ---------- Filtres catalogue (serveur) ---------- */
const f = reactive({
    scope: props.filters.scope ?? '',
    mode: props.filters.mode ?? '',
    colis: props.filters.colis ?? '',
    origin: props.filters.origin ?? '',
    destination: props.filters.destination ?? '',
});
const apply = () => {
    const q = Object.fromEntries(Object.entries(f).filter(([, v]) => v));
    router.get('/fret', q, { preserveScroll: true, preserveState: true });
};
const toggle = (key, value) => {
    f[key] = f[key] === value ? '' : value;
    apply();
};

/* ---------- Services d'expedition (onglets facon DHL) ---------- */
const segments = [
    { key: 'export', label: 'Exportation', scope: 'export', text: 'Du Congo vers l\'etranger : documents, colis, marchandises, vehicules et conteneurs.' },
    { key: 'import', label: 'Importation', scope: 'import', text: 'De l\'etranger vers le Congo : suivi de bout en bout, dedouanement selon le partenaire.' },
    { key: 'national', label: 'National', scope: 'national', text: 'Entre villes et departements du Congo, par route, fleuve ou air.' },
    { key: 'pro', label: 'Solutions pour les professionnels', scope: '', text: 'Volumes reguliers, palettes, conteneurs : demandez un devis personnalise a plusieurs partenaires.' },
];
const segment = ref(segments.find((s) => s.scope === f.scope)?.key ?? 'export');
const currentSegment = computed(() => segments.find((s) => s.key === segment.value));
const pickSegment = (s) => {
    segment.value = s.key;
};

// Comparatif des modes (indicatif : les delais, poids maximum et options exacts sont ceux du partenaire retenu).
const compare = {
    cols: [
        { key: 'air', title: 'Express aerien', sub: 'Le plus rapide' },
        { key: 'route', title: 'Routier', sub: 'Regional & national' },
        { key: 'mer', title: 'Maritime', sub: 'Gros volumes' },
    ],
    rows: [
        { group: 'Services inclus', items: [
            ['Suivi en temps reel de chaque etape', true, true, true],
            ['Notifications de statut sur votre compte', true, true, true],
            ['Paiement Mobile Money ou carte', true, true, true],
            ['Partenaire verifie par MOKILI TOUR', true, true, true],
            ['Reclamation en ligne', true, true, true],
        ] },
        { group: 'Specificites', items: [
            ['Documents', true, true, true],
            ['Colis', true, true, true],
            ['Palettes & marchandises', true, true, true],
            ['Vehicules', false, true, true],
            ['Conteneurs', false, false, true],
            ['Delai indicatif', 'Quelques jours', 'Selon trajet', 'Plusieurs semaines'],
        ] },
        { group: 'Transit', items: [
            ['Enlevement / depot chez le partenaire', true, true, true],
            ['Dedouanement', 'Selon partenaire', 'Selon partenaire', 'Selon partenaire'],
        ] },
    ],
};

const options = [
    { t: 'Services en option', d: 'Assurance, emballage, enlevement a domicile, livraison a l\'adresse : proposes par le partenaire dans son devis.' },
    { t: 'Dedouanement', d: 'Les formalites douanieres sont detaillees dans l\'offre ou le devis (inclus / non inclus), avant tout paiement.' },
    { t: 'Frais supplementaires', d: 'Les frais non inclus (douane, taxes, stockage, livraison finale) sont affiches clairement dans les exclusions de l\'offre.' },
];

const steps = [
    { n: 1, t: 'Decrivez votre envoi', d: 'Trajet, poids, type de marchandise.' },
    { n: 2, t: 'Comparez les devis', d: 'Des partenaires verifies vous repondent.' },
    { n: 3, t: 'Payez en securite', d: 'Mobile Money ou carte.' },
    { n: 4, t: 'Suivez votre colis', d: 'De l\'enlevement a la livraison.' },
];

const price = (o) => (o.pricing_mode === 'quote' ? 'Sur devis' : `${o.price_is_firm ? '' : 'des '}${Number(o.price_per_kg).toLocaleString('fr-FR')} ${o.currency} / ${o.unit || 'kg'}`);
</script>

<template>
    <Head title="Fret & logistique" />

    <!-- Barre de navigation secondaire (Expedier / Suivre / Gerer) -->
    <div class="border-b bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-x-8 gap-y-1 px-6 text-sm font-semibold text-navy-900">
            <a href="#expedier" class="border-b-4 border-gold-500 py-3">Expedier</a>
            <a href="#suivre" class="border-b-4 border-transparent py-3 hover:border-gold-500/60" @click.prevent="tab = 'track'">Suivre</a>
            <Link href="/fret/mes-envois" class="border-b-4 border-transparent py-3 hover:border-gold-500/60">Mes expeditions</Link>
            <Link href="/fret/commandes" class="border-b-4 border-transparent py-3 hover:border-gold-500/60">Mes commandes</Link>
            <Link href="/fret/demandes" class="border-b-4 border-transparent py-3 hover:border-gold-500/60">Mes devis</Link>
            <a href="/#contact" class="ml-auto py-3 text-navy-700 hover:underline">Aide et support</a>
        </div>
    </div>

    <!-- HERO jaune facon DHL -->
    <section id="expedier" class="bg-gold-500">
        <div class="mx-auto grid max-w-7xl items-center gap-8 px-6 py-10 lg:grid-cols-2">
            <div>
                <h1 class="text-3xl font-extrabold leading-tight text-navy-900 sm:text-4xl">Services d'expedition</h1>
                <p class="mt-3 max-w-md text-navy-900/80">
                    Envoyez documents, colis, marchandises, vehicules ou conteneurs, au Congo et dans le monde, avec des partenaires verifies et un suivi a chaque etape.
                </p>
                <ul class="mt-5 space-y-1.5 text-sm font-semibold text-navy-900">
                    <li>✓ Prix et conditions affiches avant de payer</li>
                    <li>✓ Suivi en temps reel, sans compte</li>
                    <li>✓ Devis compares, paiement securise</li>
                </ul>
            </div>

            <div class="overflow-hidden rounded-xl bg-white shadow-xl">
                <div class="flex border-b text-sm font-bold">
                    <button v-for="t in tabs" :key="t.key" type="button" class="flex-1 px-3 py-3.5 transition"
                        :class="tab === t.key ? 'border-b-4 border-gold-500 text-navy-900' : 'text-navy-500 hover:bg-gray-50'" @click="tab = t.key">
                        {{ t.label }}
                    </button>
                </div>
                <div class="p-5">
                    <div v-if="tab === 'rate'" class="space-y-4">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="text-[11px] font-bold uppercase text-navy-500">Type d'envoi</label>
                                <select v-model="parcelType" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                                    <option v-for="(r, key) in parcelRates" :key="key" :value="key">{{ r.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[11px] font-bold uppercase text-navy-500">Poids (kg)</label>
                                <input v-model.number="parcelWeight" type="number" min="0.1" step="0.1" class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                            </div>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-navy-50 px-4 py-3">
                            <span class="text-xs uppercase text-navy-500">Estimation indicative</span>
                            <span class="text-2xl font-extrabold text-navy-900">{{ estimate.toLocaleString('fr-FR') }} XAF</span>
                        </div>
                        <p class="text-xs text-navy-500">Tarif confirme au depot. Pour un prix ferme, demandez un devis a nos partenaires.</p>
                        <Link href="/fret/demandes/creer" class="btn-gold block text-center">Demander un devis</Link>
                    </div>

                    <form v-else-if="tab === 'track'" id="suivre" class="space-y-3" @submit.prevent="track">
                        <label class="text-[11px] font-bold uppercase text-navy-500">Numero de suivi</label>
                        <input v-model="trackingCode" type="text" placeholder="FRT-XXXXXXXXXX" class="w-full rounded-lg border-gray-300 text-sm uppercase" />
                        <button type="submit" class="btn-gold w-full">Suivre</button>
                        <p class="text-xs text-navy-500">Aucune connexion requise.</p>
                    </form>

                    <div v-else class="space-y-3">
                        <p class="text-sm text-navy-700">Decrivez votre expedition (trajet, poids, type de marchandise) : plusieurs partenaires peuvent vous repondre.</p>
                        <Link href="/fret/demandes/creer" class="btn-gold block text-center">Creer une expedition</Link>
                        <p v-if="!user" class="text-center text-xs text-navy-500">Connexion requise pour valider une expedition.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Comment ca marche -->
    <section class="border-b bg-white">
        <div class="mx-auto grid max-w-7xl gap-4 px-6 py-6 sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="s in steps" :key="s.n" class="flex items-start gap-3">
                <span class="flex h-8 w-8 flex-none items-center justify-center rounded-full bg-navy-900 text-sm font-extrabold text-white">{{ s.n }}</span>
                <div><p class="text-sm font-bold text-navy-900">{{ s.t }}</p><p class="text-xs text-navy-500">{{ s.d }}</p></div>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-6 py-12">
        <!-- SERVICES D'EXPEDITION -->
        <section>
            <h2 class="text-2xl font-extrabold text-navy-900">Nos services d'expedition</h2>
            <p class="mt-1 max-w-3xl text-sm text-navy-700">Des services de transport adaptes a chacun de vos besoins, avec la portee de notre reseau de partenaires.</p>

            <div class="mt-5 flex flex-wrap gap-2 border-b">
                <button v-for="s in segments" :key="s.key" type="button" class="-mb-px border-b-4 px-4 py-2.5 text-sm font-bold transition"
                    :class="segment === s.key ? 'border-gold-500 text-navy-900' : 'border-transparent text-navy-500 hover:text-navy-900'" @click="pickSegment(s)">
                    {{ s.label }}
                </button>
            </div>
            <p class="mt-4 text-sm text-navy-700">{{ currentSegment.text }}</p>

            <div class="mt-4 overflow-x-auto rounded-xl border">
                <table class="w-full min-w-[640px] text-sm">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="w-1/3 px-4 py-4 text-left"></th>
                            <th v-for="c in compare.cols" :key="c.key" class="px-4 py-4 text-center">
                                <div class="text-2xl">{{ modeIcon[c.key] }}</div>
                                <div class="font-extrabold text-navy-900">{{ c.title }}</div>
                                <div class="text-xs font-normal text-navy-500">{{ c.sub }}</div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="g in compare.rows" :key="g.group">
                            <tr><td colspan="4" class="bg-navy-900 px-4 py-2 text-xs font-bold uppercase tracking-wide text-white">{{ g.group }}</td></tr>
                            <tr v-for="r in g.items" :key="r[0]" class="border-t">
                                <td class="px-4 py-3 font-medium text-navy-900">{{ r[0] }}</td>
                                <td v-for="i in 3" :key="i" class="px-4 py-3 text-center">
                                    <span v-if="r[i] === true" class="font-bold text-emerald-600">✓</span>
                                    <span v-else-if="r[i] === false" class="text-gray-300">—</span>
                                    <span v-else class="text-xs text-navy-700">{{ r[i] }}</span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <p class="mt-2 text-xs text-navy-500">La disponibilite d'un service depend de l'origine et de la destination. Delais, poids maximum et options exacts : voir l'offre du partenaire.</p>

            <div class="mt-5 flex flex-wrap gap-3">
                <Link href="/fret/demandes/creer" class="btn-gold px-8">Creer une expedition</Link>
                <button type="button" class="rounded-full border-2 border-navy-900 px-8 py-2.5 text-sm font-bold text-navy-900 hover:bg-navy-50" @click="goRate">Demander un tarif</button>
            </div>

            <div class="mt-8 grid gap-4 md:grid-cols-3">
                <div v-for="o in options" :key="o.t" class="rounded-xl border-l-4 border-gold-500 bg-gray-50 p-5">
                    <p class="font-extrabold text-navy-900">{{ o.t }}</p>
                    <p class="mt-1 text-sm text-navy-700">{{ o.d }}</p>
                </div>
            </div>
        </section>

        <!-- OFFRES DES PARTENAIRES -->
        <section class="mt-14">
            <h2 class="text-2xl font-extrabold text-navy-900">Offres de nos partenaires</h2>
            <p class="mt-1 text-sm text-navy-700">Transporteurs verifies : comparez les prix, les delais et les conditions.</p>

            <div class="mt-5 rounded-xl border bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center gap-x-8 gap-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="w-20 text-[11px] font-bold uppercase text-navy-500">Zone</span>
                        <button v-for="z in [['national', 'National'], ['international', 'International'], ['export', 'Exportation'], ['import', 'Importation']]" :key="z[0]" type="button"
                            class="rounded-full border px-4 py-1.5 text-xs font-semibold transition"
                            :class="f.scope === z[0] ? 'border-navy-900 bg-navy-900 text-white' : 'text-navy-700 hover:bg-gray-50'" @click="toggle('scope', z[0])">{{ z[1] }}</button>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="w-20 text-[11px] font-bold uppercase text-navy-500">Transport</span>
                        <button v-for="(label, key) in modeLabel" :key="key" type="button"
                            class="rounded-full border px-4 py-1.5 text-xs font-semibold transition"
                            :class="f.mode === key ? 'border-gold-500 bg-gold-500 text-navy-900' : 'text-navy-700 hover:bg-gray-50'" @click="toggle('mode', key)">{{ modeIcon[key] }} {{ label }}</button>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="w-20 text-[11px] font-bold uppercase text-navy-500">Type de colis</span>
                    <button v-for="(label, key) in packageTypes" :key="key" type="button"
                        class="rounded-full border px-4 py-1.5 text-xs font-semibold transition"
                        :class="f.colis === key ? 'border-navy-900 bg-navy-900 text-white' : 'text-navy-700 hover:bg-gray-50'" @click="toggle('colis', key)">{{ label }}</button>
                </div>
                <form class="mt-4 flex flex-col gap-3 sm:flex-row" @submit.prevent="apply">
                    <input v-model="f.origin" type="text" placeholder="Depart (ex. Brazzaville)" class="w-full rounded-lg border-gray-300 text-sm" />
                    <input v-model="f.destination" type="text" placeholder="Arrivee (ex. Douala, Guangzhou)" class="w-full rounded-lg border-gray-300 text-sm" />
                    <button type="submit" class="btn-gold whitespace-nowrap px-8">Rechercher</button>
                </form>
            </div>

            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <Link v-for="o in offers.data" :key="o.id" :href="`/fret/${o.slug}`" class="group overflow-hidden rounded-xl border bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                    <div class="relative">
                        <ListingImage :src="o.image_url" fallback-class="bg-teal-700">{{ o.origin_city }} &rarr; {{ o.destination_city }}</ListingImage>
                        <span class="absolute left-3 top-3 rounded-full bg-gold-500 px-3 py-1 text-[11px] font-bold text-navy-900 shadow">{{ modeIcon[o.mode] }} {{ modeLabel[o.mode] }}</span>
                    </div>
                    <div class="p-4">
                        <p class="text-xs font-semibold uppercase text-navy-500">{{ o.origin_city }} &rarr; {{ o.destination_city }}</p>
                        <p class="mt-1 font-bold text-navy-900">{{ o.title }}</p>
                        <p v-if="o.partner_profile" class="mt-1 text-xs text-navy-500"><span class="font-bold text-emerald-600">✓</span> {{ o.partner_profile.trade_name || o.partner_profile.company_name }} - partenaire verifie</p>
                        <div v-if="o.package_type_labels?.length" class="mt-2 flex flex-wrap gap-1">
                            <span v-for="l in o.package_type_labels" :key="l" class="rounded bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-navy-700">{{ l }}</span>
                        </div>
                        <div class="mt-3 flex items-end justify-between border-t pt-3">
                            <span class="text-xs text-navy-500">
                                <template v-if="o.transit_days_max">Delai {{ o.transit_days_min ? o.transit_days_min + ' a ' : '' }}{{ o.transit_days_max }} j</template>
                                <template v-else-if="o.transit_time">{{ o.transit_time }}</template>
                            </span>
                            <span class="text-sm font-extrabold text-navy-900">{{ price(o) }}</span>
                        </div>
                    </div>
                </Link>
            </div>

            <div v-if="!offers.data.length" class="mt-8 rounded-xl border border-dashed bg-gray-50 p-10 text-center">
                <p class="font-bold text-navy-900">Aucune offre ne correspond a ces criteres</p>
                <p class="mt-1 text-sm text-navy-700">Decrivez votre envoi : nos partenaires vous repondent par un devis detaille.</p>
                <Link href="/fret/demandes/creer" class="btn-gold mt-4 inline-block px-8">Demander un devis</Link>
            </div>

            <div v-if="offers.links && offers.last_page > 1" class="mt-8 flex flex-wrap justify-center gap-1">
                <Link v-for="l in offers.links" :key="l.label" :href="l.url || '#'" preserve-scroll class="rounded-lg border px-3 py-1.5 text-xs font-semibold"
                    :class="[l.active ? 'border-navy-900 bg-navy-900 text-white' : 'text-navy-700 hover:bg-gray-50', !l.url && 'pointer-events-none opacity-40']" v-html="l.label" />
            </div>
        </section>
    </div>
</template>
