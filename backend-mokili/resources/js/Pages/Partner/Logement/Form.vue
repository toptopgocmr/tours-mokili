<script setup>
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: PartnerLayout });

const props = defineProps({ listing: { type: Object, default: null } });
const isEdit = computed(() => !!props.listing);

const form = useForm({
    title: props.listing?.title ?? '',
    description: props.listing?.description ?? '',
    city: props.listing?.city ?? '',
    country: props.listing?.country ?? '',
    address: props.listing?.address ?? '',
    price_per_night: props.listing?.price_per_night ?? '',
    currency: props.listing?.currency ?? 'XAF',
    bedrooms: props.listing?.bedrooms ?? 1,
    bathrooms: props.listing?.bathrooms ?? 1,
    max_guests: props.listing?.max_guests ?? 2,
    is_active: props.listing?.is_active ?? true,
});

const submit = () => isEdit.value ? form.put(`/partner/logement/${props.listing.id}`) : form.post('/partner/logement');
</script>

<template>
    <Head :title="isEdit ? 'Editer le logement' : 'Nouveau logement'" />
    <h1 class="text-2xl font-bold text-navy-900">{{ isEdit ? 'Editer' : 'Nouveau' }} logement</h1>

    <form class="mt-6 grid max-w-3xl gap-4 rounded-2xl border bg-white p-6 sm:grid-cols-2" @submit.prevent="submit">
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-navy-900">Titre</label>
            <input v-model="form.title" type="text" class="mt-1 w-full rounded-lg border-gray-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-navy-900">Ville</label>
            <input v-model="form.city" type="text" class="mt-1 w-full rounded-lg border-gray-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-navy-900">Pays (ISO2)</label>
            <input v-model="form.country" type="text" maxlength="2" class="mt-1 w-full rounded-lg border-gray-300" required />
        </div>
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-navy-900">Adresse</label>
            <input v-model="form.address" type="text" class="mt-1 w-full rounded-lg border-gray-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-navy-900">Prix / nuit</label>
            <input v-model.number="form.price_per_night" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border-gray-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-navy-900">Devise</label>
            <input v-model="form.currency" type="text" maxlength="3" class="mt-1 w-full rounded-lg border-gray-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-navy-900">Chambres</label>
            <input v-model.number="form.bedrooms" type="number" min="0" class="mt-1 w-full rounded-lg border-gray-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-navy-900">Salles de bain</label>
            <input v-model.number="form.bathrooms" type="number" min="0" class="mt-1 w-full rounded-lg border-gray-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-navy-900">Capacite (invites)</label>
            <input v-model.number="form.max_guests" type="number" min="1" class="mt-1 w-full rounded-lg border-gray-300" />
        </div>
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-navy-900">Description</label>
            <textarea v-model="form.description" rows="4" class="mt-1 w-full rounded-lg border-gray-300"></textarea>
        </div>
        <div class="flex items-center gap-2">
            <input v-model="form.is_active" type="checkbox" id="active" />
            <label for="active" class="text-sm text-navy-900">Publie (visible publiquement)</label>
        </div>
        <div class="sm:col-span-2">
            <button type="submit" class="btn-gold w-full" :disabled="form.processing">{{ isEdit ? 'Enregistrer' : 'Publier' }}</button>
        </div>
    </form>
</template>
