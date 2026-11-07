<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: MainLayout });
defineProps({ orders: { type: Object, required: true } });
</script>

<template>
    <Head title="Mes commandes de fret" />
    <div class="mx-auto max-w-4xl px-6 py-10">
        <h1 class="text-xl font-bold text-navy-900">Mes commandes de fret</h1>
        <Link v-for="o in orders.data" :key="o.id" :href="`/fret/commandes/${o.reference}`" class="mt-3 flex items-center justify-between rounded-2xl border p-4 shadow-sm hover:shadow-md">
            <div>
                <p class="font-semibold text-navy-900">{{ o.reference }} <span class="text-xs font-normal text-navy-500">{{ o.title }}</span></p>
                <p class="text-sm text-navy-500">{{ o.status_label }}</p>
                <p v-if="o.shipment" class="text-xs text-navy-500">Suivi : {{ o.shipment.tracking_code }}</p>
            </div>
            <p class="font-semibold text-gold-600">{{ Number(o.total_amount).toLocaleString('fr-FR') }} {{ o.currency }}</p>
        </Link>
        <p v-if="!orders.data.length" class="mt-10 text-center text-navy-500">Aucune commande.</p>
    </div>
</template>
