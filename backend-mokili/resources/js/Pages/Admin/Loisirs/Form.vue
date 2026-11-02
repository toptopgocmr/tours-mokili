<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    park: { type: Object, default: null },
    categories: { type: Object, default: () => ({}) },
    cities: { type: Array, default: () => [] },
});

const isEdit = computed(() => !!props.park);

const form = useForm({
    name: props.park?.name ?? '',
    category: props.park?.category ?? 'parc_attractions',
    city: props.park?.city ?? 'Brazzaville',
    district: props.park?.district ?? '',
    address: props.park?.address ?? '',
    description: props.park?.description ?? '',
    activities: (props.park?.activities ?? []).join('\n'),
    opening_hours: props.park?.opening_hours ?? '',
    price_adult: props.park?.price_adult ?? '',
    price_child: props.park?.price_child ?? '',
    currency: props.park?.currency ?? 'XAF',
    min_age: props.park?.min_age ?? '',
    phone: props.park?.phone ?? '',
    website: props.park?.website ?? '',
    latitude: props.park?.latitude ?? '',
    longitude: props.park?.longitude ?? '',
    is_featured: props.park?.is_featured ?? false,
    is_active: props.park?.is_active ?? true,
    image: null,
    remove_image: false,
});

const imagePreview = ref(props.park?.image_url ?? null);
const onImageChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    form.image = file;
    form.remove_image = false;
    imagePreview.value = URL.createObjectURL(file);
};

// Empty strings -> null so "Tarif sur place" (no price) round-trips cleanly.
const clean = (data) => {
    const out = { ...data };
    ['price_adult', 'price_child', 'latitude', 'longitude', 'website'].forEach((k) => {
        if (out[k] === '') out[k] = null;
    });
    return out;
};

const submit = () => {
    if (isEdit.value) {
        // PHP can't read multipart bodies on PUT: send a POST spoofed as PUT.
        form.transform((data) => ({ ...clean(data), _method: 'put' })).post(`/admin/loisirs/${props.park.id}`, { forceFormData: true });
    } else {
        form.transform(clean).post('/admin/loisirs', { forceFormData: true });
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Editer un parc' : 'Nouveau parc'" />

    <div class="mb-5 flex items-center justify-between">
        <h1 class="text-xl font-bold text-slate-900">{{ isEdit ? 'Editer' : 'Nouveau' }} parc / site de loisirs</h1>
        <Link href="/admin/loisirs" class="text-sm text-slate-500 hover:underline">&larr; Retour a la liste</Link>
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
            <p v-if="form.errors.image" class="mt-1 text-xs text-red-600">{{ form.errors.image }}</p>
        </div>

        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Nom du parc</label>
            <input v-model="form.name" type="text" class="mt-1 w-full rounded border-slate-300" required />
            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Type</label>
            <select v-model="form.category" class="mt-1 w-full rounded border-slate-300">
                <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Ville</label>
            <input v-model="form.city" list="park-cities" type="text" class="mt-1 w-full rounded border-slate-300" required />
            <datalist id="park-cities"><option v-for="c in cities" :key="c" :value="c" /></datalist>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Quartier</label>
            <input v-model="form.district" type="text" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Adresse</label>
            <input v-model="form.address" type="text" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Tarif adulte (vide = sur place)</label>
            <input v-model="form.price_adult" type="number" min="0" step="1" class="mt-1 w-full rounded border-slate-300" />
            <p v-if="form.errors.price_adult" class="mt-1 text-xs text-red-600">{{ form.errors.price_adult }}</p>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Tarif enfant</label>
            <input v-model="form.price_child" type="number" min="0" step="1" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Devise</label>
            <input v-model="form.currency" type="text" maxlength="3" class="mt-1 w-full rounded border-slate-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Age conseille</label>
            <input v-model="form.min_age" type="text" placeholder="Des 5 ans" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Horaires</label>
            <input v-model="form.opening_hours" type="text" placeholder="Tous les jours 10h - 22h" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Telephone</label>
            <input v-model="form.phone" type="text" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Site web</label>
            <input v-model="form.website" type="url" class="mt-1 w-full rounded border-slate-300" />
            <p v-if="form.errors.website" class="mt-1 text-xs text-red-600">{{ form.errors.website }}</p>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Latitude</label>
            <input v-model="form.latitude" type="number" step="0.0000001" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Longitude</label>
            <input v-model="form.longitude" type="number" step="0.0000001" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Activites (une par ligne)</label>
            <textarea v-model="form.activities" rows="4" class="mt-1 w-full rounded border-slate-300" placeholder="Toboggans&#10;Piscine&#10;Restaurant"></textarea>
        </div>
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Description</label>
            <textarea v-model="form.description" rows="5" class="mt-1 w-full rounded border-slate-300"></textarea>
        </div>
        <div class="flex items-center gap-2">
            <input id="featured" v-model="form.is_featured" type="checkbox" />
            <label for="featured" class="text-sm text-slate-700">Coup de coeur (mis en avant)</label>
        </div>
        <div class="flex items-center gap-2">
            <input id="active" v-model="form.is_active" type="checkbox" />
            <label for="active" class="text-sm text-slate-700">Visible sur le site et l'app</label>
        </div>

        <div class="sm:col-span-2">
            <button type="submit" class="btn-console-primary w-full" :disabled="form.processing">
                {{ isEdit ? 'Enregistrer les modifications' : 'Ajouter le parc' }}
            </button>
        </div>
    </form>
</template>
