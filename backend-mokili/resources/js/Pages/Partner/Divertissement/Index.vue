<script setup>
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineOptions({ layout: PartnerLayout });
defineProps({ events: { type: Object, required: true } });

const remove = (item) => {
    if (confirm(`Supprimer "${item.title}" ?`)) router.delete(`/partner/divertissement/${item.id}`);
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
    <Head title="Mes evenements" />
    <div class="mb-5 flex items-center justify-between">
        <h1 class="text-xl font-bold text-slate-900">Mes evenements</h1>
        <Link href="/partner/divertissement/creer" class="btn-console-primary">+ Ajouter un evenement</Link>
    </div>

    <div class="console-table-wrap">
        <table class="console-table">
            <thead>
                <tr><th>Titre</th><th>Lieu</th><th>Date</th><th>Prix</th><th>Statut</th><th></th></tr>
            </thead>
            <tbody>
                <tr v-for="e in events.data" :key="e.id">
                    <td class="font-medium text-slate-900">{{ e.title }}</td>
                    <td>{{ e.venue ?? e.city }}</td>
                    <td>{{ new Date(e.starts_at).toLocaleDateString('fr-FR') }}</td>
                    <td>{{ Number(e.price).toLocaleString('fr-FR') }} {{ e.currency }}</td>
                    <td><span :class="statusBadge(e.status)">{{ statusLabel(e.status) }}</span></td>
                    <td class="space-x-3 text-right">
                        <Link :href="`/partner/divertissement/${e.id}/editer`" class="font-semibold text-[#0972D3] hover:underline">Editer</Link>
                        <button class="font-semibold text-red-600 hover:underline" @click="remove(e)">Supprimer</button>
                    </td>
                </tr>
                <tr v-if="!events.data.length"><td colspan="6" class="py-6 text-center text-slate-400">Aucun evenement publie.</td></tr>
            </tbody>
        </table>
    </div>
</template>
