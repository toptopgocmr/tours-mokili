// Trajets bus / train / taxi (rubrique Circuits, facon BlaBlaCar).
export const RIDE_ICONS = { bus: '🚌', train: '🚆', taxi: '🚕', voiture: '🚙', bateau: '⛴️', canot: '🚤', avion: '✈️', combine: '🧭' };

// Moyens proposes en premier dans la rubrique Circuits.
export const RIDE_MODES = [
    { value: '', label: 'Tous', icon: '🧭' },
    { value: 'bus', label: 'Bus', icon: '🚌' },
    { value: 'train', label: 'Train', icon: '🚆' },
    { value: 'taxi', label: 'Taxi', icon: '🚕' },
];

// Les horaires sont saisis en heure locale du Congo et stockes tels quels
// (serveur en UTC) : on les affiche donc en UTC pour retrouver l'heure saisie.
export const rideTime = (iso) => (iso ? new Date(iso).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', timeZone: 'UTC' }) : '--:--');

export const dayHeading = (iso) => {
    if (!iso) return 'Date a confirmer';
    const d = new Date(iso);
    const s = d.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', timeZone: 'UTC' });
    return s.charAt(0).toUpperCase() + s.slice(1);
};
