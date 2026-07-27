<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    UsersIcon,
    BuildingStorefrontIcon,
    UserGroupIcon,
    PaperAirplaneIcon,
    CubeIcon,
    CalendarDaysIcon,
    CheckCircleIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
} from '@heroicons/vue/24/outline';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    stats: { type: Object, required: true },
    recentBookings: { type: Array, default: () => [] },
    kpis: { type: Object, default: () => ({}) },
});

const money = (v) => `${Number(v ?? 0).toLocaleString('fr-FR')} XAF`;

const cards = [
    { key: 'users', label: 'Clients', icon: UsersIcon, color: 'text-[#0972D3]', bg: 'bg-blue-50' },
    { key: 'partners', label: 'Partenaires', icon: BuildingStorefrontIcon, color: 'text-green-600', bg: 'bg-green-50' },
    { key: 'agents', label: 'Agents', icon: UserGroupIcon, color: 'text-purple-600', bg: 'bg-purple-50' },
    { key: 'travelOffers', label: 'Offres Voyage', icon: PaperAirplaneIcon, color: 'text-gold-600', bg: 'bg-amber-50' },
    { key: 'freightOffers', label: 'Offres Fret', icon: CubeIcon, color: 'text-orange-600', bg: 'bg-orange-50' },
    { key: 'bookings', label: 'Reservations', icon: CalendarDaysIcon, color: 'text-slate-600', bg: 'bg-slate-100' },
    { key: 'bookingsConfirmed', label: 'Confirmees', icon: CheckCircleIcon, color: 'text-green-700', bg: 'bg-green-50' },
];

const badgeClass = (status) => ({
    confirmed: 'console-badge console-badge-success',
    awaiting_payment: 'console-badge console-badge-pending',
    pending: 'console-badge console-badge-pending',
    cancelled: 'console-badge console-badge-error',
}[status] ?? 'console-badge console-badge-neutral');
</script>

<template>
    <Head title="Tableau de bord" />

    <div class="mb-5">
        <h1 class="text-xl font-bold text-slate-900">Tableau de bord</h1>
        <p class="text-sm text-slate-500">Vue d'ensemble de l'activite MOKILI TOUR.</p>
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-7">
        <div v-for="c in cards" :key="c.key" class="console-stat">
            <div :class="['inline-flex h-8 w-8 items-center justify-center rounded-lg', c.bg]">
                <component :is="c.icon" :class="['h-4 w-4', c.color]" />
            </div>
            <p class="mt-2 text-xs font-medium uppercase tracking-wide text-slate-500">{{ c.label }}</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ stats[c.key] }}</p>
        </div>
    </div>

    <!-- Dynamic decision KPIs: revenue trend, bookings by module, top partners -->
    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="console-panel">
            <h2 class="font-semibold text-slate-900">Revenu ce mois-ci</h2>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ money(kpis.revenueThisMonth) }}</p>
            <div class="mt-2 flex items-center gap-1.5 text-sm">
                <component
                    :is="kpis.revenueChangePercent >= 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon"
                    :class="['h-4 w-4', kpis.revenueChangePercent >= 0 ? 'text-green-600' : 'text-red-600']"
                />
                <span :class="kpis.revenueChangePercent >= 0 ? 'text-green-600' : 'text-red-600'">
                    {{ kpis.revenueChangePercent >= 0 ? '+' : '' }}{{ kpis.revenueChangePercent }}%
                </span>
                <span class="text-slate-400">vs mois dernier ({{ money(kpis.revenueLastMonth) }})</span>
            </div>
            <div class="mt-4 border-t border-slate-100 pt-3">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Taux de remboursement</p>
                <p class="mt-1 text-xl font-bold text-slate-900">{{ kpis.refundRate }}%</p>
            </div>
        </div>

        <div class="console-panel">
            <h2 class="font-semibold text-slate-900">Reservations par module</h2>
            <ul class="mt-4 space-y-3">
                <li v-for="m in kpis.bookingsByModule" :key="m.label">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-600">{{ m.label }}</span>
                        <span class="font-semibold text-slate-900">{{ m.count }}</span>
                    </div>
                    <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-[#0972D3]" :style="{ width: `${m.percent}%` }" />
                    </div>
                </li>
            </ul>
        </div>

        <div class="console-panel">
            <h2 class="font-semibold text-slate-900">Top partenaires (revenu confirme)</h2>
            <ol class="mt-4 space-y-2 text-sm">
                <li v-for="(p, i) in kpis.topPartners" :key="p.name" class="flex items-center justify-between">
                    <span class="text-slate-600">{{ i + 1 }}. {{ p.name }}</span>
                    <span class="font-semibold text-slate-900">{{ money(p.revenue) }}</span>
                </li>
                <li v-if="!kpis.topPartners?.length" class="text-slate-400">Pas encore de revenu confirme.</li>
            </ol>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="console-panel lg:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold text-slate-900">Dernieres reservations</h2>
                <Link href="/admin/reservations" class="text-sm font-semibold text-[#0972D3] hover:underline">Voir tout &rarr;</Link>
            </div>
            <div class="console-table-wrap">
                <table class="console-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Client</th>
                            <th>Montant</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="b in recentBookings" :key="b.id">
                            <td class="font-medium text-slate-900">{{ b.reference }}</td>
                            <td>{{ b.user?.name }}</td>
                            <td>{{ Number(b.total_amount).toLocaleString('fr-FR') }} {{ b.currency }}</td>
                            <td><span :class="badgeClass(b.status)">{{ b.status }}</span></td>
                        </tr>
                        <tr v-if="!recentBookings.length"><td colspan="4" class="py-4 text-center text-slate-400">Aucune reservation.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="console-panel">
            <h2 class="font-semibold text-slate-900">Repartition</h2>
            <ul class="mt-4 space-y-3 text-sm">
                <li class="flex items-center justify-between">
                    <span class="text-slate-500">Taux de confirmation</span>
                    <span class="font-semibold text-slate-900">
                        {{ stats.bookings ? Math.round((stats.bookingsConfirmed / stats.bookings) * 100) : 0 }}%
                    </span>
                </li>
                <li class="flex items-center justify-between">
                    <span class="text-slate-500">Partenaires actifs</span>
                    <span class="font-semibold text-slate-900">{{ stats.partners }}</span>
                </li>
                <li class="flex items-center justify-between">
                    <span class="text-slate-500">Equipe (agents)</span>
                    <span class="font-semibold text-slate-900">{{ stats.agents }}</span>
                </li>
            </ul>
            <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                <div
                    class="h-full rounded-full bg-[#0972D3]"
                    :style="{ width: `${stats.bookings ? Math.round((stats.bookingsConfirmed / stats.bookings) * 100) : 0}%` }"
                />
            </div>
        </div>
    </div>
</template>
