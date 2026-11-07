<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: AdminLayout });
defineProps({ claims: Object, filters: Object });
</script>

<template>
    <Head title="Reclamations fret" />
    <h1 class="mb-4 text-xl font-bold text-slate-900">Reclamations</h1>
    <div class="console-panel overflow-x-auto"><table class="w-full text-sm">
        <thead><tr class="text-left text-slate-500"><th>Reference</th><th>Commande</th><th>Client</th><th>Motif</th><th>Statut</th><th></th></tr></thead>
        <tbody><tr v-for="c in claims.data" :key="c.id" class="border-t"><td class="py-2 font-medium">{{ c.reference }}</td><td>{{ c.order?.reference }}</td><td>{{ c.user?.name }}</td><td>{{ c.reason }}</td><td>{{ c.status }}</td>
            <td class="text-right"><Link :href="`/admin/fret/reclamations/${c.id}`" class="text-blue-600">Instruire</Link></td></tr></tbody>
    </table><p v-if="!claims.data.length" class="py-6 text-center text-slate-500">Aucune reclamation.</p></div>
</template>
