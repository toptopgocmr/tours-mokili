// Photos d'avions et logos des compagnies, servis par Wikimedia Commons
// (images libres : licences et auteurs ci-dessous, credits affiches sous
// les photos). FilePath + ?width= : Commons redimensionne (et convertit les
// SVG en PNG) a la volee.

const commons = (file, width = 800) =>
    `https://commons.wikimedia.org/wiki/Special:FilePath/${encodeURIComponent(file)}?width=${width}`;

// code IATA (ou cle interne) -> noms / logo / photo
export const AIRLINES = {
    AF: { names: ['air france'], logo: 'Air_France_Logo_(2016).png', photo: ['Air_France_Airbus_A350-900_F-HUVJ_at_Paris_May_2025.jpg', '4300streetcar', 'CC BY 4.0'] },
    ET: { names: ['ethiopian'], logo: 'Ethiopian_Airlines_Logo.svg', photo: ['Ethiopian_Airlines_Boeing_787_at_CDG.jpg', 'DiscoA340', 'CC BY-SA 4.0'] },
    KP: { names: ['asky'], logo: null, photo: ['Avion_asky.jpg', 'Ciaro pictures', 'CC BY-SA 4.0'] },
    AT: { names: ['royal air maroc'], logo: 'Logo_Royal_Air_Maroc.svg', photo: ['Royal_Air_Maroc_Boeing_787-9_Dreamliner_CN-RGZ_departing_JFK_Airport.jpg', 'Adam Moreira', 'CC BY-SA 4.0'] },
    TK: { names: ['turkish'], logo: 'Turkish_Airlines_logo_2019_compact.svg', photo: ['TC-JJA_Boeing_777_Turkish_Airlines_(7570800732).jpg', 'Aeroprints.com', 'CC BY-SA 3.0'] },
    WB: { names: ['rwandair'], logo: 'RwandAir_Logotype.png', photo: ['Airbus_A330-300_9XR-WP_of_RwandAir_Heathrow_2024H.jpg', 'Mike Peel', 'CC BY-SA 4.0'] },
    KQ: { names: ['kenya airways'], logo: 'Kenya_Airways_Logo.svg', photo: ['Kenya_Airways_Boeing_787-8_5Y-KZF_(23621400162).jpg', 'BriYYZ', 'CC BY-SA 2.0'] },
    EK: { names: ['emirates'], logo: 'Emirates_logo.svg', photo: ['Emirates_Airbus_A380-861_A6-EER_MUC_2015_01.jpg', 'Julian Herzog', 'CC BY 4.0'] },
    SN: { names: ['brussels airlines'], logo: 'Brussels_Airlines.svg', photo: ['Brussels_Airlines_Airbus_A330-300_OO-SFX_MD1.jpg', 'Acroterion', 'CC BY-SA 4.0'] },
    DT: { names: ['taag', 'angola'], logo: 'TAAG_2023_wordmark.svg', photo: ['TAAG_Angola_Airlines_Boeing_777-300ER_at_GRU_Airport_March_2024.jpg', 'Nick-D', 'CC BY-SA 4.0'] },
    QR: { names: ['qatar'], logo: 'Qatar_Airways_logo.svg', photo: ['Qatar_Airways_Boeing_787-8_Dreamliner_A7-BCF_MUC_2015_02.jpg', 'Julian Herzog', 'CC BY 4.0'] },
    SA: { names: ['south african'], logo: 'South_African_Airways_2019_logo.svg', photo: ['South_African_Airways_Airbus_A350-900.jpg', 'Abeinspace', 'CC BY-SA 4.0'] },
    MS: { names: ['egyptair'], logo: 'Egyptair-Logo-2010.svg', photo: ['EgyptAir,_SU-GER,_Boeing_787-9_Dreamliner_(49565303557).jpg', 'Anna Zvereva', 'CC BY-SA 2.0'] },
    FZ: { names: ['flydubai', 'fly dubai'], logo: 'Fly_Dubai_logo_2010_05.svg', photo: ['Flydubai_Boeing_737-800_(A6-FDN).jpg', 'Mohammadreza Farhadi Aref', 'CC BY-SA 4.0'] },
    '8Q': { names: ['air congo'], logo: null, photo: ['Air_Congo_Boeing_787-800_ET-ASI_FIH-BRU_ET98_2026-07-01_02.jpg', 'Giovaunni', 'CC0'] },
    TC: { names: ['trans air congo'], logo: 'Trans_Air_Congo_logo.jpg', photo: ['Trans_Air_Congo,_Boeing_737-33A,_TN-AIZ_(18922089785).jpg', 'Bob Adams', 'CC BY-SA 2.0'] },
    LC: { names: ['ecair', 'equatorial congo'], logo: 'Equatorial_Congo_Airlines_-_Logo.png', photo: ['ECAir_Boeing_737-306_at_Brazzaville_Airport_by_Vitaly_Druchenok.jpg', 'Vitaly Druchenok', 'CC BY-SA 4.0'] },
    J7: { names: ['afrijet'], logo: null, photo: ['Dassault_Falcon_50_Afrijet_Business_Services_TR-LGY.jpg', 'Curimedia', 'CC BY 2.0'] },
    CAC: { names: ['canadian airways'], logo: 'Canadian-logo-2017-big.png', photo: ['Canadian_Airways_Congo_McDonnell_Douglas_MD-82_at_Maya-Maya_Airport.jpeg', 'Vitaly Druchenok', 'CC BY-SA 4.0'] },
};

// Photo par defaut (compagnie sans photo libre connue, ex. Brazza Airlines).
export const DEFAULT_PLANE = ['Boeing_787_first_flight.jpg', 'Dave Sizer', 'CC BY 2.0'];

export const findAirline = (codeOrName) => {
    if (!codeOrName) return null;
    const key = String(codeOrName).trim();
    if (AIRLINES[key.toUpperCase()]) return { code: key.toUpperCase(), ...AIRLINES[key.toUpperCase()] };
    const lower = key.toLowerCase();
    const hit = Object.entries(AIRLINES).find(([, a]) => a.names.some((n) => lower.includes(n)));
    return hit ? { code: hit[0], ...hit[1] } : null;
};

export const airlineLogoUrl = (codeOrName, width = 160) => {
    const a = findAirline(codeOrName);
    return a?.logo ? commons(a.logo, width) : null;
};

/** { url, credit } : photo libre de l'avion de la compagnie (ou photo par defaut). */
export const airlinePhoto = (codeOrName, width = 800) => {
    const [file, author, licence] = findAirline(codeOrName)?.photo ?? DEFAULT_PLANE;
    return { url: commons(file, width), credit: `Photo : ${author} / Wikimedia Commons, ${licence}` };
};
