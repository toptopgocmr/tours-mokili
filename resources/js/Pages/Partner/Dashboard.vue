<script setup>
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: PartnerLayout });

const props = defineProps({ counts: { type: Object, required: true } });

const modules = [
    { key: 'logement', label: 'Logement', href: '/partner/logement' },
    { key: 'voiture', label: 'Voiture', href: '/partner/voiture' },
    { key: 'divertissement', label: 'Divertissement', href: '/partner/divertissement' },
    { key: 'marketplace', label: 'Marketplace', href: '/partner/marketplace' },
];
</script>

<template>
    <Head title="Tableau de bord partenaire" />

    <h1 class="text-2xl font-bold text-navy-900">Bienvenue dans votre espace partenaire</h1>
    <p class="mt-1 text-sm text-navy-500">Gerez vos annonces sur les 4 modules ouverts aux partenaires.</p>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Link v-for="m in modules" :key="m.key" :href="m.href" class="rounded-2xl border bg-white p-5 transition hover:shadow-md">
            <p class="text-2xl font-bold text-navy-900">{{ counts[m.key] }}</p>
            <p class="mt-1 text-sm text-navy-500">Annonce(s) {{ m.label }}</p>
            <p class="mt-3 text-sm font-semibold text-gold-600">Gerer →</p>
        </Link>
    </div>
</template>
