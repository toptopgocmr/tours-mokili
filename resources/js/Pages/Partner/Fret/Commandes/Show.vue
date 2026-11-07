<script setup>
import PartnerLayout from '@/Layouts/PartnerLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';

defineOptions({ layout: PartnerLayout });
const props = defineProps({ order: Object, statuses: Object, canConfirm: Boolean });
const page = usePage();
const base = `/partner/fret/commandes/${props.order.reference}`;

const confirm_ = useForm({ instructions: '' });
const confirmOrder = () => confirm_.post(`${base}/confirmer`);

const ev = useForm({ status: 'PRISE_EN_CHARGE', location: '', comment: '', occurred_at: '', corrects_event_id: '' });
const addEvent = () => ev.post(`${base}/suivi`, { onSuccess: () => ev.reset('comment', 'location', 'corrects_event_id') });

const reply = useForm({ body: '' });
const sendReply = (c) => reply.post(`/partner/fret/reclamations/${c.id}/message`, { onSuccess: () => reply.reset() });

const doc = useForm({ target: 'order', reference: props.order.reference, type: 'preuve_livraison', file: null });
const upload = () => doc.post('/fret/documents', { forceFormData: true, onSuccess: () => doc.reset('file') });
</script>

<template>
    <Head :title="order.reference" />
    <Link href="/partner/fret/commandes" class="text-sm text-slate-500">&larr; Commandes</Link>
    <h1 class="mb-3 mt-1 text-xl font-bold text-slate-900">{{ order.reference }} - {{ order.status_label }}</h1>
    <p v-if="page.props.flash?.error" class="mb-3 rounded bg-red-50 p-2 text-sm text-red-700">{{ page.props.flash.error }}</p>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="console-panel text-sm">
            <p><b>Client :</b> {{ order.user?.name }}</p>
            <p><b>Montant :</b> {{ order.total_amount }} {{ order.currency }} - <b>votre part :</b> {{ order.partner_amount }} (commission {{ order.commission_percent }} %)</p>
            <p><b>Conditions figees :</b> {{ order.frozen_terms?.title }} - {{ order.frozen_terms?.origin }} &rarr; {{ order.frozen_terms?.destination }}</p>
            <p v-if="order.pickup_instructions"><b>Instructions :</b> {{ order.pickup_instructions }}</p>

            <form v-if="canConfirm" class="mt-3 space-y-2" @submit.prevent="confirmOrder">
                <p class="font-semibold">Paiement recu : confirmez la reservation</p>
                <textarea v-model="confirm_.instructions" rows="3" placeholder="Modalites de remise / d'enlevement (adresse, horaires, contact)" class="w-full rounded border-slate-300" required></textarea>
                <p class="text-xs text-red-600">{{ confirm_.errors.instructions }}</p>
                <button class="btn-console-primary" :disabled="confirm_.processing">Confirmer la reservation</button>
            </form>
        </div>

        <div class="console-panel text-sm">
            <p class="mb-2 font-semibold">Suivi {{ order.shipment?.tracking_code }}</p>
            <ul class="mb-3 space-y-1">
                <li v-for="e in [...(order.shipment?.events ?? [])].reverse()" :key="e.id">
                    #{{ e.id }} {{ e.status }} {{ e.location }} - {{ e.occurred_at?.slice(0, 16).replace('T', ' ') }} <span class="text-xs text-slate-500">({{ e.source }}) {{ e.comment }}</span>
                    <button class="ml-2 text-xs text-blue-600" @click="ev.corrects_event_id = e.id">corriger</button>
                </li>
            </ul>
            <form class="grid grid-cols-2 gap-2" @submit.prevent="addEvent">
                <select v-model="ev.status" class="rounded border-slate-300"><option v-for="(l, k) in statuses" :key="k" :value="k">{{ l }}</option></select>
                <input v-model="ev.location" placeholder="Lieu" class="rounded border-slate-300" />
                <input v-model="ev.occurred_at" type="datetime-local" class="rounded border-slate-300" />
                <input v-model="ev.comment" placeholder="Commentaire" class="rounded border-slate-300" />
                <p v-if="ev.corrects_event_id" class="col-span-2 text-xs text-amber-700">Cet evenement corrigera l'evenement #{{ ev.corrects_event_id }} (l'original reste visible).</p>
                <p class="col-span-2 text-xs text-red-600">{{ Object.values(ev.errors).join(' ') }}</p>
                <button class="btn-console-primary col-span-2" :disabled="ev.processing">Ajouter l'evenement</button>
            </form>
            <form class="mt-3 flex flex-wrap items-center gap-2" @submit.prevent="upload">
                <span class="text-xs text-slate-500">Preuve de livraison :</span>
                <input type="file" @input="doc.file = $event.target.files[0]" />
                <button class="btn-console-primary" :disabled="!doc.file || doc.processing">Joindre</button>
            </form>
        </div>

        <div v-for="c in order.claims" :key="c.id" class="console-panel text-sm lg:col-span-2">
            <p class="font-semibold">Reclamation {{ c.reference }} - {{ c.reason }} - {{ c.status }}</p>
            <p v-for="m in c.messages" :key="m.id" class="mt-1"><b>{{ m.author_role }} :</b> {{ m.body }}</p>
            <form class="mt-2 flex gap-2" @submit.prevent="sendReply(c)"><input v-model="reply.body" placeholder="Votre reponse" class="flex-1 rounded border-slate-300" required /><button class="btn-console-primary">Repondre</button></form>
        </div>
    </div>
</template>
