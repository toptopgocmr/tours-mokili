<script setup>
// Promotions, toutes rubriques : trajets et offres vols, hotels et
// logements, voitures et garage, concerts / spectacles / cinema, parcs.
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

defineOptions({ layout: PartnerLayout });

const props = defineProps({ groups: { type: Array, default: () => [] } });

// Selection par rubrique : { trajets: [ids], logements: [ids], ... }
const sel = reactive(Object.fromEntries(props.groups.map((g) => [g.type, []])));
const active = ref(props.groups[0]?.type ?? '');
const search = ref('');
const form = useForm({ discount_percent: 10, promo_label: '', promo_ends_at: '' });

const group = computed(() => props.groups.find((g) => g.type === active.value));
const shown = computed(() => (group.value?.items ?? []).filter((i) => !search.value || `${i.title} ${i.subtitle}`.toLowerCase().includes(search.value.toLowerCase())));
const count = computed(() => Object.values(sel).reduce((n, ids) => n + ids.length, 0));
const isSel = (id) => sel[active.value]?.includes(id);
const toggle = (id) => {
    const list = sel[active.value];
    const i = list.indexOf(id);
    i >= 0 ? list.splice(i, 1) : list.push(id);
};
const allShown = computed(() => shown.value.length && shown.value.every((i) => isSel(i.id)));
const toggleAll = () => {
    const ids = shown.value.map((i) => i.id);
    sel[active.value] = allShown.value ? sel[active.value].filter((id) => !ids.includes(id)) : [...new Set([...sel[active.value], ...ids])];
};
const clear = () => Object.keys(sel).forEach((k) => { sel[k] = []; });
const payload = () => ({ items: Object.fromEntries(Object.entries(sel).filter(([, ids]) => ids.length)) });

const apply = () => form.transform((d) => ({ ...d, ...payload() })).post('/partner/promotions', { preserveScroll: true, onSuccess: clear });
const stop = () => {
    if (!confirm(`Arreter la promotion sur ${count.value} offre(s) ?`)) return;
    router.delete('/partner/promotions', { data: payload(), preserveScroll: true, onSuccess: clear });
};

