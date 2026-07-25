<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import DestinationImage from '@/Components/DestinationImage.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineOptions({ layout: MainLayout });

const props = defineProps({ offer: { type: Object, required: true } });

const form = useForm({ quantity: 1, notes: '' });

const submit = () => form.post(`/voyage/${props.offer.slug}/reserver`);
</script>

<template>
    <Head :title="offer.title" />

    <div class="mx-auto grid max-w-5xl gap-10 px-6 py-12 md:grid-cols-2">
        <div>
            <div class="relative h-64 overflow-hidden rounded-2xl">
                <DestinationImage :city="offer.destination_city" class="h-full w-full" />
                <div class="absolute inset-0 bg-gradient-to-t from-navy-900/70 to-transparent" />
                <span class="absolute bottom-4 left-5 text-2xl font-semibold text-white drop-shadow">{{ offer.destination_city }}</span>
            </div>
            <h1 class="mt-6 text-3xl font-bold text-navy-900">{{ offer.title }}</h1>
            <p class="mt-2 text-navy-700">{{ offer.description }}</p>

            <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-navy-500">Type</dt><dd class="font-medium capitalize">{{ offer.type }}</dd></div>
                <div><dt class="text-navy-500">Compagnie</dt><dd class="font-medium">{{ offer.airline }}</dd></div>
                <div><dt class="text-navy-500">Depart</dt><dd class="font-medium">{{ offer.origin_city }}</dd></div>
                <div><dt class="text-navy-500">Destination</dt><dd class="font-medium">{{ offer.destination_city }}</dd></div>
                <div><dt class="text-navy-500">Places disponibles</dt><dd class="font-medium">{{ offer.seats_available }}</dd></div>
            </dl>
        </div>

        <div class="h-fit rounded-2xl border p-6 shadow-sm">
            <p class="text-2xl font-bold text-gold-600">
                {{ Number(offer.discounted_price ?? offer.price).toLocaleString('fr-FR') }} {{ offer.currency }}
                <span class="text-sm font-normal text-navy-500">/ personne</span>
            </p>

            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <div>
                    <label class="text-sm font-medium text-navy-900">Nombre de passagers</label>
                    <input v-model.number="form.quantity" type="number" min="1" max="20" class="mt-1 w-full rounded-lg border-gray-300" />
                </div>
                <div>
                    <label class="text-sm font-medium text-navy-900">Notes (optionnel)</label>
                    <textarea v-model="form.notes" rows="3" class="mt-1 w-full rounded-lg border-gray-300"></textarea>
                </div>
                <button type="submit" class="btn-gold w-full" :disabled="form.processing">Reserver maintenant</button>
                <p class="text-center text-xs text-navy-500">
                    Le paiement se fait via portefeuille mobile money / bancaire, verifie par Peex.
                </p>
            </form>
        </div>
    </div>
</template>
