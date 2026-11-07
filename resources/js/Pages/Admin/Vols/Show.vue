<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

defineOptions({ layout: AdminLayout });
const props = defineProps({ order: Object, statusLabel: String, audit: { type: Array, default: () => [] }, canRetry: Boolean });
const base = `/admin/vols/${props.order.reference}`;
const refundForm = useForm({ reason: '', amount: '', supplier_fee: 0, psp_fee: 0, penalty: 0 });
const retry = () => confirm('Relancer ? Le fournisseur sera interroge avant toute nouvelle emission.') && router.post(`${base}/relancer`);
const doRefund = () => refundForm.post(`${base}/remboursement`, { onSuccess: () => refundForm.reset() });
const markRefunded = (id) => confirm('Confirmer que le remboursement a ete effectue ?') && router.post(`/admin/vols/remboursements/${id}/rembourser`);
const reject = (id) => { const reason = prompt('Motif du rejet'); if (reason) router.post(`/admin/vols/remboursements/${id}/rejeter`, { reason }); };
</script>

<template>
    <Head :title="order.reference" />
    <Link href="/admin/vols" class="text-sm text-slate-500">&larr; Dossiers</Link>
    <h1 class="mb-4 mt-1 text-xl font-bold text-slate-900">{{ order.reference }} - {{ statusLabel }}</h1>

    <div v-if="order.is_anomaly" class="mb-4 rounded bg-red-50 p-3 text-sm text-red-700">Anomalie : {{ order.failure_reason }}</div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="console-panel text-sm">
            <p><b>Client :</b> {{ order.user?.name }} ({{ order.contact_email }})</p>
            <p><b>Vol :</b> {{ order.offer?.search?.origin }} &rarr; {{ order.offer?.search?.destination }} - {{ order.offer?.airline_name }}</p>
            <p><b>Fournisseur :</b> {{ order.supplier?.name }} - PNR {{ order.pnr ?? '-' }}</p>
            <p><b>Prix client :</b> {{ order.price_total }} {{ order.currency }} - <b>Cout :</b> {{ order.supplier_cost }} - <b>Commission :</b> {{ order.commission_amount }}</p>
            <p><b>Tentatives :</b> {{ order.attempts }}</p>
            <button v-if="canRetry" class="btn-console-primary mt-3" @click="retry">Relancer le traitement</button>
        </div>

        <div v-if="order.segment_statuses?.length" class="console-panel text-sm">
            <p class="mb-2 font-semibold">Suivi de vol</p>
            <p v-for="s in order.segment_statuses" :key="s.id">{{ s.flight_number }} {{ s.flight_date?.slice(0, 10) }} - {{ s.status }}<span v-if="s.delay_minutes > 0"> (+{{ s.delay_minutes }} min)</span> <span class="text-xs text-slate-500">[{{ s.provider }}]</span></p>
        </div>

        <div class="console-panel text-sm">
            <p class="mb-2 font-semibold">Passagers / billets</p>
            <p v-for="p in order.passengers" :key="p.id">{{ p.first_name }} {{ p.last_name }}
                <span v-for="t in order.tickets.filter((t) => t.flight_passenger_id === p.id)" :key="t.id" class="text-xs text-slate-500">- {{ t.ticket_number }}</span>
            </p>
        </div>

        <div class="console-panel text-sm">
            <p class="mb-2 font-semibold">Remboursements</p>
            <div v-for="r in order.refunds" :key="r.id" class="mb-2 rounded border p-2">
                {{ r.reason_code }} - {{ r.status }} - a rembourser {{ r.refundable_amount }} {{ order.currency }}
                <p class="text-xs text-slate-500">{{ r.reason }}</p>
                <div v-if="['requested', 'approved'].includes(r.status)" class="mt-1 flex gap-3">
                    <button class="text-green-700 underline" @click="markRefunded(r.id)">Marquer rembourse</button>
                    <button class="text-red-600 underline" @click="reject(r.id)">Rejeter</button>
                </div>
            </div>
            <form class="mt-3 grid grid-cols-2 gap-2" @submit.prevent="doRefund">
                <input v-model="refundForm.reason" placeholder="Motif" class="col-span-2 rounded border-slate-300" required />
                <input v-model="refundForm.amount" type="number" step="0.01" placeholder="Montant (vide = total)" class="rounded border-slate-300" />
                <input v-model="refundForm.supplier_fee" type="number" step="0.01" placeholder="Frais compagnie" class="rounded border-slate-300" />
                <input v-model="refundForm.psp_fee" type="number" step="0.01" placeholder="Frais PSP" class="rounded border-slate-300" />
                <input v-model="refundForm.penalty" type="number" step="0.01" placeholder="Penalite" class="rounded border-slate-300" />
                <button class="btn-console-primary col-span-2" :disabled="refundForm.processing">Creer une demande de remboursement</button>
            </form>
        </div>

        <div class="console-panel text-xs">
            <p class="mb-2 text-sm font-semibold">Historique des statuts</p>
            <p v-for="h in order.history" :key="h.id">{{ h.created_at?.slice(0, 16).replace('T', ' ') }} {{ h.from_status ?? '-' }} &rarr; {{ h.to_status }} ({{ h.actor }}) {{ h.reason }}</p>
            <p class="mb-2 mt-4 text-sm font-semibold">Journal d'audit</p>
            <p v-for="a in audit" :key="a.id">{{ a.created_at?.slice(0, 16).replace('T', ' ') }} {{ a.action }} ({{ a.actor }})</p>
        </div>
    </div>
</template>
