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

    <div class="mb-5 flex items-center justify-between">
        <h1 class="text-xl font-bold text-slate-900">Mes logements</h1>
        <Link href="/partner/logement/creer" class="btn-console-primary">+ Ajouter un logement</Link>
    </div>

    <div class="console-table-wrap">
        <table class="console-table">
            <thead>
                <tr><th>Titre</th><th>Ville</th><th>Prix/nuit</th><th>Statut</th><th></th></tr>
            </thead>
            <tbody>
                <tr v-for="l in listings.data" :key="l.id">
                    <td class="font-medium text-slate-900">{{ l.title }}</td>
                    <td>{{ l.city }}</td>
                    <td>{{ Number(l.price_per_night).toLocaleString('fr-FR') }} {{ l.currency }}</td>
                    <td><span :class="l.is_active ? 'console-badge console-badge-success' : 'console-badge console-badge-neutral'">{{ l.is_active ? 'Actif' : 'Inactif' }}</span></td>
                    <td class="space-x-3 text-right">
                        <Link :href="`/partner/logement/${l.id}/editer`" class="font-semibold text-[#0972D3] hover:underline">Editer</Link>
                        <button class="font-semibold text-red-600 hover:underline" @click="remove(l)">Supprimer</button>
                    </td>
                </tr>
                <tr v-if="!listings.data.length"><td colspan="5" class="py-6 text-center text-slate-400">Aucun logement publie.</td></tr>
            </tbody>
        </table>
    </div>
</template>
