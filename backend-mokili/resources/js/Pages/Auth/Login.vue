<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineOptions({ layout: MainLayout });

const form = useForm({ email: '', password: '', remember: false });

const submit = () => form.post('/login', { onFinish: () => form.reset('password') });
</script>

<template>
    <Head title="Connexion" />

    <div class="mx-auto max-w-md px-6 py-16">
        <h1 class="text-2xl font-bold text-navy-900">Connexion</h1>
        <p class="mt-1 text-sm text-navy-700">Accedez a votre compte MOKILI TOUR.</p>

        <form class="mt-8 space-y-4" @submit.prevent="submit">
            <div>
                <label class="text-sm font-medium text-navy-900">Email</label>
                <input v-model="form.email" type="email" class="mt-1 w-full rounded-lg border-gray-300" required />
                <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
            </div>
            <div>
                <label class="text-sm font-medium text-navy-900">Mot de passe</label>
                <input v-model="form.password" type="password" class="mt-1 w-full rounded-lg border-gray-300" required />
            </div>
            <button type="submit" class="btn-gold w-full" :disabled="form.processing">Se connecter</button>
        </form>

        <p class="mt-6 text-center text-sm text-navy-700">
            Pas encore de compte ?
            <Link href="/register" class="font-semibold text-gold-600">Creer un compte</Link>
        </p>
    </div>
</template>
