<script setup>
// "Devenir partenaire" : inscription des compagnies de bus, train, canots
// rapides, taxis, compagnies aeriennes, agences de billets, garages...
import MainLayout from '@/Layouts/MainLayout.vue';
import PhotoHero from '@/Components/PhotoHero.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineOptions({ layout: MainLayout });

const props = defineProps({
    activities: { type: Object, default: () => ({}) },
    activity: { type: String, default: '' },
});

const form = useForm({
    company_name: '',
    partner_activity: props.activity,
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    accept: false,
});
const submit = () => form.post('/devenir-partenaire', { onFinish: () => form.reset('password', 'password_confirmation') });

const PROFILES = [
    ['transport_routier', '🚌', 'Compagnies de bus', 'Publiez vos departs, vendez vos places en Mobile Money.'],
    ['transport_ferroviaire', '🚆', 'Train', 'Horaires et places de vos trains, liste des passagers.'],
    ['canot_rapide', '🚤', 'Canots rapides', 'Traversees Brazzaville - Kinshasa et liaisons fluviales.'],
    ['taxi_vtc', '🚕', 'Taxis & covoiturage', 'Trajets interurbains a l\'heure, places limitees.'],
    ['compagnie_aerienne', '✈️', 'Compagnies aeriennes', 'Vos offres vols et promotions sur la page Vols.'],
    ['agence_billets', '🎫', 'Agences de voyage', 'Vendez billets d\'avion, bus et trajets.'],
    ['garage', '🔧', 'Garages & mecanique', 'Vos prestations et promotions dans la rubrique Voiture.'],
    ['hotel', '🏨', 'Hotels', 'Chambres, suites et tarifs par nuit dans la rubrique Logement.'],
    ['appartement', '🏠', 'Appartements & villas', 'Logements meubles a la nuit, a la semaine ou au mois.'],
    ['location_voiture', '🚗', 'Location de voitures', 'Vos vehicules a louer avec ou sans chauffeur.'],
    ['vente_voiture', '🏷️', 'Vente de voitures', 'Concessionnaires et revendeurs : annonces et prix barres.'],
    ['organisateur_evenements', '🎤', 'Concerts & spectacles', 'Vendez vos billets, gerez les places et la prevente.'],
    ['cinema', '🎬', 'Cinemas', 'Vos seances, salles et billets en ligne.'],
    ['parc_loisirs', '🎢', "Parcs d'attractions", 'Tarifs adulte / enfant, horaires, activites.'],
    ['tourisme', '🍽️', 'Restaurants, bars, visites', 'Faites-vous connaitre et proposez vos offres.'],
    ['commerce', '🛍️', 'Commerces', 'Vendez vos produits sur la Marketplace.'],
];
</script>

