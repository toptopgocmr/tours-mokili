<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    gateways: { type: Array, required: true },
    countries: { type: Object, default: () => ({}) },
});

const isAdmin = computed(() => usePage().props.auth?.user?.role === 'admin');

// Copie locale editable de chaque passerelle.
const drafts = reactive(
    Object.fromEntries(
        props.gateways.map((g) => [g.key, { collect_enabled: g.collect_enabled, payout_enabled: g.payout_enabled, preferred_countries: [...g.preferred_countries] }]),
    ),
);

const toggleCountry = (key, code) => {
    const list = drafts[key].preferred_countries;
    const i = list.indexOf(code);
    i >= 0 ? list.splice(i, 1) : list.push(code);
};

const save = (key) => router.put(`/admin/passerelles/${key}`, drafts[key], { preserveScroll: true });

const copy = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
    } catch {
        /* presse-papiers indisponible */
    }
};
</script>

<template>
    <Head title="Moyens de paiement" />

    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-900">Moyens de paiement</h1>
        <p class="text-sm text-slate-500">
            Collecte (clients) et decaissement (remboursements, partenaires) Mobile Money. Les cles API se renseignent dans les
            variables Railway ; ici vous activez chaque passerelle et choisissez celle recommandee par pays.
        </p>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        <div v-for="g in gateways" :key="g.key" class="console-panel">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">{{ g.label }}</h2>
                    <p class="text-xs text-slate-500">Pays : {{ g.countries.map((c) => countries[c] ?? c).join(', ') }}</p>
                </div>
                <span :class="g.configured ? 'console-badge console-badge-success' : 'console-badge console-badge-error'">
                    {{ g.configured ? 'Cles configurees' : 'Cles manquantes' }}
                </span>
            </div>

            <div class="mt-4 flex flex-wrap gap-5 text-sm">
                <label class="flex items-center gap-2">
                    <input v-model="drafts[g.key].collect_enabled" type="checkbox" :disabled="!isAdmin" />
                    Collecte (pay-in)
                </label>
                <label class="flex items-center gap-2">
                    <input v-model="drafts[g.key].payout_enabled" type="checkbox" :disabled="!isAdmin" />
                    Decaissement (pay-out)
                </label>
            </div>

            <div class="mt-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Passerelle recommandee pour</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <button
                        v-for="c in g.countries"
                        :key="c"
                        type="button"
                        :disabled="!isAdmin"
                        class="rounded-full border px-3 py-1 text-xs font-medium"
                        :class="drafts[g.key].preferred_countries.includes(c) ? 'border-[#0972D3] bg-[#0972D3] text-white' : 'border-slate-300 text-slate-600'"
                        @click="toggleCountry(g.key, c)"
                    >
                        {{ countries[c] ?? c }}
                    </button>
                </div>
            </div>

            <div class="mt-4 rounded bg-slate-50 p-3 text-xs">
                <p class="font-semibold text-slate-700">URL de callback a declarer chez {{ g.label }} :</p>
                <div class="mt-1 flex items-center gap-2">
                    <code class="flex-1 break-all text-slate-600">{{ g.webhook_url }}</code>
                    <button type="button" class="text-[#0972D3] hover:underline" @click="copy(g.webhook_url)">Copier</button>
                </div>
                <p class="mt-3 font-semibold text-slate-700">Variables Railway :</p>
                <p class="mt-1 text-slate-500">{{ g.env.join(' · ') }}</p>
                <p v-if="!g.status_check" class="mt-2 text-amber-700">
                    Pas de verification de statut automatique : le resultat arrive par callback, sinon confirmez manuellement depuis Paiements.
                </p>
            </div>

            <button v-if="isAdmin" class="btn-console-primary mt-4 w-full" @click="save(g.key)">Enregistrer</button>
        </div>
    </div>
</template>
