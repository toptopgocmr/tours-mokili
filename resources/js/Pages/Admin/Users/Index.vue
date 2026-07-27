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

const roleBadgeClass = (r) => ({
    admin: 'console-badge console-badge-error',
    agent: 'console-badge console-badge-info',
    partner: 'console-badge console-badge-pending',
    client: 'console-badge console-badge-neutral',
}[r] ?? 'console-badge console-badge-neutral');
</script>

<template>
    <Head title="Agents & Partenaires" />

    <div class="mb-5 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Agents & Partenaires</h1>
            <p class="text-sm text-slate-500">Gerer les comptes clients, agents, partenaires et administrateurs.</p>
        </div>
        <Link href="/admin/utilisateurs/creer" class="btn-console-primary">+ Nouveau compte</Link>
    </div>

    <div class="mb-4 flex gap-2">
        <select v-model="role" class="rounded border-slate-300 text-sm" @change="filter">
            <option value="">Tous les roles</option>
            <option value="client">Client</option>
            <option value="partner">Partenaire</option>
            <option value="agent">Agent</option>
            <option value="admin">Admin</option>
        </select>
        <input v-model="search" type="text" placeholder="Rechercher un nom..." class="rounded border-slate-300 text-sm" @keyup.enter="filter" />
        <button class="btn-console-secondary" @click="filter">Filtrer</button>
    </div>

    <div class="console-table-wrap">
        <table class="console-table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Inscrit le</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="u in users.data" :key="u.id">
                    <td class="font-medium text-slate-900">{{ u.name }}</td>
                    <td>{{ u.email }}</td>
                    <td><span :class="roleBadgeClass(u.role)">{{ u.role }}</span></td>
                    <td>{{ new Date(u.created_at).toLocaleDateString('fr-FR') }}</td>
                </tr>
                <tr v-if="!users.data.length"><td colspan="4" class="py-6 text-center text-slate-400">Aucun compte.</td></tr>
            </tbody>
        </table>
    </div>
</template>
