<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    offer: { type: Object, default: null },
});

const isEdit = computed(() => !!props.offer);

const form = useForm({
    title: props.offer?.title ?? '',
    type: props.offer?.type ?? 'vol',
    description: props.offer?.description ?? '',
    origin_city: props.offer?.origin_city ?? '',
    origin_country: props.offer?.origin_country ?? '',
    destination_city: props.offer?.destination_city ?? '',
    destination_country: props.offer?.destination_country ?? '',
    airline: props.offer?.airline ?? '',
    departure_at: props.offer?.departure_at?.slice(0, 16) ?? '',
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
                <option value="sejour">Sejour</option>
                <option value="circuit">Circuit</option>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Compagnie</label>
            <input v-model="form.airline" type="text" class="mt-1 w-full rounded border-slate-300" />
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
