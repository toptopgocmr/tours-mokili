<script setup>
// Espace partenaire : creer / modifier un trajet (bus, train, canot, taxi)
// ou une offre vol, avec trajet regulier, promotion et photo.
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: PartnerLayout });

const props = defineProps({
    offer: { type: Object, default: null },
    kind: { type: String, default: 'circuit' },
    modes: { type: Object, default: () => ({}) },
    defaultMode: { type: String, default: 'bus' },
    company: { type: String, default: '' },
    tripScopes: { type: Object, default: () => ({}) },
    verified: { type: Boolean, default: true },
});

const isEdit = computed(() => !!props.offer?.id);
const isFlight = computed(() => props.kind === 'vol');
const o = props.offer ?? {};
const dt = (v) => (v ? String(v).replace(' ', 'T').slice(0, 16) : '');

const form = useForm({
    type: props.kind,
    transport_mode: o.transport_mode && o.transport_mode !== 'avion' ? o.transport_mode : props.defaultMode,
    title: o.title ?? '',
    origin_city: o.origin_city ?? (isFlight.value ? 'Brazzaville' : ''),
    destination_city: o.destination_city ?? '',
    destination_country: o.destination_country ?? (isFlight.value ? '' : 'CG'),
    departure_point: o.departure_point ?? '',
    trip_scope: o.trip_scope ?? '',
    airline: o.airline ?? props.company,
    description: o.description ?? '',
    departure_at: dt(o.departure_at),
    arrival_at: dt(o.arrival_at),
    return_at: dt(o.return_at),
    price: o.price ?? '',
    seats_available: o.seats_available ?? (isFlight.value ? 9 : 30),
    discount_percent: o.discount_percent ?? 0,
    promo_label: o.promo_label ?? '',
    promo_ends_at: dt(o.promo_ends_at),
    is_active: o.is_active ?? true,
    image: null,
    remove_image: false,
    repeat_days: [],
    repeat_until: '',
});

const WEEKDAYS = [[1, 'Lun'], [2, 'Mar'], [3, 'Mer'], [4, 'Jeu'], [5, 'Ven'], [6, 'Sam'], [7, 'Dim']];
const toggleDay = (d) => {
    const i = form.repeat_days.indexOf(d);
    i >= 0 ? form.repeat_days.splice(i, 1) : form.repeat_days.push(d);
};
const withPromo = ref(Number(o.discount_percent) > 0);
const regular = ref(false);

const imagePreview = ref(props.offer?.image_url ?? null);
const onImageChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    form.image = file;
    form.remove_image = false;
    imagePreview.value = URL.createObjectURL(file);
};

const final = computed(() => Math.round(Number(form.price || 0) * (1 - (withPromo.value ? Number(form.discount_percent || 0) : 0) / 100)));
const departures = computed(() => {
    if (!regular.value || !form.departure_at || !form.repeat_until || !form.repeat_days.length) return 1;
    let n = 1;
    const d = new Date(`${form.departure_at.slice(0, 10)}T12:00:00`);
    const end = new Date(`${form.repeat_until}T12:00:00`);
    for (d.setDate(d.getDate() + 1); d <= end && n < 181; d.setDate(d.getDate() + 1)) {
        if (form.repeat_days.includes(((d.getDay() + 6) % 7) + 1)) n++;
    }
    return n;
});

const PLACEHOLDERS = {
    bus: ['Brazzaville', 'Pointe-Noire', 'Agence Ocean du Nord, Moungali'],
    train: ['Brazzaville', 'Pointe-Noire', 'Gare centrale CFCO'],
    canot: ['Brazzaville', 'Kinshasa', 'Le Beach'],
    taxi: ['Brazzaville', 'Kinkala', 'Rond-point Moukondo'],
    bateau: ['Brazzaville', 'Mossaka', 'Port autonome'],
    voiture: ['Brazzaville', 'Oyo', 'Point de depart'],
};
const ph = computed(() => PLACEHOLDERS[form.transport_mode] ?? PLACEHOLDERS.bus);

const submit = () => {
    form.transform((d) => ({
        ...d,
        discount_percent: withPromo.value ? d.discount_percent : 0,
        promo_label: withPromo.value ? d.promo_label : '',
        promo_ends_at: withPromo.value ? d.promo_ends_at : '',
        repeat_days: regular.value ? d.repeat_days : [],
        repeat_until: regular.value ? d.repeat_until : '',
        ...(isEdit.value ? { _method: 'put' } : {}),
    }));
    form.post(isEdit.value ? `/partner/transport/${props.offer.id}` : '/partner/transport', { forceFormData: true });
};
</script>

