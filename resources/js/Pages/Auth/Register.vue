<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineOptions({ layout: MainLayout });

const form = useForm({ name: '', email: '', phone: '', password: '', password_confirmation: '' });

const submit = () => form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <Head title="Creer un compte" />

    <div class="mx-auto max-w-md px-6 py-16">
        <h1 class="text-2xl font-bold text-navy-900">Creer un compte</h1>
        <p class="mt-1 text-sm text-navy-700">Rejoignez MOKILI TOUR en quelques secondes.</p>

        <form class="mt-8 space-y-4" @submit.prevent="submit">
            <div>
                <label class="text-sm font-medium text-navy-900">Nom complet</label>
                <input v-model="form.name" type="text" class="mt-1 w-full rounded-lg border-gray-300" required />
                <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
            </div>
            <div>
                <label class="text-sm font-medium text-navy-900">Email</label>
                <input v-model="form.email" type="email" class="mt-1 w-full rounded-lg border-gray-300" required />
                <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
            </div>
            <div>
                <label class="text-sm font-medium text-navy-900">Telephone</label>
                <input v-model="form.phone" type="tel" class="mt-1 w-full rounded-lg border-gray-300" placeholder="+243..." />
            </div>
            <div>
                <label class="text-sm font-medium text-navy-900">Mot de passe</label>
                <input v-model="form.password" type="password" class="mt-1 w-full rounded-lg border-gray-300" required />
            </div>
            <div>
                <label class="text-sm font-medium text-navy-900">Confirmer le mot de passe</label>
                <input v-model="form.password_confirmation" type="password" class="mt-1 w-full rounded-lg border-gray-300" required />
            </div>
            <button type="submit" class="btn-gold w-full" :disabled="form.processing">Creer mon compte</button>
        </form>

        <p class="mt-6 text-center text-sm text-navy-700">
            Deja inscrit ?
            <Link href="/login" class="font-semibold text-gold-600">Se connecter</Link>
        </p>
    </div>
</template>
