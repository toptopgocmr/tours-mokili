<script setup>
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineOptions({ layout: PartnerLayout });
defineProps({ parks: { type: Array, default: () => [] }, verified: { type: Boolean, default: true } });

const remove = (p) => {
    if (confirm(`Supprimer « ${p.name} » ?`)) router.delete(`/partner/parcs/${p.id}`, { preserveScroll: true });
};
const money = (v, c) => (v == null ? 'Sur place' : `${Math.round(v).toLocaleString('fr-FR')} ${c}`);
</script>

<template>
    <Head title="Mes parcs" />
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Parcs d'attractions & loisirs</h1>
            <p class="text-sm text-slate-500">Parcs aquatiques, zoos, aires de jeux, iles de loisirs... billets vendus dans Tourisme &gt; Parcs.</p>
        </div>
        <Link href="/partner/parcs/creer" class="btn-console-primary">+ Ajouter un parc</Link>
    </div>
    <div v-if="!verified" class="mb-4 rounded border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-900">Compte en cours de validation : vos parcs seront visibles apres validation.</div>
    <div class="console-table-wrap">
        <table class="console-table">
            <thead><tr><th>Parc</th><th>Ville</th><th>Adulte</th><th>Enfant</th><th>Billets vendus</th><th>Statut</th><th></th></tr></thead>
            <tbody>
                <tr v-for="p in parks" :key="p.id">
                    <td><p class="font-medium text-slate-900">{{ p.name }}</p><p class="text-xs text-slate-500">{{ p.category_label }}</p></td>
                    <td>{{ p.city }}</td>
                    <td>
                        <span v-if="p.promo" class="mr-1 text-xs text-slate-400 line-through">{{ Math.round(p.price_adult).toLocaleString('fr-FR') }}</span>
                        {{ money(p.final_price_adult, p.currency) }}
                        <span v-if="p.promo" class="console-badge console-badge-error ml-1">-{{ p.promo.percent }} %</span>
                    </td>
                    <td>{{ money(p.final_price_child, p.currency) }}</td>
                    <td class="font-semibold">{{ p.tickets_sold }}</td>
                    <td><span :class="p.is_active ? 'console-badge console-badge-success' : 'console-badge console-badge-pending'">{{ p.is_active ? 'En vente' : verified ? 'Masque' : 'En attente' }}</span></td>
                    <td class="space-x-3 whitespace-nowrap text-right">
                        <Link :href="`/partner/parcs/${p.id}/editer`" class="font-semibold text-[#0972D3] hover:underline">Editer</Link>
                        <button class="font-semibold text-red-600 hover:underline" @click="remove(p)">Supprimer</button>
                    </td>
                </tr>
                <tr v-if="!parks.length"><td colspan="7" class="py-8 text-center text-slate-400">Aucun parc. <Link href="/partner/parcs/creer" class="font-semibold text-[#0972D3]">Ajouter mon parc</Link></td></tr>
            </tbody>
        </table>
    </div>
</template>
