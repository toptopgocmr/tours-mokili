<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: MainLayout });

const props = defineProps({
    park: { type: Object, required: true },
    similar: { type: Array, default: () => [] },
});

const user = computed(() => usePage().props.auth?.user);

const today = new Date().toISOString().slice(0, 10);
const form = useForm({ adults: 1, children: 0, visit_date: today, notes: '' });

// Prix promotion comprise (identiques au prix normal sans promotion).
const adultPrice = computed(() => Number(props.park.final_price_adult ?? props.park.price_adult ?? 0));
const childPrice = computed(() => Number(props.park.final_price_child ?? props.park.price_child ?? props.park.price_adult ?? 0));
const total = computed(() => form.adults * adultPrice.value + form.children * childPrice.value);
const fmt = (n) => `${Number(n).toLocaleString('fr-FR')} ${props.park.currency}`;

const submit = () => form.post(`/divertissement/parcs/${props.park.slug}/reserver`);

const mapsUrl = computed(() => {
    if (props.park.latitude && props.park.longitude) {
        return `https://www.google.com/maps?q=${props.park.latitude},${props.park.longitude}`;
    }
    return `https://www.google.com/maps/search/${encodeURIComponent(`${props.park.name} ${props.park.city} Congo`)}`;
});
</script>

<template>
    <Head :title="park.name" />

    <div class="mx-auto max-w-6xl px-6 py-10">
        <Link href="/divertissement?tab=parcs" class="text-sm text-navy-500 hover:text-navy-900">&larr; Tourisme &middot; Tous les parcs</Link>

        <div class="mt-4 grid gap-10 md:grid-cols-[1fr_360px]">
            <div>
                <div class="relative h-72 overflow-hidden rounded-3xl bg-gradient-to-br from-navy-700 to-navy-900">
                    <img v-if="park.image_url" :src="park.image_url" :alt="park.name" class="h-full w-full object-cover" />
                    <div class="absolute inset-0 bg-gradient-to-t from-navy-900/80 via-transparent to-transparent" />
                    <div class="absolute bottom-5 left-6 right-6 text-white">
                        <span class="rounded-full bg-gold-500 px-3 py-0.5 text-xs font-semibold text-navy-900">{{ park.category_label }}</span>
                        <h1 class="mt-2 text-3xl font-bold">{{ park.name }}</h1>
                        <p class="text-white/80">{{ park.city }}<span v-if="park.district"> · {{ park.district }}</span></p>
                    </div>
                </div>

                <div v-if="park.gallery_urls?.length > 1" class="mt-4 flex gap-3 overflow-x-auto pb-2">
                    <img v-for="(g, i) in park.gallery_urls" :key="i" :src="g" :alt="`${park.name} ${i + 1}`" class="h-24 w-36 shrink-0 rounded-xl object-cover" loading="lazy" />
                </div>

                <p class="mt-6 whitespace-pre-line leading-relaxed text-navy-700">{{ park.description }}</p>

                <div v-if="park.activities?.length" class="mt-6">
                    <h2 class="font-semibold text-navy-900">Activites</h2>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <span v-for="a in park.activities" :key="a" class="rounded-full bg-navy-50 px-3 py-1 text-sm text-navy-700">{{ a }}</span>
                    </div>
                </div>

                <dl class="mt-8 grid gap-4 rounded-2xl border p-5 text-sm sm:grid-cols-2">
                    <div v-if="park.opening_hours"><dt class="text-navy-500">Horaires</dt><dd class="font-medium">{{ park.opening_hours }}</dd></div>
                    <div v-if="park.address"><dt class="text-navy-500">Adresse</dt><dd class="font-medium">{{ park.address }}</dd></div>
                    <div v-if="park.min_age"><dt class="text-navy-500">Age</dt><dd class="font-medium">{{ park.min_age }}</dd></div>
                    <div v-if="park.phone"><dt class="text-navy-500">Telephone</dt><dd class="font-medium"><a :href="`tel:${park.phone}`" class="hover:underline">{{ park.phone }}</a></dd></div>
                    <div v-if="park.website"><dt class="text-navy-500">Site web</dt><dd class="font-medium"><a :href="park.website" target="_blank" rel="noopener" class="hover:underline">{{ park.website }}</a></dd></div>
                    <div><dt class="text-navy-500">Itineraire</dt><dd class="font-medium"><a :href="mapsUrl" target="_blank" rel="noopener" class="text-gold-600 hover:underline">Ouvrir dans Google Maps</a></dd></div>
                </dl>
            </div>

            <!-- Ticket box -->
            <aside class="h-fit rounded-3xl border p-6 shadow-sm md:sticky md:top-28">
                <template v-if="park.is_bookable">
                    <p class="text-sm text-navy-500">Billet adulte</p>
                    <p class="text-2xl font-bold text-gold-600">{{ fmt(adultPrice) }}</p>
                    <p v-if="park.price_child" class="text-sm text-navy-500">Enfant : {{ fmt(childPrice) }}</p>

                    <form class="mt-6 space-y-4" @submit.prevent="submit">
                        <div>
                            <label class="text-sm font-medium text-navy-900">Date de visite</label>
                            <input v-model="form.visit_date" type="date" :min="today" class="mt-1 w-full rounded-lg border-gray-300" required />
                            <p v-if="form.errors.visit_date" class="mt-1 text-xs text-red-600">{{ form.errors.visit_date }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-sm font-medium text-navy-900">Adultes</label>
                                <input v-model.number="form.adults" type="number" min="0" max="30" class="mt-1 w-full rounded-lg border-gray-300" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-navy-900">Enfants</label>
                                <input v-model.number="form.children" type="number" min="0" max="30" class="mt-1 w-full rounded-lg border-gray-300" />
                            </div>
                        </div>
                        <p v-if="form.errors.adults" class="text-xs text-red-600">{{ form.errors.adults }}</p>

                        <div class="flex items-center justify-between rounded-xl bg-navy-50 px-4 py-3">
                            <span class="text-sm text-navy-700">Total</span>
                            <span class="text-lg font-bold text-navy-900">{{ fmt(total) }}</span>
                        </div>

                        <button v-if="user" type="submit" class="btn-gold w-full" :disabled="form.processing || total <= 0">Reserver mes billets</button>
                        <Link v-else :href="`/login`" class="btn-gold block w-full text-center">Connectez-vous pour reserver</Link>
                        <p class="text-center text-xs text-navy-500">Paiement Mobile Money : MTN MoMo, Airtel Money, Orange Money, M-Pesa…</p>
                    </form>
                </template>
                <template v-else>
                    <p class="text-lg font-semibold text-navy-900">Tarif communique sur place</p>
                    <p class="mt-2 text-sm text-navy-500">
                        La billetterie en ligne n'est pas encore ouverte pour ce site. Contactez-le directement ou passez par notre
                        service client pour organiser votre sortie.
                    </p>
                    <a v-if="park.phone" :href="`tel:${park.phone}`" class="btn-outline mt-5 block w-full text-center">Appeler {{ park.phone }}</a>
                </template>
            </aside>
        </div>

        <section v-if="similar.length" class="mt-14">
            <h2 class="text-xl font-bold text-navy-900">Autres sorties a {{ park.city }}</h2>
            <div class="mt-4 grid gap-5 sm:grid-cols-3">
                <Link v-for="s in similar" :key="s.id" :href="`/divertissement/parcs/${s.slug}`" class="rounded-2xl border p-4 transition hover:shadow-md">
                    <p class="text-xs font-semibold uppercase text-gold-600">{{ s.category_label }}</p>
                    <p class="mt-1 font-semibold text-navy-900">{{ s.name }}</p>
                    <p class="text-sm text-navy-500">{{ s.district ?? s.city }}</p>
                </Link>
            </div>
        </section>
    </div>
</template>
