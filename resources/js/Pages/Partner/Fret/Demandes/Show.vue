<script setup>
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: PartnerLayout });
const props = defineProps({ quoteRequest: Object, defaults: Object });
const page = usePage();
const r = props.quoteRequest;

const form = useForm({
    currency: 'XAF',
    lines: [{ label: 'Transport', amount: '', kind: 'included' }],
    is_firm: true,
    valid_until: props.defaults.valid_until,
    transit_days: '',
    conditions: '',
    exclusions: '',
});
const total = computed(() => form.lines.filter((l) => l.kind !== 'excluded').reduce((s, l) => s + (Number(l.amount) || 0), 0));
const addLine = () => form.lines.push({ label: '', amount: 0, kind: 'included' });
const submit = () => form.post(`/partner/fret/demandes/${r.reference}/devis`);
</script>

<template>
    <Head :title="r.reference" />
    <Link href="/partner/fret/demandes" class="text-sm text-slate-500">&larr; Demandes</Link>
    <h1 class="mb-3 mt-1 text-xl font-bold text-slate-900">{{ r.reference }} - {{ r.user?.name }}</h1>
    <p v-if="page.props.flash?.error" class="mb-3 rounded bg-red-50 p-2 text-sm text-red-700">{{ page.props.flash.error }}</p>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="console-panel text-sm">
            <p><b>Mode :</b> {{ r.mode }} - {{ r.cargo_type }} <span v-if="r.load_type">({{ r.load_type }} {{ r.container_type }})</span></p>
            <p><b>Trajet :</b> {{ r.origin_city }} ({{ r.origin_country }}) &rarr; {{ r.destination_city }} ({{ r.destination_country }})</p>
            <p><b>Marchandise :</b> {{ r.description }}</p>
            <p><b>Mesures :</b> {{ r.weight_kg ?? '-' }} kg - {{ r.volume_m3 ?? '-' }} m3 - {{ r.dimensions ?? '-' }} - {{ r.packages ?? '-' }} colis</p>
            <p><b>Dates :</b> pret {{ r.ready_date?.slice(0, 10) ?? '-' }}, enlevement {{ r.desired_pickup_date?.slice(0, 10) ?? '-' }}, livraison {{ r.desired_delivery_date?.slice(0, 10) ?? '-' }}</p>
            <p><b>Services :</b> {{ (r.services ?? []).join(', ') || '-' }}</p>
            <p v-if="r.dangerous_goods" class="font-semibold text-red-600">Marchandise dangereuse / reglementee declaree</p>
            <p class="mt-2"><b>Documents :</b></p>
            <p v-for="d in r.documents" :key="d.id"><a :href="`/fret/documents/${d.id}/telecharger`" class="text-blue-600">{{ d.type }} - {{ d.original_name }}</a></p>
            <div v-if="r.quotes?.length" class="mt-3"><b>Vos devis :</b>
                <p v-for="q in r.quotes" :key="q.id">v{{ q.version }} - {{ q.total_amount }} {{ q.currency }} - {{ q.status }}</p>
            </div>
        </div>

        <form class="console-panel space-y-2 text-sm" @submit.prevent="submit">
            <p class="font-semibold">Repondre par un devis detaille (une nouvelle version remplace la precedente)</p>
            <div v-for="(l, i) in form.lines" :key="i" class="flex gap-2">
                <input v-model="l.label" placeholder="Libelle" class="flex-1 rounded border-slate-300" required />
                <input v-model.number="l.amount" type="number" step="0.01" class="w-28 rounded border-slate-300" required />
                <select v-model="l.kind" class="rounded border-slate-300"><option value="included">Inclus</option><option value="estimated">Estime</option><option value="third_party">Tiers</option><option value="excluded">Non inclus</option></select>
            </div>
            <button type="button" class="text-blue-600" @click="addLine">+ ligne</button>
            <p class="text-right font-semibold">Total : {{ total.toLocaleString('fr-FR') }}
                <select v-model="form.currency" class="ml-1 rounded border-slate-300"><option>XAF</option><option>USD</option><option>EUR</option></select></p>
            <label class="flex items-center gap-2"><input v-model="form.is_firm" type="checkbox" /> Prix ferme (sinon estimation)</label>
            <div class="grid grid-cols-2 gap-2">
                <label>Valable jusqu'au<input v-model="form.valid_until" type="date" class="mt-1 w-full rounded border-slate-300" required /></label>
                <label>Delai indicatif (jours)<input v-model.number="form.transit_days" type="number" min="0" class="mt-1 w-full rounded border-slate-300" /></label>
            </div>
            <textarea v-model="form.exclusions" rows="2" placeholder="Non inclus : douane, taxes, stockage, livraison..." class="w-full rounded border-slate-300"></textarea>
            <textarea v-model="form.conditions" rows="2" placeholder="Conditions" class="w-full rounded border-slate-300"></textarea>
            <p class="text-xs text-red-600">{{ Object.values(form.errors).join(' ') }}</p>
            <button class="btn-console-primary w-full" :disabled="form.processing">Transmettre le devis</button>
        </form>
    </div>
</template>
