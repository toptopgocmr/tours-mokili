<script setup>
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: PartnerLayout });
defineProps({ requests: Object });
</script>

<template>
    <Head title="Demandes de devis" />
    <h1 class="mb-4 text-xl font-bold text-slate-900">Demandes de devis recues</h1>
    <div class="console-panel overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="text-left text-slate-500"><th>Reference</th><th>Client</th><th>Trajet</th><th>Statut</th><th>Devis</th><th></th></tr></thead>
            <tbody>
                <tr v-for="r in requests.data" :key="r.id" class="border-t">
                    <td class="py-2 font-medium">{{ r.reference }}</td><td>{{ r.user?.name }}</td>
                    <td>{{ r.mode }} {{ r.origin_city }} &rarr; {{ r.destination_city }}</td><td>{{ r.status }}</td><td>{{ r.quotes_count }}</td>
                    <td class="text-right"><Link :href="`/partner/fret/demandes/${r.reference}`" class="text-blue-600">Ouvrir</Link></td>
                </tr>
            </tbody>
        </table>
        <p v-if="!requests.data.length" class="py-6 text-center text-slate-500">Aucune demande.</p>
    </div>
</template>
