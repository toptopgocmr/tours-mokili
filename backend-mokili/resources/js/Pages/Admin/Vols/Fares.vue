<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Paginator from '@/Components/Paginator.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { money } from '@/utils/flights';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    fares: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    suppliers: { type: Array, default: () => [] },
    sandbox: Boolean,
    pricing: { type: Object, default: () => ({}) },
});

const q = ref(props.filters.q ?? '');
const search = () => router.get('/admin/vols/tarifs', q.value ? { q: q.value } : {}, { preserveState: true, replace: true });
const remove = (f) => { if (confirm(`Supprimer le tarif ${f.origin}-${f.destination} ${f.airline_name} ?`)) router.delete(`/admin/vols/tarifs/${f.id}`, { preserveScroll: true }); };
const toggle = (s) => router.post(`/admin/vols/fournisseurs/${s.id}/basculer`, {}, { preserveScroll: true });
const DAYS = ['', 'Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa', 'Di'];
</script>

<template>
    <Head title="Tarifs vols" />

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-bold text-slate-900">Tarifs vols & fournisseurs</h1>
        <Link href="/admin/vols/tarifs/creer" class="btn-console-primary">+ Nouveau tarif</Link>
    </div>

    <div class="mb-5 grid gap-4 lg:grid-cols-2">
        <div class="console-panel text-sm">
            <h2 class="mb-2 font-semibold">Fournisseurs aeriens</h2>
            <table class="console-table">
                <thead><tr><th>Fournisseur</th><th>Connecteur</th><th>Etat</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="s in suppliers" :key="s.id">
                        <td>{{ s.name }}</td>
                        <td class="font-mono text-xs">{{ s.driver }}</td>
                        <td>
                            <span v-if="s.driver === 'sandbox'" :class="sandbox ? 'console-badge console-badge-pending' : 'console-badge console-badge-neutral'">{{ sandbox ? 'Actif (TEST)' : 'Inactif' }}</span>
                            <span v-else :class="s.is_active ? 'console-badge console-badge-success' : 'console-badge console-badge-neutral'">{{ s.is_active ? 'Actif' : 'Inactif' }}</span>
                        </td>
                        <td class="text-right"><button v-if="s.driver !== 'sandbox'" class="text-[#0972D3] hover:underline" @click="toggle(s)">{{ s.is_active ? 'Desactiver' : 'Activer' }}</button></td>
                    </tr>
                </tbody>
            </table>
            <p class="mt-2 text-xs text-slate-500">Un fournisseur API (consolidateur, GDS, NDC) s'ajoute comme connecteur dans config/flights.php ; ses cles restent dans les variables Railway.</p>
        </div>
        <div class="console-panel text-sm">
            <h2 class="mb-2 font-semibold">Regles de prix (variables Railway)</h2>
            <p>Marge MOKILI TOUR : <strong>{{ pricing.markup_percent }} %</strong> <span class="text-xs text-slate-500">(FLIGHTS_MARKUP_PERCENT)</span></p>
            <p>Frais de service : <strong>{{ money(pricing.service_fee) }}</strong> / passager <span class="text-xs text-slate-500">(FLIGHTS_SERVICE_FEE)</span></p>
            <p>Validite d'une offre avant revalidation : <strong>{{ pricing.offer_ttl }} min</strong> <span class="text-xs text-slate-500">(FLIGHTS_OFFER_TTL)</span></p>
            <p class="mt-2 text-xs text-slate-500">Alertes equipe : FLIGHTS_OPS_EMAIL. Verification automatique des dossiers en attente : commande flights:reconcile (toutes les 5 min).</p>
        </div>
    </div>

    <input v-model="q" type="text" placeholder="Code aeroport (BZV) ou compagnie..." class="mb-4 w-72 rounded border-slate-300 text-sm" @keyup.enter="search" />

    <div class="console-table-wrap">
        <table class="console-table">
            <thead><tr><th>Trajet</th><th>Compagnie / vol</th><th>Horaires</th><th>Jours</th><th>Validite</th><th>Classe</th><th>Tarif adulte</th><th>Statut</th><th></th></tr></thead>
            <tbody>
                <tr v-for="f in fares.data" :key="f.id">
                    <td class="font-semibold">{{ f.origin }} → {{ f.destination }}<span v-if="f.stops" class="block text-xs text-slate-500">{{ f.stops }} escale(s) {{ f.via }}</span></td>
                    <td>{{ f.airline_name }} <span class="font-mono text-xs">{{ f.flight_number }}</span></td>
                    <td>{{ f.departure_time }} → {{ f.arrival_time }}<sup v-if="f.arrival_day_offset">+{{ f.arrival_day_offset }}</sup></td>
                    <td class="text-xs">{{ f.days_of_week?.length ? f.days_of_week.map((d) => DAYS[d]).join(' ') : 'Tous' }}</td>
                    <td class="text-xs">{{ f.valid_from ? new Date(f.valid_from).toLocaleDateString('fr-FR') : '...' }} - {{ f.valid_to ? new Date(f.valid_to).toLocaleDateString('fr-FR') : '...' }}</td>
                    <td>{{ f.cabin }}</td>
                    <td>{{ money(Number(f.base_fare) + Number(f.taxes), f.currency) }}</td>
                    <td><span :class="f.is_active ? 'console-badge console-badge-success' : 'console-badge console-badge-neutral'">{{ f.is_active ? 'Actif' : 'Inactif' }}</span></td>
                    <td class="space-x-3 text-right">
                        <Link :href="`/admin/vols/tarifs/${f.id}/editer`" class="font-semibold text-[#0972D3] hover:underline">Editer</Link>
                        <button class="font-semibold text-red-600 hover:underline" @click="remove(f)">Supprimer</button>
                    </td>
                </tr>
                <tr v-if="!fares.data.length"><td colspan="9" class="py-6 text-center text-slate-400">Aucun tarif : ajoutez les tarifs negocies avec votre consolidateur pour qu'ils apparaissent dans la recherche.</td></tr>
            </tbody>
        </table>
    </div>
    <Paginator :page="fares" />
</template>
