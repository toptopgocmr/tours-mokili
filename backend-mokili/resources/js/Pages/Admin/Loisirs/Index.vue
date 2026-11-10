<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    parks: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    categories: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search ?? '');
const filter = () => router.get('/admin/loisirs', { search: search.value }, { preserveState: true });

const remove = (park) => {
    if (confirm(`Supprimer "${park.name}" ?`)) {
        router.delete(`/admin/loisirs/${park.id}`);
    }
};
</script>

<template>
    <Head title="Loisirs & Parcs" />

    <div class="mb-5 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Loisirs &amp; Parcs d'attractions</h1>
            <p class="text-sm text-slate-500">Parcs, zoos, parcs aquatiques et sites naturels du Congo affiches sur le site et l'app.</p>
        </div>
        <Link href="/admin/loisirs/creer" class="btn-console-primary">+ Nouveau parc</Link>
    </div>

    <input v-model="search" type="text" placeholder="Rechercher un parc..." class="mb-4 w-64 rounded border-slate-300 text-sm" @keyup.enter="filter" />

    <div class="console-table-wrap">
        <table class="console-table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Type</th>
                    <th>Ville</th>
                    <th>Tarif adulte</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="p in parks.data" :key="p.id">
                    <td class="font-medium text-slate-900">
                        {{ p.name }}
                        <span v-if="p.is_featured" class="ml-1 text-xs text-amber-600">★</span>
                    </td>
                    <td>{{ categories[p.category] ?? p.category }}</td>
                    <td>{{ p.city }}</td>
                    <td>
                        <span v-if="p.price_adult">{{ Number(p.price_adult).toLocaleString('fr-FR') }} {{ $cur(p.currency) }}</span>
                        <span v-else class="text-slate-400">Sur place</span>
                    </td>
                    <td>
                        <span :class="p.is_active ? 'console-badge console-badge-success' : 'console-badge console-badge-neutral'">{{ p.is_active ? 'Visible' : 'Masque' }}</span>
                    </td>
                    <td class="space-x-3 text-right">
                        <a :href="`/loisirs/${p.slug}`" target="_blank" class="font-semibold text-slate-500 hover:underline">Voir</a>
                        <Link :href="`/admin/loisirs/${p.id}/editer`" class="font-semibold text-[#0972D3] hover:underline">Editer</Link>
                        <button class="font-semibold text-red-600 hover:underline" @click="remove(p)">Supprimer</button>
                    </td>
                </tr>
                <tr v-if="!parks.data.length"><td colspan="6" class="py-6 text-center text-slate-400">Aucun parc.</td></tr>
            </tbody>
        </table>
    </div>

    <div v-if="parks.last_page > 1" class="mt-4 flex gap-2">
        <Link
            v-for="l in parks.links"
            :key="l.label"
            :href="l.url ?? ''"
            class="rounded border px-2.5 py-1 text-xs"
            :class="[l.active ? 'border-slate-800 bg-slate-800 text-white' : 'bg-white', !l.url && 'pointer-events-none opacity-40']"
            v-html="l.label"
        />
    </div>
</template>
