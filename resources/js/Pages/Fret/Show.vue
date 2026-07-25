<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head } from '@inertiajs/vue3';
defineOptions({ layout: MainLayout });
defineProps({ shipment: { type: Object, required: true } });
</script>
<template>
    <Head :title="shipment.tracking_code" />
    <div class="mx-auto max-w-2xl px-6 py-12">
        <h1 class="text-2xl font-bold text-navy-900">Expedition {{ shipment.tracking_code }}</h1>
        <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
            <div><dt class="text-navy-500">Origine</dt><dd class="font-medium">{{ shipment.origin_city }}</dd></div>
            <div><dt class="text-navy-500">Destination</dt><dd class="font-medium">{{ shipment.destination_city }}</dd></div>
            <div><dt class="text-navy-500">Mode</dt><dd class="font-medium uppercase">{{ shipment.mode }}</dd></div>
            <div><dt class="text-navy-500">Statut</dt><dd class="font-medium capitalize">{{ shipment.status }}</dd></div>
        </dl>
    </div>
</template>
