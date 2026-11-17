<script setup>
// ANNUAIRE des entreprises : recherche par nom / activite, pays, ville et
// domaine. Chaque fiche : domaine, drapeau, ville, quartier, adresse,
// telephone, e-mail, site web, carte, WhatsApp et lien vers la fiche.
import MainLayout from '@/Layouts/MainLayout.vue';
import PhotoHero from '@/Components/PhotoHero.vue';
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

        <!-- Tableau des entreprises (ordinateur / tablette) -->
        <div v-if="businesses.data.length" class="mt-4 hidden overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-black/5 md:block">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="bg-navy-900 text-xs uppercase tracking-wide text-white">
                    <tr>
                        <th class="w-[34%] px-4 py-3 font-semibold">Entreprise</th>
                        <th class="w-[16%] px-4 py-3 font-semibold">Telephone</th>
                        <th class="w-[20%] px-4 py-3 font-semibold">E-mail</th>
                        <th class="w-[30%] px-4 py-3 font-semibold">Adresse</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="b in businesses.data" :key="b.id" class="align-top odd:bg-white even:bg-slate-50/60 hover:bg-gold-50/40">
                        <td class="px-4 py-3">
                            <Link :href="`/partenaires/${b.slug}`" class="font-bold text-navy-900 hover:text-gold-600 hover:underline">{{ b.name }}</Link>
                            <p class="mt-1"><span class="inline-block rounded bg-navy-50 px-2 py-0.5 text-xs font-semibold text-navy-800">{{ b.category_label }}</span></p>
                            <div v-if="b.services?.length" class="mt-1.5 flex flex-wrap gap-1">
                                <span v-for="sv in b.services" :key="sv" class="rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[11px] text-slate-700">{{ sv }}</span>
                            </div>
                            <a v-if="b.website" :href="b.website" target="_blank" rel="noopener" class="mt-1.5 block truncate text-xs text-navy-700 hover:underline">🌐 {{ site(b.website) }}</a>
                        </td>
                        <td class="px-4 py-3">
                            <template v-if="b.phone || b.whatsapp">
                                <a v-if="b.phone" :href="tel(b.phone)" class="block whitespace-nowrap font-semibold text-navy-800 hover:underline">{{ b.phone }}</a>
                                <a v-if="b.whatsapp" :href="wa(b.whatsapp)" target="_blank" rel="noopener" class="mt-0.5 block whitespace-nowrap text-xs font-semibold text-green-700 hover:underline">WhatsApp {{ b.whatsapp }}</a>
                            </template>
                            <span v-else class="text-slate-400">—</span>
                        </td>
                        <td class="px-4 py-3">
                            <a v-if="b.email" :href="`mailto:${b.email}`" class="break-all text-navy-800 hover:underline">{{ b.email }}</a>
                            <span v-else class="text-slate-400">—</span>
                        </td>
                        <td class="px-4 py-3 text-slate-700">
                            <p v-if="b.address">{{ b.address }}</p>
                            <p class="mt-0.5 flex items-center gap-1.5 text-xs text-slate-500">
                                <span :class="flag(b.country)" class="!h-3 !w-4 shrink-0 rounded-sm ring-1 ring-black/10" />
                                {{ b.city }}<template v-if="b.district">, {{ b.district }}</template> · {{ b.country_name }}
                            </p>
                            <a :href="mapUrl(b)" target="_blank" rel="noopener" class="mt-1 inline-block text-xs font-semibold text-navy-700 hover:underline">Localiser sur la carte →</a>
                            <Link
                                v-if="!b.phone && !b.email && !b.whatsapp"
                                :href="`/annuaire/inscription?nom=${encodeURIComponent(b.name)}&domaine=${b.category}&ville=${encodeURIComponent(b.city ?? '')}&pays=${b.country ?? 'CG'}`"
                                class="mt-1 block text-xs font-semibold text-gold-700 hover:underline"
                            >Vous etes cette entreprise ? Ajoutez vos coordonnees →</Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Telephone : une carte par entreprise avec les memes rubriques -->
        <div v-if="businesses.data.length" class="mt-4 space-y-3 md:hidden">
            <article v-for="b in businesses.data" :key="b.id" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-black/5">
                <Link :href="`/partenaires/${b.slug}`" class="font-bold text-navy-900">{{ b.name }}</Link>
                <p class="mt-1"><span class="inline-block rounded bg-navy-50 px-2 py-0.5 text-xs font-semibold text-navy-800">{{ b.category_label }}</span></p>
                <div v-if="b.services?.length" class="mt-1.5 flex flex-wrap gap-1">
                    <span v-for="sv in b.services" :key="sv" class="rounded-full border border-slate-200 px-2 py-0.5 text-[11px] text-slate-700">{{ sv }}</span>
                </div>
                <dl class="mt-3 grid grid-cols-[84px_1fr] gap-x-2 gap-y-1.5 text-sm">
                    <dt class="text-xs font-semibold uppercase text-slate-500">Telephone</dt>
                    <dd class="min-w-0">
                        <a v-if="b.phone" :href="tel(b.phone)" class="font-semibold text-navy-800">{{ b.phone }}</a>
                        <span v-else class="text-slate-400">—</span>
                        <a v-if="b.whatsapp" :href="wa(b.whatsapp)" target="_blank" rel="noopener" class="ml-2 text-xs font-semibold text-green-700">WhatsApp</a>
                    </dd>
                    <dt class="text-xs font-semibold uppercase text-slate-500">E-mail</dt>
                    <dd class="min-w-0 break-all"><a v-if="b.email" :href="`mailto:${b.email}`" class="text-navy-800">{{ b.email }}</a><span v-else class="text-slate-400">—</span></dd>
                    <dt class="text-xs font-semibold uppercase text-slate-500">Adresse</dt>
                    <dd class="min-w-0 text-slate-700">
                        <span v-if="b.address">{{ b.address }}, </span>{{ b.city }} · {{ b.country_name }}
                        <a :href="mapUrl(b)" target="_blank" rel="noopener" class="block text-xs font-semibold text-navy-700">Localiser sur la carte →</a>
                    </dd>
                </dl>
                <Link
                    v-if="!b.phone && !b.email && !b.whatsapp"
                    :href="`/annuaire/inscription?nom=${encodeURIComponent(b.name)}&domaine=${b.category}&ville=${encodeURIComponent(b.city ?? '')}&pays=${b.country ?? 'CG'}`"
                    class="mt-2 block text-xs font-semibold text-gold-700"
                >Vous etes cette entreprise ? Ajoutez vos coordonnees →</Link>
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
