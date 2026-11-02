<script setup>
// Boutons Appeler / WhatsApp (annonces, garages, vendeurs).
import { computed } from 'vue';

const props = defineProps({
    phone: { type: String, default: '' },
    whatsapp: { type: String, default: '' },
    message: { type: String, default: '' },
});

const digits = (n) => String(n ?? '').replace(/[^0-9+]/g, '');
const tel = computed(() => digits(props.phone));
const wa = computed(() => {
    const n = digits(props.whatsapp || props.phone).replace(/^\+/, '');
    if (!n) return '';
    const full = n.length === 9 && n.startsWith('0') ? '242' + n : n;
    return `https://wa.me/${full}` + (props.message ? `?text=${encodeURIComponent(props.message)}` : '');
});
</script>

<template>
    <div v-if="tel || wa" class="flex flex-wrap gap-2">
        <a v-if="tel" :href="`tel:${tel}`" class="btn-outline flex-1 text-center">📞 Appeler</a>
        <a v-if="wa" :href="wa" target="_blank" rel="noopener" class="btn-gold flex-1 text-center">💬 WhatsApp</a>
    </div>
    <p v-else class="text-sm text-navy-500">Coordonnees non renseignees.</p>
</template>
