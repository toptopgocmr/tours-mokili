<script setup>
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

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
});

const submit = () => isEdit.value ? form.put(`/partner/marketplace/${props.product.id}`) : form.post('/partner/marketplace');
</script>

<template>
    <Head :title="isEdit ? 'Editer le produit' : 'Nouveau produit'" />
    <h1 class="text-2xl font-bold text-navy-900">{{ isEdit ? 'Editer' : 'Nouveau' }} produit</h1>

    <form class="mt-6 grid max-w-3xl gap-4 rounded-2xl border bg-white p-6 sm:grid-cols-2" @submit.prevent="submit">
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-navy-900">Titre</label>
            <input v-model="form.title" type="text" class="mt-1 w-full rounded-lg border-gray-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-navy-900">Categorie</label>
            <input v-model="form.category" type="text" class="mt-1 w-full rounded-lg border-gray-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-navy-900">Etat</label>
            <select v-model="form.condition" class="mt-1 w-full rounded-lg border-gray-300">
                <option value="neuf">Neuf</option>
                <option value="occasion">Occasion</option>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-navy-900">Prix</label>
            <input v-model.number="form.price" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border-gray-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-navy-900">Devise</label>
            <input v-model="form.currency" type="text" maxlength="3" class="mt-1 w-full rounded-lg border-gray-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-navy-900">Stock</label>
            <input v-model.number="form.stock" type="number" min="0" class="mt-1 w-full rounded-lg border-gray-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-navy-900">Ville</label>
            <input v-model="form.city" type="text" class="mt-1 w-full rounded-lg border-gray-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-navy-900">Pays (ISO2)</label>
            <input v-model="form.country" type="text" maxlength="2" class="mt-1 w-full rounded-lg border-gray-300" />
        </div>
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-navy-900">Description</label>
            <textarea v-model="form.description" rows="4" class="mt-1 w-full rounded-lg border-gray-300"></textarea>
        </div>
        <div class="flex items-center gap-2 sm:col-span-2">
            <input v-model="form.is_active" type="checkbox" id="active" />
            <label for="active" class="text-sm text-navy-900">Publie (visible publiquement)</label>
        </div>
        <div class="sm:col-span-2">
            <button type="submit" class="btn-gold w-full" :disabled="form.processing">{{ isEdit ? 'Enregistrer' : 'Publier' }}</button>
        </div>
    </form>
</template>
