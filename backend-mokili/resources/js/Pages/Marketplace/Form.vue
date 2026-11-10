<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: MainLayout });

const props = defineProps({ product: { type: Object, default: null } });
const page = usePage();
const cat = computed(() => page.props.catalogue?.marketplace ?? {});
const isEdit = computed(() => !!props.product);
const p = props.product ?? {};
const user = page.props.auth?.user ?? {};

const form = useForm({
    listing_type: p.listing_type ?? 'vente',
    title: p.title ?? '',
    category: p.category ?? '',
    description: p.description ?? '',
    price: p.price ?? '',
    negotiable: p.negotiable ?? false,
    condition: p.condition ?? 'occasion',
    city: p.city ?? 'Brazzaville',
    district: p.district ?? '',
    contact_name: p.contact_name ?? user.name ?? '',
    contact_phone: p.contact_phone ?? user.phone ?? '',
    contact_whatsapp: p.contact_whatsapp ?? '',
    photos: [],
    remove_photos: [],
});

const existing = ref((p.gallery ?? []).map((path, i) => ({ path, url: p.gallery_urls?.[i] })));
const previews = ref([]);
const remaining = computed(() => 6 - existing.value.length - previews.value.length);

const addPhotos = (e) => {
    const files = Array.from(e.target.files).slice(0, Math.max(0, remaining.value));
    files.forEach((f) => {
        form.photos.push(f);
        previews.value.push(URL.createObjectURL(f));
    });
    e.target.value = '';
};
const removeNew = (i) => {
    form.photos.splice(i, 1);
    previews.value.splice(i, 1);
};
const removeExisting = (i) => {
    form.remove_photos.push(existing.value[i].path);
    existing.value.splice(i, 1);
};

const submit = () => {
    if (isEdit.value) {
        form.transform((d) => ({ ...d, _method: 'put' })).post(`/marketplace/annonce/${props.product.id}`, { forceFormData: true });
    } else {
        form.post('/marketplace/deposer', { forceFormData: true });
    }
};
const err = (k) => form.errors[k];
</script>

