<script setup>
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineOptions({ layout: PartnerLayout });
defineProps({ products: { type: Object, required: true } });

const remove = (item) => {
    if (confirm(`Supprimer "${item.title}" ?`)) router.delete(`/partner/marketplace/${item.id}`);
};
</script>

<template>
    <Head title="Mes produits" />
    <div class="mb-5 flex items-center justify-between">
        <h1 class="text-xl font-bold text-slate-900">Mes produits</h1>
        <Link href="/partner/marketplace/creer" class="btn-console-primary">+ Ajouter un produit</Link>
    </div>

    <div class="console-table-wrap">
        <table class="console-table">
            <thead>
                <tr><th>Titre</th><th>Categorie</th><th>Prix</th><th>Stock</th><th></th></tr>
            </thead>
            <tbody>
                <tr v-for="p in products.data" :key="p.id">
                    <td class="font-medium text-slate-900">{{ p.title }}</td>
                    <td>{{ p.category }}</td>
                    <td>{{ Number(p.price).toLocaleString('fr-FR') }} {{ p.currency }}</td>
                    <td>{{ p.stock }}</td>
                    <td class="space-x-3 text-right">
                        <Link :href="`/partner/marketplace/${p.id}/editer`" class="font-semibold text-[#0972D3] hover:underline">Editer</Link>
                        <button class="font-semibold text-red-600 hover:underline" @click="remove(p)">Supprimer</button>
                    </td>
                </tr>
                <tr v-if="!products.data.length"><td colspan="5" class="py-6 text-center text-slate-400">Aucun produit publie.</td></tr>
            </tbody>
        </table>
    </div>
</template>
