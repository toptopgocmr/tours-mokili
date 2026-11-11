<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import GuideCover from '@/Components/GuideCover.vue';
import { usePreviewPhotos } from '@/composables/usePreviewPhotos';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

defineOptions({ layout: MainLayout });

// Fiche d'une societe partenaire. Pour un site touristique (Tourisme >
// Visite), presentation facon Boas : galerie 1/N, encart "Entree" et
// "Reserver ma visite" (demande sans engagement).
const props = defineProps({
    business: { type: Object, required: true },
    similar: { type: Array, default: () => [] },
    formulas: { type: Object, default: () => ({}) },
    mechanicServices: { type: Array, default: () => [] },
});

const { photoFor, isPreview } = usePreviewPhotos();
const b = computed(() => props.business);
const isSite = computed(() => b.value.category === 'site_touristique');
// Garage (ou concession avec atelier) : prise de rendez-vous.
const isGarage = computed(() => b.value.category === 'garage' || (b.value.category === 'concessionnaire' && (b.value.services ?? []).some((x) => /atelier|remorquage/i.test(x))));
const canBook = computed(() => isSite.value || isGarage.value);

// --- Galerie ---
const photos = computed(() => [photoFor(b.value), ...(b.value.gallery_urls ?? [])].filter(Boolean).filter((u, i, a) => a.indexOf(u) === i));
const idx = ref(0);
const broken = ref([]);
const shown = computed(() => photos.value.filter((u) => !broken.value.includes(u)));
const current = computed(() => shown.value[Math.min(idx.value, Math.max(0, shown.value.length - 1))] ?? null);
const step = (d) => {
    const n = shown.value.length;
    if (n) idx.value = (idx.value + d + n) % n;
};

const mapsUrl = computed(() =>
    b.value.latitude && b.value.longitude
        ? `https://www.google.com/maps?q=${b.value.latitude},${b.value.longitude}`
        : `https://www.google.com/maps/search/${encodeURIComponent(`${b.value.name} ${b.value.address ?? ''} ${b.value.city} Congo`)}`,
);
const MODULE_PAGES = {
    logement: ['/logement', 'Logement'],
    voyage: ['/voyage', 'Voyage'],
    voiture: ['/voiture', 'Voiture'],
    divertissement: ['/divertissement', 'Tourisme'],
    marketplace: ['/marketplace', 'Marketplace'],
};
const back = computed(() => (isSite.value ? ['/divertissement?tab=visite', 'Sites touristiques'] : b.value.category === 'garage' ? ['/voiture?type=mecanique', 'Mecanique'] : b.value.category === 'concessionnaire' ? ['/voiture?type=vente', 'Voitures a vendre'] : MODULE_PAGES[b.value.module] ?? ['/', 'Accueil']));
const whatsappUrl = computed(() => (b.value.whatsapp ? `https://wa.me/${b.value.whatsapp.replace(/\D/g, '')}` : null));
const price = computed(() => b.value.price_range || (isSite.value ? 'Gratuit' : null));
const badge = computed(() => (isGarage.value ? b.value.category_label : (b.value.services ?? []).find((s) => /visite/i.test(s)) ?? b.value.services?.[0] ?? b.value.category_label));

