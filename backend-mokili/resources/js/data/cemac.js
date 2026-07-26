// CEMAC (Communaute Economique et Monetaire de l'Afrique Centrale) countries
// and their main mobile money operators, used on the checkout page so the
// customer can pick their country and see the operators available there.
//
// Peex's Collect API (clients/verify-wallet, collection/request_payment)
// officially documents mobile money collection support for Cameroon today;
// the other CEMAC countries are listed here for a complete country picker
// and operator display, and Peex auto-detects the real operator once the
// phone number is verified - this list is informational, not a hard
// requirement sent to the API.
//
// `logo` is left null until real operator/brand logo files are provided by
// MOKILI TOUR - until then <OperatorBadge> falls back to a colored text
// badge using `color`/`bg`.
export const cemacCountries = [
    {
        code: 'CM',
        name: 'Cameroun',
        operators: [
            { key: 'mtn', name: 'MTN Mobile Money', color: '#FFCC00', text: '#111111', logo: null },
            { key: 'orange', name: 'Orange Money', color: '#FF6600', text: '#FFFFFF', logo: null },
        ],
    },
    {
        code: 'GA',
        name: 'Gabon',
        operators: [
            { key: 'airtel', name: 'Airtel Money', color: '#E4002B', text: '#FFFFFF', logo: null },
            { key: 'moov', name: 'Moov Money', color: '#0072CE', text: '#FFFFFF', logo: null },
        ],
    },
    {
        code: 'CG',
        name: 'Congo-Brazzaville',
        operators: [
            { key: 'mtn', name: 'MTN Mobile Money', color: '#FFCC00', text: '#111111', logo: null },
            { key: 'airtel', name: 'Airtel Money', color: '#E4002B', text: '#FFFFFF', logo: null },
        ],
    },
    {
        code: 'TD',
        name: 'Tchad',
        operators: [
            { key: 'airtel', name: 'Airtel Money', color: '#E4002B', text: '#FFFFFF', logo: null },
            { key: 'moov', name: 'Moov Money', color: '#0072CE', text: '#FFFFFF', logo: null },
        ],
    },
    {
        code: 'CF',
        name: 'Centrafrique',
        operators: [
            { key: 'orange', name: 'Orange Money', color: '#FF6600', text: '#FFFFFF', logo: null },
            { key: 'moov', name: 'Moov Money', color: '#0072CE', text: '#FFFFFF', logo: null },
        ],
    },
    {
        code: 'GQ',
        name: 'Guinee Equatoriale',
        operators: [
            { key: 'orange', name: 'Orange Money', color: '#FF6600', text: '#FFFFFF', logo: null },
        ],
    },
];

export const bankCards = [
    { key: 'visa', name: 'Visa', color: '#1A1F71', text: '#FFFFFF', logo: null },
    { key: 'mastercard', name: 'Mastercard', color: '#EB001B', text: '#FFFFFF', logo: null },
];

export const operatorsForCountry = (code) => cemacCountries.find((c) => c.code === code)?.operators ?? [];
