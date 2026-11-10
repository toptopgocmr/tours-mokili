<script setup>
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: PartnerLayout });

const props = defineProps({ vehicle: { type: Object, default: null } });
const isEdit = computed(() => !!props.vehicle);

const form = useForm({
    title: props.vehicle?.title ?? '',
    // ?type=mecanique : garages (prestations de mecanique) depuis le tableau de bord.
    offer_type: props.vehicle?.offer_type ?? (new URLSearchParams(typeof window !== 'undefined' ? window.location.search : '').get('type') || 'location'),
    description: props.vehicle?.description ?? '',
    sale_price: props.vehicle?.sale_price ?? '',
    mileage_km: props.vehicle?.mileage_km ?? '',
    fuel: props.vehicle?.fuel ?? '',
    services: props.vehicle?.services ?? [],
    phone: props.vehicle?.phone ?? '',
    brand: props.vehicle?.brand ?? '',
    model: props.vehicle?.model ?? '',
    year: props.vehicle?.year ?? '',
    category: props.vehicle?.category ?? 'berline',
    transmission: props.vehicle?.transmission ?? 'manuelle',
    seats: props.vehicle?.seats ?? 4,
    price_per_day: props.vehicle?.price_per_day ?? '',
    currency: props.vehicle?.currency ?? 'XAF',
    city: props.vehicle?.city ?? '',
    country: props.vehicle?.country ?? '',
    is_active: props.vehicle?.is_active ?? true,
    intent: 'draft',
    image: null,
    remove_image: false,
    color: props.vehicle?.color ?? '',
    doors: props.vehicle?.doors ?? '',
    condition: props.vehicle?.condition ?? 'occasion',
    first_owner: props.vehicle?.first_owner ?? false,
    engine: props.vehicle?.engine ?? '',
    options: props.vehicle?.options ?? [],
    version: props.vehicle?.version ?? '',
    warranty_months: props.vehicle?.warranty_months ?? '',
    gallery: [],
    remove_gallery: [],
});
// Galerie : photos existantes (chemins) + nouvelles photos.
const existingGallery = ref((props.vehicle?.gallery ?? []).map((path, i) => ({ path, url: props.vehicle.gallery_urls?.[i + (props.vehicle.image ? 1 : 0)] ?? path })));
const newPreviews = ref([]);
const onGallery = (e) => {
    for (const file of e.target.files) {
        if (existingGallery.value.length + form.gallery.length >= 15) break;
        form.gallery.push(file);
        newPreviews.value.push(URL.createObjectURL(file));
    }
    e.target.value = '';
};
const removeExisting = (g) => {
    form.remove_gallery.push(g.path);
    existingGallery.value = existingGallery.value.filter((x) => x.path !== g.path);
};
const removeNew = (i) => {
    form.gallery.splice(i, 1);
    newPreviews.value.splice(i, 1);
};

// Listes communes (config/catalogue.php > voiture).
const cat = computed(() => usePage().props.catalogue?.voiture ?? {});
const brandModels = computed(() => cat.value.brands?.[form.brand] ?? []);
const toggleOption = (o) => {
    const i = form.options.indexOf(o);
    i >= 0 ? form.options.splice(i, 1) : form.options.push(o);
};

const statusLabels = { draft: 'Brouillon', pending: 'En attente de validation', published: 'Publie', rejected: 'Rejete' };
const statusLabel = computed(() => statusLabels[props.vehicle?.status] ?? 'Brouillon');

const imagePreview = ref(props.vehicle?.image_url ?? null);
const onImageChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    form.image = file;
    form.remove_image = false;
    imagePreview.value = URL.createObjectURL(file);
};

const submit = (intent) => {
    form.intent = intent;
    // Fichiers + PUT : envoi en POST avec _method=put (PHP ne lit pas le multipart en PUT).
    if (isEdit.value) form.transform((d) => ({ ...d, _method: 'put' })).post(`/partner/voiture/${props.vehicle.id}`, { forceFormData: true });
    else form.post('/partner/voiture', { forceFormData: true });
};
</script>

