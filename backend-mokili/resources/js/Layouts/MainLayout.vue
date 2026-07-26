<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import LogoMark from '@/Components/LogoMark.vue';
import ServiceIcon from '@/Components/ServiceIcon.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const categories = computed(() => page.props.services ?? []);
const flash = computed(() => page.props.flash ?? {});

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
    { href: '/#contact', label: 'Contact' },
];
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <header class="sticky top-0 z-40 border-b bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-3">
                <Link href="/">
                    <LogoMark />
                </Link>

                <nav class="hidden items-center gap-6 text-sm font-medium text-navy-700 md:flex">
                    <Link v-for="link in navLinks" :key="link.label" :href="link.href" class="hover:text-gold-600">
                        {{ link.label }}
                    </Link>
                </nav>

                <div class="flex items-center gap-3">
                    <template v-if="user">
                        <span class="hidden text-sm text-navy-700 sm:inline">Bonjour, {{ user.name }}</span>
                        <Link v-if="spaceLink" :href="spaceLink.href" class="btn-outline !px-4 !py-2 text-sm">
                            {{ spaceLink.label }}
                        </Link>
                        <Link href="/logout" method="post" as="button" class="btn-outline !px-4 !py-2 text-sm">
                            Deconnexion
                        </Link>
                    </template>
                    <template v-else>
                        <Link href="/login" class="btn-outline !px-4 !py-2 text-sm">Connexion</Link>
                        <Link href="/register" class="btn-gold !px-4 !py-2 text-sm">Creer un compte</Link>
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
                                <Link :href="`/${c.slug}`" class="flex items-center gap-2 hover:text-white">
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
                            <li>Paiement securise via Peex</li>
                        </ul>
                    </div>
                    <div>
                        <p class="mb-2 font-semibold text-gold-600">Applications</p>
                        <p class="text-sm text-navy-50/80">Disponible sur Google Play et l'App Store</p>
                    </div>
                </div>
                <div class="mt-8 flex flex-col items-center justify-between gap-3 border-t border-white/10 pt-6 text-xs text-navy-50/50 sm:flex-row">
                    <p>&copy; {{ new Date().getFullYear() }} MOKILI TOUR. Tous droits reserves.</p>
                    <p class="space-x-4">
                        <span class="text-navy-50/30">Acces professionnel :</span>
                        <Link href="/partner/login" class="hover:text-gold-600">Espace partenaire</Link>
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
