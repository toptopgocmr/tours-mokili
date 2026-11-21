<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import ServiceIcon from '@/Components/ServiceIcon.vue';
import CoastalScene from '@/Components/CoastalScene.vue';
import DestinationImage from '@/Components/DestinationImage.vue';
import { Head, Link } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

defineOptions({ layout: MainLayout });

defineProps({
    categories: { type: Array, default: () => [] },
    featuredOffers: { type: Array, default: () => [] },
});

// Per-service brand colors, matching the mobile app's AppColors
// (mobile-mokili/lib/core/theme/app_theme.dart) so the floating
// service cards below look identical on web and mobile.
const SERVICE_COLORS = {
    voyage: '#0B2A5B',
    logement: '#1E8A3E',
    voiture: '#E06A1D',
    divertissement: '#7A2FBF',
    marketplace: '#D6216B',
    fret: '#0FA3A3',
};
const serviceColor = (slug) => SERVICE_COLORS[slug] ?? '#0B2A5B';

// Same real-photo-first pattern as DestinationImage.vue, for the
// single hero banner: drop public/images/hero.jpg to replace the
// illustration, no code change needed.
const heroPhotoFailed = ref(false);

const features = [
    {
        title: 'Sécurité',
        text: 'Vos données et vos paiements sont protégés',
        icon: 'M12 1 3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z',
    },
    {
        title: 'Support 24/7',
        text: 'Notre équipe est à votre écoute',
        icon: 'M12 1c-4.97 0-9 4.03-9 9v7c0 1.66 1.34 3 3 3h3v-8H5v-2c0-3.87 3.13-7 7-7s7 3.13 7 7v2h-4v8h4v1h-7v2h6c1.66 0 3-1.34 3-3V10c0-4.97-4.03-9-9-9z',
    },
    {
        title: 'Qualité',
        text: 'Des partenaires fiables et sélectionnés',
        icon: 'm23 12-2.44-2.79.34-3.69-3.61-.82-1.89-3.2L12 2.96 8.6 1.54 6.71 4.72l-3.61.81.34 3.68L1 12l2.44 2.79-.34 3.7 3.61.82L8.6 22.5l3.4-1.47 3.4 1.46 1.89-3.19 3.61-.82-.34-3.69L23 12zm-12.91 4.72-3.8-3.81 1.48-1.48 2.32 2.33 5.85-5.87 1.48 1.48-7.33 7.35z',
    },
    {
        title: 'Accessibilité',
        text: 'Partout, tout le temps, sur tous vos appareils',
        icon: 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z',
    },
];

// Ce que l'application permet (section "application mobile").
const appPoints = [
    'Réservez vols, hébergements, véhicules et sorties en quelques clics',
    'Payez par Mobile Money, en toute sécurité',
    'Suivez vos réservations et vos colis en temps réel',
    'Trouvez une entreprise dans l\'annuaire, où que vous soyez',
];

// Contact : le formulaire ouvre la messagerie de l'utilisateur, pre-remplie.
const CONTACT_EMAIL = 'contact@mokilitour.com';
const contact = reactive({ name: '', email: '', subject: 'Question', message: '' });
const sendContact = () => {
    const body = `${contact.message}\n\n--\n${contact.name}${contact.email ? ` (${contact.email})` : ''}`;
    window.location.href = `mailto:${CONTACT_EMAIL}?subject=${encodeURIComponent(`[MOKILI TOUR] ${contact.subject}`)}&body=${encodeURIComponent(body)}`;
};
</script>

