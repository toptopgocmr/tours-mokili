<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: MainLayout });
defineProps({ requests: { type: Object, required: true } });

const labels = { DEMANDE_ENVOYEE: 'Demande envoyee', DEVIS_TRANSMIS: 'Devis recu', DEVIS_ACCEPTE: 'Devis accepte', ANNULE: 'Annulee' };
</script>

<template>
    <Head title="Mes demandes de devis" />
    <div class="mx-auto max-w-4xl px-6 py-10">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold text-navy-900">Mes demandes de devis</h1>
            <Link href="/fret/demandes/creer" class="btn-gold">Nouvelle demande</Link>
        </div>
        <Link v-for="r in requests.data" :key="r.id" :href="`/fret/demandes/${r.reference}`" class="mt-3 flex items-center justify-between rounded-2xl border p-4 shadow-sm hover:shadow-md">
            <div>
                <p class="font-semibold text-navy-900">{{ r.reference }}</p>
                <p class="text-sm text-navy-500">{{ r.mode }} - {{ r.origin_city }} &rarr; {{ r.destination_city }}</p>
            </div>
            <div class="text-right text-sm">
                <p class="font-semibold text-gold-600">{{ labels[r.status] ?? r.status }}</p>
                <p class="text-xs text-navy-500">{{ r.quotes_count }} proposition(s)</p>
            </div>
        </Link>
        <p v-if="!requests.data.length" class="mt-10 text-center text-navy-500">Aucune demande pour le moment.</p>
    </div>
</template>
