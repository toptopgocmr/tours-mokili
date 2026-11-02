<script setup>
// Recu de paiement / confirmation de reservation / billet electronique,
// imprimables (Ctrl+P -> PDF). Le billet n'existe qu'au statut TICKETED.
import LogoMark from '@/Components/LogoMark.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { dayLabel, hhmm, longDate, money, PAX_LABELS } from '@/utils/flights';

const props = defineProps({
    type: { type: String, required: true },
    dossier: { type: Object, required: true },
});

const d = computed(() => props.dossier);
const title = computed(() => ({ recu: 'Recu de paiement', confirmation: 'Confirmation de reservation', billet: 'Billet electronique - itineraire' }[props.type]));
const payment = computed(() => d.value.booking?.payments?.[0]);
</script>

<template>
    <Head :title="`${title} ${dossier.reference}`" />
    <div class="mx-auto max-w-3xl bg-white p-8 text-sm text-navy-900 print:p-0">
        <div class="mb-6 flex items-center justify-between print:hidden">
            <a :href="`/voyage/vols/dossiers/${d.reference}`" class="text-navy-500 hover:underline">&larr; Retour au dossier</a>
            <button type="button" class="btn-gold" onclick="window.print()">Imprimer / PDF</button>
        </div>

        <div class="flex items-start justify-between border-b pb-4">
            <LogoMark />
            <div class="text-right">
                <p class="text-lg font-bold">{{ title }}</p>
                <p>Dossier {{ d.reference }}</p>
                <p v-if="d.pnr && type !== 'recu'">PNR (reference compagnie) : <strong class="font-mono text-base">{{ d.pnr }}</strong></p>
            </div>
        </div>

        <div v-if="type === 'recu'" class="mt-6 space-y-2">
            <p>Recu de : <strong>{{ d.contact_name }}</strong></p>
            <p>Objet : {{ d.title }} - depart le {{ longDate(d.depart_date) }} - {{ d.passenger_count }} passager(s)</p>
            <p>Montant paye : <strong>{{ money(payment?.amount ?? d.total_amount, d.currency) }}</strong></p>
            <p>Moyen de paiement : Mobile Money ({{ payment?.provider }}) - reference {{ payment?.reference }}</p>
            <p>Date du paiement : {{ new Date(payment?.paid_at ?? d.paid_at).toLocaleString('fr-FR') }}</p>
            <p class="mt-4 rounded bg-gray-50 p-3 text-xs">Ce recu atteste du paiement. Il ne vaut pas billet : le billet electronique est delivre apres son emission par la compagnie.</p>
        </div>

        <template v-else>
            <h2 class="mt-6 font-semibold">Passagers</h2>
            <table class="mt-2 w-full">
                <thead class="text-left text-xs uppercase text-navy-500"><tr><th class="py-1">Nom</th><th>Type</th><th v-if="type === 'billet'">N° de billet</th></tr></thead>
                <tbody>
                    <tr v-for="p in d.passengers" :key="p.id" class="border-t">
                        <td class="py-1.5 font-medium">{{ p.full_name }}</td>
                        <td>{{ PAX_LABELS[p.type] }}</td>
                        <td v-if="type === 'billet'" class="font-mono">{{ p.ticket?.ticket_number }}</td>
                    </tr>
                </tbody>
            </table>

            <h2 class="mt-6 font-semibold">Itineraire</h2>
            <table class="mt-2 w-full">
                <thead class="text-left text-xs uppercase text-navy-500"><tr><th class="py-1">Vol</th><th>Depart</th><th>Arrivee</th></tr></thead>
                <tbody>
                    <template v-for="(s, i) in d.itinerary ?? []" :key="i">
                        <tr v-for="seg in s.segments" :key="seg.departure_at + seg.flight_number" class="border-t">
                            <td class="py-1.5">{{ seg.airline_name }} {{ seg.flight_number }}</td>
                            <td>{{ seg.origin }} · {{ dayLabel(seg.departure_at) }} {{ hhmm(seg.departure_at) }}</td>
                            <td>{{ seg.destination }} · {{ dayLabel(seg.arrival_at) }} {{ hhmm(seg.arrival_at) }}</td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <p v-if="d.fare_rules?.baggage" class="mt-3">Bagages : {{ d.fare_rules.baggage }}<span v-if="d.fare_rules.cabin_baggage"> · cabine {{ d.fare_rules.cabin_baggage }}</span></p>
            <p v-if="type === 'confirmation'" class="mt-4 rounded bg-amber-50 p-3 text-xs">Reservation confirmee par la compagnie. Ce document n'est pas un billet : l'emission est en cours ou a venir.</p>
            <p v-else class="mt-4 rounded bg-gray-50 p-3 text-xs">Presentez ce document et votre passeport a l'enregistrement. Horaires en heure locale des aeroports. Verifiez les formalites (visa, vaccins) de votre destination.</p>
        </template>

        <p class="mt-8 border-t pt-3 text-center text-xs text-navy-500">MOKILI TOUR - contact@mokilitour.com</p>
    </div>
</template>
