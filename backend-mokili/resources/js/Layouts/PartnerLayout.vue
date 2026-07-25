<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const flash = computed(() => page.props.flash ?? {});

const links = [
    { href: '/partner', label: 'Tableau de bord' },
    { href: '/partner/logement', label: 'Logement' },
    { href: '/partner/voiture', label: 'Voiture' },
    { href: '/partner/divertissement', label: 'Divertissement' },
    { href: '/partner/marketplace', label: 'Marketplace' },
];
</script>

<template>
    <div class="flex min-h-screen bg-navy-50/40">
        <aside class="hidden w-64 flex-col bg-navy-900 text-white md:flex">
            <div class="px-6 py-5 text-lg font-bold">
                <span class="text-gold-600">MOKILI</span> TOUR
                <div class="text-xs font-normal text-navy-50/60">Espace partenaire</div>
            </div>
            <nav class="flex-1 space-y-1 px-3">
                <Link
                    v-for="link in links"
                    :key="link.href"
                    :href="link.href"
                    class="block rounded-lg px-3 py-2 text-sm font-medium text-navy-50/80 hover:bg-white/10 hover:text-white"
                >
                    {{ link.label }}
                </Link>
            </nav>
            <div class="border-t border-white/10 p-4 text-xs text-navy-50/60">
                Connecte : {{ user?.name }}
                <Link href="/logout" method="post" as="button" class="mt-2 block text-gold-600">Deconnexion</Link>
            </div>
        </aside>

        <div class="flex-1">
            <div v-if="flash.success" class="bg-green-50 px-6 py-2 text-sm text-green-700">{{ flash.success }}</div>
            <div v-if="flash.error" class="bg-red-50 px-6 py-2 text-sm text-red-700">{{ flash.error }}</div>

            <main class="p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
