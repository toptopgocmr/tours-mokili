<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineOptions({ layout: AdminLayout });
const props = defineProps({ claim: Object });
const form = useForm({ status: 'in_review', resolution: '' });
const submit = () => form.post(`/admin/fret/reclamations/${props.claim.id}/traiter`, { preserveScroll: true, onSuccess: () => form.reset('resolution') });
</script>

<template>
    <Head :title="claim.reference" />
    <Link href="/admin/fret/reclamations" class="text-sm text-slate-500">&larr; Reclamations</Link>
    <h1 class="mb-3 mt-1 text-xl font-bold text-slate-900">{{ claim.reference }} - {{ claim.status }}</h1>
    <div class="grid gap-4 lg:grid-cols-2">
        <div class="console-panel text-sm">
            <p><b>Commande :</b> <Link :href="`/admin/fret/commandes/${claim.order?.reference}`" class="text-blue-600">{{ claim.order?.reference }}</Link> - partenaire {{ claim.order?.partner?.name }}</p>
            <p><b>Client :</b> {{ claim.user?.name }} ({{ claim.user?.email }}) - <b>Motif :</b> {{ claim.reason }}</p>
            <p v-for="m in claim.messages" :key="m.id" class="mt-2"><b>{{ m.author_role }} ({{ m.author?.name }}) :</b> {{ m.body }}</p>
        </div>
        <form class="console-panel space-y-2 text-sm" @submit.prevent="submit">
            <select v-model="form.status" class="w-full rounded border-slate-300"><option value="in_review">En instruction</option><option value="documents_requested">Pieces demandees</option><option value="resolved">Resolue</option><option value="rejected">Rejetee</option></select>
            <textarea v-model="form.resolution" rows="4" placeholder="Decision / message (conserve dans l'historique)" class="w-full rounded border-slate-300" required></textarea>
            <button class="btn-console-primary w-full" :disabled="form.processing">Enregistrer</button>
        </form>
    </div>
</template>
