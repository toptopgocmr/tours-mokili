<script setup>
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineOptions({ layout: PartnerLayout });

defineProps({ listings: { type: Object, required: true } });

const remove = (item) => {
    if (confirm(`Supprimer "${item.title}" ?`)) router.delete(`/partner/logement/${item.id}`);
};
</script>

<template>
    <Head title="Mes logements" />

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-navy-900">Mes logements</h1>
        <Link href="/partner/logement/creer" class="btn-gold !py-2 text-sm">+ Ajouter un logement</Link>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-navy-50 text-navy-500">
                <tr><th class="px-4 py-3">Titre</th><th class="px-4 py-3">Ville</th><th class="px-4 py-3">Prix/nuit</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody>
                <tr v-for="l in listings.data" :key="l.id" class="border-t">
                    <td class="px-4 py-3 font-medium">{{ l.title }}</td>
                    <td class="px-4 py-3">{{ l.city }}</td>
                    <td class="px-4 py-3">{{ Number(l.price_per_night).toLocaleString('fr-FR') }} {{ l.currency }}</td>
                    <td class="px-4 py-3"><span :class="l.is_active ? 'text-green-700' : 'text-gray-400'">{{ l.is_active ? 'Actif' : 'Inactif' }}</span></td>
                    <td class="space-x-3 px-4 py-3 text-right">
                        <Link :href="`/partner/logement/${l.id}/editer`" class="font-semibold text-gold-600">Editer</Link>
                        <button class="font-semibold text-red-600" @click="remove(l)">Supprimer</button>
                    </td>
                </tr>
                <tr v-if="!listings.data.length"><td colspan="5" class="px-4 py-6 text-center text-navy-400">Aucun logement publie.</td></tr>
            </tbody>
        </table>
    </div>
</template>
