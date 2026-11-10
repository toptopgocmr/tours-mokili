// Photos des villes (Wikimedia Commons, licences libres CC BY-SA) pour les
// cartes "Trajets populaires". Une ville absente garde l'illustration.
import { slugify } from '@/utils/slugify';

const C = 'https://upload.wikimedia.org/wikipedia/commons/thumb';
export const CITY_PHOTOS = {
    brazzaville: `${C}/d/d4/Brazzaville_bird_eye_view.jpg/960px-Brazzaville_bird_eye_view.jpg`,
    'pointe-noire': `${C}/a/ae/Pointe-Noire_downtown.jpg/960px-Pointe-Noire_downtown.jpg`,
    dolisie: `${C}/d/d9/Dolisie.jpg/960px-Dolisie.jpg`,
    kinshasa: `${C}/1/18/La_Gombe%2C_Kinshasa%2C_RDC_%28cropped%29.jpg/960px-La_Gombe%2C_Kinshasa%2C_RDC_%28cropped%29.jpg`,
    ouesso: `${C}/e/e2/Ouesso.jpg/960px-Ouesso.jpg`,
    mossaka: `${C}/4/49/Sunrise_near_Mossaka_%28Congo%29.JPG/960px-Sunrise_near_Mossaka_%28Congo%29.JPG`,
};
// Photo par defaut selon le moyen de transport.
export const MODE_PHOTOS = {
    train: `${C}/e/e0/Train_Pointe_Noire.JPG/960px-Train_Pointe_Noire.JPG`,
};
export const cityPhoto = (city, mode = '') => CITY_PHOTOS[slugify(city ?? '')] ?? MODE_PHOTOS[mode] ?? null;
export const PHOTO_CREDIT = 'Photos : Wikimedia Commons (CC BY-SA)';
