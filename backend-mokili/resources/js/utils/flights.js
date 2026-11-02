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
