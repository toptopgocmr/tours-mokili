<script setup>
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: PartnerLayout });

const props = defineProps({ vehicle: { type: Object, default: null } });
const isEdit = computed(() => !!props.vehicle);

const form = useForm({
    title: props.vehicle?.title ?? '',
    offer_type: props.vehicle?.offer_type ?? 'location',
    description: props.vehicle?.description ?? '',
    sale_price: props.vehicle?.sale_price ?? '',
    mileage_km: props.vehicle?.mileage_km ?? '',
    fuel: props.vehicle?.fuel ?? '',
    services: props.vehicle?.services ?? [],
    phone: props.vehicle?.phone ?? '',
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
    intent: 'draft',
    image: null,
    remove_image: false,
});

const statusLabels = { draft: 'Brouillon', pending: 'En attente de validation', published: 'Publie', rejected: 'Rejete' };
const statusLabel = computed(() => statusLabels[props.vehicle?.status] ?? 'Brouillon');

const imagePreview = ref(props.vehicle?.image_url ?? null);
const onImageChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    form.image = file;
    form.remove_image = false;
    imagePreview.value = URL.createObjectURL(file);
};

const submit = (intent) => {
    form.intent = intent;
    if (isEdit.value) form.put(`/partner/voiture/${props.vehicle.id}`);
    else form.post('/partner/voiture');
};
</script>

<template>
    <Head :title="isEdit ? 'Editer le vehicule' : 'Nouveau vehicule'" />
    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-900">{{ isEdit ? 'Editer' : 'Nouveau' }} vehicule</h1>
    </div>

    <form class="console-panel grid max-w-3xl gap-4 sm:grid-cols-2" @submit.prevent="submit">
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Photo</label>
            <div class="mt-1 flex items-center gap-4">
                <img v-if="imagePreview" :src="imagePreview" class="h-20 w-28 rounded-lg border border-slate-200 object-cover" />
                <div v-else class="flex h-20 w-28 items-center justify-center rounded-lg border border-dashed border-slate-300 text-xs text-slate-400">Aucune photo</div>
                <div>
                    <input type="file" accept="image/*" class="text-sm" @change="onImageChange" />
                    <label v-if="imagePreview" class="mt-1 flex items-center gap-1.5 text-xs text-red-600">
                        <input v-model="form.remove_image" type="checkbox" /> Supprimer la photo actuelle
                    </label>
                </div>
            </div>
        </div>
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Type d'annonce</label>
            <div class="mt-1 flex flex-wrap gap-2">
                <label v-for="(label, key) in $page.props.catalogue.voiture.offer_types" :key="key" class="flex items-center gap-1.5 rounded border border-slate-300 px-3 py-1.5 text-sm">
                    <input v-model="form.offer_type" type="radio" :value="key" /> {{ label }}
                </label>
            </div>
        </div>
        <div v-if="form.offer_type === 'mecanique'" class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Prestations proposees</label>
            <div class="mt-1 flex flex-wrap gap-3 text-sm">
                <label v-for="s in $page.props.catalogue.voiture.mechanic_services" :key="s" class="flex items-center gap-1.5"><input v-model="form.services" type="checkbox" :value="s" /> {{ s }}</label>
            </div>
        </div>
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
        <div v-if="form.offer_type === 'location'">
            <label class="text-sm font-medium text-slate-700">Prix / jour</label>
            <input v-model.number="form.price_per_day" type="number" min="0" step="0.01" class="mt-1 w-full rounded border-slate-300" required />
        </div>
        <div v-else>
            <label class="text-sm font-medium text-slate-700">{{ form.offer_type === 'vente' ? 'Prix de vente' : 'Prix a partir de' }}</label>
            <input v-model.number="form.sale_price" type="number" min="0" step="1" class="mt-1 w-full rounded border-slate-300" :required="form.offer_type === 'vente'" />
            <p v-if="form.errors.sale_price" class="mt-1 text-xs text-red-600">{{ form.errors.sale_price }}</p>
        </div>
        <div v-if="form.offer_type === 'vente'">
            <label class="text-sm font-medium text-slate-700">Kilometrage</label>
            <input v-model.number="form.mileage_km" type="number" min="0" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div v-if="form.offer_type !== 'mecanique'">
            <label class="text-sm font-medium text-slate-700">Carburant</label>
            <select v-model="form.fuel" class="mt-1 w-full rounded border-slate-300">
                <option value="">--</option><option value="essence">Essence</option><option value="diesel">Diesel</option><option value="hybride">Hybride</option><option value="electrique">Electrique</option>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Telephone / WhatsApp</label>
            <input v-model="form.phone" type="tel" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Description</label>
            <textarea v-model="form.description" rows="3" class="mt-1 w-full rounded border-slate-300" />
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
        <div v-if="vehicle?.status === 'rejected'" class="sm:col-span-2 rounded-lg bg-red-50 p-3 text-sm text-red-700">
            <p class="font-semibold">Annonce rejetee par un administrateur</p>
            <p class="mt-1">{{ vehicle.rejection_reason }}</p>
        </div>
        <div class="flex items-center gap-2 sm:col-span-2">
            <input v-model="form.is_active" type="checkbox" id="active" />
            <label for="active" class="text-sm text-slate-700">Actif (visible une fois publie)</label>
        </div>
        <p class="text-sm text-slate-500 sm:col-span-2">Statut actuel : <span class="font-semibold text-slate-700">{{ statusLabel }}</span></p>
        <div class="sm:col-span-2 flex gap-3">
            <button type="button" class="btn-console-secondary flex-1" :disabled="form.processing" @click="submit('draft')">Enregistrer comme brouillon</button>
            <button type="button" class="btn-console-primary flex-1" :disabled="form.processing" @click="submit('submit')">Soumettre pour validation</button>
        </div>
    </form>
</template>
