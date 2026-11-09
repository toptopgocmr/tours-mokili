<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import { dayLabel, hhmm, money, PAX_LABELS, sliceLabel, STATE_LABELS, STATE_STYLES, STATUS_STYLES } from '@/utils/flights';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    dossier: { type: Object, required: true },
    passengers: { type: Array, default: () => [] },
    statuses: { type: Object, default: () => ({}) },
    allowed: { type: Array, default: () => [] },
    afterSaleStatuses: { type: Object, default: () => ({}) },
    reconciliation: { type: Object, default: () => ({}) },
});

const d = computed(() => props.dossier);
const base = computed(() => `/admin/vols/${d.value.reference}`);
const can = (s) => props.allowed.includes(s);
const paid = computed(() => !!d.value.paid_at);

const pnrForm = useForm({ pnr: d.value.pnr ?? '', supplier_booking_ref: d.value.supplier_booking_ref ?? '', ticketing_deadline: '' });
const ticketForm = useForm({ tickets: Object.fromEntries(props.passengers.map((p) => [p.id, p.ticket?.ticket_number ?? ''])) });
const failForm = useForm({ reason: '' });
const cancelForm = useForm({ reason: '' });
const refundForm = useForm({ amount: d.value.total_amount, reason: '' });
const refundDoneForm = useForm({ amount: d.value.after_sales?.find((a) => a.refund_status === 'pending')?.refundable_amount ?? d.value.total_amount, reference: '', note: '' });

const afterSaleForms = reactive(Object.fromEntries((d.value.after_sales ?? []).map((a) => [a.id, {
    status: a.status, supplier_fee: a.supplier_fee, psp_fee: a.psp_fee, penalty: a.penalty,
    refundable_amount: a.refundable_amount, estimated_delay: a.estimated_delay, admin_notes: a.admin_notes,
}])));

const post = (path, form = null) => {
    const opts = { preserveScroll: true };
    form ? form.post(`${base.value}/${path}`, opts) : router.post(`${base.value}/${path}`, {}, opts);
};
const saveAfterSale = (id) => router.put(`/admin/vols/apres-vente/${id}`, afterSaleForms[id], { preserveScroll: true });

const showPnr = computed(() => ['BOOKING_PENDING', 'FAILED'].includes(d.value.status) && paid.value && !d.value.booked_at);
const showTickets = computed(() => ['TICKETING_PENDING', 'BOOKED', 'FAILED'].includes(d.value.status) && !!d.value.pnr && !d.value.ticketed_at);
const showProcess = computed(() => d.value.status === 'PAYMENT_CONFIRMED');
const showReconcile = computed(() => ['BOOKING_PENDING', 'TICKETING_PENDING', 'FAILED'].includes(d.value.status));
</script>

