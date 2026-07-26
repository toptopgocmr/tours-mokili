<script setup>
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: PartnerLayout });

const props = defineProps({ vehicle: { type: Object, default: null } });
const isEdit = computed(() => !!props.vehicle);

const form = useForm({
    title: props.vehicle?.title ?? '',
    brand: props.vehicle?.brand ?? '',
    model: props.vehicle?.model ?? '',
    year: props.vehicle?.year ?? '',
    category: props.vehicle?.category ?? 'berline',
    transmission: props.vehicle?.transmission ?? 'manuelle',
    seats: props.vehicle?.seats ?? 4,
    price_per_day: props.vehicle?.price_per_day ?? '',
    currency: props.vehicle?.currency ?? 'XAF',
    city: props.vehicle?.city ?? '',
    country: props.vehicle?.country ?? '',
    is_active: props.vehicle?.is_active ?? true,
});

const submit = () => isEdit.value ? form.put(`/partner/voiture/${props.vehicle.id}`) : form.post('/partner/voiture');
</script>

<template>
    <Head :title="isEdit ? 'Editer le vehicule' : 'Nouveau vehicule'" />
    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-900">{{ isEdit ? 'Editer' : 'Nouveau' }} vehicule</h1>
    </div>

    <form class="console-panel grid max-w-3xl gap-4 sm:grid-cols-2" @submit.prevent="submit">
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Titre de l'annonce</label>
            <input v-model="form.title" type="text" class="mt-1 w-full rounded border-slate-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Marque</label>
            <input v-model="form.brand" type="text" class="mt-1 w-full rounded border-slate-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Modele</label>
            <input v-model="form.model" type="text" class="mt-1 w-full rounded border-slate-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Annee</label>
            <input v-model.number="form.year" type="number" min="1980" max="2100" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Categorie</label>
            <select v-model="form.category" class="mt-1 w-full rounded border-slate-300">
                <option value="citadine">Citadine</option>
                <option value="berline">Berline</option>
                <option value="suv">SUV</option>
                <option value="utilitaire">Utilitaire</option>
                <option value="luxe">Luxe</option>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Transmission</label>
            <select v-model="form.transmission" class="mt-1 w-full rounded border-slate-300">
                <option value="manuelle">Manuelle</option>
                <option value="automatique">Automatique</option>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Places</label>
            <input v-model.number="form.seats" type="number" min="1" max="9" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Prix / jour</label>
            <input v-model.number="form.price_per_day" type="number" min="0" step="0.01" class="mt-1 w-full rounded border-slate-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Devise</label>
            <input v-model="form.currency" type="text" maxlength="3" class="mt-1 w-full rounded border-slate-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Ville</label>
            <input v-model="form.city" type="text" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Pays (ISO2)</label>
            <input v-model="form.country" type="text" maxlength="2" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div class="flex items-center gap-2 sm:col-span-2">
            <input v-model="form.is_active" type="checkbox" id="active" />
            <label for="active" class="text-sm text-slate-700">Publie (visible publiquement)</label>
        </div>
        <div class="sm:col-span-2">
            <button type="submit" class="btn-console-primary w-full" :disabled="form.processing">{{ isEdit ? 'Enregistrer' : 'Publier' }}</button>
        </div>
    </form>
</template>
