<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    payouts: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    methods: { type: Object, default: () => ({ countries: [], gateways: [] }) },
    stats: { type: Object, required: true },
    prefill: { type: Object, default: null },
});

const isAdmin = computed(() => usePage().props.auth?.user?.role === 'admin');
const GATEWAY_LABELS = computed(() => Object.fromEntries((props.methods.gateways ?? []).map((g) => [g.key, g.label])));
const money = (v, c) => `${Number(v).toLocaleString('fr-FR')} ${c}`;

const badgeClass = (s) => ({
    paid: 'console-badge console-badge-success',
    pending: 'console-badge console-badge-pending',
    processing: 'console-badge console-badge-pending',
    failed: 'console-badge console-badge-error',
}[s] ?? 'console-badge console-badge-neutral');

const STATUS_LABELS = { paid: 'Envoye', pending: 'En attente', processing: 'En cours', failed: 'Echoue' };

// --- Filtres ---
const status = ref(props.filters.status ?? '');
const gatewayFilter = ref(props.filters.gateway ?? '');
const filter = () => router.get('/admin/decaissements', { status: status.value, gateway: gatewayFilter.value }, { preserveState: true });

// --- Nouveau decaissement ---
const showForm = ref(!!props.prefill);
const countries = computed(() => props.methods.countries ?? []);
const form = useForm({
    gateway: props.prefill?.gateway ?? null,
    beneficiary_name: props.prefill?.beneficiary_name ?? '',
    phone: props.prefill?.phone ?? '',
    country_code: props.prefill?.country_code ?? countries.value[0]?.code ?? 'CG',
    network: props.prefill?.network ?? null,
    amount: props.prefill?.amount ?? '',
    currency: props.prefill?.currency ?? 'XAF',
    reason: props.prefill?.reason ?? '',
    payment_id: props.prefill?.payment_id ?? null,
    booking_id: props.prefill?.booking_id ?? null,
    user_id: props.prefill?.user_id ?? null,
});

const country = computed(() => countries.value.find((c) => c.code === form.country_code));
watch(
    () => form.country_code,
    () => {
        form.network = country.value?.networks?.[0]?.key ?? null;
        if (!country.value?.gateways?.includes(form.gateway)) form.gateway = country.value?.preferred ?? country.value?.gateways?.[0] ?? null;
    },
    { immediate: !props.prefill },
);

const submit = () => {
    if (!confirm(`Envoyer ${money(form.amount || 0, form.currency)} a ${form.beneficiary_name} (${form.phone}) via ${GATEWAY_LABELS.value[form.gateway] ?? form.gateway} ?`)) return;
    form.post('/admin/decaissements', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showForm.value = false;
        },
    });
};

const refresh = (p) => router.post(`/admin/decaissements/${p.id}/verifier`, {}, { preserveScroll: true });
</script>

