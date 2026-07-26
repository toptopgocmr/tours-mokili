<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    code: { type: String, default: null },
    shipment: { type: Object, default: null },
    notFound: { type: Boolean, default: false },
});

const query = ref(props.code || '');

const steps = ['enregistre', 'en_transit', 'dedouanement', 'livre'];
const stepLabels = {
    enregistre: 'Enregistre',
    en_transit: 'En transit',
    dedouanement: 'Dedouanement',
    livre: 'Livre',
};
const modeLabel = { air: 'Aerien', mer: 'Maritime', route: 'Routier' };

const currentStepIndex = computed(() => {
    if (!props.shipment) return -1;
    if (props.shipment.status === 'annule') return -1;
    return steps.indexOf(props.shipment.status);
});

const isCancelled = computed(() => props.shipment?.status === 'annule');

const submit = () => {
    if (!query.value.trim()) return;
    router.get(`/fret/suivi/${encodeURIComponent(query.value.trim())}`);
};
</script>

<template>
    <Head title="Suivi de colis" />

    <div class="bg-navy-900 py-10">
        <div class="mx-auto max-w-3xl px-6 text-center">
            <h1 class="text-2xl font-bold text-white">Suivez votre expedition</h1>
            <p class="mt-1 text-sm text-navy-200">Entrez votre code de suivi (ex. FRT-XXXXXXXXXX), sans connexion requise.</p>
            <form class="mt-5 flex gap-2" @submit.prevent="submit">
                <input
                    v-model="query"
                    type="text"
                    placeholder="Code de suivi"
                    class="w-full rounded-lg border-gray-300 text-sm uppercase"
                />
                <button type="submit" class="btn-gold whitespace-nowrap px-6">Suivre</button>
            </form>
        </div>
    </div>

    <div class="mx-auto max-w-3xl px-6 py-10">
        <div v-if="notFound" class="rounded-2xl border border-red-200 bg-red-50 p-6 text-center text-red-700">
            Aucune expedition ne correspond au code <span class="font-semibold">{{ code }}</span>. Verifiez le code et reessayez.
        </div>

        <div v-else-if="shipment" class="rounded-2xl border p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase text-navy-500">Code de suivi</p>
                    <p class="text-xl font-bold text-navy-900">{{ shipment.tracking_code }}</p>
                </div>
                <span
                    class="rounded-full px-3 py-1 text-xs font-semibold uppercase"
                    :class="isCancelled ? 'bg-red-50 text-red-700' : 'bg-teal-50 text-teal-700'"
                >
                    {{ isCancelled ? 'Annule' : stepLabels[shipment.status] }}
                </span>
            </div>

            <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
                <div><dt class="text-navy-500">Origine</dt><dd class="font-medium">{{ shipment.origin_city }}</dd></div>
                <div><dt class="text-navy-500">Destination</dt><dd class="font-medium">{{ shipment.destination_city }}</dd></div>
                <div><dt class="text-navy-500">Mode</dt><dd class="font-medium">{{ modeLabel[shipment.mode] }}</dd></div>
                <div v-if="shipment.weight_kg"><dt class="text-navy-500">Poids</dt><dd class="font-medium">{{ Number(shipment.weight_kg).toLocaleString('fr-FR') }} kg</dd></div>
            </dl>

            <div v-if="!isCancelled" class="mt-8">
                <div class="flex items-center justify-between">
                    <div v-for="(step, i) in steps" :key="step" class="flex flex-1 flex-col items-center">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold"
                            :class="i <= currentStepIndex ? 'bg-teal-600 text-white' : 'bg-gray-200 text-gray-400'"
                        >
                            {{ i + 1 }}
                        </div>
                        <p class="mt-2 text-center text-xs" :class="i <= currentStepIndex ? 'font-semibold text-navy-900' : 'text-navy-400'">
                            {{ stepLabels[step] }}
                        </p>
                    </div>
                </div>
                <div class="mt-3 h-1.5 w-full rounded-full bg-gray-200">
                    <div
                        class="h-1.5 rounded-full bg-teal-600 transition-all"
                        :style="{ width: `${(Math.max(currentStepIndex, 0) / (steps.length - 1)) * 100}%` }"
                    />
                </div>
            </div>
            <p v-else class="mt-6 rounded-lg bg-red-50 p-4 text-sm text-red-700">Cette expedition a ete annulee.</p>
        </div>

        <p v-else class="text-center text-navy-500">Entrez un code de suivi ci-dessus pour voir l'etat de votre expedition.</p>
    </div>
</template>
