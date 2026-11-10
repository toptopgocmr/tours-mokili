<script setup>
// Mon entreprise : nom de la societe, activite, contact.
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineOptions({ layout: PartnerLayout });

const props = defineProps({
    company: { type: Object, required: true },
    activities: { type: Object, default: () => ({}) },
});

const form = useForm({
    company_name: props.company.company_name ?? '',
    partner_activity: props.company.partner_activity ?? '',
    name: props.company.name ?? '',
    phone: props.company.phone ?? '',
});
const submit = () => form.put('/partner/entreprise', { preserveScroll: true });
</script>

<template>
    <Head title="Mon entreprise" />

    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-900">Mon entreprise</h1>
        <p class="text-sm">
            <span v-if="company.verified" class="console-badge console-badge-success">Societe validee par MOKILI TOUR</span>
            <span v-else class="console-badge console-badge-pending">En cours de validation</span>
        </p>
    </div>

    <form class="console-panel max-w-xl space-y-4" @submit.prevent="submit">
        <label class="block text-sm font-medium text-slate-700">Nom de la societe
            <input v-model="form.company_name" type="text" placeholder="ex. Ocean du Nord, CFCO, Beach Express..." class="mt-1 w-full rounded border-slate-300" required />
            <span class="text-xs text-slate-500">Affiche comme transporteur sur vos trajets.</span>
            <span v-if="form.errors.company_name" class="block text-xs text-red-600">{{ form.errors.company_name }}</span>
        </label>
        <label class="block text-sm font-medium text-slate-700">Activite
            <select v-model="form.partner_activity" class="mt-1 w-full rounded border-slate-300" required>
                <option value="">— Choisir —</option>
                <option v-for="(label, key) in activities" :key="key" :value="key">{{ label }}</option>
            </select>
        </label>
        <label class="block text-sm font-medium text-slate-700">Nom du responsable
            <input v-model="form.name" type="text" class="mt-1 w-full rounded border-slate-300" required />
        </label>
        <label class="block text-sm font-medium text-slate-700">Telephone
            <input v-model="form.phone" type="tel" placeholder="+242 06 000 0000" class="mt-1 w-full rounded border-slate-300" />
            <span v-if="form.errors.phone" class="text-xs text-red-600">{{ form.errors.phone }}</span>
        </label>
        <p class="text-sm text-slate-500">Email de connexion : <strong>{{ company.email }}</strong></p>
        <button type="submit" class="btn-console-primary" :disabled="form.processing">Enregistrer</button>
    </form>
</template>
