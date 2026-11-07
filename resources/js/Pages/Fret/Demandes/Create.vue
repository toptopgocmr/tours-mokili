<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: MainLayout });
const props = defineProps({ offer: { type: Object, default: null } });
const page = usePage();

const form = useForm({
    offer_slug: props.offer?.slug ?? '',
    mode: props.offer?.mode ?? 'air',
    origin_country: props.offer?.origin_country ?? '',
    origin_city: props.offer?.origin_city ?? '',
    destination_country: props.offer?.destination_country ?? '',
    destination_city: props.offer?.destination_city ?? '',
    cargo_type: 'colis',
    description: '',
    weight_kg: '',
    volume_m3: '',
    dimensions: '',
    packages: '',
    load_type: 'LCL',
    container_type: '',
    ready_date: '',
    desired_pickup_date: '',
    desired_delivery_date: '',
    services: [],
    declared_value: '',
    dangerous_goods: false,
});

const isAir = computed(() => form.mode === 'air');
const isSea = computed(() => form.mode === 'mer');
const isFcl = computed(() => isSea.value && form.load_type === 'FCL');
const serviceOptions = [
    ['pickup', 'Enlevement'], ['packing', 'Emballage'], ['insurance', 'Assurance'],
    ['customs', 'Dedouanement'], ['storage', 'Entreposage'], ['delivery', 'Livraison'],
];

const submit = () => form.post('/fret/demandes');
const err = (k) => form.errors[k];
</script>

<template>
    <Head title="Demander un devis" />
    <div class="mx-auto max-w-3xl px-6 py-10">
        <h1 class="text-xl font-bold text-navy-900">Demander un devis</h1>
        <p v-if="offer" class="mt-1 text-sm text-navy-500">Pour la prestation : {{ offer.title }}</p>
        <p v-if="page.props.flash?.error" class="mt-3 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ page.props.flash.error }}</p>

        <form class="mt-6 grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
            <div class="sm:col-span-2">
                <label class="text-sm font-medium text-navy-900">Mode de transport</label>
                <div class="mt-1 flex gap-2">
                    <button v-for="m in [['air', 'Aerien'], ['mer', 'Maritime'], ['route', 'Routier']]" :key="m[0]" type="button" :disabled="!!offer"
                        class="rounded-full px-4 py-1.5 text-sm font-semibold" :class="form.mode === m[0] ? 'bg-navy-900 text-white' : 'bg-navy-50 text-navy-700'" @click="form.mode = m[0]">{{ m[1] }}</button>
                </div>
            </div>

            <input v-model="form.origin_city" placeholder="Ville de depart" class="rounded-lg border-gray-300 text-sm" required />
            <input v-model="form.origin_country" maxlength="2" placeholder="Pays depart (ISO2: CG)" class="rounded-lg border-gray-300 text-sm uppercase" required />
            <input v-model="form.destination_city" placeholder="Ville d'arrivee" class="rounded-lg border-gray-300 text-sm" required />
            <input v-model="form.destination_country" maxlength="2" placeholder="Pays arrivee (ISO2: FR)" class="rounded-lg border-gray-300 text-sm uppercase" required />

            <select v-model="form.cargo_type" class="rounded-lg border-gray-300 text-sm">
                <option value="colis">Colis</option><option value="marchandises">Marchandises</option>
                <option value="vehicule">Vehicule</option><option value="conteneur">Conteneur</option>
            </select>
            <div v-if="isSea" class="flex gap-2">
                <select v-model="form.load_type" class="flex-1 rounded-lg border-gray-300 text-sm"><option value="LCL">LCL (groupage)</option><option value="FCL">FCL (conteneur complet)</option></select>
                <input v-if="isFcl" v-model="form.container_type" placeholder="Conteneur (20GP, 40HC)" class="flex-1 rounded-lg border-gray-300 text-sm" />
            </div>

            <div class="sm:col-span-2">
                <textarea v-model="form.description" rows="3" placeholder="Description precise de la marchandise" class="w-full rounded-lg border-gray-300 text-sm" required></textarea>
                <p class="text-xs text-red-600">{{ err('description') }}</p>
            </div>

            <div v-if="!isFcl"><input v-model.number="form.weight_kg" type="number" min="0.1" step="0.1" placeholder="Poids reel (kg)" class="w-full rounded-lg border-gray-300 text-sm" /><p class="text-xs text-red-600">{{ err('weight_kg') }}</p></div>
            <div><input v-model.number="form.volume_m3" type="number" min="0.001" step="0.001" :placeholder="isSea && form.load_type === 'LCL' ? 'Volume (m3) - requis' : 'Volume (m3)'" class="w-full rounded-lg border-gray-300 text-sm" /><p class="text-xs text-red-600">{{ err('volume_m3') }}</p></div>
            <div v-if="isAir || form.mode === 'route'"><input v-model="form.dimensions" :placeholder="isAir ? 'Dimensions LxlxH cm - requis' : 'Dimensions (optionnel)'" class="w-full rounded-lg border-gray-300 text-sm" /><p class="text-xs text-red-600">{{ err('dimensions') }}</p></div>
            <input v-model.number="form.packages" type="number" min="1" placeholder="Nombre de colis" class="rounded-lg border-gray-300 text-sm" />
            <p v-if="isFcl" class="text-xs text-red-600 sm:col-span-2">{{ err('container_type') }}</p>

            <label class="text-xs text-navy-700">Marchandise prete le<input v-model="form.ready_date" type="date" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></label>
            <label class="text-xs text-navy-700">Enlevement souhaite<input v-model="form.desired_pickup_date" type="date" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></label>
            <label class="text-xs text-navy-700">Livraison souhaitee (sans garantie)<input v-model="form.desired_delivery_date" type="date" class="mt-1 w-full rounded-lg border-gray-300 text-sm" /></label>
            <input v-model.number="form.declared_value" type="number" min="0" placeholder="Valeur declaree (optionnel)" class="rounded-lg border-gray-300 text-sm" />

            <div class="sm:col-span-2">
                <p class="text-sm font-medium text-navy-900">Services souhaites</p>
                <div class="mt-1 flex flex-wrap gap-3 text-sm">
                    <label v-for="s in serviceOptions" :key="s[0]" class="flex items-center gap-1"><input v-model="form.services" type="checkbox" :value="s[0]" /> {{ s[1] }}</label>
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm sm:col-span-2"><input v-model="form.dangerous_goods" type="checkbox" /> Marchandise dangereuse ou reglementee (a declarer)</label>

            <button type="submit" class="btn-gold sm:col-span-2" :disabled="form.processing">Envoyer la demande</button>
        </form>
    </div>
</template>
