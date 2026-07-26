<script setup>
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineOptions({ layout: PartnerLayout });
defineProps({ offers: { type: Object, required: true } });

const modeLabel = { air: 'Aerien', mer: 'Maritime', route: 'Routier' };

const remove = (item) => {
    if (confirm(`Supprimer "${item.title}" ?`)) router.delete(`/partner/fret/${item.id}`);
};
</script>

<template>
    <Head title="Mes offres de fret" />
    <div class="mb-5 flex items-center justify-between">
        <h1 class="text-xl font-bold text-slate-900">Mes offres de fret</h1>
        <Link href="/partner/fret/creer" class="btn-console-primary">+ Ajouter une offre</Link>
    </div>

    <div class="console-table-wrap">
        <table class="console-table">
            <thead>
                <tr><th>Titre</th><th>Trajet</th><th>Mode</th><th>Prix/kg</th><th>Statut</th><th></th></tr>
            </thead>
            <tbody>
                <tr v-for="o in offers.data" :key="o.id">
                    <td class="font-medium text-slate-900">{{ o.title }}</td>
                    <td>{{ o.origin_city }} &rarr; {{ o.destination_city }}</td>
                    <td class="uppercase">{{ modeLabel[o.mode] }}</td>
                    <td>{{ Number(o.price_per_kg).toLocaleString('fr-FR') }} {{ o.currency }}</td>
                    <td><span :class="o.is_active ? 'console-badge console-badge-success' : 'console-badge console-badge-neutral'">{{ o.is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="space-x-3 text-right">
                        <Link :href="`/partner/fret/${o.id}/editer`" class="font-semibold text-[#0972D3] hover:underline">Editer</Link>
                        <button class="font-semibold text-red-600 hover:underline" @click="remove(o)">Supprimer</button>
                    </td>
                </tr>
                <tr v-if="!offers.data.length"><td colspan="6" class="py-6 text-center text-slate-400">Aucune offre de fret publiee.</td></tr>
            </tbody>
        </table>
    </div>
</template>
