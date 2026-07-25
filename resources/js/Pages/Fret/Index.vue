<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
defineOptions({ layout: MainLayout });
defineProps({ shipments: { type: Object, required: true } });
</script>
<template>
    <Head title="Fret" />
    <div class="mx-auto max-w-7xl px-6 py-12">
        <h1 class="text-3xl font-bold text-navy-900">Fret</h1>
        <p class="mt-1 text-navy-700">Suivi de vos expeditions et colis.</p>
        <div class="mt-8 space-y-3">
            <Link v-for="s in shipments.data" :key="s.id" :href="`/fret/${s.id}`" class="flex items-center justify-between rounded-xl border p-4 hover:shadow-sm">
                <div>
                    <p class="font-semibold text-navy-900">{{ s.tracking_code }}</p>
                    <p class="text-xs text-navy-500">{{ s.origin_city }} → {{ s.destination_city }}</p>
                </div>
                <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold uppercase text-teal-700">{{ s.status }}</span>
            </Link>
        </div>
        <p v-if="!shipments.data.length" class="mt-10 text-center text-navy-500">Aucune expedition pour le moment.</p>
    </div>
</template>
