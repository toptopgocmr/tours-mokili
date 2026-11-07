<script setup>
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: PartnerLayout });

const props = defineProps({ offer: { type: Object, default: null } });
const isEdit = computed(() => !!props.offer);

const form = useForm({
    title: props.offer?.title ?? '',
    description: props.offer?.description ?? '',
    mode: props.offer?.mode ?? 'route',
    origin_city: props.offer?.origin_city ?? '',
    origin_country: props.offer?.origin_country ?? '',
    destination_city: props.offer?.destination_city ?? '',
    destination_country: props.offer?.destination_country ?? '',
    price_per_kg: props.offer?.price_per_kg ?? '',
    currency: props.offer?.currency ?? 'XAF',
    capacity_kg: props.offer?.capacity_kg ?? '',
    service_type: props.offer?.service_type ?? 'colis',
    pricing_mode: props.offer?.pricing_mode ?? 'instant',
    price_is_firm: props.offer?.price_is_firm ?? false,
    unit: props.offer?.unit ?? 'kg',
    min_price: props.offer?.min_price ?? '',
    valid_until: props.offer?.valid_until?.slice(0, 10) ?? '',
    transit_days_min: props.offer?.transit_days_min ?? '',
    transit_days_max: props.offer?.transit_days_max ?? '',
    promo_percent: props.offer?.promo_percent ?? '',
    promo_until: props.offer?.promo_until?.slice(0, 10) ?? '',
    inclusions: props.offer?.inclusions ?? '',
    exclusions: props.offer?.exclusions ?? '',
    restrictions: props.offer?.restrictions ?? '',
    is_active: props.offer?.is_active ?? true,
    intent: 'draft',
    image: null,
    remove_image: false,
});

const statusLabels = { draft: 'Brouillon', pending: 'En attente de validation', published: 'Publie', rejected: 'Rejete' };
const statusLabel = computed(() => statusLabels[props.offer?.status] ?? 'Brouillon');

const imagePreview = ref(props.offer?.image_url ?? null);
const onImageChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    form.image = file;
    form.remove_image = false;
    imagePreview.value = URL.createObjectURL(file);
};

const submit = (intent) => {
    form.intent = intent;
    if (isEdit.value) form.put(`/partner/fret/${props.offer.id}`);
    else form.post('/partner/fret');
};
</script>

<template>
    <Head :title="isEdit ? 'Editer une offre de fret' : 'Nouvelle offre de fret'" />

    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-900">{{ isEdit ? 'Editer' : 'Nouvelle' }} offre de fret</h1>
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
            <label class="text-sm font-medium text-slate-700">Titre</label>
            <input v-model="form.title" type="text" class="mt-1 w-full rounded border-slate-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Mode</label>
            <select v-model="form.mode" class="mt-1 w-full rounded border-slate-300">
                <option value="route">Routier</option>
                <option value="mer">Maritime</option>
                <option value="air">Aerien</option>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Capacite (kg)</label>
            <input v-model.number="form.capacity_kg" type="number" min="0" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Ville de depart</label>
            <input v-model="form.origin_city" type="text" class="mt-1 w-full rounded border-slate-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Pays de depart (ISO2)</label>
            <input v-model="form.origin_country" type="text" maxlength="2" class="mt-1 w-full rounded border-slate-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Ville de destination</label>
            <input v-model="form.destination_city" type="text" class="mt-1 w-full rounded border-slate-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Pays de destination (ISO2)</label>
            <input v-model="form.destination_country" type="text" maxlength="2" class="mt-1 w-full rounded border-slate-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Prix unitaire (0 si sur devis)</label>
            <input v-model.number="form.price_per_kg" type="number" min="0" step="0.01" class="mt-1 w-full rounded border-slate-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Devise</label>
            <input v-model="form.currency" type="text" maxlength="3" class="mt-1 w-full rounded border-slate-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Type de prestation</label>
            <select v-model="form.service_type" class="mt-1 w-full rounded border-slate-300">
                <option value="colis">Colis</option><option value="marchandises">Marchandises</option>
                <option value="vehicule">Vehicule</option><option value="conteneur">Conteneur</option><option value="groupage">Groupage</option>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Mode de vente</label>
            <select v-model="form.pricing_mode" class="mt-1 w-full rounded border-slate-300">
                <option value="instant">Tarif instantane (grille)</option>
                <option value="quote">Sur devis personnalise</option>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Unite de tarification</label>
            <select v-model="form.unit" class="mt-1 w-full rounded border-slate-300">
                <option value="kg">Par kg (poids taxable)</option><option value="m3">Par m3</option>
                <option value="tonne">Par tonne</option><option value="forfait">Forfait</option>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Facturation minimum</label>
            <input v-model.number="form.min_price" type="number" min="0" step="0.01" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div class="flex items-center gap-2 sm:col-span-2">
            <input v-model="form.price_is_firm" type="checkbox" id="firm" />
            <label for="firm" class="text-sm text-slate-700">Prix ferme (sinon presente comme estimation au client)</label>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Valide jusqu'au</label>
            <input v-model="form.valid_until" type="date" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div><label class="text-sm font-medium text-slate-700">Delai min (j)</label><input v-model.number="form.transit_days_min" type="number" min="0" class="mt-1 w-full rounded border-slate-300" /></div>
            <div><label class="text-sm font-medium text-slate-700">Delai max (j)</label><input v-model.number="form.transit_days_max" type="number" min="0" class="mt-1 w-full rounded border-slate-300" /></div>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Promotion (%)</label>
            <input v-model.number="form.promo_percent" type="number" min="0" max="90" step="0.5" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Promotion valable jusqu'au</label>
            <input v-model="form.promo_until" type="date" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div class="sm:col-span-2"><label class="text-sm font-medium text-slate-700">Services inclus</label><textarea v-model="form.inclusions" rows="2" class="mt-1 w-full rounded border-slate-300"></textarea></div>
        <div class="sm:col-span-2"><label class="text-sm font-medium text-slate-700">Exclus / frais non inclus (douane, taxes, stockage, livraison...)</label><textarea v-model="form.exclusions" rows="2" class="mt-1 w-full rounded border-slate-300"></textarea></div>
        <div class="sm:col-span-2"><label class="text-sm font-medium text-slate-700">Marchandises refusees / reglementees</label><textarea v-model="form.restrictions" rows="2" class="mt-1 w-full rounded border-slate-300"></textarea></div>
        <p class="rounded bg-amber-50 p-2 text-xs text-amber-800 sm:col-span-2">Une modification de tarif, destination ou condition d'une offre publiee la renvoie en validation avant republication.</p>
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Description</label>
            <textarea v-model="form.description" rows="4" class="mt-1 w-full rounded border-slate-300"></textarea>
        </div>
        <div v-if="offer?.status === 'rejected'" class="sm:col-span-2 rounded-lg bg-red-50 p-3 text-sm text-red-700">
            <p class="font-semibold">Annonce rejetee par un administrateur</p>
            <p class="mt-1">{{ offer.rejection_reason }}</p>
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
