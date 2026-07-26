<script setup>
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineOptions({ layout: PartnerLayout });
defineProps({ vehicles: { type: Object, required: true } });

const remove = (item) => {
    if (confirm(`Supprimer "${item.title}" ?`)) router.delete(`/partner/voiture/${item.id}`);
};

const statusBadge = (status) => ({
    draft: 'console-badge console-badge-neutral',
    pending: 'console-badge console-badge-pending',
    published: 'console-badge console-badge-success',
    rejected: 'console-badge console-badge-error',
}[status] ?? 'console-badge console-badge-neutral');

const statusLabel = (status) => ({
    draft: 'Brouillon', pending: 'En attente', published: 'Publie', rejected: 'Rejete',
}[status] ?? 'Brouillon');
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
                    <td><span :class="statusBadge(v.status)">{{ statusLabel(v.status) }}</span></td>
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
