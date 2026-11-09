<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { dayLabel, duration, hhmm, longDate, money, PAX_LABELS, sliceLabel, STATE_LABELS, STATE_STYLES, STATUS_STYLES, stopsLabel } from '@/utils/flights';

defineOptions({ layout: MainLayout });

const props = defineProps({
    dossier: { type: Object, required: true },
    history: { type: Array, default: () => [] },
    message: { type: String, default: '' },
    afterSaleTypes: { type: Object, default: () => ({}) },
    canPay: Boolean,
    canConfirmPrice: Boolean,
    canCancel: Boolean,
    canRequestAfterSale: Boolean,
});

const d = computed(() => props.dossier);
const states = computed(() => [
    { key: 'payment', label: 'Paiement', value: d.value.payment_state },
    { key: 'booking', label: 'Reservation', value: d.value.booking_state },
    { key: 'ticket', label: 'Billet', value: d.value.ticket_state },
]);

const afterSale = useForm({ type: 'cancellation', reason: '', attachments: [] });
const sendAfterSale = () => afterSale.post(`/voyage/vols/dossiers/${d.value.reference}/apres-vente`, {
    forceFormData: true, preserveScroll: true, onSuccess: () => afterSale.reset(),
});

const confirmPrice = () => router.post(`/voyage/vols/dossiers/${d.value.reference}/confirmer-prix`);
const cancel = () => {
    if (confirm('Annuler ce dossier non paye ?')) router.post(`/voyage/vols/dossiers/${d.value.reference}/annuler`, {}, { preserveScroll: true });
};
</script>

