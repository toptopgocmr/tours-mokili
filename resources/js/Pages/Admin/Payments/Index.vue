<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    payments: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, required: true },
});

const status = ref(props.filters.status ?? '');
const filter = () => router.get('/admin/paiements', { status: status.value }, { preserveState: true });

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
        <p class="text-sm text-slate-500">Suivi des transactions Peex (mobile money / carte) et des remboursements, tous modules confondus.</p>
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

    <div class="console-table-wrap">
        <table class="console-table">
            <thead>
                <tr>
                    <th>Reference reservation</th>
                    <th>Client</th>
                    <th>Module</th>
                    <th>Methode</th>
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
                    <td class="capitalize">{{ p.method?.replace('_', ' ') }}</td>
                    <td>{{ money(p.amount, p.currency) }}</td>
                    <td>
                        <span :class="badgeClass(p.status)">{{ p.status }}</span>
                        <p v-if="p.status === 'refunded' && p.refund_reason" class="mt-1 text-xs text-slate-400">{{ p.refund_reason }}</p>
                    </td>
                    <td>
                        <button v-if="p.status === 'paid'" class="text-sm font-semibold text-red-600 hover:underline" @click="openRefund(p)">
                            Rembourser
                        </button>
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
                Ceci marque le paiement comme rembourse dans MOKILI TOUR. Peex ne propose pas de remboursement automatique :
                le transfert reel au client (mobile money / virement) doit etre effectue separement.
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