<template>
    <Head title="Accueil" />

    <!-- Hero visual: bounded height so the background never has to
         stretch across an unpredictable amount of content (that's what
         caused the earlier cropping bug). Text sits left, over open
         sea/sky. The service cards below intentionally straddle this
         hero's bottom edge (negative margin-top on the next section) -
         matching the reference mockup's floating white tiles instead
         of a flat navy band. -->
    <section class="relative min-h-[360px] overflow-hidden pb-20 sm:min-h-[420px] md:min-h-[480px]">
        <div class="absolute inset-0">
            <img
                v-if="!heroPhotoFailed"
                src="/images/hero.jpg"
                alt="MOKILI TOUR"
                class="h-full w-full object-cover object-[center_20%]"
                @error="heroPhotoFailed = true"
            />
            <CoastalScene v-else class="h-full w-full" />
            <div class="absolute inset-0 bg-gradient-to-b from-navy-900/70 via-navy-900/30 to-navy-900/10" />
        </div>

        <!-- Text sits high, over the open sea/sky (left side of the photo),
             stacked tightly like the mockup so it reads as one compact
             block instead of floating in a big empty middle. -->
        <div class="relative flex max-w-2xl flex-col justify-start px-5 pt-12 text-left sm:px-10 sm:pt-20 md:px-16 md:pt-24">
            <p class="text-sm font-semibold uppercase tracking-widest text-gold-600">MOKILI TOUR</p>
            <h1 class="mt-2 text-[26px] font-extrabold leading-tight text-white drop-shadow min-[360px]:text-3xl sm:text-4xl md:text-5xl">
                LE MONDE À <span class="text-gold-600">PORTÉE</span> DE MAIN
            </h1>
            <p class="mt-2 max-w-lg text-base text-white/90 drop-shadow sm:text-lg">
                Votre compagnon de confiance pour tous vos besoins, partout dans le monde.
            </p>

            <form class="mt-6 flex w-full max-w-xl overflow-hidden rounded-full bg-white shadow-lg">
                <input
                    type="text"
                    placeholder="Rechercher une destination, un service..."
                    class="min-w-0 flex-1 border-0 px-4 py-3 text-sm focus:ring-0 sm:px-5"
                />
                <button type="submit" class="btn-gold shrink-0 rounded-none !px-4 sm:!px-8">Rechercher</button>
            </form>
        </div>
    </section>

    <!-- Service grid: white floating cards straddling the hero's bottom
         edge, matching the mobile app's ServiceGrid (see
         mobile-mokili/lib/core/widgets/service_grid.dart) and the
         reference mockup - each card is a white rounded square with a
         big colored icon (that service's own brand color) and a bold
         uppercase label in the same color underneath. -->
    <section id="services" class="relative z-10 -mt-14 px-3 pb-2 sm:-mt-16 sm:px-6">
        <div class="mx-auto grid max-w-6xl grid-cols-3 gap-2 min-[360px]:gap-3 sm:grid-cols-4 sm:gap-4 lg:grid-cols-6">
            <Link
                v-for="c in categories"
                :key="c.slug"
                :href="c.slug === 'divertissement' ? '/tourisme' : `/${c.slug}`"
                class="flex min-w-0 flex-col items-center justify-center gap-2 rounded-2xl bg-white px-1 py-3 text-center sm:p-4 shadow-[0_4px_10px_rgba(0,0,0,0.12)] transition hover:-translate-y-1 hover:shadow-[0_8px_18px_rgba(0,0,0,0.16)]"
            >
                <ServiceIcon :slug="c.slug" class="h-11 w-11" :style="{ color: serviceColor(c.slug) }" />
                <span class="max-w-full truncate text-[9.5px] font-bold uppercase min-[360px]:text-[10.5px] sm:text-[11px] sm:tracking-wide" :style="{ color: serviceColor(c.slug) }">{{ c.name }}</span>
            </Link>
        </div>
    </section>

    <section v-if="featuredOffers.length" class="mx-auto max-w-7xl px-6 py-12">
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-2xl font-bold text-navy-900">Offres du moment - Voyage</h2>
            <Link href="/voyage" class="text-sm font-semibold text-gold-600 hover:underline">Voir tout →</Link>
        </div>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="offer in featuredOffers"
                :key="offer.id"
                :href="`/voyage/${offer.slug}`"
                class="group overflow-hidden rounded-2xl border shadow-sm transition hover:shadow-lg"
            >
                <div class="relative h-40 overflow-hidden">
                    <DestinationImage :city="offer.destination_city" :image="offer.image_url" class="h-full w-full" />
                    <div class="absolute inset-0 bg-gradient-to-t from-navy-900/70 to-transparent" />
                    <span class="absolute bottom-3 left-4 text-lg font-semibold text-white drop-shadow">{{ offer.destination_city }}</span>
                </div>
                <div class="p-4">
                    <p class="font-semibold text-navy-900">{{ offer.title }}</p>
                    <p class="mt-1 text-sm text-navy-700">
                        A partir de {{ Number(offer.price).toLocaleString('fr-FR') }} {{ $cur(offer.currency) }}
                        <span v-if="offer.discount_percent" class="ml-2 rounded-full bg-gold-50 px-2 py-0.5 text-xs font-semibold text-gold-600">
                            -{{ offer.discount_percent }}%
                        </span>
                    </p>
                </div>
            </Link>
        </div>
    </section>

    <!-- Nos atouts : icones plates dans des pastilles dorees -->
    <section class="bg-navy-900 py-14 text-white">
        <div class="mx-auto grid max-w-7xl grid-cols-1 gap-6 px-6 sm:grid-cols-2 lg:grid-cols-4">
            <div
                v-for="f in features"
                :key="f.title"
                class="flex items-start gap-4 rounded-2xl border border-white/10 bg-white/[0.03] p-5 transition hover:border-gold-500/40 hover:bg-white/[0.06]"
            >
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gold-500/15 ring-1 ring-gold-500/40">
                    <svg viewBox="0 0 24 24" class="h-6 w-6 text-gold-500" fill="currentColor" aria-hidden="true"><path :d="f.icon" /></svg>
                </span>
                <div>
                    <p class="font-bold text-white">{{ f.title }}</p>
                    <p class="mt-1 text-sm leading-snug text-navy-50/70">{{ f.text }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Application mobile : texte + apercu de l'ecran d'accueil de l'app -->
    <section class="overflow-hidden bg-gradient-to-b from-white to-navy-50/40 px-6 py-16">
        <div class="mx-auto grid max-w-6xl items-center gap-12 md:grid-cols-2">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-gold-600">Application mobile</p>
                <h2 class="mt-2 text-3xl font-extrabold text-navy-900">Découvrez l'application MOKILI TOUR</h2>
                <p class="mt-3 text-navy-700">Simple, rapide et sécurisée : tous les services du site, dans votre poche.</p>
                <ul class="mt-6 space-y-3">
                    <li v-for="pt in appPoints" :key="pt" class="flex items-start gap-3 text-sm text-navy-800">
                        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-gold-500 text-navy-900">
                            <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="currentColor"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" /></svg>
                        </span>
                        {{ pt }}
                    </li>
                </ul>
                <div class="mt-8 flex flex-wrap gap-3">
                    <span class="inline-flex items-center gap-3 rounded-xl bg-navy-900 px-4 py-2.5 text-white shadow-md">
                        <svg viewBox="0 0 24 24" class="h-7 w-7" aria-hidden="true">
                            <path fill="#34A853" d="M3.6 2.3 13.4 12l-9.8 9.7c-.3-.2-.5-.6-.5-1.1V3.4c0-.5.2-.9.5-1.1z" />
                            <path fill="#FBBC04" d="m16.7 15.3-3.3-3.3 3.3-3.3 3.8 2.2c1 .6 1 1.6 0 2.2z" />
                            <path fill="#EA4335" d="M13.4 12 3.6 21.7c.3.2.8.2 1.3 0l11.8-6.4z" />
                            <path fill="#4285F4" d="M16.7 8.7 4.9 2.3c-.5-.3-1-.2-1.3 0L13.4 12z" />
                        </svg>
                        <span class="leading-tight">
                            <span class="block text-[10px] uppercase tracking-wide text-white/70">Bientôt sur</span>
                            <span class="block text-base font-semibold">Google Play</span>
                        </span>
                    </span>
                    <span class="inline-flex items-center gap-3 rounded-xl bg-navy-900 px-4 py-2.5 text-white shadow-md">
                        <svg viewBox="0 0 24 24" class="h-7 w-7" fill="currentColor" aria-hidden="true">
                            <path d="M16.37 12.6c-.02-2.2 1.8-3.26 1.88-3.31-1.03-1.5-2.62-1.7-3.18-1.72-1.35-.14-2.64.8-3.33.8-.69 0-1.74-.78-2.86-.76-1.47.02-2.83.86-3.59 2.17-1.53 2.66-.39 6.6 1.1 8.75.73 1.05 1.6 2.24 2.73 2.2 1.1-.05 1.51-.71 2.84-.71 1.32 0 1.7.71 2.86.69 1.18-.02 1.93-1.07 2.65-2.13.84-1.22 1.18-2.4 1.2-2.46-.03-.01-2.3-.88-2.32-3.52zM14.2 6.13c.6-.73 1.01-1.75.9-2.76-.87.04-1.92.58-2.55 1.31-.56.65-1.05 1.69-.92 2.68.97.08 1.96-.49 2.57-1.23z" />
                        </svg>
                        <span class="leading-tight">
                            <span class="block text-[10px] uppercase tracking-wide text-white/70">Bientôt sur</span>
                            <span class="block text-base font-semibold">App Store</span>
                        </span>
                    </span>
                </div>
            </div>

            <!-- Telephone : maquette de l'accueil de l'app (en-tete bleu, 6 tuiles) -->
            <div class="flex justify-center">
                <div class="relative w-64 rounded-[2.4rem] bg-navy-900 p-2.5 shadow-2xl ring-1 ring-black/10 sm:w-72">
                    <div class="absolute left-1/2 top-3 z-10 h-4 w-20 -translate-x-1/2 rounded-full bg-black/80" />
                    <div class="overflow-hidden rounded-[1.9rem] bg-slate-50">
                        <div class="rounded-b-3xl bg-navy-600 px-4 pb-10 pt-9 text-center">
                            <p class="text-xl font-extrabold tracking-wide text-white">MOKILI <span class="text-gold-500">TOUR</span></p>
                            <p class="mt-0.5 text-[8px] font-bold tracking-[0.2em] text-gold-500">LE MONDE À PORTÉE DE MAIN</p>
                            <p class="mt-4 text-left text-sm font-bold text-white">Bonjour !</p>
                            <p class="text-left text-[10px] text-white/70">Que souhaitez-vous faire aujourd'hui ?</p>
                        </div>
                        <div class="-mt-7 grid grid-cols-3 gap-2 px-3">
                            <div v-for="c in categories.slice(0, 6)" :key="c.slug" class="flex flex-col items-center rounded-xl bg-white py-2.5 shadow-sm">
                                <ServiceIcon :slug="c.slug" class="h-6 w-6" :style="{ color: serviceColor(c.slug) }" />
                                <span class="mt-1 text-[7.5px] font-bold uppercase" :style="{ color: serviceColor(c.slug) }">{{ c.name }}</span>
                            </div>
                        </div>
                        <div class="px-3 pb-3 pt-4">
                            <p class="text-[10px] font-bold text-navy-900">Nos offres du moment</p>
                            <div class="mt-2 flex gap-2">
                                <div class="h-16 flex-1 rounded-lg bg-gradient-to-br from-navy-700 to-navy-900" />
                                <div class="h-16 flex-1 rounded-lg bg-gradient-to-br from-gold-500 to-gold-700" />
                            </div>
                        </div>
                        <div class="flex justify-around border-t border-slate-200 bg-white py-2 text-[7px] font-semibold text-slate-400">
                            <span class="text-navy-900">Accueil</span><span>Réservations</span><span>Favoris</span><span>Profil</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="apropos" class="scroll-mt-24 bg-navy-50/40 px-6 py-16">
        <div class="mx-auto max-w-3xl text-center">
            <h2 class="text-2xl font-bold text-navy-900">À propos de MOKILI TOUR</h2>
            <p class="mt-4 text-navy-700">
                MOKILI TOUR réunit en une seule plateforme tous vos besoins de mobilité et de vie quotidienne :
                voyage, hébergement, location de véhicule, tourisme, marketplace et fret. Une filiale de
                Bilenium Conciergerie, avec des paiements vérifiés par portefeuille Mobile Money.
            </p>
        </div>
    </section>

    <!-- Contact : moyens de contact + formulaire (ouvre la messagerie) -->
    <section id="contact" class="scroll-mt-24 px-6 py-16">
        <div class="mx-auto max-w-6xl">
            <div class="text-center">
                <p class="text-xs font-bold uppercase tracking-widest text-gold-600">Contact</p>
                <h2 class="mt-2 text-3xl font-extrabold text-navy-900">Parlons de votre projet</h2>
                <p class="mt-2 text-navy-700">Une question, un partenariat ? Notre équipe vous répond.</p>
            </div>

            <div class="mt-10 grid gap-6 lg:grid-cols-5">
                <div class="space-y-4 lg:col-span-2">
                    <a :href="`mailto:${CONTACT_EMAIL}`" class="flex items-start gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5 transition hover:ring-gold-500/50">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-navy-900 text-gold-500">
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z" /></svg>
                        </span>
                        <span>
                            <span class="block text-sm font-bold text-navy-900">Écrivez-nous</span>
                            <span class="block break-all text-sm font-semibold text-gold-700">{{ CONTACT_EMAIL }}</span>
                        </span>
                    </a>
                    <Link href="/devenir-partenaire" class="flex items-start gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5 transition hover:ring-gold-500/50">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-navy-900 text-gold-500">
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z" /></svg>
                        </span>
                        <span>
                            <span class="block text-sm font-bold text-navy-900">Devenir partenaire</span>
                            <span class="block text-sm text-slate-600">Hôtels, agences, loueurs, transporteurs : proposez vos services.</span>
                        </span>
                    </Link>
                    <Link href="/annuaire/inscription" class="flex items-start gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-black/5 transition hover:ring-gold-500/50">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-navy-900 text-gold-500">
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z" /></svg>
                        </span>
                        <span>
                            <span class="block text-sm font-bold text-navy-900">Inscrire mon entreprise</span>
                            <span class="block text-sm text-slate-600">Apparaissez gratuitement dans l'annuaire.</span>
                        </span>
                    </Link>
                </div>

                <form class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-black/5 lg:col-span-3" @submit.prevent="sendContact">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-sm">
                            <span class="font-semibold text-navy-900">Nom</span>
                            <input v-model="contact.name" required type="text" class="mt-1 w-full rounded-lg border-slate-300 focus:border-gold-500 focus:ring-gold-500" />
                        </label>
                        <label class="block text-sm">
                            <span class="font-semibold text-navy-900">E-mail</span>
                            <input v-model="contact.email" type="email" class="mt-1 w-full rounded-lg border-slate-300 focus:border-gold-500 focus:ring-gold-500" />
                        </label>
                    </div>
                    <label class="mt-4 block text-sm">
                        <span class="font-semibold text-navy-900">Sujet</span>
                        <select v-model="contact.subject" class="mt-1 w-full rounded-lg border-slate-300 focus:border-gold-500 focus:ring-gold-500">
                            <option>Question</option>
                            <option>Réservation</option>
                            <option>Partenariat</option>
                            <option>Annuaire</option>
                            <option>Autre</option>
                        </select>
                    </label>
                    <label class="mt-4 block text-sm">
                        <span class="font-semibold text-navy-900">Message</span>
                        <textarea v-model="contact.message" required rows="5" class="mt-1 w-full rounded-lg border-slate-300 focus:border-gold-500 focus:ring-gold-500" />
                    </label>
                    <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs text-slate-500">Le message s'ouvre dans votre messagerie, prêt à être envoyé.</p>
                        <button type="submit" class="btn-gold">Envoyer le message</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</template>
