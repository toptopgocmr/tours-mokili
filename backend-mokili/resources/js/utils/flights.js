// Helpers d'affichage billetterie aerienne (heures locales des aeroports :
// les horaires fournisseurs sont deja exprimes en heure locale).

export const money = (amount, currency = 'XAF') =>
    `${Number(amount ?? 0).toLocaleString('fr-FR', { maximumFractionDigits: 0 })} ${currency}`;

export const hhmm = (iso) => (iso ? String(iso).slice(11, 16) : '');

export const dayLabel = (iso) => {
    if (!iso) return '';
    const d = new Date(String(iso).slice(0, 10) + 'T12:00:00');
    return d.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' });
};

export const longDate = (iso) => {
    if (!iso) return '';
    const d = new Date(String(iso).slice(0, 10) + 'T12:00:00');
    return d.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
};

export const duration = (minutes) => {
    if (!minutes) return '';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return `${h} h ${String(m).padStart(2, '0')}`;
};

export const stopsLabel = (stops, via) =>
    !stops ? 'Direct' : `${stops} escale${stops > 1 ? 's' : ''}${via ? ' (' + via + ')' : ''}`;

export const dayOffset = (dep, arr) => {
    if (!dep || !arr) return 0;
    const a = new Date(String(dep).slice(0, 10));
    const b = new Date(String(arr).slice(0, 10));
    return Math.round((b - a) / 86400000);
};

// Libelle d'un trajet : "Aller" / "Retour" pour un aller-retour,
// "Vol 1", "Vol 2"... pour un voyage multi-destinations.
export const sliceLabel = (slices, i) => {
    const list = slices ?? [];
    if (list.length === 1) return 'Aller';
    const isReturn =
        list.length === 2 && list[1]?.origin === list[0]?.destination && list[1]?.destination === list[0]?.origin;
    if (isReturn) return i === 0 ? 'Aller' : 'Retour';
    return `Vol ${i + 1}`;
};

// Parcours complet : "Brazzaville → Paris → Dubai".
export const routeLabel = (slices, city = (c) => c) => {
    const list = slices ?? [];
    if (!list.length) return '';
    return [...list.map((s) => city(s.origin)), city(list[list.length - 1].destination)].join(' → ');
};

// Date courte : "14 nov."
export const shortDate = (iso) => {
    if (!iso) return '';
    const d = new Date(String(iso).slice(0, 10) + 'T12:00:00');
    return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
};

// Minutes entre deux horaires locaux du meme aeroport (temps de connexion).
export const minutesBetween = (from, to) => {
    if (!from || !to) return 0;
    const a = new Date(String(from).slice(0, 16) + ':00Z');
    const b = new Date(String(to).slice(0, 16) + ':00Z');
    return Math.max(0, Math.round((b - a) / 60000));
};

// Duree "1 h 05 min" / "45 min".
export const durationLong = (minutes) => {
    if (!minutes) return '';
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return h ? `${h} h ${String(m).padStart(2, '0')} min` : `${m} min`;
};

// Au-dela de 4 h d'attente, la connexion est signalee (comme Skyscanner).
export const LONG_CONNECTION_MINUTES = 240;

export const offerDuration = (offer) => (offer?.slices ?? []).reduce((t, s) => t + (Number(s.duration_minutes) || 0), 0);

// Compagnies d'une offre : "Ethiopian Airlines" / "Ethiopian Airlines et 2 autres".
export const airlinesLabel = (offer) => {
    const names = [...new Set((offer?.slices ?? []).flatMap((s) => (s.segments ?? []).map((g) => g.airline_name)).filter(Boolean))];
    if (!names.length) return offer?.airline_name ?? '';
    if (names.length === 1) return names[0];
    if (names.length === 2) return `${names[0]} et ${names[1]}`;
    return `${names[0]} et ${names.length - 1} autres`;
};

// Aeroports d'escale d'un trajet : ["PNR", "ADD"].
export const stopCodes = (slice) => {
    if (Array.isArray(slice?.stop_airports) && slice.stop_airports.length) return slice.stop_airports;
    const segs = slice?.segments ?? [];
    if (segs.length > 1) return segs.slice(0, -1).map((g) => g.destination);
    return slice?.stops && slice?.via ? [slice.via] : [];
};

// Nombre max d'escales d'une offre (tous trajets confondus).
export const offerStops = (offer) => Math.max(0, ...(offer?.slices ?? []).map((s) => Number(s.stops) || 0));

// Score "Les meilleurs" : equilibre prix / duree / escales (meme formule que le serveur).
export const bestSort = (offers) => {
    if (!offers.length) return [];
    const minPrice = Math.max(1, Math.min(...offers.map((o) => Number(o.total_amount))));
    const minDur = Math.max(1, Math.min(...offers.map((o) => offerDuration(o))));
    const score = (o) => Number(o.total_amount) / minPrice + 0.7 * offerDuration(o) / minDur
        + 0.05 * (o.slices ?? []).reduce((t, s) => t + (Number(s.stops) || 0), 0);
    return [...offers].sort((a, b) => score(a) - score(b));
};

export const PAX_LABELS = { ADT: 'Adulte', CHD: 'Enfant', INF: 'Bebe' };

// Couleurs des statuts du dossier (machine a etats du cahier des charges).
export const STATUS_STYLES = {
    DRAFT: 'bg-gray-100 text-gray-700',
    SEARCHED: 'bg-amber-100 text-amber-800',
    PRICE_CONFIRMED: 'bg-sky-100 text-sky-800',
    PAYMENT_PENDING: 'bg-amber-100 text-amber-800',
    PAYMENT_CONFIRMED: 'bg-blue-100 text-blue-800',
    BOOKING_PENDING: 'bg-blue-100 text-blue-800',
    BOOKED: 'bg-indigo-100 text-indigo-800',
    TICKETING_PENDING: 'bg-indigo-100 text-indigo-800',
    TICKETED: 'bg-green-100 text-green-800',
    FAILED: 'bg-red-100 text-red-800',
    REFUND_PENDING: 'bg-orange-100 text-orange-800',
    REFUNDED: 'bg-gray-200 text-gray-800',
    CANCELLED: 'bg-gray-200 text-gray-600',
};

// Les trois suivis distincts : paiement / reservation / billet.
export const STATE_LABELS = {
    payment: { unpaid: 'Non paye', pending: 'En attente', paid: 'Confirme', refunded: 'Rembourse' },
    booking: { none: 'Pas encore', pending: 'En cours', confirmed: 'Confirmee', failed: 'Echec' },
    ticket: { none: 'Pas encore', pending: 'En cours', issued: 'Emis', failed: 'Echec' },
};

export const STATE_STYLES = {
    paid: 'text-green-700', confirmed: 'text-green-700', issued: 'text-green-700',
    pending: 'text-amber-700', failed: 'text-red-700', refunded: 'text-gray-600',
    none: 'text-navy-400', unpaid: 'text-navy-400',
};
