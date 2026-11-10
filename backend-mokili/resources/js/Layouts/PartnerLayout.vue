<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import LogoMark from '@/Components/LogoMark.vue';
import {
    HomeIcon,
    BuildingOfficeIcon,
    TruckIcon,
    TicketIcon,
    ShoppingBagIcon,
    CubeIcon,
    MagnifyingGlassIcon,
    BellIcon,
    MapIcon,
    PaperAirplaneIcon,
    ReceiptPercentIcon,
    ClipboardDocumentListIcon,
    BuildingStorefrontIcon,
    WrenchScrewdriverIcon,
    SparklesIcon,
    ArrowRightOnRectangleIcon,
} from '@heroicons/vue/24/outline';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const flash = computed(() => page.props.flash ?? {});
const currentUrl = computed(() => page.url);

// Menu : ventes (trajets, vols, promotions, reservations) puis autres rubriques.
const links = [
    { href: '/partner', label: 'Tableau de bord', icon: HomeIcon },
    { href: '/partner/reservations', label: 'Reservations', icon: ClipboardDocumentListIcon },
    { href: '/partner/promotions', label: 'Promotions', icon: ReceiptPercentIcon },
    { section: 'Transport & billets' },
    { href: '/partner/transport', label: 'Trajets & horaires', icon: MapIcon },
    { href: '/partner/transport?vue=vols', label: 'Offres vols', icon: PaperAirplaneIcon },

    { section: 'Hebergement, voitures, loisirs' },
    { href: '/partner/logement', label: 'Hotels & logements', icon: BuildingOfficeIcon },
    { href: '/partner/voiture', label: 'Voitures & garage', icon: WrenchScrewdriverIcon },
    { href: '/partner/divertissement', label: 'Concerts, cinema...', icon: TicketIcon },
    { href: '/partner/parcs', label: "Parcs d'attractions", icon: SparklesIcon },
    { section: 'Autres rubriques' },
    { href: '/partner/marketplace', label: 'Marketplace', icon: ShoppingBagIcon },
    { href: '/partner/fret', label: 'Fret', icon: CubeIcon },
    { section: 'Compte' },
    { href: '/partner/entreprise', label: 'Mon entreprise', icon: BuildingStorefrontIcon },
];
const navLinks = links.filter((l) => l.href);

const isActive = (href) => {
    const url = currentUrl.value;
    if (href === '/partner') return url === '/partner';
    if (href.includes('?')) return url.startsWith(href);
    if (href === '/partner/transport') return url.startsWith('/partner/transport') && !url.includes('vue=vols');
    return url.startsWith(href);
};

const breadcrumb = computed(() => {
    const active = navLinks.find((l) => isActive(l.href));
    return active?.label ?? 'Tableau de bord';
});
</script>

<template>
    <div class="min-h-screen bg-[#F2F3F3]">
        <!-- Top bar -->
        <header class="flex h-14 items-center justify-between bg-[#0F1B2A] px-4 text-white">
            <div class="flex items-center gap-3">
                <Link href="/partner" class="flex items-center gap-2 font-bold">
                    <LogoMark variant="light" size="h-7" />
                    <span class="hidden sm:inline">MOKILI TOUR <span class="font-normal text-white/50">| Espace partenaire</span></span>
                </Link>
            </div>

            <div class="hidden max-w-md flex-1 items-center gap-2 rounded border border-white/20 bg-white/5 px-3 py-1.5 text-sm text-white/60 md:flex">
                <MagnifyingGlassIcon class="h-4 w-4" />
                <span>Rechercher (annonces...)</span>
            </div>

            <div class="flex items-center gap-4 text-sm">
                <BellIcon class="h-5 w-5 text-white/70" />
                <div class="hidden text-right sm:block">
                    <p class="font-medium leading-tight">{{ user?.name }}</p>
                    <p class="text-xs leading-tight text-white/50">Partenaire</p>
                </div>
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="flex items-center gap-1.5 rounded border border-white/20 px-2.5 py-1.5 text-xs font-medium text-white/80 hover:bg-white/10"
                >
                    <ArrowRightOnRectangleIcon class="h-4 w-4" />
                    Deconnexion
                </Link>
            </div>
        </header>

        <div class="flex">
            <!-- Sidebar -->
            <aside class="hidden w-60 shrink-0 border-r border-slate-200 bg-white md:block">
                <nav class="space-y-0.5 p-3">
                    <template v-for="(link, i) in links" :key="link.href ?? `s${i}`">
                        <p v-if="link.section" class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ link.section }}</p>
                        <Link
                            v-else
                            :href="link.href"
                            class="console-nav-link"
                            :class="{ 'console-nav-link-active': isActive(link.href) }"
                        >
                            <component :is="link.icon" class="h-5 w-5" />
                            {{ link.label }}
                        </Link>
                    </template>
                </nav>
            </aside>

            <!-- Content -->
            <div class="min-w-0 flex-1">
                <!-- Menu mobile (le menu lateral est masque sur petit ecran) -->
                <nav class="flex gap-1 overflow-x-auto border-b border-slate-200 bg-white px-3 py-2 md:hidden">
                    <Link
                        v-for="link in navLinks"
                        :key="link.href"
                        :href="link.href"
                        class="flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold"
                        :class="isActive(link.href) ? 'bg-[#0F1B2A] text-white' : 'bg-slate-100 text-slate-700'"
                    >
                        <component :is="link.icon" class="h-4 w-4" />{{ link.label }}
                    </Link>
                </nav>
                <div class="border-b border-slate-200 bg-white px-6 py-2 text-xs text-slate-500">
                    Espace partenaire <span class="mx-1.5 text-slate-300">/</span> <span class="font-medium text-slate-700">{{ breadcrumb }}</span>
                </div>

                <div v-if="flash.success" class="border-b border-green-100 bg-green-50 px-6 py-2 text-sm text-green-700">{{ flash.success }}</div>
                <div v-if="flash.error" class="border-b border-red-100 bg-red-50 px-6 py-2 text-sm text-red-700">{{ flash.error }}</div>

                <main class="p-6">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
