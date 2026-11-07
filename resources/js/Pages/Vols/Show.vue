<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';

defineOptions({ layout: MainLayout });
const props = defineProps({
    order: { type: Object, required: true },
    statusLabel: String,
    labels: { type: Object, default: () => ({}) },
    canCancel: Boolean,
    canRefund: Boolean,
});
const page = usePage();
const refundForm = useForm({ reason: '' });
const cancel = () => confirm('Annuler ce dossier ?') && router.post(`/vols/dossier/${props.order.reference}/annuler`);
const askRefund = () => refundForm.post(`/vols/dossier/${props.order.reference}/remboursement`, { onSuccess: () => refundForm.reset() });
// Message honnete : jamais "billet emis" avant TICKETED.
const notice = {
    PRICE_CONFIRMED: 'Prix confirme. En attente de votre paiement.',
    PAYMENT_PENDING: 'Paiement en cours de verification.',
    PAYMENT_CONFIRMED: 'Paiement recu. Reservation en cours - le billet n\'est pas encore emis.',
    BOOKING_PENDING: 'Reservation aupres de la compagnie en cours.',
    BOOKED: 'Place reservee. Emission du billet en cours.',
    TICKETING_PENDING: 'Emission du billet en cours.',
    TICKETED: 'Billet emis.',
    FAILED: 'Un probleme est survenu. Notre equipe traite votre dossier ; vous serez rembourse si le billet ne peut etre emis.',
    REFUND_PENDING: 'Remboursement en cours de traitement.',
    REFUNDED: 'Dossier rembourse.',
    CANCELLED: 'Dossier annule.',
};
</script>

<template>
    <Head :title="`Dossier ${order.reference}`" />
    <div class="mx-auto max-w-3xl px-6 py-10">
        <Link href="/vols/mes-vols" class="text-sm text-navy-500">&larr; Mes vols</Link>
        <h1 class="mt-2 text-xl font-bold text-navy-900">Dossier {{ order.reference }}</h1>

        <div v-if="page.props.flash?.success" class="mt-3 rounded-lg bg-green-50 p-3 text-sm text-green-700">{{ page.props.flash.success }}</div>

        <div class="mt-4 rounded-2xl border p-4">
            <p class="font-semibold text-navy-900">{{ statusLabel }}</p>
            <p class="text-sm text-navy-500">{{ notice[order.status] }}</p>
            <p v-if="order.pnr" class="mt-2 text-sm">Reference de reservation (PNR) : <b>{{ order.pnr }}</b></p>
            <p class="mt-2 text-sm font-semibold text-gold-600">{{ Number(order.price_total).toLocaleString('fr-FR') }} {{ order.currency }}</p>
            <Link v-if="order.status === 'PRICE_CONFIRMED' && order.booking_id" :href="`/checkout/${order.booking_id}`" class="btn-gold mt-3 inline-block">Payer maintenant</Link>
        </div>

        <h2 class="mt-6 font-semibold text-navy-900">Passagers</h2>
        <ul class="mt-2 space-y-1 text-sm">
            <li v-for="p in order.passengers" :key="p.id">{{ p.first_name }} {{ p.last_name }}
                <span v-for="t in order.tickets.filter((t) => t.flight_passenger_id === p.id)" :key="t.id" class="ml-2 text-xs text-navy-500">billet {{ t.ticket_number }}</span>
            </li>
        </ul>

        <template v-if="order.segment_statuses?.length">
            <h2 class="mt-6 font-semibold text-navy-900">Suivi de vol</h2>
            <ul class="mt-2 space-y-1 text-sm">
                <li v-for="s in order.segment_statuses" :key="s.id">
                    {{ s.flight_number }} {{ s.origin }} &rarr; {{ s.destination }} ({{ s.flight_date?.slice(0, 10) }}) :
                    <b>{{ ({ scheduled: 'Programme', delayed: 'Retarde', active: 'En vol', landed: 'Atterri', cancelled: 'Annule', diverted: 'Deroute' })[s.status] ?? s.status }}</b>
                    <span v-if="s.delay_minutes > 0"> - retard {{ s.delay_minutes }} min</span>
                </li>
            </ul>
        </template>

        <h2 class="mt-6 font-semibold text-navy-900">Historique</h2>
        <ul class="mt-2 space-y-1 text-xs text-navy-500">
            <li v-for="h in order.history" :key="h.id">{{ h.created_at?.slice(0, 16).replace('T', ' ') }} - {{ labels[h.to_status] ?? h.to_status }}</li>
        </ul>

        <div v-if="canCancel" class="mt-6"><button class="text-sm text-red-600 underline" @click="cancel">Annuler le dossier</button></div>

        <form v-if="canRefund" class="mt-6 rounded-2xl border p-4" @submit.prevent="askRefund">
            <p class="text-sm font-semibold text-navy-900">Demander un remboursement</p>
            <textarea v-model="refundForm.reason" rows="3" class="mt-2 w-full rounded-lg border-gray-200 text-sm" placeholder="Motif" required></textarea>
            <p class="text-xs text-red-600">{{ refundForm.errors.reason }}</p>
            <button class="btn-gold mt-2" :disabled="refundForm.processing">Envoyer la demande</button>
        </form>
    </div>
</template>