<template>
    <Head :title="isEdit ? 'Modifier' : isFlight ? 'Nouvelle offre vol' : 'Nouveau trajet'" />

    <div class="mb-5">
        <Link :href="isFlight ? '/partner/transport?vue=vols' : '/partner/transport'" class="text-sm text-slate-500 hover:text-slate-900">← Retour</Link>
        <h1 class="text-xl font-bold text-slate-900">{{ isEdit ? 'Modifier' : isFlight ? 'Nouvelle offre vol' : 'Nouveau trajet' }}</h1>
        <p class="text-sm text-slate-500">{{ isFlight ? 'Offre affichee dans « Offres vols du moment » ; les clients reservent via la billetterie.' : 'Depart visible dans la recherche Trains, bus et canots et dans le Programme des departs.' }}</p>
    </div>

    <form class="grid max-w-5xl gap-5 lg:grid-cols-[1fr_320px]" @submit.prevent="submit">
        <div class="space-y-5">
            <!-- Moyen -->
            <section v-if="!isFlight" class="console-panel">
                <h2 class="mb-3 font-semibold text-slate-900">Moyen de transport</h2>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="(label, key) in modes"
                        :key="key"
                        type="button"
                        class="rounded border px-3 py-1.5 text-sm font-medium"
                        :class="form.transport_mode === key ? 'border-[#0F1B2A] bg-[#0F1B2A] text-white' : 'border-slate-300 bg-white text-slate-700'"
                        @click="form.transport_mode = key"
                    >
                        {{ label }}
                    </button>
                </div>
            </section>

            <!-- Trajet -->
            <section class="console-panel grid gap-4 sm:grid-cols-2">
                <h2 class="font-semibold text-slate-900 sm:col-span-2">{{ isFlight ? 'Destination' : 'Trajet' }}</h2>
                <label class="text-sm font-medium text-slate-700">Ville de depart
                    <input v-model="form.origin_city" type="text" :placeholder="isFlight ? 'Brazzaville' : ph[0]" class="mt-1 w-full rounded border-slate-300" required />
                    <span v-if="form.errors.origin_city" class="text-xs text-red-600">{{ form.errors.origin_city }}</span>
                </label>
                <label class="text-sm font-medium text-slate-700">Ville d'arrivee
                    <input v-model="form.destination_city" type="text" :placeholder="isFlight ? 'Paris, Dubai...' : ph[1]" class="mt-1 w-full rounded border-slate-300" required />
                    <span v-if="form.errors.destination_city" class="text-xs text-red-600">{{ form.errors.destination_city }}</span>
                </label>
                <label v-if="!isFlight" class="text-sm font-medium text-slate-700 sm:col-span-2">Point de depart (gare, agence, port)
                    <input v-model="form.departure_point" type="text" :placeholder="ph[2]" class="mt-1 w-full rounded border-slate-300" />
                </label>
                <label v-if="isFlight" class="text-sm font-medium text-slate-700">Pays d'arrivee (code ISO)
                    <input v-model="form.destination_country" type="text" maxlength="2" placeholder="FR, AE, TR..." class="mt-1 w-full rounded border-slate-300 uppercase" />
                </label>
                <label v-if="!isFlight" class="text-sm font-medium text-slate-700">Type de trajet
                    <select v-model="form.trip_scope" class="mt-1 w-full rounded border-slate-300">
                        <option value="">—</option>
                        <option v-for="(label, key) in tripScopes" :key="key" :value="key">{{ label }}</option>
                    </select>
                </label>
                <label class="text-sm font-medium text-slate-700">{{ isFlight ? 'Compagnie aerienne' : 'Transporteur' }}
                    <input v-model="form.airline" type="text" class="mt-1 w-full rounded border-slate-300" />
                </label>
            </section>

            <!-- Horaires -->
            <section class="console-panel grid gap-4 sm:grid-cols-2">
                <h2 class="font-semibold text-slate-900 sm:col-span-2">{{ isFlight ? 'Dates (optionnel)' : 'Horaires' }}</h2>
                <label class="text-sm font-medium text-slate-700">{{ isFlight ? 'Date de depart' : 'Depart' }}
                    <input v-model="form.departure_at" type="datetime-local" class="mt-1 w-full rounded border-slate-300" :required="!isFlight" />
                    <span v-if="form.errors.departure_at" class="text-xs text-red-600">{{ form.errors.departure_at }}</span>
                </label>
                <label v-if="!isFlight" class="text-sm font-medium text-slate-700">Arrivee prevue
                    <input v-model="form.arrival_at" type="datetime-local" class="mt-1 w-full rounded border-slate-300" />
                    <span v-if="form.errors.arrival_at" class="text-xs text-red-600">{{ form.errors.arrival_at }}</span>
                </label>
                <label v-else class="text-sm font-medium text-slate-700">Date de retour (aller-retour)
                    <input v-model="form.return_at" type="datetime-local" class="mt-1 w-full rounded border-slate-300" />
                </label>

                <div v-if="!isFlight && !isEdit" class="sm:col-span-2">
                    <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                        <input v-model="regular" type="checkbox" class="rounded" /> Trajet regulier (creer tous les departs d'un coup)
                    </label>
                    <div v-if="regular" class="mt-3 rounded border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">Jours de depart</p>
                        <div class="mt-1 flex flex-wrap gap-1.5">
                            <button
                                v-for="[d, label] in WEEKDAYS"
                                :key="d"
                                type="button"
                                class="h-9 w-12 rounded border text-sm font-semibold"
                                :class="form.repeat_days.includes(d) ? 'border-sky-600 bg-sky-600 text-white' : 'border-slate-300 bg-white text-slate-700'"
                                @click="toggleDay(d)"
                            >
                                {{ label }}
                            </button>
                        </div>
                        <label class="mt-3 block text-xs text-slate-500">Jusqu'au (6 mois maximum)
                            <input v-model="form.repeat_until" type="date" class="mt-1 block rounded border-slate-300 text-sm" />
                        </label>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ departures }} depart(s) seront crees.</p>
                        <p v-if="form.errors.repeat_until || form.errors.repeat_days" class="mt-1 text-xs text-red-600">{{ form.errors.repeat_until || form.errors.repeat_days }}</p>
                    </div>
                </div>
            </section>

            <section class="console-panel">
                <h2 class="mb-2 font-semibold text-slate-900">Informations pour les voyageurs</h2>
                <textarea v-model="form.description" rows="3" placeholder="Bagages autorises, climatisation, arrets, conditions d'annulation..." class="w-full rounded border-slate-300 text-sm" />
                <div class="mt-3 flex items-center gap-4">
                    <img v-if="imagePreview" :src="imagePreview" class="h-20 w-28 rounded-lg border border-slate-200 object-cover" />
                    <div>
                        <p class="text-sm font-medium text-slate-700">Photo (bus, train, canot, destination...)</p>
                        <input type="file" accept="image/*" class="text-sm" @change="onImageChange" />
                        <label v-if="imagePreview && isEdit" class="mt-1 flex items-center gap-1.5 text-xs text-red-600">
                            <input v-model="form.remove_image" type="checkbox" /> Supprimer la photo actuelle
                        </label>
                    </div>
                </div>
            </section>
        </div>

        <!-- Prix, places, promotion -->
        <aside class="space-y-5">
            <section class="console-panel space-y-4">
                <h2 class="font-semibold text-slate-900">Prix et places</h2>
                <label class="block text-sm font-medium text-slate-700">Prix par passager (XAF)
                    <input v-model="form.price" type="number" min="0" step="100" class="mt-1 w-full rounded border-slate-300" required />
                    <span v-if="form.errors.price" class="text-xs text-red-600">{{ form.errors.price }}</span>
                </label>
                <label class="block text-sm font-medium text-slate-700">Places disponibles
                    <input v-model="form.seats_available" type="number" min="0" class="mt-1 w-full rounded border-slate-300" required />
                </label>
            </section>

            <section class="console-panel space-y-3">
                <label class="flex items-center gap-2 font-semibold text-slate-900">
                    <input v-model="withPromo" type="checkbox" class="rounded" /> Promotion
                </label>
                <template v-if="withPromo">
                    <label class="block text-sm font-medium text-slate-700">Remise (%)
                        <input v-model="form.discount_percent" type="number" min="1" max="90" class="mt-1 w-full rounded border-slate-300" />
                    </label>
                    <label class="block text-sm font-medium text-slate-700">Libelle (affiche aux clients)
                        <input v-model="form.promo_label" type="text" maxlength="60" placeholder="Promo de Noel, -20 % etudiants..." class="mt-1 w-full rounded border-slate-300" />
                    </label>
                    <label class="block text-sm font-medium text-slate-700">Fin de la promotion
                        <input v-model="form.promo_ends_at" type="datetime-local" class="mt-1 w-full rounded border-slate-300" />
                        <span class="text-xs text-slate-500">Laissez vide pour une promotion sans fin.</span>
                    </label>
                </template>
                <div class="rounded bg-slate-50 p-3 text-sm">
                    <p class="text-slate-500">Prix affiche au client</p>
                    <p class="text-xl font-bold text-slate-900">{{ final.toLocaleString('fr-FR') }} XAF</p>
                </div>
            </section>

            <section class="console-panel space-y-3">
                <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                    <input v-model="form.is_active" type="checkbox" class="rounded" :disabled="!verified" /> En vente sur le site et l'application
                </label>
                <p v-if="!verified" class="text-xs text-amber-700">Votre compte est en cours de validation : l'offre sera mise en vente apres validation.</p>
                <button type="submit" class="btn-console-primary w-full" :disabled="form.processing">
                    {{ isEdit ? 'Enregistrer' : regular && departures > 1 ? `Creer ${departures} departs` : 'Publier' }}
                </button>
            </section>
        </aside>
    </form>
</template>
