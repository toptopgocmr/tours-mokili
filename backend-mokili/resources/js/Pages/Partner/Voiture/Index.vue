<script setup>
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineOptions({ layout: PartnerLayout });
defineProps({ vehicles: { type: Object, required: true } });

const remove = (item) => {
    if (confirm(`Supprimer "${item.title}" ?`)) router.delete(`/partner/voiture/${item.id}`);
};
</script>

<template>
    <Head title="Mes vehicules" />
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-navy-900">Mes vehicules</h1>
        <Link href="/partner/voiture/creer" class="btn-gold !py-2 text-sm">+ Ajouter un vehicule</Link>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-navy-50 text-navy-500">
                <tr><th class="px-4 py-3">Titre</th><th class="px-4 py-3">Marque/Modele</th><th class="px-4 py-3">Prix/jour</th><th class="px-4 py-3">Statut</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody>
                <tr v-for="v in vehicles.data" :key="v.id" class="border-t">
                    <td class="px-4 py-3 font-medium">{{ v.title }}</td>
                    <td class="px-4 py-3">{{ v.brand }} {{ v.model }}</td>
                    <td class="px-4 py-3">{{ Number(v.price_per_day).toLocaleString('fr-FR') }} {{ v.currency }}</td>
                    <td class="px-4 py-3"><span :class="v.is_active ? 'text-green-700' : 'text-gray-400'">{{ v.is_active ? 'Actif' : 'Inactif' }}</span></td>
                    <td class="space-x-3 px-4 py-3 text-right">
                        <Link :href="`/partner/voiture/${v.id}/editer`" class="font-semibold text-gold-600">Editer</Link>
                        <button class="font-semibold text-red-600" @click="remove(v)">Supprimer</button>
                    </td>
                </tr>
                <tr v-if="!vehicles.data.length"><td colspan="5" class="px-4 py-6 text-center text-navy-400">Aucun vehicule publie.</td></tr>
            </tbody>
        </table>
    </div>
</template>