<template>
    <Head :title="isEdit ? 'Editer le vehicule' : 'Nouveau vehicule'" />
    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-900">{{ isEdit ? 'Editer' : 'Nouveau' }} vehicule</h1>
    </div>

    <form class="console-panel grid max-w-3xl gap-4 sm:grid-cols-2" @submit.prevent="submit">
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Photo</label>
            <div class="mt-1 flex items-center gap-4">
                <img v-if="imagePreview" :src="imagePreview" class="h-20 w-28 rounded-lg border border-slate-200 object-cover" />
                <div v-else class="flex h-20 w-28 items-center justify-center rounded-lg border border-dashed border-slate-300 text-xs text-slate-400">Aucune photo</div>
                <div>
                    <input type="file" accept="image/*" class="text-sm" @change="onImageChange" />
                    <label v-if="imagePreview" class="mt-1 flex items-center gap-1.5 text-xs text-red-600">
                        <input v-model="form.remove_image" type="checkbox" /> Supprimer la photo actuelle
                    </label>
                </div>
            </div>
        </div>
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Type d'annonce</label>
            <div class="mt-1 flex flex-wrap gap-2">
                <label v-for="(label, key) in $page.props.catalogue.voiture.offer_types" :key="key" class="flex items-center gap-1.5 rounded border border-slate-300 px-3 py-1.5 text-sm">
                    <input v-model="form.offer_type" type="radio" :value="key" /> {{ label }}
                </label>
            </div>
        </div>
        <div v-if="form.offer_type === 'mecanique'" class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Prestations proposees</label>
            <div class="mt-1 flex flex-wrap gap-3 text-sm">
                <label v-for="s in $page.props.catalogue.voiture.mechanic_services" :key="s" class="flex items-center gap-1.5"><input v-model="form.services" type="checkbox" :value="s" /> {{ s }}</label>
            </div>
        </div>
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Titre de l'annonce</label>
            <input v-model="form.title" type="text" class="mt-1 w-full rounded border-slate-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Marque</label>
            <input v-model="form.brand" type="text" list="mk-brands" placeholder="Choisir ou saisir" class="mt-1 w-full rounded border-slate-300" required />
            <datalist id="mk-brands"><option v-for="(m, b) in cat.brands" :key="b" :value="b" /></datalist>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Modele</label>
            <input v-model="form.model" type="text" list="mk-models" placeholder="Choisir ou saisir" class="mt-1 w-full rounded border-slate-300" required />
            <datalist id="mk-models"><option v-for="m in brandModels" :key="m" :value="m" /></datalist>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Annee</label>
            <input v-model.number="form.year" type="number" min="1980" max="2100" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Categorie</label>
            <select v-model="form.category" class="mt-1 w-full rounded border-slate-300">
                <option v-for="(label, key) in cat.categories" :key="key" :value="key">{{ label }}</option>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Transmission</label>
            <select v-model="form.transmission" class="mt-1 w-full rounded border-slate-300">
                <option value="manuelle">Manuelle</option>
                <option value="automatique">Automatique</option>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Places</label>
            <input v-model.number="form.seats" type="number" min="1" max="9" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div v-if="form.offer_type === 'location'">
            <label class="text-sm font-medium text-slate-700">Prix / jour</label>
            <input v-model.number="form.price_per_day" type="number" min="0" step="0.01" class="mt-1 w-full rounded border-slate-300" required />
        </div>
        <div v-else>
            <label class="text-sm font-medium text-slate-700">{{ form.offer_type === 'vente' ? 'Prix de vente' : 'Prix a partir de' }}</label>
            <input v-model.number="form.sale_price" type="number" min="0" step="1" class="mt-1 w-full rounded border-slate-300" :required="form.offer_type === 'vente'" />
            <p v-if="form.errors.sale_price" class="mt-1 text-xs text-red-600">{{ form.errors.sale_price }}</p>
        </div>
        <div v-if="form.offer_type !== 'mecanique'">
            <label class="text-sm font-medium text-slate-700">Kilometrage</label>
            <input v-model.number="form.mileage_km" type="number" min="0" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div v-if="form.offer_type !== 'mecanique'">
            <label class="text-sm font-medium text-slate-700">Carburant</label>
            <select v-model="form.fuel" class="mt-1 w-full rounded border-slate-300">
                <option value="">--</option>
                <option v-for="(label, key) in cat.fuels" :key="key" :value="key">{{ label }}</option>
            </select>
        </div>
        <template v-if="form.offer_type !== 'mecanique'">
            <div class="sm:col-span-2">
                <label class="text-sm font-medium text-slate-700">Couleur</label>
                <div class="mt-2 flex flex-wrap gap-2">
                    <button v-for="(c, key) in cat.colors" :key="key" type="button" class="flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold" :class="form.color === key ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 text-slate-700'" @click="form.color = form.color === key ? '' : key">
                        <span class="h-3.5 w-3.5 rounded-full ring-1 ring-black/15" :style="{ background: c[1] }" />{{ c[0] }}
                    </button>
                </div>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Portes</label>
                <select v-model="form.doors" class="mt-1 w-full rounded border-slate-300">
                    <option value="">--</option><option :value="2">2 portes</option><option :value="3">3 portes</option><option :value="4">4 portes</option><option :value="5">5 portes</option>
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Moteur</label>
                <input v-model="form.engine" type="text" placeholder="ex. 2.0 L, V6, 150 ch" class="mt-1 w-full rounded border-slate-300" />
            </div>
        </template>
        <template v-if="form.offer_type === 'vente'">
            <div>
                <label class="text-sm font-medium text-slate-700">Version / finition</label>
                <input v-model="form.version" type="text" placeholder="ex. 2.5 Hybrid Dynamic" class="mt-1 w-full rounded border-slate-300" />
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Garantie (mois)</label>
                <input v-model.number="form.warranty_months" type="number" min="0" max="120" placeholder="ex. 12" class="mt-1 w-full rounded border-slate-300" />
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Etat</label>
                <select v-model="form.condition" class="mt-1 w-full rounded border-slate-300">
                    <option v-for="(label, key) in cat.conditions" :key="key" :value="key">{{ label }}</option>
                </select>
            </div>
            <label class="flex items-center gap-2 self-end pb-2 text-sm text-slate-700">
                <input v-model="form.first_owner" type="checkbox" /> Premiere main
            </label>
        </template>
        <div v-if="form.offer_type !== 'mecanique'" class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Equipements</label>
            <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                <label v-for="o in cat.options" :key="o" class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" :checked="form.options.includes(o)" @change="toggleOption(o)" /> {{ o }}
                </label>
            </div>
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Telephone / WhatsApp</label>
            <input v-model="form.phone" type="tel" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div v-if="form.offer_type !== 'mecanique'" class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Autres photos (galerie, 15 max)</label>
            <p class="text-xs text-slate-500">Exterieur avant / arriere / profil, interieur, tableau de bord, compteur, coffre, moteur.</p>
            <div class="mt-2 flex flex-wrap gap-2">
                <div v-for="g in existingGallery" :key="g.path" class="relative h-20 w-28 overflow-hidden rounded border">
                    <img :src="g.url" alt="" class="h-full w-full object-cover" />
                    <button type="button" class="absolute right-1 top-1 rounded bg-black/60 px-1.5 text-xs text-white" @click="removeExisting(g)">×</button>
                </div>
                <div v-for="(u, i) in newPreviews" :key="u" class="relative h-20 w-28 overflow-hidden rounded border border-green-400">
                    <img :src="u" alt="" class="h-full w-full object-cover" />
                    <button type="button" class="absolute right-1 top-1 rounded bg-black/60 px-1.5 text-xs text-white" @click="removeNew(i)">×</button>
                </div>
                <label class="flex h-20 w-28 cursor-pointer items-center justify-center rounded border-2 border-dashed border-slate-300 text-xs text-slate-500 hover:border-slate-500">
                    + Ajouter
                    <input type="file" accept="image/*" multiple class="hidden" @change="onGallery" />
                </label>
            </div>
            <p v-if="form.errors.gallery || form.errors['gallery.0']" class="mt-1 text-xs text-red-600">{{ form.errors.gallery || form.errors['gallery.0'] }}</p>
        </div>
        <div class="sm:col-span-2">
            <label class="text-sm font-medium text-slate-700">Description</label>
            <textarea v-model="form.description" rows="3" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Devise</label>
            <input v-model="form.currency" type="text" maxlength="3" class="mt-1 w-full rounded border-slate-300" required />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Ville</label>
            <input v-model="form.city" type="text" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Pays (ISO2)</label>
            <input v-model="form.country" type="text" maxlength="2" class="mt-1 w-full rounded border-slate-300" />
        </div>
        <div v-if="vehicle?.status === 'rejected'" class="sm:col-span-2 rounded-lg bg-red-50 p-3 text-sm text-red-700">
            <p class="font-semibold">Annonce rejetee par un administrateur</p>
            <p class="mt-1">{{ vehicle.rejection_reason }}</p>
        </div>
        <div class="flex items-center gap-2 sm:col-span-2">
            <input v-model="form.is_active" type="checkbox" id="active" />
            <label for="active" class="text-sm text-slate-700">Actif (visible une fois publie)</label>
        </div>
        <p class="text-sm text-slate-500 sm:col-span-2">Statut actuel : <span class="font-semibold text-slate-700">{{ statusLabel }}</span></p>
        <div class="sm:col-span-2 flex gap-3">
            <button type="button" class="btn-console-secondary flex-1" :disabled="form.processing" @click="submit('draft')">Enregistrer comme brouillon</button>
            <button type="button" class="btn-console-primary flex-1" :disabled="form.processing" @click="submit('submit')">Soumettre pour validation</button>
        </div>
    </form>
</template>
