<script setup>
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineOptions({ layout: PartnerLayout });
const props = defineProps({ profile: { type: Object, default: null }, statusLabels: Object, documentTypes: Array });

const form = useForm({
    company_name: props.profile?.company_name ?? '',
    trade_name: props.profile?.trade_name ?? '',
    description: props.profile?.description ?? '',
    contact_email: props.profile?.contact_email ?? '',
    contact_phone: props.profile?.contact_phone ?? '',
    address: props.profile?.address ?? '',
    country: props.profile?.country ?? '',
    zones: (props.profile?.zones ?? []).join(', '),
    modes: props.profile?.modes ?? [],
});
const submit = () => form.transform((d) => ({ ...d, zones: d.zones.split(',').map((z) => z.trim()).filter(Boolean) })).post('/partner/fret/profil');

const doc = useForm({ target: 'partner_profile', type: props.documentTypes?.[0] ?? 'autre', expires_at: '', file: null });
const upload = () => doc.post('/fret/documents', { forceFormData: true, onSuccess: () => doc.reset('file') });
</script>

<template>
    <Head title="Profil partenaire fret" />
    <h1 class="mb-4 text-xl font-bold text-slate-900">Profil partenaire fret</h1>

    <div v-if="profile" class="console-panel mb-4 text-sm">
        <p>Statut : <b>{{ statusLabels[profile.status] }}</b></p>
        <p v-if="profile.review_note" class="mt-1 rounded bg-amber-50 p-2 text-amber-800">{{ profile.review_note }}</p>
        <p v-if="profile.status !== 'approved'" class="mt-1 text-xs text-slate-500">Vos offres ne sont publiees qu'apres validation de votre candidature et de vos documents.</p>
    </div>

    <form class="console-panel grid max-w-3xl gap-4 sm:grid-cols-2" @submit.prevent="submit">
        <input v-model="form.company_name" placeholder="Raison sociale" class="rounded border-slate-300" required />
        <input v-model="form.trade_name" placeholder="Nom commercial" class="rounded border-slate-300" />
        <input v-model="form.contact_email" type="email" placeholder="E-mail" class="rounded border-slate-300" required />
        <input v-model="form.contact_phone" placeholder="Telephone" class="rounded border-slate-300" required />
        <input v-model="form.address" placeholder="Adresse" class="rounded border-slate-300" />
        <input v-model="form.country" maxlength="2" placeholder="Pays (ISO2)" class="rounded border-slate-300 uppercase" required />
        <input v-model="form.zones" placeholder="Zones desservies (separees par des virgules)" class="rounded border-slate-300 sm:col-span-2" />
        <div class="flex gap-4 text-sm sm:col-span-2">
            <label v-for="m in [['air', 'Aerien'], ['mer', 'Maritime'], ['route', 'Routier']]" :key="m[0]"><input v-model="form.modes" type="checkbox" :value="m[0]" /> {{ m[1] }}</label>
        </div>
        <textarea v-model="form.description" rows="3" placeholder="Presentation de l'entreprise" class="rounded border-slate-300 sm:col-span-2"></textarea>
        <p class="text-xs text-red-600 sm:col-span-2">{{ Object.values(form.errors).join(' ') }}</p>
        <button class="btn-console-primary sm:col-span-2" :disabled="form.processing">{{ profile ? 'Mettre a jour / resoumettre' : 'Deposer ma candidature' }}</button>
    </form>

    <div v-if="profile" class="console-panel mt-4 max-w-3xl text-sm">
        <p class="mb-2 font-semibold">Documents (stockage prive)</p>
        <ul class="mb-3"><li v-for="d in profile.documents" :key="d.id">{{ d.type }} - {{ d.original_name }} <span v-if="d.expires_at" class="text-xs text-slate-500">(expire {{ d.expires_at.slice(0, 10) }})</span></li></ul>
        <form class="flex flex-wrap items-center gap-2" @submit.prevent="upload">
            <select v-model="doc.type" class="rounded border-slate-300"><option v-for="t in documentTypes" :key="t" :value="t">{{ t.replaceAll('_', ' ') }}</option></select>
            <input v-model="doc.expires_at" type="date" class="rounded border-slate-300" title="Date d'expiration" />
            <input type="file" @input="doc.file = $event.target.files[0]" />
            <button class="btn-console-primary" :disabled="!doc.file || doc.processing">Deposer</button>
            <p class="w-full text-xs text-red-600">{{ doc.errors.file }}</p>
        </form>
    </div>
</template>
