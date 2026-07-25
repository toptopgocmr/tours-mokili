<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
defineOptions({ layout: MainLayout });
defineProps({ events: { type: Object, required: true } });
</script>
<template>
    <Head title="Divertissement" />
    <div class="mx-auto max-w-7xl px-6 py-12">
        <h1 class="text-3xl font-bold text-navy-900">Divertissement</h1>
        <p class="mt-1 text-navy-700">Concerts, spectacles, sport et billetterie.</p>
        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <Link v-for="e in events.data" :key="e.id" :href="`/divertissement/${e.slug}`" class="overflow-hidden rounded-2xl border shadow-sm transition hover:shadow-lg">
                <div class="flex h-36 items-center justify-center bg-purple-700 text-white">{{ e.venue ?? e.city }}</div>
                <div class="p-4">
                    <p class="font-semibold text-navy-900">{{ e.title }}</p>
                    <p class="mt-1 text-sm font-semibold text-gold-600">{{ Number(e.price).toLocaleString('fr-FR') }} {{ e.currency }}</p>
                </div>
            </Link>
        </div>
        <p v-if="!events.data.length" class="mt-10 text-center text-navy-500">Aucun evenement pour le moment.</p>
    </div>
</template>
