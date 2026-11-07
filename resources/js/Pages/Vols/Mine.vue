<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: MainLayout });
defineProps({ orders: { type: Object, required: true }, labels: { type: Object, default: () => ({}) } });
</script>

<template>
    <Head title="Mes vols" />
    <div class="mx-auto max-w-4xl px-6 py-10">
        <h1 class="text-xl font-bold text-navy-900">Mes vols</h1>
        <Link
            v-for="o in orders.data"
            :key="o.id"
            :href="`/vols/dossier/${o.reference}`"
            class="mt-3 flex items-center justify-between rounded-2xl border p-4 shadow-sm hover:shadow-md"
        >
            <div>
                <p class="font-semibold text-navy-900">{{ o.reference }}</p>
                <p class="text-sm text-navy-500">{{ o.offer?.search?.origin }} &rarr; {{ o.offer?.search?.destination }} - {{ o.offer?.search?.depart_date?.slice(0, 10) }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm font-semibold text-gold-600">{{ Number(o.price_total).toLocaleString('fr-FR') }} {{ o.currency }}</p>
                <p class="text-xs text-navy-500">{{ labels[o.status] ?? o.status }}</p>
            </div>
        </Link>
        <p v-if="!orders.data.length" class="mt-10 text-center text-navy-500">Aucun vol reserve. <Link href="/vols" class="text-gold-600">Rechercher un vol</Link></p>
    </div>
</template>
