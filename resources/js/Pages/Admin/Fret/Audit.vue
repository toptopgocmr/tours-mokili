<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AdminLayout });
const props = defineProps({ logs: Object, filters: { type: Object, default: () => ({}) } });
const action = ref(props.filters.action ?? '');
const apply = () => router.get('/admin/fret/audit', action.value ? { action: action.value } : {}, { preserveState: true });
</script>

<template>
    <Head title="Audit fret" />
    <h1 class="mb-4 text-xl font-bold text-slate-900">Journal d'audit Fret</h1>
    <div class="console-panel mb-3 flex gap-2"><input v-model="action" placeholder="Action (ex: partner., order., payment.)" class="rounded border-slate-300 text-sm" @keyup.enter="apply" /><button class="btn-console-primary" @click="apply">Filtrer</button></div>
    <div class="console-panel overflow-x-auto"><table class="w-full text-xs">
        <thead><tr class="text-left text-slate-500"><th>Date</th><th>Action</th><th>Auteur</th><th>Objet</th><th>Motif / contexte</th></tr></thead>
        <tbody><tr v-for="l in logs.data" :key="l.id" class="border-t"><td class="py-1">{{ l.created_at?.slice(0, 16).replace('T', ' ') }}</td><td>{{ l.action }}</td><td>{{ l.actor }}</td><td>{{ l.subject_type }} #{{ l.subject_id }}</td><td>{{ l.reason }} <span class="text-slate-400">{{ l.context ? JSON.stringify(l.context) : '' }}</span></td></tr></tbody>
    </table></div>
</template>
