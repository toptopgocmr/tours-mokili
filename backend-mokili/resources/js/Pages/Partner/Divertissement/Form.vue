<script setup>
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: PartnerLayout });

const props = defineProps({ event: { type: Object, default: null } });
const isEdit = computed(() => !!props.event);

const form = useForm({
    title: props.event?.title ?? '',
    category: props.event?.category ?? '',
    description: props.event?.description ?? '',
    venue: props.event?.venue ?? '',
    city: props.event?.city ?? '',
    country: props.event?.country ?? '',
    starts_at: props.event?.starts_at?.slice(0, 16) ?? '',
    ends_at: props.event?.ends_at?.slice(0, 16) ?? '',
    price: props.event?.price ?? '',
    currency: props.event?.currency ?? 'XAF',
    capacity: props.event?.capacity ?? 0,
    is_active: props.event?.is_active ?? true,
});

const submit = () => isEdit.value ? form.put(`/partner/divertissement/${props.event.id}`) : form.post('/partner/divertissement');
</script>

<template>
    <Head :title="isEdit ? 'Editer l\'evenement' : 'Nouvel evenement'" />
    <h1 class="text-2xl font-bold text-navy-900">{{ isEdit ? 'Editer' : 'Nouvel' }} evenement</h1>

    <form class="mt-6 grid max-w-3xl gap-4 rounded-2xl border bg-white p-6 sm:grid-cols-2" @submit.prevent="submit">
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-navy-900">Titre</label>
            <input v-model="form.title" type="text" class="mt-1 w-full rounded-lg border-gray-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-navy-900">Categorie</label>
            <input v-model="form.category" type="text" placeholder="concert, sport, spectacle..." class="mt-1 w-full rounded-lg border-gray-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-navy-900">Lieu</label>
            <input v-model="form.venue" type="text" class="mt-1 w-full rounded-lg border-gray-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-navy-900">Ville</label>
            <input v-model="form.city" type="text" class="mt-1 w-full rounded-lg border-gray-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-navy-900">Pays (ISO2)</label>
            <input v-model="form.country" type="text" maxlength="2" class="mt-1 w-full rounded-lg border-gray-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-navy-900">Debut</label>
            <input v-model="form.starts_at" type="datetime-local" class="mt-1 w-full rounded-lg border-gray-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-navy-900">Fin</label>
            <input v-model="form.ends_at" type="datetime-local" class="mt-1 w-full rounded-lg border-gray-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-navy-900">Prix du billet</label>
            <input v-model.number="form.price" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border-gray-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-navy-900">Devise</label>
            <input v-model="form.currency" type="text" maxlength="3" class="mt-1 w-full rounded-lg border-gray-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-navy-900">Capacite</label>
            <input v-model.number="form.capacity" type="number" min="0" class="mt-1 w-full rounded-lg border-gray-300" />
        </div>
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-navy-900">Description</label>
            <textarea v-model="form.description" rows="4" class="mt-1 w-full rounded-lg border-gray-300"></textarea>
        </div>
        <div class="flex items-center gap-2 sm:col-span-2">
            <input v-model="form.is_active" type="checkbox" id="active" />
            <label for="active" class="text-sm text-navy-900">Publie (billetterie ouverte)</label>
        </div>
        <div class="sm:col-span-2">
            <button type="submit" class="btn-gold w-full" :disabled="form.processing">{{ isEdit ? 'Enregistrer' : 'Publier' }}</button>
        </div>
    </form>
</template>
