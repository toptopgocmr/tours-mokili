<script setup>
// ANNUAIRE : inscription gratuite d'une entreprise (tous pays). La fiche est
// publiee apres verification par MOKILI TOUR (admin > partenaires).
import MainLayout from '@/Layouts/MainLayout.vue';
import PhotoHero from '@/Components/PhotoHero.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: MainLayout });

defineProps({
    domains: { type: Array, default: () => [] },
    countries: { type: Array, default: () => [] },
});

const form = useForm({
    name: '',
    category: '',
    country: 'CG',
    city: '',
    district: '',
    address: '',
    description: '',
    opening_hours: '',
    phone: '',
    whatsapp: '',
    email: '',
    website: '',
    latitude: '',
    longitude: '',
    contact_person: '',
    image: null,
    accept: false,
    fax: '', // champ piege (robots)
});

const preview = ref(null);
const onFile = (e) => {
    const file = e.target.files[0];
    form.image = file ?? null;
    preview.value = file ? URL.createObjectURL(file) : null;
};

// Position GPS : celle du telephone / ordinateur, si l'utilisateur accepte.
const locating = ref(false);
const gpsError = ref('');
const locate = () => {
    gpsError.value = '';
    if (!navigator.geolocation) {
        gpsError.value = "Votre navigateur ne donne pas la position. Saisissez-la a la main.";
        return;
    }
    locating.value = true;
    navigator.geolocation.getCurrentPosition(
        (p) => {
            form.latitude = p.coords.latitude.toFixed(6);
            form.longitude = p.coords.longitude.toFixed(6);
            locating.value = false;
        },
        () => {
            gpsError.value = 'Position refusee ou indisponible. Vous pouvez la saisir a la main.';
            locating.value = false;
        },
        { enableHighAccuracy: true, timeout: 10000 },
    );
};

const submit = () => form.post('/annuaire/inscription', { forceFormData: true, preserveScroll: true });
const input = 'mt-1 w-full rounded-lg border-slate-300 text-sm';
</script>

