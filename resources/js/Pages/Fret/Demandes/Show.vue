<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { reactive } from 'vue';

defineOptions({ layout: MainLayout });
const props = defineProps({ quoteRequest: { type: Object, required: true } });
const page = usePage();

const accept = reactive({});
const kindLabel = { included: 'Inclus', excluded: 'Non inclus', estimated: 'Estime', third_party: 'Facture par un tiers' };
const fmt = (n) => Number(n).toLocaleString('fr-FR');

const acceptQuote = (q) => router.post(`/fret/devis/${q.reference}/accepter`, { accept_terms: !!accept[q.reference] });
const refuseQuote = (q) => confirm('Refuser ce devis ?') && router.post(`/fret/devis/${q.reference}/refuser`);

const doc = useForm({ target: 'quote_request', reference: props.quoteRequest.reference, type: 'facture_commerciale', file: null });
const upload = () => doc.post('/fret/documents', { forceFormData: true, onSuccess: () => doc.reset('file') });
</script>

<template>
    <Head :title="quoteRequest.reference" />
    <div class="mx-auto max-w-4xl px-6 py-10">
        <Link href="/fret/demandes" class="text-sm text-navy-500">&larr; Mes demandes</Link>
        <h1 class="mt-2 text-xl font-bold text-navy-900">{{ quoteRequest.reference }}</h1>
        <p class="text-sm text-navy-500">{{ quoteRequest.mode }} - {{ quoteRequest.origin_city }} &rarr; {{ quoteRequest.destination_city }} - {{ quoteRequest.description }}</p>
        <p v-if="page.props.flash?.error" class="mt-3 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ page.props.flash.error }}</p>
        <p v-if="page.props.flash?.success" class="mt-3 rounded-lg bg-green-50 p-3 text-sm text-green-700">{{ page.props.flash.success }}</p>

        <h2 class="mt-8 font-semibold text-navy-900">Propositions des partenaires</h2>
        <p v-if="!quoteRequest.quotes.length" class="mt-2 text-sm text-navy-500">Aucune proposition pour le moment. Vous serez notifie des reponses.</p>

        <div v-for="q in quoteRequest.quotes" :key="q.id" class="mt-4 rounded-2xl border p-5 shadow-sm" :class="q.status === 'sent' ? '' : 'opacity-70'">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="font-semibold text-navy-900">{{ q.partner?.name }} - {{ q.reference }} (v{{ q.version }})</p>
                <span class="rounded-full bg-navy-50 px-3 py-1 text-xs font-semibold">{{ q.status }}</span>
            </div>
            <table class="mt-3 w-full text-sm">
                <tr v-for="(l, i) in q.lines" :key="i" class="border-t"><td class="py-1">{{ l.label }}</td><td class="text-xs text-navy-500">{{ kindLabel[l.kind] }}</td><td class="text-right">{{ fmt(l.amount) }}</td></tr>
            </table>
            <p class="mt-2 text-right text-lg font-bold text-gold-600">{{ fmt(q.total_amount) }} {{ q.currency }}</p>
            <p class="text-right text-xs text-navy-500">{{ q.is_firm ? 'Prix ferme' : 'Estimation - a confirmer' }} - valable jusqu'au {{ q.valid_until?.slice(0, 10) }}<span v-if="q.transit_days"> - delai indicatif {{ q.transit_days }} j</span></p>
            <p v-if="q.exclusions" class="mt-2 text-xs text-amber-700">Non inclus : {{ q.exclusions }}</p>
            <p v-if="q.conditions" class="mt-1 text-xs text-navy-700">Conditions : {{ q.conditions }}</p>

            <div v-if="q.status === 'sent'" class="mt-4 border-t pt-3">
                <label class="flex items-start gap-2 text-sm"><input v-model="accept[q.reference]" type="checkbox" class="mt-1" /> J'accepte ce devis et ses conditions (version figee a l'acceptation).</label>
                <div class="mt-3 flex gap-3">
                    <button class="btn-gold" :disabled="!accept[q.reference]" @click="acceptQuote(q)">Accepter et payer</button>
                    <button class="text-sm text-red-600 underline" @click="refuseQuote(q)">Refuser</button>
                </div>
            </div>
        </div>

        <h2 class="mt-8 font-semibold text-navy-900">Documents</h2>
        <ul class="mt-2 text-sm"><li v-for="d in quoteRequest.documents" :key="d.id"><a :href="`/fret/documents/${d.id}/telecharger`" class="text-navy-700 underline">{{ d.type }} - {{ d.original_name }}</a></li></ul>
        <form class="mt-3 flex flex-wrap items-center gap-2" @submit.prevent="upload">
            <select v-model="doc.type" class="rounded-lg border-gray-300 text-sm"><option value="facture_commerciale">Facture commerciale</option><option value="colisage">Liste de colisage</option><option value="autorisation">Autorisation / justificatif</option><option value="autre">Autre</option></select>
            <input type="file" class="text-sm" @input="doc.file = $event.target.files[0]" />
            <button class="btn-gold" :disabled="!doc.file || doc.processing">Ajouter</button>
            <p class="w-full text-xs text-red-600">{{ doc.errors.file }}</p>
        </form>
    </div>
</template>
