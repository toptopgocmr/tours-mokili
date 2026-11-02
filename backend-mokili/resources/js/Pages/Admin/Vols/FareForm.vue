<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    fare: { type: Object, default: null },
    suppliers: { type: Array, default: () => [] },
    airports: { type: Array, default: () => [] },
    cabins: { type: Object, default: () => ({}) },
});

const isEdit = computed(() => !!props.fare);
const f = props.fare ?? {};
const form = useForm({
    flight_supplier_id: f.flight_supplier_id ?? props.suppliers[0]?.id ?? '',
    airline_code: f.airline_code ?? '', airline_name: f.airline_name ?? '', flight_number: f.flight_number ?? '',
    origin: f.origin ?? 'BZV', destination: f.destination ?? '',
    departure_time: f.departure_time ?? '', arrival_time: f.arrival_time ?? '', arrival_day_offset: f.arrival_day_offset ?? 0,
    duration_minutes: f.duration_minutes ?? '', stops: f.stops ?? 0, via: f.via ?? '',
    days_of_week: f.days_of_week ?? [], valid_from: f.valid_from?.slice(0, 10) ?? '', valid_to: f.valid_to?.slice(0, 10) ?? '',
    cabin: f.cabin ?? 'economy', fare_family: f.fare_family ?? '',
    base_fare: f.base_fare ?? '', taxes: f.taxes ?? 0, child_percent: f.child_percent ?? 75, infant_percent: f.infant_percent ?? 10,
    currency: f.currency ?? 'XAF', baggage: f.baggage ?? '1 x 23 kg', cabin_baggage: f.cabin_baggage ?? '1 x 7 kg',
    refundable: f.refundable ?? false, changeable: f.changeable ?? true, change_fee: f.change_fee ?? '', cancel_fee: f.cancel_fee ?? '',
    conditions: f.conditions ?? '', seats_available: f.seats_available ?? '', ticketing_delay_hours: f.ticketing_delay_hours ?? 24,
    is_active: f.is_active ?? true,
});

const DAYS = [[1, 'Lun'], [2, 'Mar'], [3, 'Mer'], [4, 'Jeu'], [5, 'Ven'], [6, 'Sam'], [7, 'Dim']];
const submit = () => (isEdit.value ? form.put(`/admin/vols/tarifs/${props.fare.id}`) : form.post('/admin/vols/tarifs'));
const input = 'mt-1 w-full rounded border-slate-300 text-sm';
</script>

