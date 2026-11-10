// Favoris et comparateur des vehicules a vendre, gardes dans le navigateur
// (pas besoin de compte). Comparateur : 4 vehicules maximum.
import { ref, watch } from 'vue';

const read = (k) => {
    try {
        return JSON.parse(localStorage.getItem(k) || '[]');
    } catch {
        return [];
    }
};
const favorites = ref(typeof window !== 'undefined' ? read('mk_car_favorites') : []);
const compare = ref(typeof window !== 'undefined' ? read('mk_car_compare') : []);
const save = (k, v) => {
    try {
        localStorage.setItem(k, JSON.stringify(v));
    } catch {
        /* navigation privee */
    }
};
watch(favorites, (v) => save('mk_car_favorites', v), { deep: true });
watch(compare, (v) => save('mk_car_compare', v), { deep: true });

export function useCarShortlist() {
    const toggle = (list, id, max = Infinity) => {
        const i = list.value.indexOf(id);
        if (i >= 0) list.value.splice(i, 1);
        else if (list.value.length < max) list.value.push(id);
    };
    return {
        favorites,
        compare,
        isFav: (id) => favorites.value.includes(id),
        inCompare: (id) => compare.value.includes(id),
        toggleFav: (id) => toggle(favorites, id),
        toggleCompare: (id) => toggle(compare, id, 4),
        clearCompare: () => (compare.value = []),
    };
}

/** Mensualite indicative : capital emprunte, duree (mois), taux annuel (%). */
export function monthly(price, { down_payment_percent = 20, months = 36, annual_rate = 12 } = {}) {
    const capital = Number(price) * (1 - down_payment_percent / 100);
    const r = annual_rate / 100 / 12;
    if (!capital || !months) return 0;
    return Math.round(r ? (capital * r) / (1 - Math.pow(1 + r, -months)) : capital / months);
}
