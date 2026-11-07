<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: MainLayout });

const page = usePage();
const socialError = computed(() => page.props.errors?.social);

const form = useForm({ email: '', password: '', remember: false });

const submit = () => form.post('/login', { onFinish: () => form.reset('password') });
</script>

<template>
    <Head title="Connexion" />

    <div class="mx-auto max-w-md px-6 py-16">
        <h1 class="text-2xl font-bold text-navy-900">Connexion</h1>
        <p class="mt-1 text-sm text-navy-700">Accedez a votre compte MOKILI TOUR.</p>

        <p v-if="socialError" class="mt-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{{ socialError }}</p>

        <div class="mt-6 space-y-2">
            <a href="/auth/google/redirect" class="btn-outline w-full !justify-center">Continuer avec Google</a>
            <a href="/auth/facebook/redirect" class="btn-outline w-full !justify-center">Continuer avec Facebook</a>
            <a href="/auth/instagram/redirect" class="btn-outline w-full !justify-center">Continuer avec Instagram</a>
        </div>

        <div class="my-6 flex items-center gap-3">
            <div class="h-px flex-1 bg-gray-200" />
            <span class="text-xs font-medium uppercase text-navy-500">ou</span>
            <div class="h-px flex-1 bg-gray-200" />
        </div>

        <form class="space-y-4" @submit.prevent="submit">
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
