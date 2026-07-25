<script setup>
import LogoMark from '@/Components/LogoMark.vue';
import { Head, useForm } from '@inertiajs/vue3';

// Standalone layout - non-public entry point at /partner/login.
const form = useForm({ email: '', password: '' });
const submit = () => form.post('/partner/login', { onFinish: () => form.reset('password') });
</script>

<template>
    <Head title="Connexion partenaire" />

    <div class="flex min-h-screen flex-col items-center justify-center gap-6 bg-navy-50 px-6 py-12">
        <div class="w-full max-w-sm rounded-2xl border bg-white p-8 shadow-sm">
            <div class="flex justify-center">
                <LogoMark size="h-16" />
            </div>
            <h1 class="mt-6 text-center text-lg font-bold text-navy-900">Espace partenaire</h1>
            <p class="mt-1 text-center text-xs text-navy-500">Gerez vos annonces Logement, Voiture, Divertissement, Marketplace.</p>

            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <div>
                    <label class="text-sm font-medium text-navy-900">Email</label>
                    <input v-model="form.email" type="email" class="mt-1 w-full rounded-lg border-gray-300" required autofocus />
                </div>
                <div>
                    <label class="text-sm font-medium text-navy-900">Mot de passe</label>
                    <input v-model="form.password" type="password" class="mt-1 w-full rounded-lg border-gray-300" required />
                </div>
                <p v-if="form.errors.email" class="text-xs text-red-600">{{ form.errors.email }}</p>
                <button type="submit" class="btn-gold w-full" :disabled="form.processing">Se connecter</button>
            </form>
        </div>

        <p class="text-center text-xs text-navy-500/60">
            MOKILI TOUR a ete concu avec 💔 par Basile Marius NGASSAKI
        </p>
    </div>
</template>
