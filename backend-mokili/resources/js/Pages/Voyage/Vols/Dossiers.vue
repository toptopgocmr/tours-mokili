<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import Paginator from '@/Components/Paginator.vue';
import { Head, Link } from '@inertiajs/vue3';
import { longDate, money, STATUS_STYLES } from '@/utils/flights';

defineOptions({ layout: MainLayout });
defineProps({ dossiers: { type: Object, required: true } });
</script>

<template>
    <Head title="Mes billets d'avion" />
    <div class="mx-auto max-w-4xl px-6 py-10">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-navy-900">Mes billets d'avion</h1>
                <p class="text-sm text-navy-600">Suivez le paiement, la reservation et l'emission de chaque dossier.</p>
            </div>
            <Link href="/voyage?type=vol" class="btn-gold">Nouveau vol</Link>
        </div>

        <div class="mt-6 space-y-3">
            <Link
                v-for="d in dossiers.data"
                :key="d.id"
                :href="`/voyage/vols/dossiers/${d.reference}`"
                class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border p-4 transition hover:shadow-md"
            >
                <div>
                    <p class="font-semibold text-navy-900">{{ d.title }}</p>
                    <p class="text-sm text-navy-600">{{ longDate(d.depart_date) }} · {{ d.passenger_count }} passager(s) · {{ d.reference }}<span v-if="d.pnr"> · PNR {{ d.pnr }}</span></p>
                </div>
                <div class="text-right">
                    <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="STATUS_STYLES[d.status]">{{ d.status_label }}</span>
                    <p class="mt-1 text-sm font-semibold text-navy-900">{{ money(d.total_amount, d.currency) }}</p>
                </div>
            </Link>
            <p v-if="!dossiers.data.length" class="rounded-2xl border p-10 text-center text-navy-500">Aucun billet pour le moment.</p>
        </div>
        <Paginator :page="dossiers" />
    </div>
</template>
