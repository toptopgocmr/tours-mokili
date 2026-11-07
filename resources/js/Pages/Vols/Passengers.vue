<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    offer: { type: Object, required: true },
    airports: { type: Array, default: () => [] },
});

const page = usePage();
const s = props.offer.search;
const blank = (type) => ({
    type, title: '', first_name: '', last_name: '', birth_date: '', gender: '',
    nationality: '', passport_number: '', passport_expiry: '',
});
const passengers = [
    ...Array.from({ length: s.adults }, () => blank('adult')),
    ...Array.from({ length: s.children }, () => blank('child')),
    ...Array.from({ length: s.infants }, () => blank('infant')),
];

const form = useForm({
    accepted_price: props.offer.price_total,
    contact_email: page.props.auth?.user?.email ?? '',
    contact_phone: '',
    passengers,
});

const typeLabel = { adult: 'Adulte', child: 'Enfant', infant: 'Bebe' };
const newPrice = computed(() => page.props.flash?.new_price);
const submit = () => {
    if (newPrice.value) form.accepted_price = newPrice.value;
    form.post(`/vols/offres/${props.offer.id}/commander`);
};
const err = (k) => form.errors[k];
</script>

<template>
    <Head title="Passagers" />
    <div class="mx-auto max-w-3xl px-6 py-10">
        <h1 class="text-xl font-bold text-navy-900">Passagers - {{ s.origin }} &rarr; {{ s.destination }}</h1>
        <p class="mt-1 text-sm text-navy-500">
            {{ offer.airline_name }} - {{ Number(offer.price_total).toLocaleString('fr-FR') }} {{ offer.currency }}.
            Les noms doivent etre identiques a la piece d'identite / au passeport.
        </p>

        <div v-if="page.props.flash?.error" class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
            {{ page.props.flash.error }}
        </div>

        <form class="mt-6 space-y-5" @submit.prevent="submit">
            <div v-for="(p, i) in form.passengers" :key="i" class="rounded-2xl border p-4">
                <p class="mb-3 font-semibold text-navy-900">Passager {{ i + 1 }} ({{ typeLabel[p.type] }})</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <input v-model="p.first_name" placeholder="Prenom" class="rounded-lg border-gray-200 text-sm" required />
                    <input v-model="p.last_name" placeholder="Nom" class="rounded-lg border-gray-200 text-sm" required />
                    <input v-model="p.birth_date" type="date" class="rounded-lg border-gray-200 text-sm" required />
                    <select v-model="p.gender" class="rounded-lg border-gray-200 text-sm">
                        <option value="">Genre</option><option value="M">Homme</option><option value="F">Femme</option>
                    </select>
                    <input v-model="p.nationality" maxlength="2" placeholder="Nationalite (ISO2: CG)" class="rounded-lg border-gray-200 text-sm uppercase" />
                    <input v-model="p.passport_number" placeholder="N. passeport" class="rounded-lg border-gray-200 text-sm" />
                    <input v-model="p.passport_expiry" type="date" class="rounded-lg border-gray-200 text-sm" />
                </div>
                <p v-for="k in ['first_name', 'last_name', 'birth_date', 'passport_expiry']" :key="k" class="text-xs text-red-600">
                    {{ err(`passengers.${i}.${k}`) }}
                </p>
            </div>

            <div class="grid gap-3 rounded-2xl border p-4 sm:grid-cols-2">
                <input v-model="form.contact_email" type="email" placeholder="Email de contact" class="rounded-lg border-gray-200 text-sm" required />
                <input v-model="form.contact_phone" placeholder="Telephone" class="rounded-lg border-gray-200 text-sm" />
                <p class="text-xs text-red-600">{{ err('contact_email') }}</p>
            </div>

            <button type="submit" class="btn-gold w-full" :disabled="form.processing">
                {{ newPrice ? 'Accepter le nouveau prix et continuer' : 'Confirmer le prix et payer' }}
            </button>
        </form>
    </div>
</template>
