<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import GuideCover from '@/Components/GuideCover.vue';
import { usePreviewPhotos } from '@/composables/usePreviewPhotos';

const { photoFor, isPreview } = usePreviewPhotos();
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    businesses: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    categories: { type: Object, default: () => ({}) },
    modules: { type: Object, default: () => ({}) },
});

const MODULE_LABELS = { logement: 'Logement', voyage: 'Voyage', voiture: 'Voiture', divertissement: 'Tourisme', marketplace: 'Marketplace' };

const search = ref(props.filters.search ?? '');
const category = ref(props.filters.category ?? '');
const filter = () => router.get('/admin/partenaires', { search: search.value, category: category.value }, { preserveState: true });

const remove = (b) => {
    if (confirm(`Supprimer "${b.name}" ) ?`)) router.delete(`/admin/partenaires/${b.id}`);
};
</script>

<template>
    <Head title="Societes partenaires" />

    <div class="mb-5 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Societes partenaires</h1>
            <p class="text-sm text-slate-500">Chaque societe apparait dans la rubrique de son activite : hotels → Logement, compagnies / bus / canots → Voyage, taxis → Voiture, restaurants / bars / night clubs / visites → Tourisme, garages → Voiture, supermarches / e-commerce → Marketplace.</p>
        </div>
        <Link href="/admin/partenaires/creer" class="btn-console-primary">+ Nouvelle societe</Link>
    </div>

    <div class="mb-4 flex gap-2">
        <input v-model="search" type="text" placeholder="Rechercher..." class="w-64 rounded border-slate-300 text-sm" @keyup.enter="filter" />
        <select v-model="category" class="rounded border-slate-300 text-sm" @change="filter">
            <option value="">Toutes les categories</option>
            <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
        </select>
    </div>

    <div class="console-table-wrap">
        <table class="console-table">
            <thead>
                <tr><th></th><th>Nom</th><th>Categorie</th><th>Rubrique</th><th>Quartier</th><th>Statut</th><th></th></tr>
            </thead>
            <tbody>
                <tr v-for="b in businesses.data" :key="b.id">
                    <td class="w-16"><div class="h-10 w-14 overflow-hidden rounded"><GuideCover :src="photoFor(b)" :category="b.category" icon-class="h-5 w-5" /></div></td>
                    <td class="font-medium text-slate-900">
                        {{ b.name }} <span v-if="b.stars" class="text-xs text-amber-500">{{ '★'.repeat(b.stars) }}</span>
                        <p v-if="isPreview(b)" class="text-xs font-semibold text-amber-600">Photo en apercu (non publiee)</p>
                        <p v-else-if="!b.image_url" class="text-xs text-amber-600">Pas encore de photo</p>
                    </td>
                    <td>{{ categories[b.category] ?? b.category }}</td>
                    <td class="font-medium">{{ MODULE_LABELS[modules[b.category]] ?? '-' }}</td>
                    <td>{{ b.district ?? b.city }}</td>
                    <td><span :class="b.is_active ? 'console-badge console-badge-success' : 'console-badge console-badge-neutral'">{{ b.is_active ? 'Visible' : 'Masquee' }}</span></td>
                    <td class="space-x-3 whitespace-nowrap text-right">
                        <a :href="`/partenaires/${b.slug}`" target="_blank" class="font-semibold text-slate-500 hover:underline">Voir</a>
                        <Link :href="`/admin/partenaires/${b.id}/editer`" class="font-semibold text-[#0972D3] hover:underline">Editer</Link>
                        <button class="font-semibold text-red-600 hover:underline" @click="remove(b)">Supprimer</button>
                    </td>
                </tr>
                <tr v-if="!businesses.data.length"><td colspan="7" class="py-6 text-center text-slate-400">Aucune societe.</td></tr>
            </tbody>
        </table>
    </div>

    <div v-if="businesses.last_page > 1" class="mt-4 flex gap-2">
        <Link
            v-for="l in businesses.links"
            :key="l.label"
            :href="l.url ?? ''"
            class="rounded border px-2.5 py-1 text-xs"
            :class="[l.active ? 'border-slate-800 bg-slate-800 text-white' : 'bg-white', !l.url && 'pointer-events-none opacity-40']"
            v-html="l.label"
        />
    </div>
</template>
