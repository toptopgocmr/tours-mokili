<script setup>
// Phone number field with an embedded country flag + dial code
// selector, so a phone value always carries an explicit international
// prefix (e.g. "+243..."), matching the model's `phone` column.
import { ref, computed, watch } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue']);

// Central/West Africa first (this app's core market), then a few
// common international ones.
const countries = [
    { iso: 'CD', dial: '243', flag: '🇨🇩', name: 'RD Congo' },
    { iso: 'CG', dial: '242', flag: '🇨🇬', name: 'Congo-Brazzaville' },
    { iso: 'CM', dial: '237', flag: '🇨🇲', name: 'Cameroun' },
    { iso: 'GA', dial: '241', flag: '🇬🇦', name: 'Gabon' },
    { iso: 'CF', dial: '236', flag: '🇨🇫', name: 'Centrafrique' },
    { iso: 'TD', dial: '235', flag: '🇹🇩', name: 'Tchad' },
    { iso: 'CI', dial: '225', flag: '🇨🇮', name: "Cote d'Ivoire" },
    { iso: 'SN', dial: '221', flag: '🇸🇳', name: 'Senegal' },
    { iso: 'BJ', dial: '229', flag: '🇧🇯', name: 'Benin' },
    { iso: 'TG', dial: '228', flag: '🇹🇬', name: 'Togo' },
    { iso: 'FR', dial: '33', flag: '🇫🇷', name: 'France' },
    { iso: 'BE', dial: '32', flag: '🇧🇪', name: 'Belgique' },
    { iso: 'US', dial: '1', flag: '🇺🇸', name: 'Etats-Unis' },
    { iso: 'AE', dial: '971', flag: '🇦🇪', name: 'Emirats Arabes Unis' },
    { iso: 'CN', dial: '86', flag: '🇨🇳', name: 'Chine' },
];

const selectedIso = ref('CD');
const selectedCountry = computed(() => countries.find((c) => c.iso === selectedIso.value) ?? countries[0]);
const localNumber = ref('');

// Parse an incoming "+<dial><number>" value once on mount (e.g. when
// editing a profile that already has a phone saved).
if (props.modelValue) {
    const match = [...countries].sort((a, b) => b.dial.length - a.dial.length).find((c) => props.modelValue.startsWith('+' + c.dial));
    if (match) {
        selectedIso.value = match.iso;
        localNumber.value = props.modelValue.slice(match.dial.length + 1);
    } else {
        localNumber.value = props.modelValue;
    }
}

const emitValue = () => {
    const digits = localNumber.value.replace(/\D/g, '');
    emit('update:modelValue', digits ? `+${selectedCountry.value.dial}${digits}` : '');
};

watch(selectedIso, emitValue);
watch(localNumber, emitValue);
</script>

<template>
    <div class="mt-1 flex overflow-hidden rounded-lg border border-gray-300 focus-within:ring-2 focus-within:ring-gold-600">
        <select v-model="selectedIso" class="border-0 border-r border-gray-300 bg-gray-50 py-2 pl-2 pr-1 text-sm focus:ring-0">
            <option v-for="c in countries" :key="c.iso" :value="c.iso">{{ c.flag }} +{{ c.dial }}</option>
        </select>
        <input
            v-model="localNumber"
            type="tel"
            inputmode="numeric"
            placeholder="Numero de telephone"
            class="flex-1 border-0 py-2 text-sm focus:ring-0"
        />
    </div>
</template>
