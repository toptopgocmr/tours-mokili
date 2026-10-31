<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import GuideCover from '@/Components/GuideCover.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    business: { type: Object, default: null },
    categories: { type: Object, default: () => ({}) },
});

const isEdit = computed(() => !!props.business);
const b = props.business ?? {};

const form = useForm({
    name: b.name ?? '',
    category: b.category ?? 'hotel',
    city: b.city ?? 'Brazzaville',
    district: b.district ?? '',
    address: b.address ?? '',
    stars: b.stars ?? '',
    description: b.description ?? '',
    services: (b.services ?? []).join('\n'),
    opening_hours: b.opening_hours ?? '',
    price_range: b.price_range ?? '',
    phone: b.phone ?? '',
    whatsapp: b.whatsapp ?? '',
    email: b.email ?? '',
    website: b.website ?? '',
    latitude: b.latitude ?? '',
    longitude: b.longitude ?? '',
    is_featured: b.is_featured ?? false,
    is_active: b.is_active ?? true,
    image: null,
    image_link: '',
    remove_image: false,
});

const preview = ref(b.image_url ?? null);
const onFile = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    form.image = file;
    form.remove_image = false;
    preview.value = URL.createObjectURL(file);
};
const onLink = () => {
    if (form.image_link) preview.value = form.image_link;
};

const submit = () => {
    const url = isEdit.value ? `/admin/partenaires/${props.business.id}` : '/admin/partenaires';
    form.transform((data) => ({ ...data, ...(isEdit.value ? { _method: 'put' } : {}) })).post(url, { forceFormData: true });
};
</script>

<template>
    <Head :title="isEdit ? 'Editer une fiche' : 'Nouvelle societe'" />

    <div class="mb-5 flex items-center justify-between">
        <h1 class="text-xl font-bold text-slate-900">{{ isEdit ? 'Editer' : 'Nouvelle' }} societe partenaire</h1>
        <Link href="/admin/partenaires" class="text-sm text-slate-500 hover:underline">&larr; Retour</Link>
    </div>

    <form class="console-panel grid max-w-3xl gap-4 sm:grid-cols-2" @submit.prevent="submit">
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Photo</label>
            <div class="mt-1 flex items-start gap-4">
                <div class="h-24 w-36 shrink-0 overflow-hidden rounded-lg border border-slate-200">
                    <GuideCover :src="preview" :category="form.category" icon-class="h-8 w-8" />
                </div>
                <div class="flex-1 space-y-2">
                    <input type="file" accept="image/*" class="text-sm" @change="onFile" />
                    <input v-model="form.image_link" type="url" placeholder="ou coller l'URL d'une photo (site officiel de la societe...)" class="w-full rounded border-slate-300 text-sm" @blur="onLink" />
                    <label v-if="preview" class="flex items-center gap-1.5 text-xs text-red-600">
                        <input v-model="form.remove_image" type="checkbox" /> Supprimer la photo actuelle
                    </label>
                    <p class="text-xs text-slate-400">Utilisez une photo dont vous avez les droits (fournie par la societe ou prise par vous).</p>
                    <p v-if="form.errors.image || form.errors.image_link" class="text-xs text-red-600">{{ form.errors.image ?? form.errors.image_link }}</p>
                </div>
            </div>
        </div>

        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Nom</label>
            <input v-model="form.name" type="text" class="mt-1 w-full rounded border-slate-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Categorie</label>
            <select v-model="form.category" class="mt-1 w-full rounded border-slate-300">
                <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Etoiles (hotels)</label>
            <select v-model="form.stars" class="mt-1 w-full rounded border-slate-300">
                <option value="">-</option>
                <option v-for="n in 5" :key="n" :value="n">{{ '★'.repeat(n) }}</option>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Ville</label>
            <input v-model="form.city" type="text" class="mt-1 w-full rounded border-slate-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Quartier</label>
            <input v-model="form.district" type="text" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Adresse</label>
            <input v-model="form.address" type="text" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Telephone</label>
            <input v-model="form.phone" type="text" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">WhatsApp</label>
            <input v-model="form.whatsapp" type="text" class="mt-1 w-full rounded border-slate-300" placeholder="+242 06..." />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Email</label>
            <input v-model="form.email" type="email" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Site web</label>
            <input v-model="form.website" type="url" class="mt-1 w-full rounded border-slate-300" />
            <p v-if="form.errors.website" class="mt-1 text-xs text-red-600">{{ form.errors.website }}</p>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Horaires</label>
            <input v-model="form.opening_hours" type="text" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Tarifs indicatifs</label>
            <input v-model="form.price_range" type="text" class="mt-1 w-full rounded border-slate-300" placeholder="A partir de 12 000 FCFA" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Latitude</label>
            <input v-model="form.latitude" type="number" step="0.0000001" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Longitude</label>
            <input v-model="form.longitude" type="number" step="0.0000001" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Services (un par ligne)</label>
            <textarea v-model="form.services" rows="3" class="mt-1 w-full rounded border-slate-300"></textarea>
        </div>
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Description</label>
            <textarea v-model="form.description" rows="4" class="mt-1 w-full rounded border-slate-300"></textarea>
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-700"><input v-model="form.is_featured" type="checkbox" /> Mise en avant</label>
        <label class="flex items-center gap-2 text-sm text-slate-700"><input v-model="form.is_active" type="checkbox" /> Visible sur le site et l'app</label>

        <div class="sm:col-span-2">
            <button type="submit" class="btn-console-primary w-full" :disabled="form.processing">{{ isEdit ? 'Enregistrer' : 'Ajouter' }}</button>
        </div>
    </form>
</template>
