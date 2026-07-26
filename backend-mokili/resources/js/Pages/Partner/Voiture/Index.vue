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
    <div class="mb-5 flex items-center justify-between">
        <h1 class="text-xl font-bold text-slate-900">Mes vehicules</h1>
        <Link href="/partner/voiture/creer" class="btn-console-primary">+ Ajouter un vehicule</Link>
    </div>

    <div class="console-table-wrap">
        <table class="console-table">
            <thead>
                <tr><th>Titre</th><th>Marque/Modele</th><th>Prix/jour</th><th>Statut</th><th></th></tr>
            </thead>
            <tbody>
                <tr v-for="v in vehicles.data" :key="v.id">
                    <td class="font-medium text-slate-900">{{ v.title }}</td>
                    <td>{{ v.brand }} {{ v.model }}</td>
                    <td>{{ Number(v.price_per_day).toLocaleString('fr-FR') }} {{ v.currency }}</td>
                    <td><span :class="v.is_active ? 'console-badge console-badge-success' : 'console-badge console-badge-neutral'">{{ v.is_active ? 'Actif' : 'Inactif' }}</span></td>
                    <td class="space-x-3 text-right">
                        <Link :href="`/partner/voiture/${v.id}/editer`" class="font-semibold text-[#0972D3] hover:underline">Editer</Link>
                        <button class="font-semibold text-red-600 hover:underline" @click="remove(v)">Supprimer</button>
                    </td>
                </tr>
                <tr v-if="!vehicles.data.length"><td colspan="5" class="py-6 text-center text-slate-400">Aucun vehicule publie.</td></tr>
            </tbody>
        </table>
    </div>
</template>
