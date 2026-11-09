<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    offer: { type: Object, default: null },
    transportModes: { type: Object, default: () => ({}) },
    tripScopes: { type: Object, default: () => ({}) },
    departments: { type: Array, default: () => [] },
});

const isEdit = computed(() => !!props.offer);

const form = useForm({
    title: props.offer?.title ?? '',
    type: props.offer?.type ?? 'vol',
    transport_mode: props.offer?.transport_mode ?? '',
    departure_point: props.offer?.departure_point ?? '',
    duration_label: props.offer?.duration_label ?? '',
    trip_scope: props.offer?.trip_scope ?? '',
    origin_department: props.offer?.origin_department ?? '',
    destination_department: props.offer?.destination_department ?? '',
    description: props.offer?.description ?? '',
    origin_city: props.offer?.origin_city ?? '',
    origin_country: props.offer?.origin_country ?? '',
    destination_city: props.offer?.destination_city ?? '',
    destination_country: props.offer?.destination_country ?? '',
    airline: props.offer?.airline ?? '',
    departure_at: props.offer?.departure_at?.slice(0, 16) ?? '',
    arrival_at: props.offer?.arrival_at?.slice(0, 16) ?? '',
    return_at: props.offer?.return_at?.slice(0, 16) ?? '',
    price: props.offer?.price ?? '',
    discount_percent: props.offer?.discount_percent ?? 0,
    currency: props.offer?.currency ?? 'XAF',
    seats_available: props.offer?.seats_available ?? 0,
    is_featured: props.offer?.is_featured ?? false,
    is_active: props.offer?.is_active ?? true,
    image: null,
    remove_image: false,
});

const imagePreview = ref(props.offer?.image_url ?? null);
const onImageChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    form.image = file;
    form.remove_image = false;
    imagePreview.value = URL.createObjectURL(file);
};

const submit = () => {
    if (isEdit.value) {
        form.put(`/admin/voyage/${props.offer.id}`);
    } else {
        form.post('/admin/voyage');
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Editer une offre' : 'Nouvelle offre'" />

    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-900">{{ isEdit ? 'Editer' : 'Nouvelle' }} offre Voyage</h1>
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
            <label class="text-sm font-medium text-slate-700">Type</label>
            <select v-model="form.type" class="mt-1 w-full rounded border-slate-300">
                <option value="vol">Vol</option>
                <option value="circuit">Circuit / trajet (bus, train, taxi...)</option>
                <option v-if="form.type === 'sejour'" value="sejour">Sejour (ancien)</option>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Compagnie / transporteur</label>
            <input v-model="form.airline" type="text" placeholder="Ex : Ocean du Nord, CFCO, ECAir..." class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div v-if="form.type !== 'vol'">
            <label class="text-sm font-medium text-slate-700">Moyen de transport <span v-if="form.type === 'circuit'" class="text-red-600">*</span></label>
            <select v-model="form.transport_mode" class="mt-1 w-full rounded border-slate-300" :required="form.type === 'circuit'">
                <option value="">-- Choisir --</option>
                <option v-for="(label, key) in transportModes" :key="key" :value="key">{{ label }}</option>
            </select>
            <p v-if="form.errors.transport_mode" class="mt-1 text-xs text-red-600">{{ form.errors.transport_mode }}</p>
        </div>
        <div v-if="form.type !== 'vol'">
            <label class="text-sm font-medium text-slate-700">Lieu de depart / embarquement</label>
            <input v-model="form.departure_point" type="text" placeholder="Ex : Gare routiere de Mikalou, Port du Beach..." class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div v-if="form.type !== 'vol'">
            <label class="text-sm font-medium text-slate-700">Portee du trajet</label>
            <select v-model="form.trip_scope" class="mt-1 w-full rounded border-slate-300">
                <option value="">--</option>
                <option v-for="(label, key) in tripScopes" :key="key" :value="key">{{ label }}</option>
            </select>
        </div>
        <div v-if="form.type !== 'vol'">
            <label class="text-sm font-medium text-slate-700">Departement de depart</label>
            <select v-model="form.origin_department" class="mt-1 w-full rounded border-slate-300">
                <option value="">--</option>
                <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
            </select>
        </div>
        <div v-if="form.type !== 'vol'">
            <label class="text-sm font-medium text-slate-700">Departement d'arrivee</label>
            <select v-model="form.destination_department" class="mt-1 w-full rounded border-slate-300">
                <option value="">--</option>
                <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
            </select>
        </div>
        <div v-if="form.type !== 'vol'">
            <label class="text-sm font-medium text-slate-700">Duree</label>
            <input v-model="form.duration_label" type="text" placeholder="Ex : 3 jours / 2 nuits, 8 h de route" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Ville de depart</label>
            <input v-model="form.origin_city" type="text" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Pays de depart (ISO2)</label>
            <input v-model="form.origin_country" type="text" maxlength="2" class="mt-1 w-full rounded border-slate-300" />
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
            <label class="text-sm font-medium text-slate-700">Depart le</label>
            <input v-model="form.departure_at" type="datetime-local" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div v-if="form.type === 'circuit'">
            <label class="text-sm font-medium text-slate-700">Arrivee prevue le</label>
            <input v-model="form.arrival_at" type="datetime-local" class="mt-1 w-full rounded border-slate-300" />
            <p v-if="form.errors.arrival_at" class="mt-1 text-xs text-red-600">{{ form.errors.arrival_at }}</p>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Retour le</label>
            <input v-model="form.return_at" type="datetime-local" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Prix</label>
            <input v-model.number="form.price" type="number" min="0" step="0.01" class="mt-1 w-full rounded border-slate-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Devise</label>
            <input v-model="form.currency" type="text" maxlength="3" class="mt-1 w-full rounded border-slate-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Reduction (%)</label>
            <input v-model.number="form.discount_percent" type="number" min="0" max="90" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Places disponibles</label>
            <input v-model.number="form.seats_available" type="number" min="0" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Description</label>
            <textarea v-model="form.description" rows="4" class="mt-1 w-full rounded border-slate-300"></textarea>
        </div>
        <div class="flex items-center gap-2">
            <input v-model="form.is_featured" type="checkbox" id="featured" />
            <label for="featured" class="text-sm text-slate-700">Mise en avant (page d'accueil)</label>
        </div>
        <div class="flex items-center gap-2">
            <input v-model="form.is_active" type="checkbox" id="active" />
            <label for="active" class="text-sm text-slate-700">Active (visible publiquement)</label>
        </div>

        <div class="sm:col-span-2">
            <button type="submit" class="btn-console-primary w-full" :disabled="form.processing">
                {{ isEdit ? 'Enregistrer les modifications' : 'Creer l\'offre' }}
            </button>
        </div>
    </form>
</template>
