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
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-navy-900">Mes produits</h1>
        <Link href="/partner/marketplace/creer" class="btn-gold !py-2 text-sm">+ Ajouter un produit</Link>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-navy-50 text-navy-500">
                <tr><th class="px-4 py-3">Titre</th><th class="px-4 py-3">Categorie</th><th class="px-4 py-3">Prix</th><th class="px-4 py-3">Stock</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody>
                <tr v-for="p in products.data" :key="p.id" class="border-t">
                    <td class="px-4 py-3 font-medium">{{ p.title }}</td>
                    <td class="px-4 py-3">{{ p.category }}</td>
                    <td class="px-4 py-3">{{ Number(p.price).toLocaleString('fr-FR') }} {{ p.currency }}</td>
                    <td class="px-4 py-3">{{ p.stock }}</td>
                    <td class="space-x-3 px-4 py-3 text-right">
                        <Link :href="`/partner/marketplace/${p.id}/editer`" class="font-semibold text-gold-600">Editer</Link>
                        <button class="font-semibold text-red-600" @click="remove(p)">Supprimer</button>
                    </td>
                </tr>
                <tr v-if="!products.data.length"><td colspan="5" class="px-4 py-6 text-center text-navy-400">Aucun produit publie.</td></tr>
            </tbody>
        </table>
    </div>
</template>
