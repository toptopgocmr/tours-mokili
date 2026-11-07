<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

defineOptions({ layout: AdminLayout });
const props = defineProps({ profile: Object, statusLabels: Object, history: Array });
const page = usePage();
const form = useForm({ decision: 'approve', reason: '', commission_percent: props.profile.commission_percent ?? '' });
const submit = () => form.post(`/admin/fret/partenaires/${props.profile.id}/decision`, { preserveScroll: true });
const needsReason = () => !['approve', 'reactivate'].includes(form.decision);
</script>

<template>
    <Head :title="profile.company_name" />
    <Link href="/admin/fret/partenaires" class="text-sm text-slate-500">&larr; Partenaires</Link>
    <h1 class="mb-3 mt-1 text-xl font-bold text-slate-900">{{ profile.company_name }} - {{ statusLabels[profile.status] }}</h1>
    <p v-if="page.props.flash?.error" class="mb-3 rounded bg-red-50 p-2 text-sm text-red-700">{{ page.props.flash.error }}</p>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="console-panel text-sm">
            <p><b>Nom commercial :</b> {{ profile.trade_name ?? '-' }}</p>
            <p><b>Compte :</b> {{ profile.user?.name }} ({{ profile.user?.email }})</p>
            <p><b>Contact :</b> {{ profile.contact_email }} / {{ profile.contact_phone }} - {{ profile.address }} ({{ profile.country }})</p>
            <p><b>Modes :</b> {{ (profile.modes ?? []).join(', ') }} - <b>Zones :</b> {{ (profile.zones ?? []).join(', ') }}</p>
            <p class="mt-2">{{ profile.description }}</p>
            <p class="mt-3 font-semibold">Documents</p>
            <p v-for="d in profile.documents" :key="d.id"><a :href="`/admin/fret/documents/${d.id}`" class="text-blue-600">{{ d.type }} - {{ d.original_name }}</a>
                <span v-if="d.expires_at" class="text-xs" :class="new Date(d.expires_at) < new Date() ? 'text-red-600' : 'text-slate-500'"> (expire {{ d.expires_at.slice(0, 10) }})</span></p>
            <p v-if="!profile.documents.length" class="text-red-600">Aucun document depose.</p>
        </div>

        <form class="console-panel space-y-2 text-sm" @submit.prevent="submit">
            <p class="font-semibold">Decision</p>
            <select v-model="form.decision" class="w-full rounded border-slate-300">
                <option value="approve">Approuver</option><option value="correction">Demander une correction</option><option value="reject">Refuser</option>
                <option value="suspend">Suspendre</option><option value="reactivate">Reactiver</option>
            </select>
            <textarea v-model="form.reason" rows="3" :placeholder="needsReason() ? 'Motif obligatoire' : 'Note (optionnelle)'" class="w-full rounded border-slate-300"></textarea>
            <label class="block">Commission specifique (%, vide = taux par defaut)<input v-model="form.commission_percent" type="number" step="0.1" min="0" max="60" class="mt-1 w-full rounded border-slate-300" /></label>
            <button class="btn-console-primary w-full" :disabled="form.processing">Enregistrer la decision</button>
            <p v-if="profile.review_note" class="rounded bg-slate-50 p-2 text-xs">Derniere note : {{ profile.review_note }}</p>
        </form>

        <div class="console-panel text-xs lg:col-span-2">
            <p class="mb-2 text-sm font-semibold">Historique des decisions</p>
            <p v-for="h in history" :key="h.id">{{ h.created_at?.slice(0, 16).replace('T', ' ') }} - {{ h.action }} ({{ h.actor }}) {{ h.reason }}</p>
        </div>
    </div>
</template>
