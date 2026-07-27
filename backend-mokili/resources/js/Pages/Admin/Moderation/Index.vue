<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    pending: { type: Array, required: true },
});

const approve = (item) => {
    router.post(`/admin/moderation/${item.module}/${item.id}/approuver`, {}, { preserveScroll: true });
};

const rejectTarget = ref(null);
const rejectForm = useForm({ reason: '' });

const openReject = (item) => {
    rejectTarget.value = item;
    rejectForm.reset();
};

const submitReject = () => {
    rejectForm.post(`/admin/moderation/${rejectTarget.value.module}/${rejectTarget.value.id}/rejeter`, {
        preserveScroll: true,
        onSuccess: () => { rejectTarget.value = null; },
    });
};
</script>

<template>
    <Head title="Moderation des annonces" />

    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-900">Moderation des annonces</h1>
        <p class="text-sm text-slate-500">Annonces soumises par les partenaires, en attente d'approbation avant mise en ligne.</p>
    </div>

    <div class="console-table-wrap">
        <table class="console-table">
            <thead>
                <tr>
                    <th>Module</th>
                    <th>Titre</th>
                    <th>Partenaire</th>
                    <th>Ville</th>
                    <th>Soumise le</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="item in pending" :key="`${item.module}-${item.id}`">
                    <td><span class="console-badge console-badge-info">{{ item.moduleLabel }}</span></td>
                    <td class="font-medium text-slate-900">{{ item.title }}</td>
                    <td>{{ item.owner }}</td>
                    <td>{{ item.city ?? '-' }}</td>
                    <td>{{ new Date(item.created_at).toLocaleDateString('fr-FR') }}</td>
                    <td class="whitespace-nowrap">
                        <button class="mr-3 text-sm font-semibold text-green-600 hover:underline" @click="approve(item)">Approuver</button>
                        <button class="text-sm font-semibold text-red-600 hover:underline" @click="openReject(item)">Rejeter</button>
                    </td>
                </tr>
                <tr v-if="!pending.length"><td colspan="6" class="py-6 text-center text-slate-400">Aucune annonce en attente.</td></tr>
            </tbody>
        </table>
    </div>

    <div v-if="rejectTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
        <div class="w-full max-w-sm rounded-lg bg-white p-5 shadow-xl">
            <h2 class="font-semibold text-slate-900">Rejeter "{{ rejectTarget.title }}"</h2>
            <p class="mt-1 text-sm text-slate-500">Le partenaire pourra corriger et resoumettre.</p>
            <textarea
                v-model="rejectForm.reason"
                rows="3"
                class="mt-3 w-full rounded border-slate-300 text-sm"
                placeholder="Motif du rejet (photo manquante, prix incoherent, description incomplete...)"
            />
            <p v-if="rejectForm.errors.reason" class="mt-1 text-xs text-red-600">{{ rejectForm.errors.reason }}</p>
            <div class="mt-4 flex justify-end gap-2">
                <button class="btn-console-secondary" @click="rejectTarget = null">Annuler</button>
                <button class="btn-console-primary !bg-red-600" :disabled="rejectForm.processing" @click="submitReject">
                    Confirmer le rejet
                </button>
            </div>
        </div>
    </div>
</template>
