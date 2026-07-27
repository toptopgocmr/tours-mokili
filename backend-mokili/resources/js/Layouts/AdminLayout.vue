<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import LogoMark from '@/Components/LogoMark.vue';
import {
    HomeIcon,
    PaperAirplaneIcon,
    CalendarDaysIcon,
    UsersIcon,
    CreditCardIcon,
    ClipboardDocumentCheckIcon,
    MagnifyingGlassIcon,
    BellIcon,
    ArrowRightOnRectangleIcon,
} from '@heroicons/vue/24/outline';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const flash = computed(() => page.props.flash ?? {});
const currentUrl = computed(() => page.url);

const links = [
    { href: '/admin', label: 'Tableau de bord', icon: HomeIcon },
    { href: '/admin/voyage', label: 'Offres Voyage', icon: PaperAirplaneIcon },
    { href: '/admin/reservations', label: 'Reservations', icon: CalendarDaysIcon },
    { href: '/admin/paiements', label: 'Paiements', icon: CreditCardIcon },
    { href: '/admin/moderation', label: 'Moderation annonces', icon: ClipboardDocumentCheckIcon },
    { href: '/admin/utilisateurs', label: 'Agents & Partenaires', icon: UsersIcon },
];

const isActive = (href) => currentUrl.value === href || (href !== '/admin' && currentUrl.value.startsWith(href));

const breadcrumb = computed(() => {
    const active = links.find((l) => isActive(l.href));
    return active?.label ?? 'Tableau de bord';
});
</script>

<template>
    <div class="min-h-screen bg-[#F2F3F3]">
        <!-- Top bar -->
        <header class="flex h-14 items-center justify-between bg-[#0F1B2A] px-4 text-white">
            <div class="flex items-center gap-3">
                <Link href="/admin" class="flex items-center gap-2 font-bold">
                    <LogoMark variant="light" size="h-7" />
                    <span class="hidden sm:inline">MOKILI TOUR <span class="font-normal text-white/50">| Back-office</span></span>
                </Link>
            </div>

            <div class="hidden max-w-md flex-1 items-center gap-2 rounded border border-white/20 bg-white/5 px-3 py-1.5 text-sm text-white/60 md:flex">
                <MagnifyingGlassIcon class="h-4 w-4" />
                <span>Rechercher (reservations, offres, utilisateurs...)</span>
            </div>

            <div class="flex items-center gap-4 text-sm">
                <BellIcon class="h-5 w-5 text-white/70" />
                <div class="hidden text-right sm:block">
                    <p class="font-medium leading-tight">{{ user?.name }}</p>
                    <p class="text-xs capitalize leading-tight text-white/50">{{ user?.role }}</p>
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
                    <Link
                        v-for="link in links"
                        :key="link.href"
                        :href="link.href"
                        class="console-nav-link"
                        :class="{ 'console-nav-link-active': isActive(link.href) }"
                    >
                        <component :is="link.icon" class="h-5 w-5" />
                        {{ link.label }}
                    </Link>
                </nav>
            </aside>

            <!-- Content -->
            <div class="min-w-0 flex-1">
                <div class="border-b border-slate-200 bg-white px-6 py-2 text-xs text-slate-500">
                    Back-office <span class="mx-1.5 text-slate-300">/</span> <span class="font-medium text-slate-700">{{ breadcrumb }}</span>
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
