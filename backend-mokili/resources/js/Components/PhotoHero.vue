<script setup>
// En-tete de page avec la photo de la page d'accueil (public/images/hero.jpg)
// et un voile bleu marine pour garder le texte lisible. Meme fond partout.
defineProps({
    tag: { type: String, default: 'section' },
    // Position de la photo (object-position Tailwind).
    position: { type: String, default: 'object-[center_30%]' },
    // Voile : "left" (degrade de gauche a droite) ou "full" (uniforme).
    veil: { type: String, default: 'left' },
});
</script>

<template>
    <component :is="tag" class="relative overflow-hidden">
        <img src="/images/hero.jpg" alt="" aria-hidden="true" class="absolute inset-0 h-full w-full object-cover" :class="position" />
        <div
            class="absolute inset-0"
            :class="veil === 'full' ? 'bg-navy-900/70' : 'bg-gradient-to-r from-navy-900/90 via-navy-900/70 to-navy-900/30'"
        />
        <div class="relative">
            <slot />
        </div>
    </component>
</template>
