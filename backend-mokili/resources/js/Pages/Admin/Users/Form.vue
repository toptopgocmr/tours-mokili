<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineOptions({ layout: AdminLayout });

// This admin-only form is how MOKILI TOUR staff provisions the two
// non-self-service roles: agent (internal staff) and partner
// (listing owner). Clients register themselves via the public /register.
const form = useForm({
    name: '',
    email: '',
    phone: '',
    role: 'agent',
    password: '',
});

const submit = () => form.post('/admin/utilisateurs');
</script>

<template>
    <Head title="Nouveau compte" />

    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-900">Creer un compte agent ou partenaire</h1>
    </div>

    <form class="console-panel max-w-lg space-y-4" @submit.prevent="submit">
        <div>
            <label class="text-sm font-medium text-slate-700">Role</label>
            <select v-model="form.role" class="mt-1 w-full rounded border-slate-300">
                <option value="agent">Agent (staff interne)</option>
                <option value="partner">Partenaire</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Nom complet</label>
            <input v-model="form.name" type="text" class="mt-1 w-full rounded border-slate-300" required />
            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Email</label>
            <input v-model="form.email" type="email" class="mt-1 w-full rounded border-slate-300" required />
            <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Telephone</label>
            <input v-model="form.phone" type="tel" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Mot de passe temporaire</label>
            <input v-model="form.password" type="text" class="mt-1 w-full rounded border-slate-300" required />
            <p class="mt-1 text-xs text-slate-500">Communiquez-le a la personne concernee ; elle pourra le changer ensuite.</p>
            <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
        </div>
        <button type="submit" class="btn-console-primary w-full" :disabled="form.processing">Creer le compte</button>
    </form>
</template>
