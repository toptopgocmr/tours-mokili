<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';

defineOptions({ layout: MainLayout });
const props = defineProps({ order: { type: Object, required: true }, canCancel: Boolean, canClaim: Boolean });
const page = usePage();
const t = props.order.frozen_terms ?? {};
const fmt = (n) => Number(n).toLocaleString('fr-FR');

const cancel = () => confirm('Annuler cette commande ?') && router.post(`/fret/commandes/${props.order.reference}/annuler`);
const claim = useForm({ reason: 'retard', description: '' });
const openClaim = () => claim.post(`/fret/commandes/${props.order.reference}/reclamation`, { onSuccess: () => claim.reset() });
</script>

<template>
    <Head :title="order.reference" />
    <div class="mx-auto max-w-3xl px-6 py-10">
        <Link href="/fret/commandes" class="text-sm text-navy-500">&larr; Mes commandes</Link>
        <h1 class="mt-2 text-xl font-bold text-navy-900">{{ order.reference }}</h1>
        <p v-if="page.props.flash?.success" class="mt-3 rounded-lg bg-green-50 p-3 text-sm text-green-700">{{ page.props.flash.success }}</p>
        <p v-if="page.props.flash?.error" class="mt-3 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ page.props.flash.error }}</p>

        <div class="mt-4 rounded-2xl border p-4">
            <p class="font-semibold text-navy-900">{{ order.status_label }}</p>
            <p class="text-xs text-navy-500">Paiement confirme, reservation confirmee et livraison sont des etapes distinctes.</p>
            <p class="mt-2 text-lg font-bold text-gold-600">{{ fmt(order.total_amount) }} {{ order.currency }}</p>
            <Link v-if="order.status === 'DEVIS_ACCEPTE' && order.booking_id" :href="`/checkout/${order.booking_id}`" class="btn-gold mt-3 inline-block">Payer maintenant</Link>
            <p v-if="order.pickup_instructions" class="mt-3 rounded-lg bg-navy-50 p-3 text-sm"><b>Instructions de remise / enlevement :</b> {{ order.pickup_instructions }}</p>
        </div>

        <h2 class="mt-6 font-semibold text-navy-900">Conditions acceptees (version figee)</h2>
        <p class="text-sm text-navy-700">{{ t.title }} - {{ t.origin }} &rarr; {{ t.destination }} - partenaire {{ order.partner?.name }}</p>
        <table class="mt-2 w-full text-sm"><tr v-for="(l, i) in t.lines" :key="i" class="border-t"><td class="py-1">{{ l.label }}</td><td class="text-right">{{ fmt(l.amount) }}</td></tr></table>
        <p class="mt-1 text-xs text-navy-500">{{ t.is_firm ? 'Prix ferme' : 'Estimation' }}<span v-if="t.valid_until"> - valable jusqu'au {{ t.valid_until }}</span></p>
        <p v-if="t.exclusions" class="mt-1 text-xs text-amber-700">Non inclus : {{ t.exclusions }}</p>

        <h2 class="mt-6 font-semibold text-navy-900">Suivi {{ order.shipment?.tracking_code }}</h2>
        <ul class="mt-2 space-y-2 border-l-2 border-teal-200 pl-4 text-sm">
            <li v-for="e in [...(order.shipment?.events ?? [])].reverse()" :key="e.id">
                <b>{{ e.status.replaceAll('_', ' ') }}</b><span v-if="e.location"> - {{ e.location }}</span>
                <span class="block text-xs text-navy-500">{{ e.occurred_at?.slice(0, 16).replace('T', ' ') }} {{ e.comment }}</span>
            </li>
            <li v-if="!order.shipment?.events?.length" class="text-navy-500">Aucun evenement de transport pour le moment.</li>
        </ul>

        <button v-if="canCancel" class="mt-6 text-sm text-red-600 underline" @click="cancel">Annuler la commande</button>

        <div v-if="order.claims?.length" class="mt-6 text-sm">
            <p class="font-semibold text-navy-900">Reclamations</p>
            <p v-for="c in order.claims" :key="c.id">{{ c.reference }} - {{ c.reason }} - {{ c.status }}</p>
        </div>
        <form v-if="canClaim" class="mt-6 rounded-2xl border p-4" @submit.prevent="openClaim">
            <p class="text-sm font-semibold text-navy-900">Ouvrir une reclamation</p>
            <select v-model="claim.reason" class="mt-2 rounded-lg border-gray-300 text-sm"><option value="retard">Retard</option><option value="perte">Perte</option><option value="dommage">Dommage</option><option value="facturation">Facturation</option><option value="autre">Autre</option></select>
            <textarea v-model="claim.description" rows="3" class="mt-2 w-full rounded-lg border-gray-300 text-sm" placeholder="Decrivez le probleme (10 caracteres minimum)"></textarea>
            <p class="text-xs text-red-600">{{ claim.errors.description }}</p>
            <button class="btn-gold mt-2" :disabled="claim.processing">Envoyer</button>
        </form>
    </div>
</template>
