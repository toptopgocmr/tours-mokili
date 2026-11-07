<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import FlightSearchForm from '@/Components/FlightSearchForm.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    airports: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    search: { type: Object, default: null },
    offers: { type: Array, default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

const fmtTime = (iso) => new Date(iso).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
const fmtDur = (m) => `${Math.floor(m / 60)}h${String(m % 60).padStart(2, '0')}`;
const price = (v, c) => `${Number(v).toLocaleString('fr-FR')} ${c}`;
const orderUrl = (o) => (user.value ? `/vols/offres/${o.id}/passagers` : '/login');
</script>

<template>
    <Head title="Vols" />

    <section class="bg-navy-900 px-6 pb-10 pt-12">
        <div class="mx-auto max-w-5xl">
            <h1 class="text-2xl font-extrabold text-white">Reservez votre vol</h1>
            <p class="mt-1 text-sm text-white/80">Le billet est emis uniquement apres paiement confirme et reservation compagnie.</p>

            <div class="mt-5 rounded-2xl bg-white p-4 sm:p-5">
                <FlightSearchForm :airports="airports" :initial="filters" />
            </div>

            <Link v-if="user" href="/vols/mes-vols" class="mt-3 inline-block text-sm font-semibold text-gold-600">Mes vols &rarr;</Link>
        </div>
    </section>

    <div class="mx-auto max-w-5xl px-6 py-8">
        <div v-if="page.props.flash?.error" class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ page.props.flash.error }}</div>

        <template v-if="search">
            <p class="mb-4 text-sm text-navy-500">{{ offers.length }} offre(s) {{ search.origin }} &rarr; {{ search.destination }}</p>
            <p v-if="search.supplier_errors && search.supplier_errors.length" class="mb-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                Certains fournisseurs n'ont pas repondu : les resultats peuvent etre partiels.
            </p>

            <div v-for="o in offers" :key="o.id" class="mb-4 rounded-2xl border p-4 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex-1">
                        <p class="text-xs font-semibold uppercase text-navy-500">{{ o.airline_name }}</p>
                        <div v-for="(seg, i) in o.itinerary.outbound" :key="'o' + i" class="mt-1 text-sm text-navy-900">
                            <span class="font-semibold">{{ fmtTime(seg.departure) }}</span> {{ seg.from }}
                            &rarr; <span class="font-semibold">{{ fmtTime(seg.arrival) }}</span> {{ seg.to }}
                            <span class="text-xs text-navy-500">({{ fmtDur(seg.duration_minutes) }}, {{ seg.stops ? seg.stops + ' escale(s)' : 'direct' }})</span>
                        </div>
                        <div v-for="(seg, i) in o.itinerary.inbound" :key="'i' + i" class="mt-1 text-sm text-navy-900">
                            <span class="font-semibold">{{ fmtTime(seg.departure) }}</span> {{ seg.from }}
                            &rarr; <span class="font-semibold">{{ fmtTime(seg.arrival) }}</span> {{ seg.to }}
                            <span class="text-xs text-navy-500">(retour, {{ fmtDur(seg.duration_minutes) }})</span>
                        </div>
                        <p v-if="o.fare_rules" class="mt-2 text-xs text-navy-500">
                            {{ o.fare_rules.baggage }} - {{ o.fare_rules.changes }} - {{ o.fare_rules.refund }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-gold-600">{{ price(o.price_total, o.currency) }}</p>
                        <p class="text-xs text-navy-500">total, toutes taxes</p>
                        <component :is="user ? Link : 'a'" :href="orderUrl(o)" class="btn-gold mt-2 inline-block">Choisir</component>
                    </div>
                </div>
            </div>

            <p v-if="!offers.length" class="mt-10 text-center text-navy-500">Aucun vol trouve pour ces criteres.</p>
        </template>
    </div>
</template>
