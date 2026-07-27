<script setup>
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { BuildingOfficeIcon, TruckIcon, TicketIcon, ShoppingBagIcon, CubeIcon } from '@heroicons/vue/24/outline';

defineOptions({ layout: PartnerLayout });

const props = defineProps({ counts: { type: Object, required: true } });

const modules = [
    { key: 'logement', label: 'Logement', href: '/partner/logement', icon: BuildingOfficeIcon, color: 'text-green-600', bg: 'bg-green-50' },
    { key: 'voiture', label: 'Voiture', href: '/partner/voiture', icon: TruckIcon, color: 'text-orange-600', bg: 'bg-orange-50' },
    { key: 'divertissement', label: 'Divertissement', href: '/partner/divertissement', icon: TicketIcon, color: 'text-purple-600', bg: 'bg-purple-50' },
    { key: 'marketplace', label: 'Marketplace', href: '/partner/marketplace', icon: ShoppingBagIcon, color: 'text-pink-600', bg: 'bg-pink-50' },
    { key: 'fret', label: 'Fret', href: '/partner/fret', icon: CubeIcon, color: 'text-[#0972D3]', bg: 'bg-blue-50' },
];

const totalListings = () => modules.reduce((sum, m) => sum + Number(props.counts[m.key] ?? 0), 0);
</script>

<template>
    <Head title="Tableau de bord partenaire" />

    <div class="mb-6 flex items-end justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Bienvenue dans votre espace partenaire</h1>
            <p class="text-sm text-slate-500">Gerez vos annonces sur les 5 modules ouverts aux partenaires.</p>
        </div>
        <div class="console-stat !min-w-[140px] text-right">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total annonces</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ totalListings() }}</p>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        <Link v-for="m in modules" :key="m.key" :href="m.href" class="console-panel group transition hover:border-[#0972D3] hover:shadow-md">
            <div :class="['inline-flex h-10 w-10 items-center justify-center rounded-lg', m.bg]">
                <component :is="m.icon" :class="['h-5 w-5', m.color]" />
            </div>
            <p class="mt-3 text-2xl font-bold text-slate-900">{{ counts[m.key] }}</p>
            <p class="mt-1 text-sm text-slate-500">Annonce(s) {{ m.label }}</p>
            <p class="mt-3 text-sm font-semibold text-[#0972D3] opacity-0 transition group-hover:opacity-100">Gerer &rarr;</p>
        </Link>
    </div>

    <div class="mt-6 console-panel">
        <h2 class="font-semibold text-slate-900">Bon a savoir</h2>
        <ul class="mt-3 space-y-2 text-sm text-slate-600">
            <li>&bull; Ajoutez une photo a chaque annonce : elle s'affichera automatiquement cote client.</li>
            <li>&bull; Les paiements clients sont verifies via Peex (mobile money) ou carte bancaire avant confirmation.</li>
            <li>&bull; Toute nouvelle annonce est visible immediatement sur le site public une fois enregistree.</li>
        </ul>
    </div>
</template>