const when = (iso) => (iso ? new Date(`${iso}:00`).toLocaleString('fr-FR', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : '');
const until = (iso) => (iso ? `jusqu'au ${new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })}` : 'sans date de fin');
const money = (v, c) => (v == null ? '—' : `${Math.round(v).toLocaleString('fr-FR')} ${c}`);
const ICONS = { trajets: '🚌', logements: '🏨', vehicules: '🚗', evenements: '🎤', parcs: '🎡' };
</script>

<template>
    <Head title="Promotions" />

    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-900">Promotions</h1>
        <p class="text-sm text-slate-500">Cochez vos offres (trajets, chambres, voitures, prestations, concerts, parcs...), choisissez la remise et la date de fin. La promotion s'arrete toute seule.</p>
    </div>

    <div v-if="!groups.length" class="console-panel py-10 text-center text-slate-500">
        Vous n'avez encore aucune offre a mettre en promotion.
        <div class="mt-3 flex flex-wrap justify-center gap-2">
            <Link href="/partner/transport/creer" class="btn-console-secondary">Ajouter un trajet</Link>
            <Link href="/partner/logement/creer" class="btn-console-secondary">Ajouter un logement</Link>
            <Link href="/partner/voiture/creer" class="btn-console-secondary">Ajouter un vehicule</Link>
            <Link href="/partner/divertissement/creer" class="btn-console-secondary">Ajouter un evenement</Link>
            <Link href="/partner/parcs/creer" class="btn-console-secondary">Ajouter un parc</Link>
        </div>
    </div>

    <div v-else class="grid gap-5 lg:grid-cols-[1fr_320px]">
        <section class="console-panel">
            <!-- Rubriques -->
            <div class="mb-3 flex flex-wrap gap-1.5">
                <button
                    v-for="g in groups"
                    :key="g.type"
                    type="button"
                    class="flex items-center gap-1.5 rounded border px-3 py-1.5 text-sm font-medium"
                    :class="active === g.type ? 'border-[#0F1B2A] bg-[#0F1B2A] text-white' : 'border-slate-300 bg-white text-slate-700'"
                    @click="active = g.type"
                >
                    <span>{{ ICONS[g.type] }}</span>{{ g.label }}
                    <span class="rounded-full px-1.5 text-xs" :class="active === g.type ? 'bg-white/20' : 'bg-slate-100'">{{ sel[g.type].length || g.items.length }}</span>
                </button>
            </div>
            <div class="mb-3 flex flex-wrap items-center gap-2">
                <input v-model="search" type="text" placeholder="Filtrer (ville, ligne, nom...)" class="flex-1 rounded border-slate-300 text-sm" />
                <button type="button" class="btn-console-secondary" @click="toggleAll">{{ allShown ? 'Tout decocher' : 'Tout cocher' }}</button>
            </div>
            <div class="console-table-wrap max-h-[520px] overflow-auto">
                <table class="console-table">
                    <thead><tr><th></th><th>Offre</th><th>Date</th><th>Prix</th><th>Promotion</th></tr></thead>
                    <tbody>
                        <tr v-for="i in shown" :key="i.id" class="cursor-pointer" @click="toggle(i.id)">
                            <td><input type="checkbox" :checked="isSel(i.id)" class="rounded" @click.stop="toggle(i.id)" /></td>
                            <td><p class="font-medium text-slate-900">{{ i.title }}</p><p class="text-xs text-slate-500">{{ i.subtitle }}</p></td>
                            <td class="whitespace-nowrap text-sm">{{ when(i.date) }}</td>
                            <td class="whitespace-nowrap">{{ money(i.price, i.currency) }}</td>
                            <td>
                                <template v-if="i.discount_percent > 0">
                                    <span class="console-badge console-badge-error">-{{ i.discount_percent }} % {{ i.promo_label }}</span>
                                    <p class="text-xs text-slate-500">{{ until(i.promo_ends_at) }}</p>
                                </template>
                                <span v-else class="text-xs text-slate-400">—</span>
                            </td>
                        </tr>
                        <tr v-if="!shown.length"><td colspan="5" class="py-6 text-center text-slate-400">Aucune offre.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="console-panel h-fit space-y-4 lg:sticky lg:top-4">
            <h2 class="font-semibold text-slate-900">Nouvelle promotion</h2>
            <p class="text-sm text-slate-500"><strong class="text-slate-900">{{ count }}</strong> offre(s) selectionnee(s)</p>
            <label class="block text-sm font-medium text-slate-700">Remise
                <div class="mt-1 flex flex-wrap gap-1.5">
                    <button v-for="p in [5, 10, 15, 20, 30, 50]" :key="p" type="button" class="rounded border px-2.5 py-1 text-sm font-semibold" :class="Number(form.discount_percent) === p ? 'border-rose-600 bg-rose-600 text-white' : 'border-slate-300 bg-white'" @click="form.discount_percent = p">-{{ p }} %</button>
                </div>
                <input v-model="form.discount_percent" type="number" min="1" max="90" class="mt-2 w-full rounded border-slate-300" />
                <span v-if="form.errors.discount_percent" class="text-xs text-red-600">{{ form.errors.discount_percent }}</span>
            </label>
            <label class="block text-sm font-medium text-slate-700">Libelle (optionnel)
                <input v-model="form.promo_label" type="text" maxlength="60" placeholder="Promo de Noel, Happy hour, Week-end..." class="mt-1 w-full rounded border-slate-300" />
            </label>
            <label class="block text-sm font-medium text-slate-700">Fin de la promotion
                <input v-model="form.promo_ends_at" type="datetime-local" class="mt-1 w-full rounded border-slate-300" />
                <span v-if="form.errors.promo_ends_at" class="text-xs text-red-600">{{ form.errors.promo_ends_at }}</span>
            </label>
            <button type="button" class="btn-console-primary w-full" :disabled="!count || form.processing" @click="apply">Appliquer la promotion</button>
            <button type="button" class="w-full text-sm font-semibold text-red-600 hover:underline disabled:opacity-40" :disabled="!count" @click="stop">Arreter la promotion des offres cochees</button>
        </aside>
    </div>
</template>
