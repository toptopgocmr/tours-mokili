<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import ListingImage from '@/Components/ListingImage.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    listing: { type: Object, required: true },
    search: { type: Object, default: () => ({}) },
});

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    starts_at: props.search.check_in || today,
    ends_at: props.search.check_out || '',
    guests: props.search.guests ? Number(props.search.guests) : 1,
    notes: '',
});

const nights = computed(() => {
    if (!form.starts_at || !form.ends_at) return 0;
    const diff = (new Date(form.ends_at) - new Date(form.starts_at)) / 86400000;
    return diff > 0 ? Math.round(diff) : 0;
});

const total = computed(() => nights.value * Number(props.listing.price_per_night));

const amenities = computed(() => (Array.isArray(props.listing.amenities) ? props.listing.amenities : []));

const submit = () => form.post(`/logement/${props.listing.slug}/reserver`);
</script>

<template>
    <Head :title="listing.title" />

    <div class="mx-auto grid max-w-5xl gap-10 px-6 py-12 md:grid-cols-2">
        <div>
            <div class="overflow-hidden rounded-2xl">
                <ListingImage :src="listing.image_url" fallback-class="bg-green-700 text-xl font-semibold" class="h-64">
                    {{ listing.city }}
                </ListingImage>
            </div>
            <h1 class="mt-6 text-2xl font-bold text-navy-900">{{ listing.title }}</h1>
            <p class="text-sm text-navy-500">{{ listing.city }}, {{ listing.country }}</p>
            <p class="mt-3 text-navy-700">{{ listing.description }}</p>

            <dl class="mt-6 grid grid-cols-3 gap-4 text-sm">
                <div><dt class="text-navy-500">Chambres</dt><dd class="font-medium">{{ listing.bedrooms }}</dd></div>
                <div><dt class="text-navy-500">Salles de bain</dt><dd class="font-medium">{{ listing.bathrooms }}</dd></div>
                <div><dt class="text-navy-500">Voyageurs max.</dt><dd class="font-medium">{{ listing.max_guests }}</dd></div>
            </dl>

            <div v-if="amenities.length" class="mt-6">
                <p class="font-semibold text-navy-900">Equipements</p>
                <ul class="mt-2 grid grid-cols-2 gap-2 text-sm text-navy-700">
                    <li v-for="a in amenities" :key="a" class="flex items-center gap-2">
                        <span class="h-1.5 w-1.5 rounded-full bg-gold-500"></span>{{ a }}
                    </li>
                </ul>
            </div>

            <div class="mt-6 rounded-lg bg-navy-50 p-4 text-sm text-navy-700">
                <p class="font-semibold text-navy-900">Politique d'annulation</p>
                <p class="mt-1">Annulation gratuite jusqu'a 48h avant l'arrivee. Passe ce delai, la premiere nuit reste due.</p>
            </div>
        </div>

        <div class="h-fit rounded-2xl border p-6 shadow-sm">
            <p class="text-2xl font-bold text-gold-600">
                {{ Number(listing.price_per_night).toLocaleString('fr-FR') }} {{ listing.currency }}
                <span class="text-sm font-normal text-navy-500">/ nuit</span>
            </p>

            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-medium text-navy-900">Arrivee</label>
                        <input v-model="form.starts_at" type="date" :min="today" class="mt-1 w-full rounded-lg border-gray-300" />
                        <p v-if="form.errors.starts_at" class="mt-1 text-xs text-red-600">{{ form.errors.starts_at }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-navy-900">Depart</label>
                        <input v-model="form.ends_at" type="date" :min="form.starts_at || today" class="mt-1 w-full rounded-lg border-gray-300" />
                        <p v-if="form.errors.ends_at" class="mt-1 text-xs text-red-600">{{ form.errors.ends_at }}</p>
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-navy-900">Voyageurs</label>
                    <input v-model.number="form.guests" type="number" min="1" :max="listing.max_guests" class="mt-1 w-full rounded-lg border-gray-300" />
                    <p v-if="form.errors.guests" class="mt-1 text-xs text-red-600">{{ form.errors.guests }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-navy-900">Notes (optionnel)</label>
                    <textarea v-model="form.notes" rows="2" class="mt-1 w-full rounded-lg border-gray-300"></textarea>
                </div>

                <div v-if="nights > 0" class="rounded-lg bg-navy-50 p-3 text-sm text-navy-700">
                    <div class="flex justify-between">
                        <span>{{ Number(listing.price_per_night).toLocaleString('fr-FR') }} {{ listing.currency }} x {{ nights }} nuit(s)</span>
                        <span class="font-semibold">{{ total.toLocaleString('fr-FR') }} {{ listing.currency }}</span>
                    </div>
                </div>

                <button type="submit" class="btn-gold w-full" :disabled="form.processing || nights === 0">Reserver maintenant</button>
                <p class="text-center text-xs text-navy-500">
                    Le paiement se fait via portefeuille mobile money / bancaire, verifie par Peex.
                </p>
            </form>
        </div>
    </div>
</template>
