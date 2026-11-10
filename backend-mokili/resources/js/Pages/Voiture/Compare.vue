<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import ListingImage from '@/Components/ListingImage.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useCarShortlist } from '@/composables/useCarShortlist';

defineOptions({ layout: MainLayout });

// Comparateur de vehicules a vendre (jusqu'a 4), cote a cote.
const props = defineProps({ vehicles: { type: Array, default: () => [] } });
const { compare, toggleCompare } = useCarShortlist();
const cat = computed(() => usePage().props.catalogue?.voiture ?? {});
const money = (x) => Number(x ?? 0).toLocaleString('fr-FR');

const ROWS = [
    ['Prix', (v) => (v.display_price ? `${money(v.display_price.amount)} ${v.currency}` : 'Sur demande'), 'price'],
    ['Annee', (v) => v.year],
    ['Kilometrage', (v) => (v.mileage_km ? `${money(v.mileage_km)} km` : null), 'km'],
    ['Carburant', (v) => cat.value.fuels?.[v.fuel] ?? v.fuel],
    ['Boite', (v) => (v.transmission === 'manuelle' ? 'Manuelle' : v.transmission ? 'Automatique' : null)],
    ['Moteur', (v) => v.engine],
    ['Carrosserie', (v) => cat.value.categories?.[v.category] ?? v.category],
    ['Couleur', (v) => cat.value.colors?.[v.color]?.[0] ?? v.color],
    ['Portes', (v) => (v.doors ? `${v.doors} portes` : null)],
    ['Places', (v) => (v.seats ? `${v.seats} places` : null)],
    ['Etat', (v) => cat.value.conditions?.[v.condition] ?? v.condition],
    ['Premiere main', (v) => (v.first_owner ? 'Oui' : 'Non')],
    ['Garantie', (v) => (v.warranty_months ? `${v.warranty_months} mois` : 'Non precisee')],
    ['Ville', (v) => v.city],
];
// Meilleure valeur par ligne : prix et km les plus bas.
const best = (key) => {
    const vals = props.vehicles.map((v) => (key === 'price' ? v.display_price?.amount : v.mileage_km)).filter((x) => x != null);
    return vals.length > 1 ? Math.min(...vals) : null;
};
const isBest = (key, v) => key && best(key) !== null && (key === 'price' ? v.display_price?.amount : v.mileage_km) === best(key);
const allOptions = computed(() => [...new Set(props.vehicles.flatMap((v) => v.options ?? []))]);
const remove = (id) => {
    if (compare.value.includes(id)) toggleCompare(id);
    router.get('/voiture/comparer', { ids: props.vehicles.map((v) => v.id).filter((x) => x !== id).join(',') }, { replace: true });
};
</script>

<template>
    <Head title="Comparer des vehicules" />
    <div class="mx-auto max-w-6xl px-4 py-8">
        <Link href="/voiture?type=vente" class="text-sm text-[#006ce4] hover:underline">&larr; Retour aux annonces</Link>
        <h1 class="mt-2 text-2xl font-extrabold text-slate-900">Comparer les vehicules</h1>

        <div v-if="vehicles.length < 2" class="mt-6 rounded-2xl border bg-white p-10 text-center text-slate-600">
            Choisissez au moins 2 vehicules avec le bouton « Comparer » sur les annonces.
        </div>

        <div v-else class="mt-6 overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="w-full min-w-[720px] text-sm">
                <thead>
                    <tr>
                        <th class="w-44 p-4" />
                        <th v-for="v in vehicles" :key="v.id" class="p-4 text-left align-top">
                            <div class="overflow-hidden rounded-xl"><ListingImage :src="v.image_url" fallback-class="bg-navy-800" class="h-32">{{ v.brand }}</ListingImage></div>
                            <p class="mt-2 font-bold text-slate-900">{{ v.brand }} {{ v.model }}</p>
                            <p class="truncate text-xs text-slate-500">{{ v.version || v.title }}</p>
                            <div class="mt-2 flex gap-2">
                                <Link :href="`/voiture/${v.slug}`" class="rounded-lg bg-navy-900 px-3 py-1.5 text-xs font-semibold text-white">Voir</Link>
                                <button type="button" class="rounded-lg border px-3 py-1.5 text-xs font-semibold text-slate-600" @click="remove(v.id)">Retirer</button>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="[label, get, key] in ROWS" :key="label">
                        <td class="bg-slate-50 px-4 py-3 font-semibold text-slate-600">{{ label }}</td>
                        <td v-for="v in vehicles" :key="v.id" class="px-4 py-3" :class="isBest(key, v) ? 'font-bold text-green-700' : 'text-slate-900'">
                            {{ get(v) || '-' }}<span v-if="isBest(key, v)" class="ml-1 text-[10px] uppercase">meilleur</span>
                        </td>
                    </tr>
                    <tr v-for="o in allOptions" :key="o">
                        <td class="bg-slate-50 px-4 py-2.5 text-slate-600">{{ o }}</td>
                        <td v-for="v in vehicles" :key="v.id" class="px-4 py-2.5" :class="v.options?.includes(o) ? 'text-green-700' : 'text-slate-300'">{{ v.options?.includes(o) ? '✓' : '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
