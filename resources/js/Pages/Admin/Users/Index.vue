<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    users: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const role = ref(props.filters.role ?? '');
const search = ref(props.filters.search ?? '');

const filter = () => router.get('/admin/utilisateurs', { role: role.value, search: search.value }, { preserveState: true });
</script>

<template>
    <Head title="Agents & Partenaires" />

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-navy-900">Agents & Partenaires</h1>
        <Link href="/admin/utilisateurs/creer" class="btn-gold !py-2 text-sm">+ Nouveau compte</Link>
    </div>

    <div class="mt-4 flex gap-2">
        <select v-model="role" class="rounded-lg border-gray-300 text-sm" @change="filter">
            <option value="">Tous les roles</option>
            <option value="client">Client</option>
            <option value="partner">Partenaire</option>
            <option value="agent">Agent</option>
            <option value="admin">Admin</option>
        </select>
        <input v-model="search" type="text" placeholder="Rechercher un nom..." class="rounded-lg border-gray-300 text-sm" @keyup.enter="filter" />
        <button class="btn-outline !py-2 text-sm" @click="filter">Filtrer</button>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-navy-50 text-navy-500">
                <tr>
                    <th class="px-4 py-3">Nom</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Inscrit le</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="u in users.data" :key="u.id" class="border-t">
                    <td class="px-4 py-3 font-medium">{{ u.name }}</td>
                    <td class="px-4 py-3">{{ u.email }}</td>
                    <td class="px-4 py-3 capitalize">{{ u.role }}</td>
                    <td class="px-4 py-3">{{ new Date(u.created_at).toLocaleDateString('fr-FR') }}</td>
                </tr>
                <tr v-if="!users.data.length"><td colspan="4" class="px-4 py-6 text-center text-navy-400">Aucun compte.</td></tr>
            </tbody>
        </table>
    </div>
</template>