<template>
    <Head title="Decaissements" />

    <div class="mb-5 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Decaissements Mobile Money</h1>
            <p class="text-sm text-slate-500">Remboursements clients, reversements partenaires et paiements fournisseurs via Peex, pawaPay, MaishaPay ou Digitwace.</p>
        </div>
        <button v-if="isAdmin" class="btn-console-primary" @click="showForm = !showForm">{{ showForm ? 'Fermer' : '+ Nouveau decaissement' }}</button>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <div class="console-stat">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total envoye</p>
            <p class="mt-1 text-xl font-bold text-slate-900">{{ money(stats.totalPaid, 'XAF') }}</p>
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

    <!-- Formulaire -->
    <form v-if="showForm && isAdmin" class="console-panel mt-5 grid max-w-3xl gap-4 sm:grid-cols-2" @submit.prevent="submit">
        <div v-if="prefill" class="rounded bg-blue-50 p-3 text-sm text-blue-800 sm:col-span-2">
            Remboursement du paiement #{{ prefill.payment_id }} : le paiement sera marque "rembourse" une fois le decaissement accepte.
        </div>
        <div v-if="!countries.length" class="rounded bg-amber-50 p-3 text-sm text-amber-800 sm:col-span-2">
            Aucune passerelle de decaissement n'est configuree. Renseignez les cles API dans Railway puis activez-les dans
            <Link href="/admin/passerelles" class="font-semibold underline">Moyens de paiement</Link>.
        </div>
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Beneficiaire</label>
            <input v-model="form.beneficiary_name" type="text" class="mt-1 w-full rounded border-slate-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Pays</label>
            <select v-model="form.country_code" class="mt-1 w-full rounded border-slate-300">
                <option v-for="c in countries" :key="c.code" :value="c.code">{{ c.name }} (+{{ c.dial_code }})</option>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Operateur</label>
            <select v-model="form.network" class="mt-1 w-full rounded border-slate-300">
                <option v-for="n in country?.networks ?? []" :key="n.key" :value="n.key">{{ n.label }}</option>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Numero Mobile Money</label>
            <input v-model="form.phone" type="tel" class="mt-1 w-full rounded border-slate-300" placeholder="06 123 45 67" required />
            <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600">{{ form.errors.phone }}</p>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Passerelle</label>
            <select v-model="form.gateway" class="mt-1 w-full rounded border-slate-300" required>
                <option v-for="g in country?.gateways ?? []" :key="g" :value="g">{{ GATEWAY_LABELS[g] ?? g }}{{ g === country.preferred ? ' (recommandee)' : '' }}</option>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Montant</label>
            <input v-model.number="form.amount" type="number" min="1" class="mt-1 w-full rounded border-slate-300" required />
            <p v-if="form.errors.amount" class="mt-1 text-xs text-red-600">{{ form.errors.amount }}</p>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Devise</label>
            <input v-model="form.currency" type="text" maxlength="3" class="mt-1 w-full rounded border-slate-300" required />
        </div>
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Motif</label>
            <input v-model="form.reason" type="text" class="mt-1 w-full rounded border-slate-300" placeholder="Remboursement, reversement partenaire..." />
        </div>
        <div class="sm:col-span-2">
            <button type="submit" class="btn-console-primary w-full" :disabled="form.processing || !form.gateway">Envoyer l'argent</button>
        </div>
    </form>

    <div class="mb-4 mt-5 flex gap-2">
        <select v-model="status" class="rounded border-slate-300 text-sm" @change="filter">
            <option value="">Tous les statuts</option>
            <option v-for="(label, key) in STATUS_LABELS" :key="key" :value="key">{{ label }}</option>
        </select>
        <select v-model="gatewayFilter" class="rounded border-slate-300 text-sm" @change="filter">
            <option value="">Toutes les passerelles</option>
            <option v-for="g in methods.gateways" :key="g.key" :value="g.key">{{ g.label }}</option>
        </select>
    </div>

    <div class="console-table-wrap">
        <table class="console-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Beneficiaire</th>
                    <th>Passerelle</th>
                    <th>Montant</th>
                    <th>Motif</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="p in payouts.data" :key="p.id">
                    <td class="whitespace-nowrap text-xs">{{ new Date(p.created_at).toLocaleString('fr-FR') }}</td>
                    <td>
                        <p class="font-medium text-slate-900">{{ p.beneficiary_name }}</p>
                        <p class="text-xs text-slate-400">+{{ p.phone }} {{ p.network ?? '' }}</p>
                    </td>
                    <td>{{ GATEWAY_LABELS[p.gateway] ?? p.gateway }}</td>
                    <td class="font-medium">{{ money(p.amount, p.currency) }}</td>
                    <td class="max-w-[220px] text-xs">
                        {{ p.reason }}
                        <span v-if="p.booking" class="block text-slate-400">Resa {{ p.booking.reference }}</span>
                        <span v-if="p.creator" class="block text-slate-400">par {{ p.creator.name }}</span>
                    </td>
                    <td>
                        <span :class="badgeClass(p.status)">{{ STATUS_LABELS[p.status] ?? p.status }}</span>
                        <p v-if="p.failure_reason" class="mt-1 max-w-[200px] text-xs text-red-400">{{ p.failure_reason }}</p>
                    </td>
                    <td class="text-right">
                        <button v-if="['pending', 'processing'].includes(p.status)" class="text-sm font-semibold text-[#0972D3] hover:underline" @click="refresh(p)">
                            Verifier
                        </button>
                    </td>
                </tr>
                <tr v-if="!payouts.data.length"><td colspan="7" class="py-6 text-center text-slate-400">Aucun decaissement.</td></tr>
            </tbody>
        </table>
    </div>
</template>
