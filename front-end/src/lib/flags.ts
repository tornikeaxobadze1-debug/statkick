/**
 * Country → flag resolution for the vector flag set (flag-icons, MIT).
 *
 * The API stores FIFA-style codes (GER, NED, SUI…), sometimes ISO alpha-3, and some
 * countries have no code at all — so a flag is resolved from the code first and the
 * country name second. Keys are flag-icons file names: ISO 3166-1 alpha-2 in lower
 * case, plus the UK home nations (gb-eng, gb-sct, gb-wls, gb-nir) and Kosovo (xk).
 */

/** FIFA / IOC / ISO alpha-3 code → flag key. */
const CODE_TO_KEY: Record<string, string> = {
  // UK home nations
  ENG: 'gb-eng', SCO: 'gb-sct', WAL: 'gb-wls', NIR: 'gb-nir', GBR: 'gb',
  // Europe
  ESP: 'es', ITA: 'it', GER: 'de', DEU: 'de', FRA: 'fr', POR: 'pt', PRT: 'pt', NED: 'nl', NLD: 'nl',
  BEL: 'be', CRO: 'hr', HRV: 'hr', DEN: 'dk', DNK: 'dk', SUI: 'ch', CHE: 'ch', AUT: 'at', POL: 'pl',
  SWE: 'se', NOR: 'no', FIN: 'fi', ISL: 'is', IRL: 'ie', GRE: 'gr', GRC: 'gr', UKR: 'ua', SRB: 'rs',
  SVN: 'si', SVK: 'sk', CZE: 'cz', HUN: 'hu', ROU: 'ro', BUL: 'bg', BGR: 'bg', RUS: 'ru', TUR: 'tr',
  GEO: 'ge', ARM: 'am', AZE: 'az', ALB: 'al', KOS: 'xk', KVX: 'xk', XKX: 'xk', MKD: 'mk', MNE: 'me', BIH: 'ba',
  LUX: 'lu', CYP: 'cy', MLT: 'mt', EST: 'ee', LVA: 'lv', LTU: 'lt', BLR: 'by', MDA: 'md', ISR: 'il',
  // Americas
  ARG: 'ar', BRA: 'br', URU: 'uy', URY: 'uy', COL: 'co', ECU: 'ec', PAR: 'py', PRY: 'py', CHI: 'cl',
  CHL: 'cl', PER: 'pe', VEN: 've', BOL: 'bo', USA: 'us', CAN: 'ca', MEX: 'mx', JAM: 'jm', CRC: 'cr',
  CRI: 'cr', PAN: 'pa', HON: 'hn', HND: 'hn',
  // Africa
  MAR: 'ma', ALG: 'dz', DZA: 'dz', TUN: 'tn', EGY: 'eg', SEN: 'sn', CIV: 'ci', GHA: 'gh', NGA: 'ng',
  CMR: 'cm', GUI: 'gn', GIN: 'gn', MLI: 'ml', BFA: 'bf', GAB: 'ga', COD: 'cd', CGO: 'cg', COG: 'cg',
  RSA: 'za', ZAF: 'za', ANG: 'ao', AGO: 'ao', GAM: 'gm', GMB: 'gm', CPV: 'cv', ZAM: 'zm', ZMB: 'zm',
  ZIM: 'zw', ZWE: 'zw', TOG: 'tg', BEN: 'bj', EQG: 'gq', GNQ: 'gq', GNB: 'gw', SLE: 'sl', MTN: 'mr', CTA: 'cf', CAF: 'cf',
  // Asia & Oceania
  JPN: 'jp', KOR: 'kr', CHN: 'cn', AUS: 'au', NZL: 'nz', IRN: 'ir', IRQ: 'iq', KSA: 'sa', SAU: 'sa',
  QAT: 'qa', UAE: 'ae', ARE: 'ae', UZB: 'uz', KAZ: 'kz',
}

/** Lower-cased country name → flag key, for countries stored without a code. */
const NAME_TO_KEY: Record<string, string> = {
  england: 'gb-eng', scotland: 'gb-sct', wales: 'gb-wls', 'northern ireland': 'gb-nir',
  'united kingdom': 'gb', 'great britain': 'gb',
  spain: 'es', italy: 'it', germany: 'de', france: 'fr', portugal: 'pt', netherlands: 'nl',
  belgium: 'be', croatia: 'hr', denmark: 'dk', switzerland: 'ch', austria: 'at', poland: 'pl',
  sweden: 'se', norway: 'no', finland: 'fi', iceland: 'is', ireland: 'ie', 'republic of ireland': 'ie',
  greece: 'gr', ukraine: 'ua', serbia: 'rs', slovenia: 'si', slovakia: 'sk', czechia: 'cz',
  'czech republic': 'cz', hungary: 'hu', romania: 'ro', bulgaria: 'bg', russia: 'ru', turkey: 'tr',
  'türkiye': 'tr', georgia: 'ge', armenia: 'am', azerbaijan: 'az', albania: 'al', kosovo: 'xk',
  'north macedonia': 'mk', montenegro: 'me', 'bosnia and herzegovina': 'ba', 'bosnia-herzegovina': 'ba',
  luxembourg: 'lu', cyprus: 'cy', malta: 'mt', estonia: 'ee', latvia: 'lv', lithuania: 'lt',
  belarus: 'by', moldova: 'md', israel: 'il',
  argentina: 'ar', brazil: 'br', uruguay: 'uy', colombia: 'co', ecuador: 'ec', paraguay: 'py',
  chile: 'cl', peru: 'pe', venezuela: 've', bolivia: 'bo', 'united states': 'us', usa: 'us',
  canada: 'ca', mexico: 'mx', jamaica: 'jm', 'costa rica': 'cr', panama: 'pa', honduras: 'hn',
  morocco: 'ma', algeria: 'dz', tunisia: 'tn', egypt: 'eg', senegal: 'sn', 'ivory coast': 'ci',
  "côte d'ivoire": 'ci', "cote d'ivoire": 'ci', ghana: 'gh', nigeria: 'ng', cameroon: 'cm',
  guinea: 'gn', mali: 'ml', 'burkina faso': 'bf', gabon: 'ga', 'dr congo': 'cd',
  'democratic republic of the congo': 'cd', congo: 'cg', 'south africa': 'za', angola: 'ao',
  gambia: 'gm', 'the gambia': 'gm', 'cape verde': 'cv', zambia: 'zm', zimbabwe: 'zw', togo: 'tg',
  benin: 'bj', 'equatorial guinea': 'gq', 'guinea-bissau': 'gw', 'sierra leone': 'sl', mauritania: 'mr',
  'central african republic': 'cf',
  japan: 'jp', 'south korea': 'kr', 'korea republic': 'kr', china: 'cn', australia: 'au',
  'new zealand': 'nz', iran: 'ir', iraq: 'iq', 'saudi arabia': 'sa', qatar: 'qa',
  'united arab emirates': 'ae', uzbekistan: 'uz', kazakhstan: 'kz',
}

/** The flag-icons key for a country, or null when it can't be identified. */
export function flagKey(code: string | null | undefined, name?: string | null): string | null {
  const c = code?.trim().toUpperCase()
  if (c) {
    if (CODE_TO_KEY[c]) return CODE_TO_KEY[c]
    if (/^[A-Z]{2}$/.test(c)) return c.toLowerCase()
  }
  const n = name?.trim().toLowerCase()
  return (n && NAME_TO_KEY[n]) || null
}
