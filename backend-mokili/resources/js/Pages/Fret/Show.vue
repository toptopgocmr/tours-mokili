<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import ListingImage from '@/Components/ListingImage.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: MainLayout });
const props = defineProps({ offer: { type: Object, required: true } });

const modeLabel = { air: 'Aerien', mer: 'Maritime', route: 'Routier' };

const form = useForm({ weight_kg: 1, dimensions: '', notes: '', package_type: props.offer.package_types?.[0] ?? 'marchandise', package_description: '' });
const page = usePage();
const packageTypes = computed(() => {
    const all = page.props.catalogue?.fret?.package_types ?? {};
    const allowed = props.offer.package_types?.length ? props.offer.package_types : Object.keys(all);
    return allowed.map((k) => [k, all[k] ?? k]);
});

const total = computed(() => (Number(form.weight_kg) || 0) * Number(props.offer.price_per_kg));

const submit = () => form.post(`/fret/${props.offer.slug}/demande`);
</script>
<template>
    <Head :title="offer.title" />
    <div class="mx-auto grid max-w-5xl gap-10 px-6 py-12 md:grid-cols-2">
        <div>
            <div class="overflow-hidden rounded-2xl border shadow-sm">
                <ListingImage :src="offer.image_url" fallback-class="bg-teal-700 h-56" class="h-56">{{ offer.origin_city }} &rarr; {{ offer.destination_city }}</ListingImage>
            </div>
            <h1 class="mt-6 text-2xl font-bold text-navy-900">{{ offer.title }}</h1>
            <p class="mt-1 text-sm uppercase text-navy-500">{{ modeLabel[offer.mode] }} · {{ offer.scope === 'national' ? 'National' : 'International' }}<span v-if="offer.transit_time"> · {{ offer.transit_time }}</span></p>
            <div class="mt-2 flex flex-wrap gap-1.5">
                <span v-for="l in offer.package_type_labels" :key="l" class="rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-semibold text-teal-700">{{ l }}</span>
            </div>
            <p v-if="offer.description" class="mt-4 text-navy-700">{{ offer.description }}</p>

            <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-navy-500">Origine</dt><dd class="font-medium">{{ offer.origin_city }}</dd></div>
                <div><dt class="text-navy-500">Destination</dt><dd class="font-medium">{{ offer.destination_city }}</dd></div>
                <div><dt class="text-navy-500">Prix</dt><dd class="font-semibold text-gold-600">{{ Number(offer.price_per_kg).toLocaleString('fr-FR') }} {{ offer.currency }} / kg</dd></div>
                <div v-if="offer.capacity_kg"><dt class="text-navy-500">Capacite</dt><dd class="font-medium">{{ offer.capacity_kg }} kg</dd></div>
            </dl>
        </div>

        <div class="h-fit rounded-2xl border p-6 shadow-sm">
            <p class="font-semibold text-navy-900">Demander ce transport</p>
            <p class="mt-1 text-xs text-navy-500">Un code de suivi vous sera fourni immediatement, avant meme la confirmation du paiement.</p>

            <form class="mt-5 space-y-4" @submit.prevent="submit">
                <div>
                    <label class="text-sm font-medium text-navy-900">Type de colis</label>
                    <select v-model="form.package_type" class="mt-1 w-full rounded-lg border-gray-300">
                        <option v-for="[k, l] in packageTypes" :key="k" :value="k">{{ l }}</option>
                    </select>
                    <p v-if="form.errors.package_type" class="mt-1 text-xs text-red-600">{{ form.errors.package_type }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-navy-900">Contenu</label>
                    <input v-model="form.package_description" type="text" :placeholder="form.package_type === 'vehicule' ? 'ex. Toyota RAV4 2018' : form.package_type === 'conteneur' ? 'ex. 1 conteneur 20 pieds, electromenager' : 'ex. vetements, pieces detachees...'" class="mt-1 w-full rounded-lg border-gray-300" />
                </div>
                <div>
                    <label class="text-sm font-medium text-navy-900">Poids (kg)</label>
                    <input
                        v-model.number="form.weight_kg"
                        type="number"
                        min="0.1"
                        step="0.1"
                        :max="offer.capacity_kg || undefined"
                        class="mt-1 w-full rounded-lg border-gray-300"
                    />
                    <p v-if="form.errors.weight_kg" class="mt-1 text-xs text-red-600">{{ form.errors.weight_kg }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-navy-900">Dimensions (optionnel)</label>
                    <input v-model="form.dimensions" type="text" placeholder="ex. 60x40x40 cm" class="mt-1 w-full rounded-lg border-gray-300" />
                </div>
                <div>
                    <label class="text-sm font-medium text-navy-900">Notes (optionnel)</label>
                    <textarea v-model="form.notes" rows="2" class="mt-1 w-full rounded-lg border-gray-300"></textarea>
                </div>

                <div class="rounded-lg bg-navy-50 p-3 text-sm text-navy-700">
                    <div class="flex justify-between">
                        <span>{{ Number(offer.price_per_kg).toLocaleString('fr-FR') }} {{ offer.currency }} x {{ form.weight_kg || 0 }} kg</span>
                        <span class="font-semibold">{{ total.toLocaleString('fr-FR') }} {{ offer.currency }}</span>
                    </div>
                </div>

                <button type="submit" class="btn-gold w-full" :disabled="form.processing">Demander &amp; obtenir un code de suivi</button>
                <p class="text-center text-xs text-navy-500">
                    Paiement Mobile Money securise : Peex, pawaPay, MaishaPay ou Digitwace.
                </p>
            </form>
        </div>
    </div>
</template>
