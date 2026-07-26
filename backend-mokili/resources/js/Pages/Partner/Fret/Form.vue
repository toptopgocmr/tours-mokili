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

const submit = () => (isEdit.value ? form.put(`/partner/fret/${props.offer.id}`) : form.post('/partner/fret'));
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
            <label class="text-sm font-medium text-slate-700">Prix / kg</label>
            <input v-model.number="form.price_per_kg" type="number" min="0" step="0.01" class="mt-1 w-full rounded border-slate-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Devise</label>
            <input v-model="form.currency" type="text" maxlength="3" class="mt-1 w-full rounded border-slate-300" required />
        </div>
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Description</label>
            <textarea v-model="form.description" rows="4" class="mt-1 w-full rounded border-slate-300"></textarea>
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