<template>
    <Head title="Inscrire mon entreprise - Annuaire" />

    <PhotoHero>
        <div class="mx-auto max-w-4xl px-6 py-10 text-white">
            <Link href="/annuaire" class="text-sm text-white/80 hover:underline">← Annuaire</Link>
            <h1 class="mt-2 text-3xl font-bold">Inscrire mon entreprise</h1>
            <p class="mt-1 text-white/80">Gratuit, dans tous les pays. Votre fiche est publiee apres verification par MOKILI TOUR.</p>
        </div>
    </PhotoHero>

    <form class="mx-auto mt-8 max-w-4xl space-y-6 px-6" @submit.prevent="submit">
        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-black/5">
            <h2 class="font-bold text-navy-900">L'entreprise</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="text-sm font-medium text-slate-700">Nom de l'entreprise *</label>
                    <input v-model="form.name" type="text" :class="input" required />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                </div>
                <div class="sm:col-span-2">
                    <label class="text-sm font-medium text-slate-700">Domaine d'activite *</label>
                    <select v-model="form.category" :class="input" required>
                        <option value="" disabled>Choisir...</option>
                        <option v-for="d in domains" :key="d.key" :value="d.key">{{ d.label }}</option>
                    </select>
                    <p v-if="form.errors.category" class="mt-1 text-xs text-red-600">{{ form.errors.category }}</p>
                </div>
                <div class="sm:col-span-2">
                    <label class="text-sm font-medium text-slate-700">Presentation (activites, services)</label>
                    <textarea v-model="form.description" rows="3" :class="input" maxlength="2000" />
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Horaires</label>
                    <input v-model="form.opening_hours" type="text" placeholder="Lun-Ven 8h-17h" :class="input" />
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Logo ou photo</label>
                    <input type="file" accept="image/*" class="mt-1 block w-full text-sm" @change="onFile" />
                    <img v-if="preview" :src="preview" alt="" class="mt-2 h-20 rounded object-cover" />
                    <p v-if="form.errors.image" class="mt-1 text-xs text-red-600">{{ form.errors.image }}</p>
                </div>
            </div>
        </section>

        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-black/5">
            <h2 class="font-bold text-navy-900">Adresse</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-slate-700">Pays *</label>
                    <select v-model="form.country" :class="input" required>
                        <option v-for="c in countries" :key="c.code" :value="c.code">{{ c.name }}</option>
                    </select>
                    <p v-if="form.errors.country" class="mt-1 text-xs text-red-600">{{ form.errors.country }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Ville *</label>
                    <input v-model="form.city" type="text" :class="input" required />
                    <p v-if="form.errors.city" class="mt-1 text-xs text-red-600">{{ form.errors.city }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Quartier</label>
                    <input v-model="form.district" type="text" :class="input" />
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Adresse *</label>
                    <input v-model="form.address" type="text" placeholder="N°, rue, repere" :class="input" required />
                    <p v-if="form.errors.address" class="mt-1 text-xs text-red-600">{{ form.errors.address }}</p>
                </div>
                <div class="sm:col-span-2">
                    <div class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="text-sm font-medium text-slate-700">Latitude</label>
                            <input v-model="form.latitude" type="text" inputmode="decimal" :class="input" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-700">Longitude</label>
                            <input v-model="form.longitude" type="text" inputmode="decimal" :class="input" />
                        </div>
                        <button type="button" class="rounded-lg border border-navy-300 px-3 py-2 text-sm font-semibold text-navy-800 hover:bg-slate-50" :disabled="locating" @click="locate">
                            {{ locating ? 'Localisation...' : '📍 Utiliser ma position' }}
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Facultatif : a remplir depuis les locaux de l'entreprise pour un point exact sur la carte.</p>
                    <p v-if="gpsError" class="mt-1 text-xs text-amber-700">{{ gpsError }}</p>
                    <p v-if="form.errors.latitude || form.errors.longitude" class="mt-1 text-xs text-red-600">{{ form.errors.latitude || form.errors.longitude }}</p>
                </div>
            </div>
        </section>

        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-black/5">
            <h2 class="font-bold text-navy-900">Contacts</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-slate-700">Telephone *</label>
                    <input v-model="form.phone" type="tel" placeholder="+242 06 000 00 00" :class="input" required />
                    <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600">{{ form.errors.phone }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">WhatsApp</label>
                    <input v-model="form.whatsapp" type="tel" :class="input" />
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">E-mail</label>
                    <input v-model="form.email" type="email" :class="input" />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Site web</label>
                    <input v-model="form.website" type="url" placeholder="https://" :class="input" />
                    <p v-if="form.errors.website" class="mt-1 text-xs text-red-600">{{ form.errors.website }}</p>
                </div>
                <div class="sm:col-span-2">
                    <label class="text-sm font-medium text-slate-700">Personne a contacter pour la verification *</label>
                    <input v-model="form.contact_person" type="text" placeholder="Nom et fonction" :class="input" required />
                    <p v-if="form.errors.contact_person" class="mt-1 text-xs text-red-600">{{ form.errors.contact_person }}</p>
                </div>
                <input v-model="form.fax" type="text" name="fax" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />
            </div>
        </section>

        <label class="flex items-start gap-2 text-sm text-slate-700">
            <input v-model="form.accept" type="checkbox" class="mt-0.5" />
            <span>Je confirme representer cette entreprise et que les informations sont exactes. Elles seront visibles publiquement apres verification.</span>
        </label>
        <p v-if="form.errors.accept" class="text-xs text-red-600">{{ form.errors.accept }}</p>

        <div class="flex items-center gap-3">
            <button type="submit" class="btn-gold !px-6 !py-2.5" :disabled="form.processing">Envoyer mon inscription</button>
            <Link href="/annuaire" class="text-sm text-slate-600 hover:underline">Annuler</Link>
        </div>
    </form>
</template>