// --- Reserver ma visite / Prendre RDV (fenetre facon Boas) ---
const user = computed(() => usePage().props.auth?.user);
const [first0, ...rest0] = String(user.value?.name ?? '').split(' ');
const open = ref(typeof window !== 'undefined' && new URLSearchParams(window.location.search).has('rdv'));
const today = new Date().toISOString().slice(0, 10);
const who = reactive({ first: first0 ?? '', last: rest0.join(' ') });
const garageServices = computed(() => {
    const own = (b.value.services ?? []).filter((x) => props.mechanicServices.includes(x));
    return own.length ? [...own, ...props.mechanicServices.filter((x) => !own.includes(x))] : props.mechanicServices;
});
const form = useForm({ name: '', phone: user.value?.phone ?? '', email: user.value?.email ?? '', visit_date: '', visit_time: '', people: 1, formula: 'individuel', service: '', vehicle: '', message: '' });
const sent = ref(false);
const bump = (d) => (form.people = Math.min(500, Math.max(1, Number(form.people || 1) + d)));
const submit = () => {
    form.name = `${who.first} ${who.last}`.trim();
    form.transform((d) => (isGarage.value ? { ...d, people: null, formula: null } : { ...d, service: null, vehicle: null }))
        .post(`/partenaires/${b.value.slug}/visite`, {
            preserveScroll: true,
            onSuccess: () => {
                sent.value = true;
                open.value = false;
                form.reset('visit_date', 'visit_time', 'message', 'vehicle');
            },
        });
};
const ctaLabel = computed(() => (isGarage.value ? 'Prendre rendez-vous' : 'Reserver ma visite'));
</script>

