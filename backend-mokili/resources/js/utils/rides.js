// Trajets bus / train / taxi (rubrique Circuits, facon BlaBlaCar).
export const RIDE_ICONS = { bus: '🚌', train: '🚆', taxi: '🚕', voiture: '🚙', bateau: '⛴️', canot: '🚤', avion: '✈️', combine: '🧭' };

// Moyens proposes en premier dans la rubrique Circuits.
export const RIDE_MODES = [
    { value: '', label: 'Tous', icon: '🧭' },
    { value: 'bus', label: 'Bus', icon: '🚌' },
    { value: 'train', label: 'Train', icon: '🚆' },
    { value: 'canot', label: 'Canot rapide', icon: '🚤' },
    { value: 'taxi', label: 'Taxi', icon: '🚕' },
];

// Presentation de chaque moyen (titre, accroche, trajets populaires suggeres).
export const RIDE_PAGES = {
    '': {
        title: 'Bus, train, canot rapide ou taxi',
        subtitle: 'Comparez les departs, choisissez votre place et payez par Mobile Money.',
        routes: [['Brazzaville', 'Pointe-Noire'], ['Brazzaville', 'Kinshasa'], ['Brazzaville', 'Dolisie'], ['Brazzaville', 'Oyo']],
    },
    bus: {
        title: 'Voyagez en bus partout au Congo',
        subtitle: 'Les compagnies de bus interurbains, leurs horaires et leurs prix, au meme endroit.',
        routes: [['Brazzaville', 'Pointe-Noire'], ['Brazzaville', 'Dolisie'], ['Brazzaville', 'Oyo'], ['Brazzaville', 'Ouesso'], ['Pointe-Noire', 'Dolisie'], ['Brazzaville', 'Owando']],
    },
    train: {
        title: 'Le train Congo-Ocean',
        subtitle: 'Reservez votre place sur la ligne Brazzaville - Dolisie - Pointe-Noire.',
        routes: [['Brazzaville', 'Pointe-Noire'], ['Pointe-Noire', 'Brazzaville'], ['Brazzaville', 'Dolisie'], ['Dolisie', 'Pointe-Noire']],
    },
    canot: {
        title: 'Canots rapides sur le fleuve Congo',
        subtitle: 'Traversee Brazzaville - Kinshasa et liaisons fluviales : departs, prix et places en direct.',
        routes: [['Brazzaville', 'Kinshasa'], ['Kinshasa', 'Brazzaville'], ['Brazzaville', 'Mossaka'], ['Brazzaville', 'Impfondo']],
    },
    taxi: {
        title: 'Taxis interurbains',
        subtitle: 'Partagez un taxi entre les villes : depart a l\'heure, places limitees, prix fixe.',
        routes: [['Brazzaville', 'Kinkala'], ['Brazzaville', 'Pointe-Noire'], ['Brazzaville', 'Gamboma'], ['Pointe-Noire', 'Dolisie']],
    },
};

// Tranches horaires de depart (filtre facon BlaBlaCar).
export const TIME_SLOTS = [
    { key: 'early', label: 'Avant 06:00', test: (h) => h < 6 },
    { key: 'morning', label: '06:00 - 12:00', test: (h) => h >= 6 && h < 12 },
    { key: 'afternoon', label: '12:00 - 18:00', test: (h) => h >= 12 && h < 18 },
    { key: 'evening', label: 'Apres 18:00', test: (h) => h >= 18 },
];

export const rideHour = (iso) => (iso ? new Date(iso).getUTCHours() : 12);
export const rideMinutes = (o) => {
    const a = o.departure_at ? new Date(o.departure_at) : null;
    const b = o.arrival_at ? new Date(o.arrival_at) : null;
    return a && b && b > a ? Math.round((b - a) / 60000) : Infinity;
};

// Les horaires sont saisis en heure locale du Congo et stockes tels quels
// (serveur en UTC) : on les affiche donc en UTC pour retrouver l'heure saisie.
export const rideTime = (iso) => (iso ? new Date(iso).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', timeZone: 'UTC' }) : '--:--');

export const dayHeading = (iso) => {
    if (!iso) return 'Date a confirmer';
    const d = new Date(iso);
    const s = d.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', timeZone: 'UTC' });
    return s.charAt(0).toUpperCase() + s.slice(1);
};
