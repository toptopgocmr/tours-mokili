// MODE PRESENTATION : photos "apercu" des societes partenaires (hotels...),
// chargees seulement pour l'admin / les agents connectes. Le public continue
// de voir l'illustration tant que la photo n'est pas publiee.
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import axios from 'axios';

const previews = ref({});
let loaded = false;

export function usePreviewPhotos() {
    const page = usePage();
    const isStaff = computed(() => ['admin', 'agent'].includes(page.props.auth?.user?.role));

    if (isStaff.value && !loaded) {
        loaded = true;
        axios.get('/admin/partenaires/apercus').then(({ data }) => (previews.value = data ?? {})).catch(() => (loaded = false));
    }

    // Photo publique si elle existe, sinon l'apercu (staff uniquement).
    const photoFor = (b) => b?.image_url || (isStaff.value ? previews.value[b?.id] ?? null : null);
    const isPreview = (b) => !b?.image_url && isStaff.value && !!previews.value[b?.id];

    return { photoFor, isPreview };
}
