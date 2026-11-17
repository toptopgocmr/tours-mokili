<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import LogoMark from '@/Components/LogoMark.vue';
import ServiceIcon from '@/Components/ServiceIcon.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const categories = computed(() => page.props.services ?? []);
const flash = computed(() => page.props.flash ?? {});

// Menu telephone (bouton ☰) : se ferme a chaque changement de page.
const menuOpen = ref(false);
watch(() => page.url, () => (menuOpen.value = false));
router.on('navigate', () => (menuOpen.value = false));

// Staff (admin/agent) and partners have their own space, linked from
// the public site's navbar so they can jump back into their back-office.
const spaceLink = computed(() => {
    if (!user.value) return null;
    if (['admin', 'agent'].includes(user.value.role)) return { href: '/admin', label: 'Back-office' };
    if (user.value.role === 'partner') return { href: '/partner', label: 'Espace partenaire' };
    return null;
});

// Fixed nav subtitles matching the marketing mockup's top bar.
const navLinks = [
    { href: '/', label: 'Accueil' },
    { href: '/#services', label: 'Services' },
    { href: '/voyage', label: 'Offres' },
    { href: '/#apropos', label: 'À propos' },
    { href: '/annuaire', label: 'Annuaire' },
    { href: '/#contact', label: 'Contact' },
];
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <header class="sticky top-0 z-40 border-b bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-2.5 sm:px-6 sm:py-3">
                <Link href="/" class="shrink-0">
                    <LogoMark />
                </Link>

                <nav class="hidden items-center gap-6 text-sm font-medium text-navy-700 md:flex">
                    <Link v-for="link in navLinks" :key="link.label" :href="link.href" class="hover:text-gold-600">
                        {{ link.label }}
                    </Link>
                </nav>

                <div class="flex items-center gap-2 sm:gap-3">
                    <template v-if="user">
                        <span class="hidden text-sm text-navy-700 lg:inline">Bonjour, {{ user.name }}</span>
                        <Link href="/voyage/vols/mes-billets" class="hidden text-sm font-medium text-navy-700 hover:text-gold-600 lg:inline">Mes billets</Link>
                        <span class="hidden items-center gap-3 md:flex">
                            <Link v-if="spaceLink" :href="spaceLink.href" class="btn-outline !px-4 !py-2 text-sm">
                                {{ spaceLink.label }}
                            </Link>
                            <Link href="/logout" method="post" as="button" class="btn-outline !px-4 !py-2 text-sm">
                                Deconnexion
                            </Link>
                        </span>
                    </template>
                    <template v-else>
                        <Link href="/login" class="btn-outline !px-3 !py-1.5 text-sm sm:!px-4 sm:!py-2">Connexion</Link>
                        <span class="hidden sm:flex">
                            <Link href="/register" class="btn-gold !px-4 !py-2 text-sm">Creer un compte</Link>
                        </span>
                    </template>

                    <!-- Bouton menu (telephone / tablette) -->
                    <button
                        type="button"
                        class="flex h-10 w-10 items-center justify-center rounded-lg border border-navy-200 text-navy-800 md:hidden"
                        :aria-expanded="menuOpen"
                        aria-label="Menu"
                        @click="menuOpen = !menuOpen"
                    >
                        <svg v-if="!menuOpen" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                        <svg v-else class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" /></svg>
                    </button>
                </div>
            </div>

            <!-- Menu deroulant telephone -->
            <div v-if="menuOpen" class="max-h-[calc(100vh-60px)] overflow-y-auto border-t bg-white px-4 pb-5 pt-3 shadow-lg md:hidden">
                <p v-if="user" class="mb-2 text-sm text-slate-500">Bonjour, <b class="text-navy-900">{{ user.name }}</b></p>
                <nav class="grid gap-1">
                    <Link v-for="link in navLinks" :key="link.label" :href="link.href" class="rounded-lg px-3 py-2.5 font-semibold text-navy-800 hover:bg-navy-50">
                        {{ link.label }}
                    </Link>
                </nav>
                <p class="mb-1 mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Nos services</p>
                <div class="grid grid-cols-2 gap-1">
                    <Link
                        v-for="c in categories"
                        :key="c.slug"
                        :href="c.slug === 'divertissement' ? '/tourisme' : `/${c.slug}`"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-navy-800 hover:bg-navy-50"
                    >
                        <ServiceIcon :slug="c.slug" class="h-5 w-5" :style="{ color: c.color }" />
                        {{ c.name }}
                    </Link>
                </div>
                <div class="mt-4 grid gap-2 border-t pt-4">
                    <template v-if="user">
                        <Link href="/voyage/vols/mes-billets" class="btn-outline w-full justify-center !py-2.5 text-sm">Mes billets</Link>
                        <Link v-if="spaceLink" :href="spaceLink.href" class="btn-outline w-full justify-center !py-2.5 text-sm">{{ spaceLink.label }}</Link>
                        <Link href="/logout" method="post" as="button" class="btn-outline w-full justify-center !py-2.5 text-sm">Deconnexion</Link>
                    </template>
                    <template v-else>
                        <Link href="/register" class="btn-gold w-full justify-center !py-2.5 text-sm">Creer un compte</Link>
                        <Link href="/devenir-partenaire" class="text-center text-sm font-semibold text-gold-600">Devenir partenaire</Link>
                    </template>
                </div>
            </div>
        </header>

        <div v-if="flash.success" class="bg-green-50 px-6 py-2 text-center text-sm text-green-700">
            {{ flash.success }}
        </div>
        <div v-if="flash.error" class="bg-red-50 px-6 py-2 text-center text-sm text-red-700">
            {{ flash.error }}
        </div>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="mt-16 bg-navy-900 py-10 text-navy-50">
            <div class="mx-auto max-w-7xl px-6">
                <div class="grid gap-8 md:grid-cols-4">
                    <div>
                        <LogoMark variant="light" />
                        <p class="mt-3 text-sm text-navy-50/70">Le monde a portee de main.</p>
                        <p class="mt-4 text-xs text-navy-50/50">Une filiale de Bilenium Conciergerie</p>
                    </div>
                    <div>
                        <p class="mb-2 font-semibold text-gold-600">Services</p>
                        <ul class="space-y-2 text-sm text-navy-50/80">
                            <li v-for="c in categories" :key="c.slug">
                                <Link :href="c.slug === 'divertissement' ? '/tourisme' : `/${c.slug}`" class="flex items-center gap-2 hover:text-white">
                                    <ServiceIcon :slug="c.slug" class="h-4 w-4" accent="#050F21" />
                                    {{ c.name }}
                                </Link>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <p class="mb-2 font-semibold text-gold-600">Support</p>
                        <ul class="space-y-1 text-sm text-navy-50/80">
                            <li>Assistance 24/7</li>
                            <li>Paiement securise par Mobile Money</li>
                        </ul>
                    </div>
                    <div>
                        <p class="mb-2 font-semibold text-gold-600">Applications</p>
                        <p class="text-sm text-navy-50/80">Disponible sur Google Play et l'App Store</p>
                    </div>
                </div>
                <div class="mt-8 flex flex-col items-center justify-between gap-3 border-t border-white/10 pt-6 text-xs text-navy-50/50 sm:flex-row">
                    <p>&copy; {{ new Date().getFullYear() }} MOKILI TOUR. Tous droits reserves.</p>
                    <p class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1">
                        <span class="text-navy-50/30">Acces professionnel :</span>
                        <Link href="/partner/login" class="hover:text-gold-600">Espace partenaire</Link>
                        <Link href="/devenir-partenaire" class="font-semibold text-gold-500 hover:text-gold-400">Devenir partenaire</Link>
                        <Link href="/admin/login" class="hover:text-gold-600">Administration</Link>
                    </p>
                </div>
                <p class="mt-4 text-center text-xs text-navy-50/40">
                    MOKILI TOUR a ete concu avec 💔 par Basile Marius NGASSAKI
                </p>
            </div>
        </footer>
    </div>
</template>
