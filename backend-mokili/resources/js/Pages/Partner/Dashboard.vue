<script setup>
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { BuildingOfficeIcon, TruckIcon, TicketIcon, ShoppingBagIcon } from '@heroicons/vue/24/outline';

defineOptions({ layout: PartnerLayout });

const props = defineProps({ counts: { type: Object, required: true } });

const modules = [
    { key: 'logement', label: 'Logement', href: '/partner/logement', icon: BuildingOfficeIcon },
    { key: 'voiture', label: 'Voiture', href: '/partner/voiture', icon: TruckIcon },
    { key: 'divertissement', label: 'Divertissement', href: '/partner/divertissement', icon: TicketIcon },
    { key: 'marketplace', label: 'Marketplace', href: '/partner/marketplace', icon: ShoppingBagIcon },
];
</script>

<template>
    <Head title="Tableau de bord partenaire" />

    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-900">Bienvenue dans votre espace partenaire</h1>
        <p class="text-sm text-slate-500">Gerez vos annonces sur les 4 modules ouverts aux partenaires.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Link v-for="m in modules" :key="m.key" :href="m.href" class="console-panel transition hover:border-[#0972D3]">
            <component :is="m.icon" class="h-6 w-6 text-[#0972D3]" />
            <p class="mt-3 text-2xl font-bold text-slate-900">{{ counts[m.key] }}</p>
            <p class="mt-1 text-sm text-slate-500">Annonce(s) {{ m.label }}</p>
            <p class="mt-3 text-sm font-semibold text-[#0972D3]">Gerer &rarr;</p>
        </Link>
    </div>
</template>
