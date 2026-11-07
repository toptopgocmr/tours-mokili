<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

defineOptions({ layout: AdminLayout });
const props = defineProps({ order: Object, statuses: Object, history: Array });
const page = usePage();
const base = `/admin/fret/commandes/${props.order.reference}`;
const cancel = useForm({ reason: '' });
const refund = useForm({ reason: '' });
const ev = useForm({ status: 'EN_TRANSIT', location: '', comment: '' });
</script>

<template>
    <Head :title="order.reference" />
    <Link href="/admin/fret/commandes" class="text-sm text-slate-500">&larr; Commandes</Link>
    <h1 class="mb-3 mt-1 text-xl font-bold text-slate-900">{{ order.reference }} - {{ order.status_label }}</h1>
    <p v-if="page.props.flash?.error" class="mb-3 rounded bg-red-50 p-2 text-sm text-red-700">{{ page.props.flash.error }}</p>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="console-panel text-sm">
            <p><b>Client :</b> {{ order.user?.name }} ({{ order.user?.email }}) - <b>Partenaire :</b> {{ order.partner?.name }}</p>
            <p><b>Total :</b> {{ order.total_amount }} {{ order.currency }} - <b>Commission :</b> {{ order.commission_amount }} ({{ order.commission_percent }} %) - <b>Part partenaire :</b> {{ order.partner_amount }}</p>
            <p><b>Paiement confirme :</b> {{ order.payment_confirmed_at ?? '-' }} - <b>Reservation confirmee :</b> {{ order.confirmed_at ?? '-' }}</p>
            <p><b>Reversement :</b> {{ order.settled_at ? order.settlement_ref + ' le ' + order.settled_at.slice(0, 10) : '-' }} - <b>Remboursement :</b> {{ order.refunded_at ?? '-' }}</p>
            <p class="mt-2 font-semibold">Paiements</p>
            <p v-for="p in order.booking?.payments ?? []" :key="p.id">{{ p.reference }} - {{ p.provider }} - {{ p.status }} - {{ p.amount }}</p>
            <p class="mt-2 font-semibold">Conditions figees</p>
            <pre class="max-h-40 overflow-auto rounded bg-slate-50 p-2 text-xs">{{ JSON.stringify(order.frozen_terms, null, 1) }}</pre>
        </div>

        <div class="console-panel space-y-3 text-sm">
            <form v-if="order.status !== 'ANNULE'" class="flex gap-2" @submit.prevent="cancel.post(`${base}/annuler`)">
                <input v-model="cancel.reason" placeholder="Motif d'annulation" class="flex-1 rounded border-slate-300" required /><button class="rounded bg-red-600 px-3 text-white">Annuler</button>
            </form>
            <form v-if="order.status === 'ANNULE' && order.payment_confirmed_at && !order.refunded_at" class="flex gap-2" @submit.prevent="refund.post(`${base}/rembourser`)">
                <input v-model="refund.reason" placeholder="Reference / motif du remboursement" class="flex-1 rounded border-slate-300" required /><button class="btn-console-primary">Marquer rembourse</button>
            </form>
            <p class="font-semibold">Suivi {{ order.shipment?.tracking_code }}</p>
            <p v-for="e in [...(order.shipment?.events ?? [])].reverse()" :key="e.id" class="text-xs">{{ e.occurred_at?.slice(0, 16).replace('T', ' ') }} {{ e.status }} {{ e.location }} ({{ e.source }}) {{ e.comment }}</p>
            <form v-if="['RESERVATION_CONFIRMEE', 'LITIGE'].includes(order.status)" class="grid grid-cols-2 gap-2" @submit.prevent="ev.post(`${base}/suivi`)">
                <select v-model="ev.status" class="rounded border-slate-300"><option v-for="(l, k) in statuses" :key="k" :value="k">{{ l }}</option></select>
                <input v-model="ev.location" placeholder="Lieu" class="rounded border-slate-300" />
                <input v-model="ev.comment" placeholder="Commentaire (obligatoire)" class="col-span-2 rounded border-slate-300" required />
                <button class="btn-console-primary col-span-2">Ajouter un evenement (staff)</button>
            </form>
            <p class="font-semibold">Reclamations</p>
            <p v-for="c in order.claims" :key="c.id"><Link :href="`/admin/fret/reclamations/${c.id}`" class="text-blue-600">{{ c.reference }}</Link> - {{ c.reason }} - {{ c.status }}</p>
        </div>

        <div class="console-panel text-xs lg:col-span-2">
            <p class="mb-2 text-sm font-semibold">Journal d'audit</p>
            <p v-for="h in history" :key="h.id">{{ h.created_at?.slice(0, 16).replace('T', ' ') }} - {{ h.action }} ({{ h.actor }}) {{ h.reason }}</p>
        </div>
    </div>
</template>
