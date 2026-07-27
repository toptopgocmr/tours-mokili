<script setup>
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: PartnerLayout });

const props = defineProps({ product: { type: Object, default: null } });
const isEdit = computed(() => !!props.product);

const form = useForm({
    title: props.product?.title ?? '',
    description: props.product?.description ?? '',
    category: props.product?.category ?? '',
    price: props.product?.price ?? '',
    currency: props.product?.currency ?? 'XAF',
    stock: props.product?.stock ?? 1,
    condition: props.product?.condition ?? 'neuf',
    city: props.product?.city ?? '',
    country: props.product?.country ?? '',
    is_active: props.product?.is_active ?? true,
    intent: 'draft',
    image: null,
    remove_image: false,
});

const statusLabels = { draft: 'Brouillon', pending: 'En attente de validation', published: 'Publie', rejected: 'Rejete' };
const statusLabel = computed(() => statusLabels[props.product?.status] ?? 'Brouillon');

const imagePreview = ref(props.product?.image_url ?? null);
const onImageChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    form.image = file;
    form.remove_image = false;
    imagePreview.value = URL.createObjectURL(file);
};

const submit = (intent) => {
    form.intent = intent;
    if (isEdit.value) form.put(`/partner/marketplace/${props.product.id}`);
    else form.post('/partner/marketplace');
};
</script>

<template>
    <Head :title="isEdit ? 'Editer le produit' : 'Nouveau produit'" />
    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-900">{{ isEdit ? 'Editer' : 'Nouveau' }} produit</h1>
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
            <label class="text-sm font-medium text-slate-700">Categorie</label>
            <input v-model="form.category" type="text" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Etat</label>
            <select v-model="form.condition" class="mt-1 w-full rounded border-slate-300">
                <option value="neuf">Neuf</option>
                <option value="occasion">Occasion</option>
            </select>
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
            <label class="text-sm font-medium text-slate-700">Stock</label>
            <input v-model.number="form.stock" type="number" min="0" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Ville</label>
            <input v-model="form.city" type="text" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Pays (ISO2)</label>
            <input v-model="form.country" type="text" maxlength="2" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Description</label>
            <textarea v-model="form.description" rows="4" class="mt-1 w-full rounded border-slate-300"></textarea>
        </div>
        <div v-if="product?.status === 'rejected'" class="sm:col-span-2 rounded-lg bg-red-50 p-3 text-sm text-red-700">
            <p class="font-semibold">Annonce rejetee par un administrateur</p>
            <p class="mt-1">{{ product.rejection_reason }}</p>
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
