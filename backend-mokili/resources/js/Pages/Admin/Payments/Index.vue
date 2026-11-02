<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    payments: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, required: true },
});

const status = ref(props.filters.status ?? '');
const gateway = ref(props.filters.gateway ?? '');
const filter = () => router.get('/admin/paiements', { status: status.value, gateway: gateway.value }, { preserveState: true });

const GATEWAY_LABELS = { peex: 'Peex', pawapay: 'pawaPay', maishapay: 'MaishaPay', digitwace: 'Digitwace' };

const refresh = (p) => router.post(`/admin/paiements/${p.id}/verifier`, {}, { preserveScroll: true });
const markPaid = (p) => {
    if (confirm(`Confirmer manuellement le paiement ${p.reference ?? p.id} ? Verifiez d'abord sur le tableau de bord ${GATEWAY_LABELS[p.provider] ?? p.provider}.`)) {
        router.post(`/admin/paiements/${p.id}/confirmer`, {}, { preserveScroll: true });
    }
};
const markFailed = (p) => {
    const reason = prompt('Motif de l\'echec ?', 'Non valide par le client');
    if (reason !== null) router.post(`/admin/paiements/${p.id}/echec`, { reason }, { preserveScroll: true });
};

const badgeClass = (s) => ({
    paid: 'console-badge console-badge-success',
    refunded: 'console-badge console-badge-info',
    pending: 'console-badge console-badge-pending',
    processing: 'console-badge console-badge-pending',
    failed: 'console-badge console-badge-error',
}[s] ?? 'console-badge console-badge-neutral');

const money = (v, c) => `${Number(v).toLocaleString('fr-FR')} ${c}`;

// Refund flow: a small inline confirmation panel instead of a full
// modal library - click "Rembourser", type a reason, confirm.
const refundTarget = ref(null);
const refundForm = useForm({ reason: '' });

const openRefund = (payment) => {
    refundTarget.value = payment;
    refundForm.reset();
};

const submitRefund = () => {
    refundForm.post(`/admin/paiements/${refundTarget.value.id}/rembourser`, {
        preserveScroll: true,
        onSuccess: () => { refundTarget.value = null; },
    });
};
</script>

<template>
    <Head title="Paiements" />

    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-900">Paiements</h1>
        <p class="text-sm text-slate-500">Collectes Mobile Money (Peex, pawaPay, MaishaPay, Digitwace) et remboursements, tous modules confondus.</p>
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        <div class="console-stat">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total encaisse</p>
            <p class="mt-1 text-xl font-bold text-slate-900">{{ money(stats.totalPaid, 'XAF') }}</p>
        </div>
        <div class="console-stat">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total rembourse</p>
            <p class="mt-1 text-xl font-bold text-slate-900">{{ money(stats.totalRefunded, 'XAF') }}</p>
        </div>
        <div class="console-stat">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">En attente</p>
            <p class="mt-1 text-xl font-bold text-slate-900">{{ stats.pendingCount }}</p>
        </div>
        <div class="console-stat">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Echecs</p>
            <p class="mt-1 text-xl font-bold text-slate-900">{{ stats.failedCount }}</p>
        </div>
    </div>

    <select v-model="status" class="mb-4 mt-4 rounded border-slate-300 text-sm" @change="filter">
        <option value="">Tous les statuts</option>
        <option value="pending">En attente</option>
        <option value="processing">En cours</option>
        <option value="paid">Paye</option>
        <option value="failed">Echoue</option>
        <option value="refunded">Rembourse</option>
    </select>
    <select v-model="gateway" class="mb-4 ml-2 mt-4 rounded border-slate-300 text-sm" @change="filter">
        <option value="">Toutes les passerelles</option>
        <option v-for="(label, key) in GATEWAY_LABELS" :key="key" :value="key">{{ label }}</option>
    </select>

    <div class="console-table-wrap">
        <table class="console-table">
            <thead>
                <tr>
                    <th>Reference reservation</th>
                    <th>Client</th>
                    <th>Module</th>
                    <th>Passerelle</th>
                    <th>Montant</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="p in payments.data" :key="p.id">
                    <td class="font-medium text-slate-900">{{ p.booking?.reference }}</td>
                    <td>{{ p.user?.name }}</td>
                    <td>{{ p.booking?.bookable_type?.split('\\').pop() ?? '-' }}</td>
                    <td>
                        <span class="font-medium">{{ GATEWAY_LABELS[p.provider] ?? p.provider }}</span>
                        <p v-if="p.phone" class="text-xs text-slate-400">+{{ p.phone }} {{ p.network ?? '' }}</p>
                    </td>
                    <td>{{ money(p.amount, p.currency) }}</td>
                    <td>
                        <span :class="badgeClass(p.status)">{{ p.status }}</span>
                        <p v-if="p.status === 'refunded' && p.refund_reason" class="mt-1 text-xs text-slate-400">{{ p.refund_reason }}</p>
                        <p v-if="p.status === 'failed' && p.failure_reason" class="mt-1 max-w-[220px] text-xs text-red-400">{{ p.failure_reason }}</p>
                    </td>
                    <td class="space-y-1 text-right text-sm">
                        <template v-if="p.status === 'paid'">
                            <Link :href="`/admin/decaissements?payment=${p.id}`" class="block font-semibold text-[#0972D3] hover:underline">Rembourser (Mobile Money)</Link>
                            <button class="block w-full text-right font-semibold text-red-600 hover:underline" @click="openRefund(p)">Marquer rembourse</button>
                        </template>
                        <template v-if="['pending', 'processing'].includes(p.status)">
                            <button class="block w-full text-right font-semibold text-[#0972D3] hover:underline" @click="refresh(p)">Verifier le statut</button>
                            <button class="block w-full text-right font-semibold text-green-700 hover:underline" @click="markPaid(p)">Confirmer recu</button>
                            <button class="block w-full text-right font-semibold text-red-600 hover:underline" @click="markFailed(p)">Marquer en echec</button>
                        </template>
                    </td>
                </tr>
                <tr v-if="!payments.data.length"><td colspan="7" class="py-6 text-center text-slate-400">Aucun paiement.</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Refund confirmation panel -->
    <div v-if="refundTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
        <div class="w-full max-w-sm rounded-lg bg-white p-5 shadow-xl">
            <h2 class="font-semibold text-slate-900">Rembourser {{ refundTarget.booking?.reference }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ money(refundTarget.amount, refundTarget.currency) }} - {{ refundTarget.user?.name }}</p>
            <p class="mt-3 text-xs text-slate-400">
                Ceci marque seulement le paiement comme rembourse (remboursement fait hors plateforme). Pour renvoyer
                l'argent automatiquement sur le Mobile Money du client, utilisez "Rembourser (Mobile Money)".
            </p>
            <textarea
                v-model="refundForm.reason"
                rows="3"
                class="mt-3 w-full rounded border-slate-300 text-sm"
                placeholder="Motif du remboursement (annulation, litige, erreur...)"
            />
            <p v-if="refundForm.errors.reason" class="mt-1 text-xs text-red-600">{{ refundForm.errors.reason }}</p>
            <div class="mt-4 flex justify-end gap-2">
                <button class="btn-console-secondary" @click="refundTarget = null">Annuler</button>
                <button class="btn-console-primary !bg-red-600" :disabled="refundForm.processing" @click="submitRefund">
                    Confirmer le remboursement
                </button>
            </div>
        </div>
    </div>
</template>