<template>
    <Head :title="`Dossier ${dossier.reference}`" />

    <Link href="/admin/vols" class="text-sm text-slate-500 hover:underline">&larr; Billetterie</Link>
    <div class="mb-4 mt-1 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900">{{ d.title }} · {{ d.reference }}</h1>
            <p class="text-sm text-slate-600">
                {{ d.contact_name }} · {{ d.contact_phone }} · {{ d.contact_email }} · fournisseur {{ d.supplier?.name ?? '-' }}
                <span v-if="d.pnr"> · PNR <strong>{{ d.pnr }}</strong></span>
                <span v-if="d.ticketing_deadline"> · emettre avant {{ new Date(d.ticketing_deadline).toLocaleString('fr-FR') }}</span>
            </p>
        </div>
        <span class="rounded-full px-3 py-1 text-sm font-semibold" :class="STATUS_STYLES[d.status]">{{ d.status }} - {{ statuses[d.status] }}</span>
    </div>

    <div v-if="d.needs_attention" class="mb-4 flex flex-wrap items-center justify-between gap-2 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-800">
        <span>⚠ {{ d.attention_reason }}<span v-if="d.failure_reason"> - {{ d.failure_reason }}</span></span>
        <button class="font-semibold underline" @click="post('alerte-levee')">Marquer comme traite</button>
    </div>

    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <div class="console-panel"><p class="text-xs text-slate-500">Paiement</p><p class="font-bold" :class="STATE_STYLES[d.payment_state]">{{ STATE_LABELS.payment[d.payment_state] }}</p></div>
        <div class="console-panel"><p class="text-xs text-slate-500">Reservation</p><p class="font-bold" :class="STATE_STYLES[d.booking_state]">{{ STATE_LABELS.booking[d.booking_state] }}</p></div>
        <div class="console-panel"><p class="text-xs text-slate-500">Billet</p><p class="font-bold" :class="STATE_STYLES[d.ticket_state]">{{ STATE_LABELS.ticket[d.ticket_state] }}</p></div>
    </div>

    <div class="grid gap-4 xl:grid-cols-[1fr_380px]">
        <div class="space-y-4">
            <div class="console-panel">
                <h2 class="mb-2 font-semibold">Itineraire</h2>
                <template v-for="(s, i) in d.itinerary ?? []" :key="i">
                    <p v-for="seg in s.segments" :key="seg.departure_at + seg.flight_number" class="text-sm">
                        <strong>{{ sliceLabel(d.itinerary, i) }}</strong> · {{ seg.airline_name }} {{ seg.flight_number }} · {{ seg.origin }} {{ dayLabel(seg.departure_at) }} {{ hhmm(seg.departure_at) }} → {{ seg.destination }} {{ hhmm(seg.arrival_at) }}
                    </p>
                </template>
                <p class="mt-2 text-xs text-slate-500">{{ d.fare_rules?.cabin }} · {{ d.fare_rules?.baggage }} · {{ d.fare_rules?.refundable ? 'remboursable' : 'non remboursable' }}</p>
            </div>

            <div class="console-panel">
                <h2 class="mb-2 font-semibold">Passagers <span class="text-xs font-normal text-slate-500">(consultation journalisee)</span></h2>
                <table class="console-table">
                    <thead><tr><th>Nom</th><th>Type</th><th>Naissance</th><th>Document</th><th>Expiration</th><th>Billet</th></tr></thead>
                    <tbody>
                        <tr v-for="p in passengers" :key="p.id">
                            <td class="font-medium">{{ p.full_name }}</td>
                            <td>{{ PAX_LABELS[p.type] }}</td>
                            <td>{{ p.birth_date ? new Date(p.birth_date).toLocaleDateString('fr-FR') : '' }}</td>
                            <td class="font-mono text-xs">{{ p.document_number ?? '-' }} <span class="text-slate-500">{{ p.nationality }}</span></td>
                            <td>{{ p.document_expiry ? new Date(p.document_expiry).toLocaleDateString('fr-FR') : '' }}</td>
                            <td class="font-mono text-xs">{{ p.ticket?.ticket_number ?? '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="d.after_sales?.length" class="console-panel">
                <h2 class="mb-2 font-semibold">Apres-vente</h2>
                <div v-for="a in d.after_sales" :key="a.id" class="mb-4 rounded border border-slate-200 p-3 text-sm">
                    <p class="font-semibold">{{ a.type_label }} - {{ a.status_label }} <span class="font-normal text-slate-500">({{ new Date(a.created_at).toLocaleString('fr-FR') }}<span v-if="a.user"> par {{ a.user.name }}</span>)</span></p>
                    <p class="mt-1 whitespace-pre-line text-slate-700">{{ a.reason }}</p>
                    <p v-if="a.attachments?.length" class="mt-1 space-x-3 text-xs">
                        <a v-for="(f, i) in a.attachments" :key="i" :href="`/admin/vols/apres-vente/${a.id}/piece/${i}`" class="text-[#0972D3] hover:underline">Piece {{ i + 1 }}</a>
                    </p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-3">
                        <select v-model="afterSaleForms[a.id].status" class="rounded border-slate-300 text-sm">
                            <option v-for="(label, key) in afterSaleStatuses" :key="key" :value="key">{{ label }}</option>
                        </select>
                        <input v-model="afterSaleForms[a.id].supplier_fee" type="number" min="0" placeholder="Frais compagnie" class="rounded border-slate-300 text-sm" />
                        <input v-model="afterSaleForms[a.id].psp_fee" type="number" min="0" placeholder="Frais PSP" class="rounded border-slate-300 text-sm" />
                        <input v-model="afterSaleForms[a.id].penalty" type="number" min="0" placeholder="Penalites" class="rounded border-slate-300 text-sm" />
                        <input v-model="afterSaleForms[a.id].refundable_amount" type="number" min="0" placeholder="Montant remboursable" class="rounded border-slate-300 text-sm" />
                        <input v-model="afterSaleForms[a.id].estimated_delay" type="text" placeholder="Delai estime" class="rounded border-slate-300 text-sm" />
                        <textarea v-model="afterSaleForms[a.id].admin_notes" rows="2" placeholder="Reponse au client" class="rounded border-slate-300 text-sm sm:col-span-3" />
                    </div>
                    <button class="btn-console-secondary mt-2" @click="saveAfterSale(a.id)">Enregistrer</button>
                </div>
            </div>

            <div class="console-panel">
                <h2 class="mb-2 font-semibold">Historique des statuts</h2>
                <ol class="space-y-1 text-sm">
                    <li v-for="h in d.histories" :key="h.id">
                        <span class="text-slate-500">{{ new Date(h.created_at).toLocaleString('fr-FR') }}</span> ·
                        <strong>{{ h.from_status ?? '-' }} → {{ h.to_status }}</strong> · {{ h.actor_type }}<span v-if="h.actor"> ({{ h.actor.name }})</span>
                        <span v-if="h.note" class="text-slate-600"> - {{ h.note }}</span>
                    </li>
                </ol>
            </div>

            <div class="console-panel">
                <h2 class="mb-2 font-semibold">Echanges fournisseur</h2>
                <table class="console-table">
                    <thead><tr><th>Date</th><th>Operation</th><th>Resultat</th><th>Reference</th><th>Duree</th></tr></thead>
                    <tbody>
                        <tr v-for="l in d.supplier_logs" :key="l.id">
                            <td class="text-xs">{{ new Date(l.created_at).toLocaleString('fr-FR') }}</td>
                            <td>{{ l.operation }}<span v-if="l.idempotency_key" class="block text-xs text-slate-400">{{ l.idempotency_key }}</span></td>
                            <td :class="l.success ? 'text-green-700' : 'text-amber-700'">{{ l.outcome }}<span v-if="l.response?.message" class="block text-xs text-slate-500">{{ l.response.message }}</span></td>
                            <td class="font-mono text-xs">{{ l.external_ref }}</td>
                            <td class="text-xs">{{ l.duration_ms }} ms</td>
                        </tr>
                        <tr v-if="!d.supplier_logs?.length"><td colspan="5" class="py-3 text-center text-slate-400">Aucun echange.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Actions -->
        <div class="space-y-4">
            <div class="console-panel text-sm">
                <h2 class="mb-2 font-semibold">Rapprochement financier</h2>
                <dl class="grid grid-cols-2 gap-1">
                    <dt class="text-slate-500">Encaisse</dt><dd class="text-right">{{ money(reconciliation.collected, d.currency) }}</dd>
                    <dt class="text-slate-500">Cout fournisseur</dt><dd class="text-right">{{ money(reconciliation.supplier_cost, d.currency) }}</dd>
                    <dt class="text-slate-500">Marge</dt><dd class="text-right">{{ money(reconciliation.markup, d.currency) }}</dd>
                    <dt class="text-slate-500">Frais de service</dt><dd class="text-right">{{ money(reconciliation.service_fee, d.currency) }}</dd>
                    <dt class="text-slate-500">Commission agence</dt><dd class="text-right">{{ money(reconciliation.commission, d.currency) }}</dd>
                    <dt class="text-slate-500">Rembourse</dt><dd class="text-right">{{ money(reconciliation.refunded, d.currency) }}</dd>
                    <dt class="font-semibold">Net</dt><dd class="text-right font-semibold">{{ money(reconciliation.net, d.currency) }}</dd>
                </dl>
                <p class="mt-2 text-xs text-slate-500">
                    Paiements : <span v-for="p in d.booking?.payments ?? []" :key="p.id" class="mr-2">{{ p.provider }} {{ p.status }} {{ money(p.amount, p.currency) }}</span>
                </p>
            </div>

            <div v-if="showProcess || showReconcile" class="console-panel space-y-2 text-sm">
                <h2 class="font-semibold">Traitement</h2>
                <button v-if="showProcess" class="btn-console-primary w-full" @click="post('traiter')">Lancer la reservation fournisseur</button>
                <button v-if="showReconcile" class="btn-console-secondary w-full" @click="post('verifier')">Verifier l'etat aupres du fournisseur</button>
                <p class="text-xs text-slate-500">En cas de timeout, la verification interroge le fournisseur avant toute relance : jamais de double emission.</p>
            </div>

            <form v-if="showPnr" class="console-panel space-y-2 text-sm" @submit.prevent="post('pnr', pnrForm)">
                <h2 class="font-semibold">1. Reservation effectuee chez le consolidateur</h2>
                <input v-model="pnrForm.pnr" type="text" required placeholder="PNR (6 caracteres)" class="w-full rounded border-slate-300 uppercase" />
                <p v-if="pnrForm.errors.pnr" class="text-xs text-red-600">{{ pnrForm.errors.pnr }}</p>
                <input v-model="pnrForm.supplier_booking_ref" type="text" placeholder="Reference fournisseur (optionnel)" class="w-full rounded border-slate-300" />
                <label class="block text-xs text-slate-500">Date limite d'emission</label>
                <input v-model="pnrForm.ticketing_deadline" type="datetime-local" class="w-full rounded border-slate-300" />
                <button class="btn-console-primary w-full" :disabled="pnrForm.processing">Enregistrer le PNR (BOOKED)</button>
            </form>

            <form v-if="showTickets" class="console-panel space-y-2 text-sm" @submit.prevent="post('billets', ticketForm)">
                <h2 class="font-semibold">2. Billets emis</h2>
                <div v-for="p in passengers" :key="p.id">
                    <label class="text-xs text-slate-500">{{ p.full_name }} ({{ PAX_LABELS[p.type] }})</label>
                    <input v-model="ticketForm.tickets[p.id]" type="text" required placeholder="N° de billet (13 chiffres)" class="w-full rounded border-slate-300 font-mono" />
                    <p v-if="ticketForm.errors[`tickets.${p.id}`]" class="text-xs text-red-600">{{ ticketForm.errors[`tickets.${p.id}`] }}</p>
                </div>
                <button class="btn-console-primary w-full" :disabled="ticketForm.processing">Enregistrer les billets (TICKETED)</button>
                <p class="text-xs text-slate-500">Le client recoit son billet electronique uniquement a cette etape.</p>
            </form>

            <form v-if="can('REFUND_PENDING') && paid" class="console-panel space-y-2 text-sm" @submit.prevent="post('remboursement', refundForm)">
                <h2 class="font-semibold">Lancer un remboursement</h2>
                <input v-model="refundForm.amount" type="number" min="1" :max="d.total_amount" required class="w-full rounded border-slate-300" />
                <input v-model="refundForm.reason" type="text" required placeholder="Motif" class="w-full rounded border-slate-300" />
                <button class="btn-console-secondary w-full">Passer en REFUND_PENDING</button>
            </form>

            <form v-if="d.status === 'REFUND_PENDING'" class="console-panel space-y-2 text-sm" @submit.prevent="post('rembourse', refundDoneForm)">
                <h2 class="font-semibold">Remboursement effectue</h2>
                <p class="text-xs text-slate-500">Apres le reversement (menu Decaissements ou virement), saisissez la reference.</p>
                <input v-model="refundDoneForm.amount" type="number" min="1" :max="d.total_amount" required class="w-full rounded border-slate-300" />
                <input v-model="refundDoneForm.reference" type="text" placeholder="Reference du decaissement" class="w-full rounded border-slate-300" />
                <button class="btn-console-primary w-full">Confirmer (REFUNDED)</button>
            </form>

            <form v-if="can('FAILED')" class="console-panel space-y-2 text-sm" @submit.prevent="post('echec', failForm)">
                <h2 class="font-semibold">Declarer un echec</h2>
                <input v-model="failForm.reason" type="text" required placeholder="Motif (classe complete, refus compagnie...)" class="w-full rounded border-slate-300" />
                <button class="btn-console-secondary w-full">Passer en FAILED</button>
            </form>

            <form v-if="can('CANCELLED')" class="console-panel space-y-2 text-sm" @submit.prevent="post('annuler', cancelForm)">
                <h2 class="font-semibold">Annuler le dossier</h2>
                <input v-model="cancelForm.reason" type="text" required placeholder="Motif" class="w-full rounded border-slate-300" />
                <button class="btn-console-secondary w-full !text-red-700">Annuler</button>
            </form>
        </div>
    </div>
</template>