<template>
    <Head :title="isEdit ? 'Editer un tarif' : 'Nouveau tarif'" />
    <Link href="/admin/vols/tarifs" class="text-sm text-slate-500 hover:underline">&larr; Tarifs vols</Link>
    <h1 class="mb-4 mt-1 text-xl font-bold text-slate-900">{{ isEdit ? 'Editer le' : 'Nouveau' }} tarif negocie</h1>

    <form class="console-panel grid max-w-4xl gap-4 sm:grid-cols-4" @submit.prevent="submit">
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Fournisseur / consolidateur</label>
            <select v-model="form.flight_supplier_id" :class="input"><option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option></select>
        </div>
        <div><label class="text-sm font-medium text-slate-700">Code compagnie</label><input v-model="form.airline_code" maxlength="3" placeholder="ET, AF, WB..." :class="input" required /></div>
        <div><label class="text-sm font-medium text-slate-700">N° de vol</label><input v-model="form.flight_number" placeholder="ET 834" :class="input" /></div>
        <div class="sm:col-span-2"><label class="text-sm font-medium text-slate-700">Compagnie</label><input v-model="form.airline_name" placeholder="Ethiopian Airlines" :class="input" required /></div>
        <div>
            <label class="text-sm font-medium text-slate-700">Depart</label>
            <select v-model="form.origin" :class="input"><option v-for="a in airports" :key="a.iata_code" :value="a.iata_code">{{ a.iata_code }} - {{ a.city }}</option></select>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Arrivee</label>
            <select v-model="form.destination" :class="input" required><option value="">--</option><option v-for="a in airports" :key="a.iata_code" :value="a.iata_code">{{ a.iata_code }} - {{ a.city }}</option></select>
            <p v-if="form.errors.destination" class="text-xs text-red-600">{{ form.errors.destination }}</p>
        </div>
        <div><label class="text-sm font-medium text-slate-700">Heure depart (locale)</label><input v-model="form.departure_time" type="time" :class="input" required /></div>
        <div><label class="text-sm font-medium text-slate-700">Heure arrivee (locale)</label><input v-model="form.arrival_time" type="time" :class="input" required /></div>
        <div><label class="text-sm font-medium text-slate-700">Arrivee</label><select v-model="form.arrival_day_offset" :class="input"><option :value="0">Meme jour</option><option :value="1">Lendemain (+1)</option><option :value="2">+2 jours</option></select></div>
        <div><label class="text-sm font-medium text-slate-700">Duree (minutes)</label><input v-model="form.duration_minutes" type="number" min="20" :class="input" /></div>
        <div><label class="text-sm font-medium text-slate-700">Escales</label><input v-model="form.stops" type="number" min="0" max="3" :class="input" /></div>
        <div><label class="text-sm font-medium text-slate-700">Via</label><input v-model="form.via" placeholder="ADD" :class="input" /></div>
        <div class="sm:col-span-4">
            <label class="text-sm font-medium text-slate-700">Jours d'operation (aucun = tous les jours)</label>
            <div class="mt-1 flex flex-wrap gap-3 text-sm">
                <label v-for="[v, l] in DAYS" :key="v" class="flex items-center gap-1"><input v-model="form.days_of_week" type="checkbox" :value="v" /> {{ l }}</label>
            </div>
        </div>
        <div><label class="text-sm font-medium text-slate-700">Valable du</label><input v-model="form.valid_from" type="date" :class="input" /></div>
        <div><label class="text-sm font-medium text-slate-700">au</label><input v-model="form.valid_to" type="date" :class="input" /></div>
        <div><label class="text-sm font-medium text-slate-700">Classe</label><select v-model="form.cabin" :class="input"><option v-for="(l, k) in cabins" :key="k" :value="k">{{ l }}</option></select></div>
        <div><label class="text-sm font-medium text-slate-700">Famille tarifaire</label><input v-model="form.fare_family" placeholder="Eco Classic" :class="input" /></div>
        <div><label class="text-sm font-medium text-slate-700">Tarif de base (adulte)</label><input v-model="form.base_fare" type="number" min="0" :class="input" required /></div>
        <div><label class="text-sm font-medium text-slate-700">Taxes (adulte)</label><input v-model="form.taxes" type="number" min="0" :class="input" required /></div>
        <div><label class="text-sm font-medium text-slate-700">Enfant (% du tarif)</label><input v-model="form.child_percent" type="number" min="0" max="100" :class="input" /></div>
        <div><label class="text-sm font-medium text-slate-700">Bebe (% du total)</label><input v-model="form.infant_percent" type="number" min="0" max="100" :class="input" /></div>
        <div><label class="text-sm font-medium text-slate-700">Devise</label><input v-model="form.currency" maxlength="3" :class="input" /></div>
        <div><label class="text-sm font-medium text-slate-700">Bagage soute</label><input v-model="form.baggage" :class="input" /></div>
        <div><label class="text-sm font-medium text-slate-700">Bagage cabine</label><input v-model="form.cabin_baggage" :class="input" /></div>
        <div><label class="text-sm font-medium text-slate-700">Sieges disponibles</label><input v-model="form.seats_available" type="number" min="0" placeholder="illimite" :class="input" /></div>
        <div class="flex items-center gap-2"><input id="ref" v-model="form.refundable" type="checkbox" /><label for="ref" class="text-sm">Remboursable</label></div>
        <div><label class="text-sm font-medium text-slate-700">Frais d'annulation</label><input v-model="form.cancel_fee" type="number" min="0" :class="input" /></div>
        <div class="flex items-center gap-2"><input id="chg" v-model="form.changeable" type="checkbox" /><label for="chg" class="text-sm">Modifiable</label></div>
        <div><label class="text-sm font-medium text-slate-700">Frais de modification</label><input v-model="form.change_fee" type="number" min="0" :class="input" /></div>
        <div><label class="text-sm font-medium text-slate-700">Delai d'emission (h)</label><input v-model="form.ticketing_delay_hours" type="number" min="2" :class="input" /></div>
        <div class="sm:col-span-4"><label class="text-sm font-medium text-slate-700">Conditions affichees au client</label><textarea v-model="form.conditions" rows="3" :class="input" /></div>
        <div class="flex items-center gap-2 sm:col-span-4"><input id="act" v-model="form.is_active" type="checkbox" /><label for="act" class="text-sm">Actif (propose dans la recherche)</label></div>
        <div v-if="Object.keys(form.errors).length" class="text-sm text-red-600 sm:col-span-4">{{ Object.values(form.errors)[0] }}</div>
        <div class="sm:col-span-4"><button class="btn-console-primary w-full" :disabled="form.processing">{{ isEdit ? 'Enregistrer' : 'Creer le tarif' }}</button></div>
    </form>
</template>