<template>
    <Head :title="`Dossier ${dossier.reference}`" />

    <div class="mx-auto max-w-5xl px-6 py-10">
        <Link href="/voyage/vols/mes-billets" class="text-sm text-navy-500 hover:text-navy-900">&larr; Mes billets d'avion</Link>

        <div class="mt-2 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-navy-900">{{ d.title }}</h1>
                <p class="text-sm text-navy-600">Dossier {{ d.reference }}<span v-if="d.pnr"> · PNR <strong>{{ d.pnr }}</strong></span></p>
            </div>
            <span class="rounded-full px-4 py-1.5 text-sm font-semibold" :class="STATUS_STYLES[d.status]">{{ d.status_label }}</span>
        </div>

        <p class="mt-4 rounded-xl bg-navy-50/60 p-4 text-sm text-navy-800">{{ message }}</p>

        <!-- Trois suivis distincts : paiement / reservation / billet -->
        <div class="mt-4 grid gap-3 sm:grid-cols-3">
            <div v-for="s in states" :key="s.key" class="rounded-2xl border p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-navy-500">{{ s.label }}</p>
                <p class="mt-1 text-lg font-bold" :class="STATE_STYLES[s.value]">{{ STATE_LABELS[s.key][s.value] ?? s.value }}</p>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            <Link v-if="canPay && dossier.booking_id" :href="`/checkout/${dossier.booking_id}`" class="btn-gold">Payer {{ money(d.total_amount, d.currency) }}</Link>
            <button v-if="canConfirmPrice" type="button" class="btn-gold" @click="confirmPrice">Confirmer le nouveau prix ({{ money(d.total_amount, d.currency) }})</button>
            <button v-if="canCancel" type="button" class="btn-outline" @click="cancel">Annuler le dossier</button>
            <a v-if="d.paid_at" :href="`/voyage/vols/dossiers/${d.reference}/document/recu`" target="_blank" class="btn-outline">Recu de paiement</a>
            <a v-if="d.booked_at" :href="`/voyage/vols/dossiers/${d.reference}/document/confirmation`" target="_blank" class="btn-outline">Confirmation de reservation</a>
            <a v-if="d.status === 'TICKETED'" :href="`/voyage/vols/dossiers/${d.reference}/document/billet`" target="_blank" class="btn-gold">Billet electronique</a>
        </div>

        <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_320px]">
            <div class="space-y-6">
                <section class="rounded-2xl border p-5">
                    <h2 class="font-semibold text-navy-900">Itineraire</h2>
                    <div v-for="(s, i) in d.itinerary ?? []" :key="i" class="mt-4 border-t pt-4 first:mt-2 first:border-0 first:pt-0">
                        <p class="text-xs font-semibold uppercase text-navy-500">{{ sliceLabel(d.itinerary, i) }} · {{ dayLabel(s.departure_at) }}</p>
                        <p class="mt-1 text-navy-900">
                            <strong>{{ hhmm(s.departure_at) }}</strong> {{ s.origin }} → <strong>{{ hhmm(s.arrival_at) }}</strong> {{ s.destination }}
                            <span class="text-sm text-navy-500">· {{ duration(s.duration_minutes) }} · {{ stopsLabel(s.stops, s.via) }}</span>
                        </p>
                        <p v-for="seg in s.segments" :key="seg.flight_number + seg.departure_at" class="text-sm text-navy-600">{{ seg.airline_name }} {{ seg.flight_number }}</p>
                    </div>
                    <p v-if="d.fare_rules" class="mt-4 text-xs text-navy-500">
                        Bagages : {{ d.fare_rules.baggage ?? 'selon compagnie' }}<span v-if="d.fare_rules.cabin_baggage"> · cabine {{ d.fare_rules.cabin_baggage }}</span>
                        · {{ d.fare_rules.refundable ? 'remboursable' : 'non remboursable' }}
                    </p>
                </section>

                <section class="rounded-2xl border p-5">
                    <h2 class="font-semibold text-navy-900">Passagers</h2>
                    <table class="mt-3 w-full text-sm">
                        <thead class="text-left text-xs uppercase text-navy-500"><tr><th class="py-1">Nom</th><th>Type</th><th>Document</th><th>Billet</th></tr></thead>
                        <tbody>
                            <tr v-for="p in d.passengers" :key="p.id" class="border-t">
                                <td class="py-2 font-medium text-navy-900">{{ p.full_name }}</td>
                                <td>{{ PAX_LABELS[p.type] }}</td>
                                <td class="text-navy-600">{{ p.document_masked ?? '-' }}</td>
                                <td>
                                    <span v-if="p.ticket && d.status === 'TICKETED'" class="font-mono text-green-700">{{ p.ticket.ticket_number }}</span>
                                    <span v-else class="text-navy-400">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <section v-if="canRequestAfterSale || d.after_sales?.length" class="rounded-2xl border p-5">
                    <h2 class="font-semibold text-navy-900">Annulation, modification, remboursement</h2>
                    <div v-for="a in d.after_sales" :key="a.id" class="mt-3 rounded-xl bg-navy-50/50 p-3 text-sm">
                        <p class="font-semibold text-navy-900">{{ a.type_label }} · {{ a.status_label }}<span v-if="a.refund_status_label"> · {{ a.refund_status_label }}</span></p>
                        <p class="text-navy-600">{{ a.reason }}</p>
                        <p v-if="a.refundable_amount !== null" class="mt-1 text-navy-700">
                            Montant remboursable : <strong>{{ money(a.refundable_amount, d.currency) }}</strong>
                            <span v-if="Number(a.supplier_fee) || Number(a.penalty) || Number(a.psp_fee)" class="text-xs text-navy-500">
                                (frais compagnie {{ money(a.supplier_fee ?? 0, d.currency) }}, penalites {{ money(a.penalty ?? 0, d.currency) }}, frais de paiement {{ money(a.psp_fee ?? 0, d.currency) }})
                            </span>
                        </p>
                        <p v-if="a.estimated_delay" class="text-xs text-navy-500">Delai estime : {{ a.estimated_delay }}</p>
                        <p v-if="a.admin_notes" class="mt-1 text-xs text-navy-600">Reponse : {{ a.admin_notes }}</p>
                    </div>

                    <form v-if="canRequestAfterSale" class="mt-4 space-y-3" @submit.prevent="sendAfterSale">
                        <select v-model="afterSale.type" class="w-full rounded-lg border-gray-200 text-sm">
                            <option v-for="(label, key) in afterSaleTypes" :key="key" :value="key">{{ label }}</option>
                        </select>
                        <textarea v-model="afterSale.reason" rows="3" required minlength="10" placeholder="Expliquez votre demande (motif, nouvelle date souhaitee...)" class="w-full rounded-lg border-gray-200 text-sm" />
                        <p v-if="afterSale.errors.reason" class="text-xs text-red-600">{{ afterSale.errors.reason }}</p>
                        <div>
                            <label class="text-xs text-navy-600">Justificatifs (PDF, JPG, PNG - 3 max)</label>
                            <input type="file" multiple accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block text-sm" @change="afterSale.attachments = Array.from($event.target.files).slice(0, 3)" />
                        </div>
                        <button type="submit" class="btn-gold" :disabled="afterSale.processing">Envoyer la demande</button>
                        <p class="text-xs text-navy-500">Les frais de la compagnie, de paiement et penalites eventuelles vous sont detailles avant toute confirmation.</p>
                    </form>
                </section>
            </div>

            <aside class="space-y-4">
                <div class="rounded-2xl border p-5 text-sm">
                    <p class="font-semibold text-navy-900">Montant</p>
                    <p class="mt-1 text-2xl font-bold text-navy-900">{{ money(d.total_amount, d.currency) }}</p>
                    <p v-if="Number(d.refunded_amount)" class="text-green-700">Rembourse : {{ money(d.refunded_amount, d.currency) }}</p>
                    <p class="mt-2 text-navy-600">Depart : {{ longDate(d.depart_date) }}</p>
                    <p v-if="d.return_date" class="text-navy-600">Retour : {{ longDate(d.return_date) }}</p>
                </div>
                <div class="rounded-2xl border p-5 text-sm">
                    <p class="font-semibold text-navy-900">Historique</p>
                    <ol class="mt-2 space-y-2">
                        <li v-for="(h, i) in history" :key="i" class="border-l-2 border-gold-600 pl-3">
                            <p class="font-medium text-navy-900">{{ { DRAFT: 'Dossier commence', SEARCHED: 'Offre selectionnee', PRICE_CONFIRMED: 'Prix confirme', PAYMENT_PENDING: 'Paiement en attente', PAYMENT_CONFIRMED: 'Paiement confirme', BOOKING_PENDING: 'Reservation en cours', BOOKED: 'Reservation confirmee', TICKETING_PENDING: 'Emission en cours', TICKETED: 'Billet emis', FAILED: 'Incident', REFUND_PENDING: 'Remboursement en cours', REFUNDED: 'Rembourse', CANCELLED: 'Annule' }[h.to_status] ?? h.to_status }}</p>
                            <p class="text-xs text-navy-500">{{ new Date(h.created_at).toLocaleString('fr-FR') }}</p>
                        </li>
                    </ol>
                </div>
            </aside>
        </div>
    </div>
</template>