<template>
    <Head :title="isEdit ? 'Modifier mon annonce' : 'Deposer une annonce'" />
    <div class="mx-auto max-w-3xl px-6 py-10">
        <Link href="/marketplace" class="text-sm text-navy-500 hover:text-navy-900">&larr; Marketplace</Link>
        <h1 class="mt-2 text-2xl font-bold text-navy-900">{{ isEdit ? 'Modifier mon annonce' : 'Deposer une annonce' }}</h1>
        <p class="text-sm text-navy-600">Gratuit. Votre annonce est publiee apres une verification rapide par notre equipe.</p>

        <form class="mt-6 space-y-6" @submit.prevent="submit">
            <section class="rounded-2xl border p-5">
                <div class="flex flex-wrap gap-2">
                    <label v-for="(label, key) in cat.listing_types" :key="key" class="flex cursor-pointer items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold" :class="form.listing_type === key ? 'border-pink-700 bg-pink-50 text-pink-700' : 'border-gray-200'">
                        <input v-model="form.listing_type" type="radio" :value="key" class="hidden" /> {{ label }}
                    </label>
                </div>
                <div class="mt-4">
                    <label class="text-sm font-medium text-navy-900">Titre de l'annonce</label>
                    <input v-model="form.title" type="text" maxlength="120" required placeholder="Ex. iPhone 13 128 Go tres bon etat" class="mt-1 w-full rounded-lg border-gray-300" />
                    <p v-if="err('title')" class="mt-1 text-xs text-red-600">{{ err('title') }}</p>
                </div>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-navy-900">Categorie</label>
                        <select v-model="form.category" required class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="" disabled>Choisir...</option>
                            <option v-for="(label, key) in cat.categories" :key="key" :value="key">{{ label }}</option>
                        </select>
                        <p v-if="err('category')" class="mt-1 text-xs text-red-600">{{ err('category') }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-navy-900">Etat</label>
                        <select v-model="form.condition" class="mt-1 w-full rounded-lg border-gray-300">
                            <option v-for="(label, key) in cat.conditions" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4">
                    <label class="text-sm font-medium text-navy-900">Description</label>
                    <textarea v-model="form.description" rows="5" required placeholder="Etat, caracteristiques, raison de la vente..." class="mt-1 w-full rounded-lg border-gray-300" />
                    <p v-if="err('description')" class="mt-1 text-xs text-red-600">{{ err('description') }}</p>
                </div>
            </section>

            <section class="rounded-2xl border p-5">
                <h2 class="font-semibold text-navy-900">Photos <span class="text-sm font-normal text-navy-500">(jusqu'a 6)</span></h2>
                <div class="mt-3 grid grid-cols-3 gap-3 sm:grid-cols-6">
                    <div v-for="(ph, i) in existing" :key="ph.path" class="relative h-20 overflow-hidden rounded-lg border">
                        <img :src="ph.url" class="h-full w-full object-cover" />
                        <button type="button" class="absolute right-1 top-1 rounded-full bg-black/60 px-1.5 text-xs text-white" @click="removeExisting(i)">✕</button>
                    </div>
                    <div v-for="(src, i) in previews" :key="src" class="relative h-20 overflow-hidden rounded-lg border">
                        <img :src="src" class="h-full w-full object-cover" />
                        <button type="button" class="absolute right-1 top-1 rounded-full bg-black/60 px-1.5 text-xs text-white" @click="removeNew(i)">✕</button>
                    </div>
                    <label v-if="remaining > 0" class="flex h-20 cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-gray-300 text-2xl text-gray-400 hover:border-pink-400">
                        +
                        <input type="file" accept="image/*" multiple class="hidden" @change="addPhotos" />
                    </label>
                </div>
                <p v-if="Object.keys(form.errors).some((k) => k.startsWith('photos'))" class="mt-1 text-xs text-red-600">Photo invalide (image de 5 Mo maximum).</p>
            </section>

            <section class="grid gap-4 rounded-2xl border p-5 sm:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-navy-900">{{ form.listing_type === 'recherche' ? 'Budget (FCFA)' : 'Prix (FCFA)' }}</label>
                    <input v-model="form.price" type="number" min="0" required class="mt-1 w-full rounded-lg border-gray-300" />
                    <p v-if="err('price')" class="mt-1 text-xs text-red-600">{{ err('price') }}</p>
                </div>
                <label class="flex items-end gap-2 pb-2 text-sm"><input v-model="form.negotiable" type="checkbox" /> Prix a debattre</label>
                <div>
                    <label class="text-sm font-medium text-navy-900">Ville</label>
                    <input v-model="form.city" type="text" required class="mt-1 w-full rounded-lg border-gray-300" />
                </div>
                <div>
                    <label class="text-sm font-medium text-navy-900">Quartier</label>
                    <input v-model="form.district" type="text" class="mt-1 w-full rounded-lg border-gray-300" />
                </div>
            </section>

            <section class="grid gap-4 rounded-2xl border p-5 sm:grid-cols-3">
                <div>
                    <label class="text-sm font-medium text-navy-900">Nom affiche</label>
                    <input v-model="form.contact_name" type="text" class="mt-1 w-full rounded-lg border-gray-300" />
                </div>
                <div>
                    <label class="text-sm font-medium text-navy-900">Telephone</label>
                    <input v-model="form.contact_phone" type="tel" required placeholder="06 000 00 00" class="mt-1 w-full rounded-lg border-gray-300" />
                    <p v-if="err('contact_phone')" class="mt-1 text-xs text-red-600">{{ err('contact_phone') }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-navy-900">WhatsApp (si different)</label>
                    <input v-model="form.contact_whatsapp" type="tel" class="mt-1 w-full rounded-lg border-gray-300" />
                </div>
            </section>

            <button type="submit" class="btn-gold w-full" :disabled="form.processing">{{ isEdit ? 'Enregistrer les modifications' : 'Publier mon annonce' }}</button>
        </form>
    </div>
</template>
