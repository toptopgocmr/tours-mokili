<script setup>
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineOptions({ layout: PartnerLayout });
defineProps({ claims: Object });
const reply = useForm({ body: '' });
const send = (c) => reply.post(`/partner/fret/reclamations/${c.id}/message`, { onSuccess: () => reply.reset() });
</script>

<template>
    <Head title="Reclamations" />
    <h1 class="mb-4 text-xl font-bold text-slate-900">Reclamations</h1>
    <div v-for="c in claims.data" :key="c.id" class="console-panel mb-3 text-sm">
        <p class="font-semibold">{{ c.reference }} - commande {{ c.order?.reference }} - {{ c.reason }} - {{ c.status }}</p>
        <p v-for="m in c.messages" :key="m.id" class="mt-1"><b>{{ m.author_role }} :</b> {{ m.body }}</p>
        <form class="mt-2 flex gap-2" @submit.prevent="send(c)"><input v-model="reply.body" placeholder="Votre reponse" class="flex-1 rounded border-slate-300" required /><button class="btn-console-primary">Repondre</button></form>
    </div>
    <p v-if="!claims.data.length" class="text-slate-500">Aucune reclamation.</p>
</template>
