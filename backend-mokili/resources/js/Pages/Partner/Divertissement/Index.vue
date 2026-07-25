<script setup>
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineOptions({ layout: PartnerLayout });
defineProps({ events: { type: Object, required: true } });

const remove = (item) => {
    if (confirm(`Supprimer "${item.title}" ?`)) router.delete(`/partner/divertissement/${item.id}`);
};
</script>

<template>
    <Head title="Mes evenements" />
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-navy-900">Mes evenements</h1>
        <Link href="/partner/divertissement/creer" class="btn-gold !py-2 text-sm">+ Ajouter un evenement</Link>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-navy-50 text-navy-500">
                <tr><th class="px-4 py-3">Titre</th><th class="px-4 py-3">Lieu</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Prix</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody>
                <tr v-for="e in events.data" :key="e.id" class="border-t">
                    <td class="px-4 py-3 font-medium">{{ e.title }}</td>
                    <td class="px-4 py-3">{{ e.venue ?? e.city }}</td>
                    <td class="px-4 py-3">{{ new Date(e.starts_at).toLocaleDateString('fr-FR') }}</td>
                    <td class="px-4 py-3">{{ Number(e.price).toLocaleString('fr-FR') }} {{ e.currency }}</td>
                    <td class="space-x-3 px-4 py-3 text-right">
                        <Link :href="`/partner/divertissement/${e.id}/editer`" class="font-semibold text-gold-600">Editer</Link>
                        <button class="font-semibold text-red-600" @click="remove(e)">Supprimer</button>
                    </td>
                </tr>
                <tr v-if="!events.data.length"><td colspan="5" class="px-4 py-6 text-center text-navy-400">Aucun evenement publie.</td></tr>
            </tbody>
        </table>
    </div>
</template>
