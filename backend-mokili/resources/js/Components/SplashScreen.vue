<script setup>
// Ecran d'ouverture MOKILI TOUR (back-office + espace admin) : fond bleu
// nuit, halo dore qui tourne autour du logo, logo qui "eclot", slogan
// lettre par lettre, puis fondu. Affiche une seule fois par session de
// navigateur (sessionStorage) pour ne pas gener la navigation.
import { onMounted, ref } from 'vue';

const props = defineProps({
    storageKey: { type: String, default: 'mokili_splash_seen' },
    subtitle: { type: String, default: '' },
    duration: { type: Number, default: 2200 },
    always: { type: Boolean, default: false },
});

const visible = ref(false);
const leaving = ref(false);
const tagline = 'LE MONDE À PORTÉE DE MAIN';

const alreadySeen = () => {
    try {
        return sessionStorage.getItem(props.storageKey) === '1';
    } catch {
        return false;
    }
};

const markSeen = () => {
    try {
        sessionStorage.setItem(props.storageKey, '1');
    } catch {
        /* navigation privee : on ignore */
    }
};

const close = () => {
    leaving.value = true;
    setTimeout(() => (visible.value = false), 550);
};

onMounted(() => {
    const reduced = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    if ((!props.always && alreadySeen()) || reduced) return;
    visible.value = true;
    markSeen();
    setTimeout(close, props.duration);
});
</script>

<template>
    <div
        v-if="visible"
        class="mk-splash fixed inset-0 z-[100] flex cursor-pointer flex-col items-center justify-center"
        :class="{ 'mk-splash--leave': leaving }"
        role="presentation"
        @click="close"
    >
        <div class="mk-glow" />
        <div class="relative flex h-56 w-56 items-center justify-center">
            <svg class="mk-ring absolute inset-0" viewBox="0 0 200 200">
                <defs>
                    <linearGradient id="mkGold" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="#F6D77A" />
                        <stop offset="100%" stop-color="#D4A017" stop-opacity="0" />
                    </linearGradient>
                </defs>
                <circle cx="100" cy="100" r="92" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="2" />
                <circle cx="100" cy="100" r="92" fill="none" stroke="url(#mkGold)" stroke-width="3" stroke-linecap="round" stroke-dasharray="200 380" />
            </svg>
            <svg class="mk-plane absolute inset-0" viewBox="0 0 200 200">
                <path fill="#D4A017" d="M100 4l5 9h-3.5v6h-3v-6H95z" transform="rotate(0 100 100)" />
            </svg>
            <img src="/images/logo-transparent.png" alt="MOKILI TOUR" class="mk-logo relative w-40 object-contain" />
        </div>
        <p class="mk-tagline mt-6 text-xs font-semibold tracking-[0.35em] text-[#E4B740]">
            <span v-for="(ch, i) in tagline" :key="i" :style="{ animationDelay: `${0.7 + i * 0.03}s` }">{{ ch === ' ' ? ' ' : ch }}</span>
        </p>
        <p v-if="subtitle" class="mk-sub mt-3 text-sm text-white/50">{{ subtitle }}</p>
        <div class="mk-bar absolute bottom-16 h-0.5 w-40 overflow-hidden rounded bg-white/10"><span /></div>
    </div>
</template>

<style scoped>
.mk-splash {
    background: radial-gradient(circle at 50% 40%, #0f3270 0%, #0b2a5b 35%, #050f21 100%);
    transition: opacity 0.55s ease, transform 0.55s ease;
}
.mk-splash--leave {
    opacity: 0;
    transform: scale(1.04);
}
.mk-glow {
    position: absolute;
    width: 420px;
    height: 420px;
    border-radius: 9999px;
    background: radial-gradient(circle, rgba(212, 160, 23, 0.22), transparent 65%);
    animation: mk-pulse 2.4s ease-in-out infinite;
}
.mk-ring {
    animation: mk-spin 1.6s linear infinite;
}
.mk-plane {
    animation: mk-spin 2.2s cubic-bezier(0.6, 0.1, 0.3, 0.9) infinite;
}
.mk-logo {
    opacity: 0;
    animation: mk-pop 0.9s 0.15s cubic-bezier(0.2, 1.4, 0.4, 1) forwards;
    filter: drop-shadow(0 8px 24px rgba(0, 0, 0, 0.45));
}
.mk-tagline span {
    display: inline-block;
    opacity: 0;
    transform: translateY(6px);
    animation: mk-letter 0.35s ease forwards;
}
.mk-sub {
    opacity: 0;
    animation: mk-fade 0.6s 1.3s ease forwards;
}
.mk-bar span {
    display: block;
    height: 100%;
    width: 40%;
    background: linear-gradient(90deg, transparent, #d4a017, transparent);
    animation: mk-load 1.1s ease-in-out infinite;
}
@keyframes mk-spin {
    to {
        transform: rotate(360deg);
    }
}
@keyframes mk-pop {
    from {
        opacity: 0;
        transform: scale(0.6);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
@keyframes mk-letter {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
@keyframes mk-fade {
    to {
        opacity: 1;
    }
}
@keyframes mk-pulse {
    0%,
    100% {
        transform: scale(0.9);
        opacity: 0.7;
    }
    50% {
        transform: scale(1.1);
        opacity: 1;
    }
}
@keyframes mk-load {
    from {
        transform: translateX(-100%);
    }
    to {
        transform: translateX(250%);
    }
}
</style>
