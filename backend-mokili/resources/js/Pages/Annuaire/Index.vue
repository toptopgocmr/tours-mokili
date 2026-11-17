<script setup>
// ANNUAIRE des entreprises : recherche par nom / activite, pays, ville et
// domaine. Chaque fiche : domaine, drapeau, ville, quartier, adresse,
// telephone, e-mail, site web, carte, WhatsApp et lien vers la fiche.
import MainLayout from '@/Layouts/MainLayout.vue';
import PhotoHero from '@/Components/PhotoHero.vue';
import GuideCover from '@/Components/GuideCover.vue';
import { usePreviewPhotos } from '@/composables/usePreviewPhotos';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import 'flag-icons/css/flag-icons.min.css';

defineOptions({ layout: MainLayout });

const props = defineProps({
    businesses: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    domains: { type: Array, default: () => [] },
    countries: { type: Array, default: () => [] },
    cities: { type: Array, default: () => [] },
    total: { type: Number, default: 0 },
});

const { photoFor, isIllustration } = usePreviewPhotos();

const f = reactive({
    q: props.filters.q ?? '',
    pays: props.filters.pays ?? '',
    ville: props.filters.ville ?? '',
    domaine: props.filters.domaine ?? '',
});

// Pays qui ont des entreprises en premier, puis les autres.
const countriesWith = computed(() => props.countries.filter((c) => c.count > 0));
const countriesWithout = computed(() => props.countries.filter((c) => c.count === 0));

const apply = () => {
    const params = Object.fromEntries(Object.entries(f).filter(([, v]) => v !== ''));
    router.get('/annuaire', params, { preserveState: true, preserveScroll: true, replace: true });
};
const onCountry = () => {
    f.ville = '';
    apply();
};
const reset = () => {
    Object.assign(f, { q: '', pays: '', ville: '', domaine: '' });
    apply();
};
const hasFilters = computed(() => Object.values(f).some((v) => v !== ''));

const flag = (code) => `fi fi-${String(code ?? '').toLowerCase()}`;
const tel = (n) => `tel:${String(n).replace(/[^\d+]/g, '')}`;
const wa = (n) => `https://wa.me/${String(n).replace(/\D/g, '')}`;
const site = (u) => String(u).replace(/^https?:\/\/(www\.)?/, '').replace(/\/$/, '');
const mapUrl = (b) =>
    b.latitude && b.longitude
        ? `https://www.google.com/maps?q=${b.latitude},${b.longitude}`
        : `https://www.google.com/maps/search/${encodeURIComponent(`${b.name} ${b.address ?? ''} ${b.city ?? ''} ${b.country_name ?? ''}`)}`;
</script>