<template>
    <Head title="Devenir partenaire" />

    <PhotoHero class="px-4 pb-12 pt-10 text-white">
        <div class="mx-auto max-w-5xl">
            <p class="text-sm font-semibold uppercase tracking-widest text-gold-400">Partenaires MOKILI TOUR</p>
            <h1 class="mt-2 max-w-2xl text-3xl font-extrabold sm:text-4xl">Vendez vos billets, sejours et services sur MOKILI TOUR</h1>
            <p class="mt-2 max-w-2xl text-navy-100">Transport, hotels et appartements, location et vente de voitures, garages, concerts, cinemas, parcs d'attractions : publiez vos offres et vos promotions, encaissez par Mobile Money.</p>
        </div>
    </PhotoHero>

    <div class="mx-auto grid max-w-5xl gap-8 px-4 py-10 lg:grid-cols-[1fr_420px]">
        <div>
            <h2 class="text-xl font-extrabold text-navy-900">Pour qui ?</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <button
                    v-for="p in PROFILES"
                    :key="p[0]"
                    type="button"
                    class="flex items-start gap-3 rounded-2xl border bg-white p-4 text-left transition"
                    :class="form.partner_activity === p[0] ? 'border-gold-500 ring-2 ring-gold-400' : 'border-navy-100 hover:border-navy-300'"
                    @click="form.partner_activity = p[0]"
                >
                    <span class="text-2xl">{{ p[1] }}</span>
                    <span>
                        <span class="block font-bold text-navy-900">{{ p[2] }}</span>
                        <span class="block text-sm text-navy-600">{{ p[3] }}</span>
                    </span>
                </button>
            </div>

            <h2 class="mt-8 text-xl font-extrabold text-navy-900">Votre espace partenaire</h2>
            <ul class="mt-3 space-y-2 text-navy-700">
                <li>✅ <strong>Trajets et horaires</strong> : creez un depart ou un trajet regulier (tous les lundis, mercredis...) en une fois.</li>
                <li>✅ <strong>Promotions</strong> : remise en %, libelle et date de fin, sur plusieurs departs d'un coup.</li>
                <li>✅ <strong>Reservations</strong> : liste des passagers, places vendues, ventes du mois.</li>
                <li>✅ <strong>Visibilite</strong> : vos departs dans la recherche, le Programme des departs et l'application mobile.</li>
                <li>✅ <strong>Paiement</strong> : vos clients paient par Mobile Money.</li>
            </ul>
        </div>

        <form class="h-fit space-y-4 rounded-2xl border border-navy-100 bg-white p-6 shadow-sm" @submit.prevent="submit">
            <h2 class="text-lg font-extrabold text-navy-900">Creer mon compte partenaire</h2>
            <label class="block text-sm font-semibold text-navy-900">Nom de la societe
                <input v-model="form.company_name" type="text" placeholder="ex. Ocean du Nord" class="mt-1 w-full rounded-lg border-gray-300" required />
                <span v-if="form.errors.company_name" class="text-xs text-red-600">{{ form.errors.company_name }}</span>
            </label>
            <label class="block text-sm font-semibold text-navy-900">Activite
                <select v-model="form.partner_activity" class="mt-1 w-full rounded-lg border-gray-300" required>
                    <option value="">— Choisir —</option>
                    <option v-for="(label, key) in activities" :key="key" :value="key">{{ label }}</option>
                </select>
                <span v-if="form.errors.partner_activity" class="text-xs text-red-600">{{ form.errors.partner_activity }}</span>
            </label>
            <label class="block text-sm font-semibold text-navy-900">Nom du responsable
                <input v-model="form.name" type="text" class="mt-1 w-full rounded-lg border-gray-300" required />
            </label>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block text-sm font-semibold text-navy-900">Telephone
                    <input v-model="form.phone" type="tel" placeholder="+242 06..." class="mt-1 w-full rounded-lg border-gray-300" required />
                    <span v-if="form.errors.phone" class="text-xs text-red-600">{{ form.errors.phone }}</span>
                </label>
                <label class="block text-sm font-semibold text-navy-900">Email
                    <input v-model="form.email" type="email" class="mt-1 w-full rounded-lg border-gray-300" required />
                    <span v-if="form.errors.email" class="text-xs text-red-600">{{ form.errors.email }}</span>
                </label>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block text-sm font-semibold text-navy-900">Mot de passe
                    <input v-model="form.password" type="password" minlength="8" class="mt-1 w-full rounded-lg border-gray-300" required />
                </label>
                <label class="block text-sm font-semibold text-navy-900">Confirmation
                    <input v-model="form.password_confirmation" type="password" class="mt-1 w-full rounded-lg border-gray-300" required />
                </label>
            </div>
            <span v-if="form.errors.password" class="text-xs text-red-600">{{ form.errors.password }}</span>
            <label class="flex items-start gap-2 text-sm text-navy-700">
                <input v-model="form.accept" type="checkbox" class="mt-0.5 rounded" />
                J'accepte que MOKILI TOUR verifie ma societe avant la mise en ligne de mes offres.
            </label>
            <span v-if="form.errors.accept" class="text-xs text-red-600">Veuillez accepter la verification de votre societe.</span>
            <button type="submit" class="btn-gold w-full" :disabled="form.processing">Creer mon espace partenaire</button>
            <p class="text-center text-sm text-navy-600">Deja partenaire ? <Link href="/partner/login" class="font-semibold text-gold-600 hover:underline">Se connecter</Link></p>
        </form>
    </div>
</template>
