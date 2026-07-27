<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import ServiceIcon from '@/Components/ServiceIcon.vue';
import CoastalScene from '@/Components/CoastalScene.vue';
import DestinationImage from '@/Components/DestinationImage.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: MainLayout });

defineProps({
    categories: { type: Array, default: () => [] },
    featuredOffers: { type: Array, default: () => [] },
});

// Same real-photo-first pattern as DestinationImage.vue, for the
// single hero banner: drop public/images/hero.jpg to replace the
// illustration, no code change needed.
const heroPhotoFailed = ref(false);

const features = [
    { title: 'Securite', text: 'Vos donnees sont protegees', icon: '🛡️' },
    { title: 'Support 24/7', text: 'Notre equipe est a votre ecoute', icon: '🎧' },
    { title: 'Qualite', text: 'Des partenaires fiables et selectionnes', icon: '🏅' },
    { title: 'Accessibilite', text: 'Partout, tout le temps, sur tous vos appareils', icon: '🌍' },
];
</script>

<template>
    <Head title="Accueil" />

    <!-- Hero visual: bounded height so the background never has to
         stretch across an unpredictable amount of content (that's what
         caused the earlier cropping bug). Text sits left, over open
         sea/sky. Service tiles no longer float on top of the photo here
         (they used to straddle the bottom edge, which put them over an
         unpredictable mix of photo/gradient) - they now live in their
         own solid navy band right below, matching the client's mockup. -->
    <section class="relative h-[360px] overflow-hidden sm:h-[420px] md:h-[480px]">
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
        <div class="relative flex h-full max-w-2xl flex-col justify-start px-6 pt-16 text-left sm:px-10 sm:pt-20 md:px-16 md:pt-24">
            <p class="text-sm font-semibold uppercase tracking-widest text-gold-600">MOKILI TOUR</p>
            <h1 class="mt-2 text-3xl font-extrabold text-white drop-shadow sm:text-4xl md:text-5xl">
                LE MONDE À <span class="text-gold-600">PORTÉE</span> DE MAIN
            </h1>
            <p class="mt-2 max-w-lg text-base text-white/90 drop-shadow sm:text-lg">
                Votre compagnon de confiance pour tous vos besoins, partout dans le monde.
            </p>

            <form class="mt-6 flex w-full max-w-xl overflow-hidden rounded-full bg-white shadow-lg">
                <input
                    type="text"
                    placeholder="Rechercher une destination, un service..."
                    class="flex-1 border-0 px-5 py-3 text-sm focus:ring-0"
                />
                <button type="submit" class="btn-gold rounded-none px-8">Rechercher</button>
            </form>
        </div>
    </section>

    <!-- Service strip: solid navy band under the hero photo, matching
         the mockup's "fond" (background) instead of tiles floating over
         a busy photo. Icons render in white here (rather than each
         category's own brand color) since Voyage's brand color is the
         same navy as this background - white keeps every icon legible
         and consistent, brand colors stay intact everywhere else. -->
    <section id="services" class="scroll-mt-24 bg-navy-900 py-10">
        <div class="mx-auto grid max-w-5xl grid-cols-3 gap-3 px-6 sm:grid-cols-6 sm:gap-4">
            <Link
                v-for="c in categories"
                :key="c.slug"
                :href="`/${c.slug}`"
                class="flex flex-col items-center gap-2 rounded-2xl p-3 text-center transition hover:bg-white/10"
            >
                <ServiceIcon :slug="c.slug" class="h-12 w-12 text-white" accent="#050F21" />
                <span class="text-xs font-semibold uppercase text-white">{{ c.name }}</span>
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
                        A partir de {{ Number(offer.price).toLocaleString('fr-FR') }} {{ offer.currency }}
                        <span v-if="offer.discount_percent" class="ml-2 rounded-full bg-gold-50 px-2 py-0.5 text-xs font-semibold text-gold-600">
                            -{{ offer.discount_percent }}%
                        </span>
                    </p>
                </div>
            </Link>
        </div>
    </section>

    <section class="bg-navy-900 py-14 text-white">
        <div class="mx-auto grid max-w-7xl grid-cols-2 gap-8 px-6 md:grid-cols-4">
            <div v-for="f in features" :key="f.title" class="text-center">
                <div class="text-3xl">{{ f.icon }}</div>
                <p class="mt-2 font-semibold text-gold-600">{{ f.title }}</p>
                <p class="mt-1 text-xs text-navy-50/70">{{ f.text }}</p>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-5xl px-6 py-16 text-center">
        <h2 class="text-2xl font-bold text-navy-900">Decouvrez notre application mobile</h2>
        <p class="mt-2 text-navy-700">Simple, rapide et securisee. Reservation en quelques clics, paiement securise via Peex.</p>
        <div class="mt-6 flex justify-center gap-4">
            <span class="btn-outline">Google Play</span>
            <span class="btn-outline">App Store</span>
        </div>
    </section>

    <section id="apropos" class="scroll-mt-24 bg-navy-50/40 px-6 py-16">
        <div class="mx-auto max-w-3xl text-center">
            <h2 class="text-2xl font-bold text-navy-900">À propos de MOKILI TOUR</h2>
            <p class="mt-4 text-navy-700">
                MOKILI TOUR reunit en une seule plateforme tous vos besoins de mobilite et de vie quotidienne :
                voyage, logement, location de vehicule, divertissement, marketplace et fret. Une filiale de
                Bilenium Conciergerie, avec des paiements verifies par portefeuille mobile money via Peex.
            </p>
        </div>
    </section>

    <section id="contact" class="scroll-mt-24 px-6 py-16 text-center">
        <h2 class="text-2xl font-bold text-navy-900">Contact</h2>
        <p class="mt-3 text-navy-700">Une question, un partenariat ? Notre equipe vous repond.</p>
        <p class="mt-4 text-sm font-semibold text-gold-600">contact@mokilitour.com</p>
    </section>
</template>