<template>
    <Head title="Annuaire des entreprises" />

    <PhotoHero>
        <div class="mx-auto max-w-7xl px-6 py-12 text-white">
            <p class="text-sm font-semibold uppercase tracking-wider text-gold-400">Annuaire</p>
            <h1 class="mt-2 text-3xl font-bold md:text-4xl">Annuaire des entreprises</h1>
            <p class="mt-2 max-w-2xl text-white/80">
                Trouvez une entreprise par son nom, son activite, son pays ou sa ville : adresse, telephone, e-mail, site web et localisation.
            </p>
            <div class="mt-5 flex flex-wrap items-center gap-3">
                <span class="rounded-full bg-white/15 px-3 py-1 text-sm">{{ total }} entreprises</span>
                <Link href="/annuaire/inscription" class="btn-gold !px-4 !py-2 text-sm">Inscrire mon entreprise (gratuit)</Link>
            </div>
        </div>
    </PhotoHero>

    <div class="mx-auto max-w-7xl px-6">
        <!-- Recherche -->
        <form class="-mt-6 relative grid gap-3 rounded-2xl bg-white p-4 shadow-lg ring-1 ring-black/5 md:grid-cols-12" @submit.prevent="apply">
            <div class="md:col-span-4">
                <label class="text-xs font-semibold uppercase text-slate-500">Nom ou activite</label>
                <input v-model="f.q" type="search" placeholder="Ex. banque, BGFI, pharmacie..." class="mt-1 w-full rounded-lg border-slate-300 text-sm" />
            </div>
            <div class="md:col-span-3">
                <label class="text-xs font-semibold uppercase text-slate-500">Domaine d'activite</label>
                <select v-model="f.domaine" class="mt-1 w-full rounded-lg border-slate-300 text-sm" @change="apply">
                    <option value="">Tous les domaines</option>
                    <option v-for="d in domains" :key="d.key" :value="d.key">{{ d.label }}</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="text-xs font-semibold uppercase text-slate-500">Pays</label>
                <select v-model="f.pays" class="mt-1 w-full rounded-lg border-slate-300 text-sm" @change="onCountry">
                    <option value="">Tous les pays</option>
                    <optgroup v-if="countriesWith.length" label="Avec des entreprises">
                        <option v-for="c in countriesWith" :key="c.code" :value="c.code">{{ c.name }} ({{ c.count }})</option>
                    </optgroup>
                    <optgroup label="Autres pays">
                        <option v-for="c in countriesWithout" :key="c.code" :value="c.code">{{ c.name }}</option>
                    </optgroup>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="text-xs font-semibold uppercase text-slate-500">Ville</label>
                <select v-model="f.ville" class="mt-1 w-full rounded-lg border-slate-300 text-sm" @change="apply">
                    <option value="">Toutes les villes</option>
                    <option v-for="c in cities" :key="c" :value="c">{{ c }}</option>
                </select>
            </div>
            <div class="flex items-end gap-2 md:col-span-1">
                <button type="submit" class="btn-gold w-full !px-3 !py-2 text-sm">Chercher</button>
            </div>
        </form>

        <div class="mt-6 flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm text-slate-600">
                <b class="text-navy-900">{{ businesses.total }}</b> resultat{{ businesses.total > 1 ? 's' : '' }}
            </p>
            <button v-if="hasFilters" type="button" class="text-sm font-semibold text-navy-700 hover:underline" @click="reset">Effacer les filtres</button>
        </div>

        <!-- Fiches -->
        <div v-if="businesses.data.length" class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <article v-for="b in businesses.data" :key="b.id" class="flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-black/5">
                <Link :href="`/partenaires/${b.slug}`" class="relative block h-36">
                    <GuideCover :src="photoFor(b)" :category="b.category" :alt="b.name" icon-class="h-10 w-10" />
                    <span v-if="isIllustration(b)" class="absolute bottom-1 right-2 text-[10px] text-white/80 drop-shadow">Photo d'illustration</span>
                    <span class="absolute left-3 top-3 rounded-full bg-white/95 px-2.5 py-1 text-xs font-semibold text-navy-800 shadow">{{ b.category_label }}</span>
                </Link>
                <div class="flex flex-1 flex-col p-4">
                    <Link :href="`/partenaires/${b.slug}`" class="text-lg font-bold text-navy-900 hover:text-gold-600">{{ b.name }}</Link>
                    <p class="mt-1 flex items-center gap-2 text-sm text-slate-600">
                        <span :class="flag(b.country)" class="!h-3.5 !w-5 shrink-0 rounded-sm ring-1 ring-black/10" />
                        <span>{{ b.city }}<template v-if="b.district">, {{ b.district }}</template> · {{ b.country_name }}</span>
                    </p>

                    <ul class="mt-3 space-y-1.5 text-sm text-slate-700">
                        <li v-if="b.address" class="flex gap-2"><span class="w-5 shrink-0">📍</span><span>{{ b.address }}</span></li>
                        <li v-if="b.phone" class="flex gap-2"><span class="w-5 shrink-0">📞</span><a :href="tel(b.phone)" class="font-medium text-navy-800 hover:underline">{{ b.phone }}</a></li>
                        <li v-if="b.email" class="flex gap-2"><span class="w-5 shrink-0">✉️</span><a :href="`mailto:${b.email}`" class="truncate text-navy-800 hover:underline">{{ b.email }}</a></li>
                        <li v-if="b.website" class="flex gap-2"><span class="w-5 shrink-0">🌐</span><a :href="b.website" target="_blank" rel="noopener" class="truncate text-navy-800 hover:underline">{{ site(b.website) }}</a></li>
                        <li v-if="!b.phone && !b.email" class="text-xs italic text-slate-500">Coordonnees en cours de mise a jour.</li>
                    </ul>

                    <div class="mt-auto flex flex-wrap gap-2 pt-4">
                        <a :href="mapUrl(b)" target="_blank" rel="noopener" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-navy-800 hover:bg-slate-50">Localiser sur la carte</a>
                        <a v-if="b.whatsapp" :href="wa(b.whatsapp)" target="_blank" rel="noopener" class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-700">WhatsApp</a>
                        <Link :href="`/partenaires/${b.slug}`" class="rounded-lg bg-navy-800 px-3 py-1.5 text-xs font-semibold text-white hover:bg-navy-900">Voir la fiche</Link>
                    </div>
                </div>
            </article>
        </div>

        <div v-else class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
            <p class="font-semibold text-navy-900">Aucune entreprise trouvee.</p>
            <p class="mt-1 text-sm text-slate-600">Essayez un autre mot, un autre pays ou un autre domaine.</p>
            <Link href="/annuaire/inscription" class="btn-gold mt-4 inline-block !px-4 !py-2 text-sm">Inscrire une entreprise</Link>
        </div>

        <!-- Pagination -->
        <div v-if="businesses.last_page > 1" class="mt-8 flex flex-wrap justify-center gap-1.5">
            <Link
                v-for="l in businesses.links"
                :key="l.label"
                :href="l.url ?? ''"
                preserve-scroll
                class="rounded-lg border px-3 py-1.5 text-sm"
                :class="[l.active ? 'border-navy-800 bg-navy-800 text-white' : 'bg-white text-navy-800', !l.url && 'pointer-events-none opacity-40']"
                v-html="l.label"
            />
        </div>

        <div class="mt-10 rounded-2xl bg-navy-900 p-6 text-white md:flex md:items-center md:justify-between">
            <div>
                <p class="text-lg font-bold">Votre entreprise n'est pas encore dans l'annuaire ?</p>
                <p class="text-sm text-white/75">Inscription gratuite, dans tous les pays. Votre fiche est publiee apres verification par MOKILI TOUR.</p>
            </div>
            <Link href="/annuaire/inscription" class="btn-gold mt-4 inline-block !px-5 !py-2.5 md:mt-0">Inscrire mon entreprise</Link>
        </div>
    </div>
</template>