<template>
    <Head :title="business.name" />

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <nav class="text-xs text-slate-500">
            <Link href="/" class="hover:text-navy-900">Accueil</Link> ›
            <Link :href="back[0]" class="hover:text-navy-900">{{ back[1] }}</Link> ›
            <span class="text-slate-700">{{ business.name }}</span>
        </nav>

        <h1 class="mt-4 text-3xl font-extrabold text-navy-900 sm:text-5xl">{{ business.name }}</h1>
        <p class="mt-2 flex flex-wrap items-center gap-x-2 text-sm text-slate-600">
            <span class="font-medium text-emerald-700">{{ business.city }}</span>
            <span v-if="business.address || business.district">· {{ business.address || business.district }}</span>
            <span v-if="business.stars" class="text-gold-500">· {{ '★'.repeat(business.stars) }}</span>
        </p>

        <div class="mt-6 grid gap-8 lg:grid-cols-[1fr_360px]">
            <div>
                <!-- Galerie 1/N -->
                <div class="relative h-72 overflow-hidden rounded-3xl bg-slate-100 sm:h-[460px]">
                    <img v-if="current" :key="current" :src="current" :alt="business.name" referrerpolicy="no-referrer" class="h-full w-full object-cover" @error="broken.push(current)" />
                    <GuideCover v-else :src="null" :category="business.category" :alt="business.name" icon-class="h-20 w-20" />
                    <span v-if="isPreview(business)" class="absolute left-3 top-3 rounded bg-amber-400 px-1.5 py-0.5 text-[10px] font-bold uppercase text-amber-950 shadow">Apercu admin</span>
                    <template v-if="shown.length > 1">
                        <button type="button" aria-label="Photo precedente" class="absolute left-4 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white text-xl text-navy-900 shadow-lg hover:bg-slate-100" @click="step(-1)">‹</button>
                        <button type="button" aria-label="Photo suivante" class="absolute right-4 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white text-xl text-navy-900 shadow-lg hover:bg-slate-100" @click="step(1)">›</button>
                        <span class="absolute bottom-4 left-1/2 -translate-x-1/2 rounded-full bg-black/60 px-3 py-1 text-xs font-bold text-white">{{ Math.min(idx, shown.length - 1) + 1 }} / {{ shown.length }}</span>
                    </template>
                </div>
                <div v-if="shown.length > 1" class="mt-3 flex gap-2 overflow-x-auto pb-1">
                    <button v-for="(g, i) in shown" :key="g" type="button" class="h-16 w-24 shrink-0 overflow-hidden rounded-xl ring-2 transition" :class="i === idx ? 'ring-emerald-500' : 'ring-transparent opacity-70 hover:opacity-100'" @click="idx = i">
                        <img :src="g" :alt="`${business.name} ${i + 1}`" referrerpolicy="no-referrer" class="h-full w-full object-cover" loading="lazy" @error="broken.push(g)" />
                    </button>
                </div>

                <h2 class="mt-8 text-xl font-bold text-navy-900">A propos</h2>
                <p class="mt-2 whitespace-pre-line leading-relaxed text-slate-700">{{ business.description }}</p>

                <div v-if="business.services?.length" class="mt-6">
                    <h2 class="text-xl font-bold text-navy-900">{{ isSite ? 'Points forts' : 'Services' }}</h2>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span v-for="s in business.services" :key="s" class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-800">{{ s }}</span>
                    </div>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    <div v-if="business.opening_hours" class="rounded-2xl border p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Horaires</p><p class="mt-1 font-semibold text-navy-900">{{ business.opening_hours }}</p></div>
                    <a :href="mapsUrl" target="_blank" rel="noopener" class="rounded-2xl border p-4 hover:border-emerald-400"><p class="text-xs uppercase tracking-wide text-slate-500">Itineraire</p><p class="mt-1 font-semibold text-emerald-700">Ouvrir dans Google Maps →</p></a>
                </div>
            </div>

            <!-- Encart reservation -->
            <aside class="h-fit space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:sticky lg:top-28">
                <span class="inline-block rounded-full border border-emerald-300 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ badge }}</span>
                <div v-if="price" class="border-b pb-5">
                    <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ isSite ? 'Entree' : 'Tarifs' }}</p>
                    <p class="mt-1 text-4xl font-extrabold text-emerald-600">{{ price }}</p>
                </div>
                <ul class="space-y-3 border-b pb-5 text-sm text-slate-700">
                    <li class="flex gap-3"><span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-emerald-500" /><span><b>{{ business.city }}</b><template v-if="business.address"> · {{ business.address }}</template></span></li>
                    <template v-if="isSite">
                        <li class="flex gap-3"><span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-emerald-500" />Reservation en ligne</li>
                        <li class="flex gap-3"><span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-emerald-500" />Individuel ou groupe</li>
                    </template>
                    <li v-if="business.phone" class="flex gap-3"><span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-emerald-500" />{{ business.phone }}</li>
                </ul>

                <template v-if="canBook">
                    <p v-if="sent" class="rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">{{ isGarage ? 'Demande de rendez-vous envoyee ! Nous vous rappelons sous 24 h pour confirmer.' : "Demande envoyee ! L'equipe MOKILI TOUR vous rappelle pour confirmer votre visite." }}</p>
                    <button type="button" class="w-full rounded-xl bg-emerald-600 py-3.5 font-bold text-white shadow hover:bg-emerald-700" @click="open = true">📅 {{ ctaLabel }}</button>
                    <p class="text-center text-xs text-slate-500">Reservation sans engagement</p>
                    <template v-if="isGarage">
                        <a v-if="business.phone" :href="`tel:${business.phone.replace(/\s/g, '')}`" class="btn-outline block w-full text-center">Appeler</a>
                        <a v-if="whatsappUrl" :href="whatsappUrl" target="_blank" rel="noopener" class="btn-outline block w-full text-center">WhatsApp</a>
                        <a :href="mapsUrl" target="_blank" rel="noopener" class="btn-outline block w-full text-center">Itineraire</a>
                    </template>
                </template>
                <template v-else>
                    <a v-if="business.phone" :href="`tel:${business.phone.replace(/\s/g, '')}`" class="btn-gold block w-full text-center">Appeler</a>
                    <a v-if="whatsappUrl" :href="whatsappUrl" target="_blank" rel="noopener" class="btn-outline block w-full text-center">WhatsApp</a>
                    <a v-if="business.website" :href="business.website" target="_blank" rel="noopener" class="btn-outline block w-full text-center">Site web</a>
                    <a :href="mapsUrl" target="_blank" rel="noopener" class="btn-outline block w-full text-center">Itineraire</a>
                    <Link v-if="business.category === 'hotel'" href="/logement" class="block text-center text-sm font-semibold text-gold-600 hover:underline">Reserver un logement avec MOKILI TOUR →</Link>
                    <Link v-if="business.category === 'compagnie_aerienne'" href="/voyage" class="block text-center text-sm font-semibold text-gold-600 hover:underline">Voir nos offres de vols →</Link>
                </template>
            </aside>
        </div>

        <section v-if="similar.length" class="mt-14">
            <h2 class="text-xl font-bold text-navy-900">{{ isSite ? 'Autres sites a visiter' : `Autres adresses : ${business.category_label}` }}</h2>
            <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <Link v-for="s in similar" :key="s.id" :href="`/partenaires/${s.slug}`" class="group overflow-hidden rounded-2xl border bg-white transition hover:shadow-lg">
                    <div class="relative h-36"><GuideCover :src="photoFor(s)" :category="s.category" :alt="s.name" icon-class="h-8 w-8" />
                        <span v-if="s.city" class="absolute right-2 top-2 rounded-full bg-navy-900/80 px-2 py-0.5 text-[11px] font-semibold text-white">{{ s.city }}</span></div>
                    <p class="p-3 font-semibold text-navy-900 group-hover:text-emerald-700">{{ s.name }}</p>
                </Link>
            </div>
        </section>
    </div>
    <!-- Fenetre de reservation (facon Boas) -->
    <div v-if="open && canBook" class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-0 sm:items-center sm:p-4" @click.self="open = false">
        <form class="flex max-h-[92vh] w-full max-w-lg flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl" @submit.prevent="submit">
            <div class="flex items-start justify-between border-b px-6 py-4">
                <div>
                    <h2 class="text-xl font-bold text-navy-900">{{ isGarage ? 'Rendez-vous' : 'Reserver' }} {{ business.name }}</h2>
                    <p class="mt-1 text-sm text-slate-500">Remplissez le formulaire ci-dessous. Nous reviendrons vers vous rapidement.</p>
                </div>
                <button type="button" class="text-2xl leading-none text-slate-400 hover:text-slate-700" aria-label="Fermer" @click="open = false">×</button>
            </div>
            <div class="flex-1 space-y-5 overflow-y-auto px-6 py-5">
                <section>
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Vos informations</p>
                    <div class="mt-2 grid grid-cols-2 gap-3">
                        <label class="block text-sm"><span class="font-semibold text-slate-700">Prenom <span class="text-red-500">*</span></span>
                            <input v-model="who.first" type="text" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" /></label>
                        <label class="block text-sm"><span class="font-semibold text-slate-700">Nom <span class="text-red-500">*</span></span>
                            <input v-model="who.last" type="text" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" /></label>
                        <label class="block text-sm"><span class="font-semibold text-slate-700">Telephone <span class="text-red-500">*</span></span>
                            <input v-model="form.phone" type="tel" required placeholder="+242 06 000 0000" class="mt-1 w-full rounded-lg border-slate-300 text-sm" />
                            <span v-if="form.errors.phone" class="text-xs text-red-600">{{ form.errors.phone }}</span></label>
                        <label class="block text-sm"><span class="font-semibold text-slate-700">Email</span>
                            <input v-model="form.email" type="email" class="mt-1 w-full rounded-lg border-slate-300 text-sm" /></label>
                    </div>
                    <span v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</span>
                </section>

                <section>
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ isGarage ? 'Votre vehicule et la prestation' : 'Details de la visite' }}</p>
                    <div class="mt-2 grid grid-cols-2 gap-3">
                        <template v-if="isGarage">
                            <label class="col-span-2 block text-sm"><span class="font-semibold text-slate-700">Prestation <span class="text-red-500">*</span></span>
                                <select v-model="form.service" required class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                    <option value="" disabled>Choisir une prestation</option>
                                    <option v-for="m in garageServices" :key="m" :value="m">{{ m }}</option>
                                </select>
                                <span v-if="form.errors.service" class="text-xs text-red-600">{{ form.errors.service }}</span></label>
                            <label class="col-span-2 block text-sm"><span class="font-semibold text-slate-700">Vehicule</span>
                                <input v-model="form.vehicle" type="text" placeholder="Marque, modele, annee (ex. Toyota Corolla 2015)" class="mt-1 w-full rounded-lg border-slate-300 text-sm" /></label>
                        </template>
                        <label class="block text-sm"><span class="font-semibold text-slate-700">{{ isGarage ? 'Date souhaitee' : 'Date de visite' }} <span class="text-red-500">*</span></span>
                            <input v-model="form.visit_date" type="date" :min="today" required class="mt-1 w-full rounded-lg border-slate-300 text-sm" />
                            <span v-if="form.errors.visit_date" class="text-xs text-red-600">{{ form.errors.visit_date }}</span></label>
                        <label v-if="isGarage" class="block text-sm"><span class="font-semibold text-slate-700">Heure souhaitee</span>
                            <input v-model="form.visit_time" type="time" min="07:00" max="19:00" class="mt-1 w-full rounded-lg border-slate-300 text-sm" /></label>
                        <div v-else class="block text-sm"><span class="font-semibold text-slate-700">Nombre de visiteurs <span class="text-red-500">*</span></span>
                            <div class="mt-1 flex h-[42px] items-center justify-between rounded-lg border border-slate-300 px-2">
                                <button type="button" class="h-8 w-8 rounded text-lg text-slate-500 hover:bg-slate-100" @click="bump(-1)">−</button>
                                <span class="font-semibold">{{ form.people }}</span>
                                <button type="button" class="h-8 w-8 rounded text-lg text-slate-500 hover:bg-slate-100" @click="bump(1)">+</button>
                            </div></div>
                        <label v-if="!isGarage && Object.keys(formulas).length" class="col-span-2 block text-sm"><span class="font-semibold text-slate-700">Formule</span>
                            <select v-model="form.formula" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option v-for="(l, k) in formulas" :key="k" :value="k">{{ l }}</option></select></label>
                        <label class="col-span-2 block text-sm"><span class="font-semibold text-slate-700">Notes / Commentaires</span>
                            <textarea v-model="form.message" rows="2" :placeholder="isGarage ? 'Symptomes, bruit, voyant allume, depannage a domicile...' : 'Besoins particuliers, langue du guide, horaire prefere...'" class="mt-1 w-full rounded-lg border-slate-300 text-sm" /></label>
                    </div>
                </section>

                <section class="overflow-hidden rounded-xl border border-emerald-200 bg-emerald-50/50">
                    <p class="border-b border-emerald-200 px-4 py-2 text-xs font-bold uppercase tracking-wide text-emerald-700">Recapitulatif</p>
                    <div class="space-y-2 px-4 py-3 text-sm text-slate-700">
                        <template v-if="isGarage">
                            <p class="flex justify-between"><span>Prestation</span><b>{{ form.service || '—' }}</b></p>
                            <p class="flex justify-between"><span>Date</span><b>{{ form.visit_date || '—' }}<template v-if="form.visit_time"> a {{ form.visit_time }}</template></b></p>
                            <p class="flex justify-between border-t border-emerald-200 pt-2"><span class="font-bold">Tarif</span><b class="text-emerald-700">{{ business.price_range || 'Devis sur place' }}</b></p>
                        </template>
                        <template v-else>
                            <p class="flex justify-between"><span>Visiteurs</span><b>{{ form.people }} personne(s)</b></p>
                            <p class="flex justify-between"><span>Entree × {{ form.people }}</span><b>{{ price }}</b></p>
                            <p class="flex justify-between border-t border-emerald-200 pt-2 text-base"><span class="font-bold">Total a payer</span><b class="text-xl text-emerald-700">{{ price }}</b></p>
                        </template>
                    </div>
                </section>
                <p class="rounded-xl bg-sky-50 p-3 text-sm text-sky-800">ⓘ Nous vous recontactons sous 24 h pour confirmer {{ isGarage ? 'votre rendez-vous' : 'votre visite et finaliser les modalites d\'accueil' }}.</p>
            </div>
            <div class="flex justify-end gap-3 border-t px-6 py-4">
                <button type="button" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="open = false">Annuler</button>
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-700 disabled:opacity-60">{{ form.processing ? 'Envoi...' : 'Confirmer la reservation' }}</button>
            </div>
        </form>
    </div>
</template>
