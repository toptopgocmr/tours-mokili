<script setup>
// Visuel d'une fiche du Guide Congo : la photo si elle existe (upload ou
// URL), sinon une illustration aux couleurs de la categorie.
import { computed, ref } from 'vue';
import { airlineLogoUrl, airlinePhoto } from '@/utils/airlines';
import {
    BuildingOffice2Icon,
    PaperAirplaneIcon,
    TruckIcon,
    MapIcon,
    LifebuoyIcon,
    ShoppingCartIcon,
    CakeIcon,
    MusicalNoteIcon,
    ComputerDesktopIcon,
    BuildingStorefrontIcon,
    WrenchScrewdriverIcon,
    SparklesIcon,
    CameraIcon,
    FilmIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    src: { type: String, default: null },
    category: { type: String, required: true },
    alt: { type: String, default: '' },
    iconClass: { type: String, default: 'h-12 w-12' },
});

const STYLES = {
    hotel: { icon: BuildingOffice2Icon, from: '#0B2A5B', to: '#1E4FA0' },
    compagnie_aerienne: { icon: PaperAirplaneIcon, from: '#0369A1', to: '#38BDF8' },
    transport_routier: { icon: TruckIcon, from: '#B45309', to: '#F59E0B' },
    taxi_vtc: { icon: MapIcon, from: '#15803D', to: '#4ADE80' },
    canot_rapide: { icon: LifebuoyIcon, from: '#0E7490', to: '#22D3EE' },
    transport_ferroviaire: { icon: MapIcon, from: '#334155', to: '#94A3B8' },
    supermarche: { icon: ShoppingCartIcon, from: '#BE123C', to: '#FB7185' },
    garage: { icon: WrenchScrewdriverIcon, from: '#C2410C', to: '#FDBA74' },
    night_club: { icon: SparklesIcon, from: '#4C1D95', to: '#EC4899' },
    cinema: { icon: FilmIcon, from: '#7F1D1D', to: '#EF4444' },
    salle_spectacle: { icon: MusicalNoteIcon, from: '#1E3A8A', to: '#A855F7' },
    site_touristique: { icon: CameraIcon, from: '#065F46', to: '#34D399' },
    restaurant: { icon: CakeIcon, from: '#9A3412', to: '#FB923C' },
    bar_lounge: { icon: MusicalNoteIcon, from: '#6D28D9', to: '#C084FC' },
    ecommerce: { icon: ComputerDesktopIcon, from: '#1D4ED8', to: '#818CF8' },
};

const style = STYLES[props.category] ?? { icon: BuildingStorefrontIcon, from: '#0B2A5B', to: '#D4A017' };
const broken = ref(false);

// Compagnies aeriennes sans photo : vraie photo de leur avion + logo.
const isAirline = computed(() => props.category === 'compagnie_aerienne');
const plane = computed(() => (isAirline.value ? airlinePhoto(props.alt) : null));
const logo = computed(() => (isAirline.value ? airlineLogoUrl(props.alt) : null));
const planeBroken = ref(false);
const logoBroken = ref(false);
</script>

<template>
    <div class="relative h-full w-full overflow-hidden">
        <img v-if="src && !broken" :src="src" :alt="alt" class="h-full w-full object-cover" loading="lazy" referrerpolicy="no-referrer" @error="broken = true" />
        <template v-else-if="plane && !planeBroken">
            <img :src="plane.url" :alt="`Avion ${alt}`" class="h-full w-full object-cover" loading="lazy" @error="planeBroken = true" />
            <span v-if="logo && !logoBroken" class="absolute bottom-2 left-2 flex h-8 max-w-[60%] items-center rounded-md bg-white/95 px-2 shadow">
                <img :src="logo" :alt="`Logo ${alt}`" class="max-h-6 max-w-full object-contain" loading="lazy" @error="logoBroken = true" />
            </span>
            <span class="absolute bottom-0 right-0 max-w-[70%] truncate bg-black/40 px-1 text-[8px] leading-3 text-white/90" :title="plane.credit">{{ plane.credit }}</span>
        </template>
        <div v-else class="flex h-full w-full items-center justify-center" :style="{ background: `linear-gradient(135deg, ${style.from}, ${style.to})` }">
            <component :is="style.icon" :class="[iconClass, 'text-white/85']" />
        </div>
    </div>
</template>
