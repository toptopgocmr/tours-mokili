<script setup>
// Badge de promotion partenaire : "-20 % · Promo de Noel" + date de fin.
defineProps({
    promo: { type: Object, default: null },
    showEnd: { type: Boolean, default: false },
});
const end = (iso) => new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
</script>

<template>
    <span v-if="promo" class="inline-flex flex-wrap items-center gap-1 rounded-md bg-rose-600 px-1.5 py-0.5 text-[11px] font-bold text-white">
        -{{ promo.percent }} %<span v-if="promo.label" class="font-semibold">· {{ promo.label }}</span>
        <span v-if="showEnd && promo.ends_at" class="font-normal opacity-90">· jusqu'au {{ end(promo.ends_at) }}</span>
    </span>
</template>
