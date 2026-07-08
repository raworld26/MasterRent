<?php

declare(strict_types=1);

/**
 * Tempistiche stimate dalle macrozone ai principali poli universitari.
 * Dati indicativi.
 */

function get_zone_estimates(): array
{
    return [
        'centro' => [
            'name' => 'Centro',
            'badges' => ['Ottima per DSU', 'Buona per Roio', 'Discreta per Coppito in bus'],
            'poles' => [
                'polo_coppito' => ['walk' => '60-75 min', 'bus' => '20-35 min', 'best' => false, 'name' => 'Polo Coppito'],
                'polo_roio' => ['walk' => '55-75 min', 'bus' => '20-35 min', 'best' => false, 'name' => 'Polo Roio / Ingegneria'],
                'polo_centro' => ['walk' => '5-20 min', 'bus' => '5-15 min', 'best' => true, 'name' => 'Polo DSU / Centro'],
            ],
            'note' => ''
        ],
        'torrione_strinella' => [
            'name' => 'Torrione/Strinella',
            'badges' => ['Comoda per DSU', 'Buona in bus'],
            'poles' => [
                'polo_coppito' => ['walk' => '50-70 min', 'bus' => '20-30 min', 'best' => false, 'name' => 'Polo Coppito'],
                'polo_roio' => ['walk' => '65-90 min', 'bus' => '30-45 min', 'best' => false, 'name' => 'Polo Roio / Ingegneria'],
                'polo_centro' => ['walk' => '10-25 min', 'bus' => '5-15 min', 'best' => true, 'name' => 'Polo DSU / Centro'],
            ],
            'note' => ''
        ],
        'croce_rossa_santa_barbara' => [
            'name' => 'Croce Rossa/Santa Barbara',
            'badges' => ['Buona per Coppito', 'Comoda per DSU', 'Zona strategica'],
            'poles' => [
                'polo_coppito' => ['walk' => '35-50 min', 'bus' => '15-25 min', 'best' => true, 'name' => 'Polo Coppito'],
                'polo_roio' => ['walk' => '60-85 min', 'bus' => '30-50 min', 'best' => false, 'name' => 'Polo Roio / Ingegneria'],
                'polo_centro' => ['walk' => '15-35 min', 'bus' => '10-20 min', 'best' => true, 'name' => 'Polo DSU / Centro'],
            ],
            'note' => ''
        ],
        'pile_pettino' => [
            'name' => 'Pile/Pettino',
            'badges' => ['Ottima per Coppito', 'Buona in bus', 'Zona servita'],
            'poles' => [
                'polo_coppito' => ['walk' => '20-45 min', 'bus' => '10-25 min', 'best' => true, 'name' => 'Polo Coppito'],
                'polo_roio' => ['walk' => '55-90 min', 'bus' => '25-45 min', 'best' => false, 'name' => 'Polo Roio / Ingegneria'],
                'polo_centro' => ['walk' => '40-65 min', 'bus' => '20-35 min', 'best' => false, 'name' => 'Polo DSU / Centro'],
            ],
            'note' => ''
        ],
        'coppito_ospedale' => [
            'name' => 'Coppito/Università/Ospedale',
            'badges' => ['Ottima per Coppito', 'Perfetta per Università'],
            'poles' => [
                'polo_coppito' => ['walk' => '0-20 min', 'bus' => '0-10 min', 'best' => true, 'name' => 'Polo Coppito'],
                'polo_roio' => ['walk' => '90-120 min', 'bus' => '35-60 min', 'best' => false, 'name' => 'Polo Roio / Ingegneria'],
                'polo_centro' => ['walk' => '60-80 min', 'bus' => '20-35 min', 'best' => false, 'name' => 'Polo DSU / Centro'],
            ],
            'note' => ''
        ],
        'roio_ingegneria' => [
            'name' => 'Roio/Ingegneria',
            'badges' => ['Ottima per Ingegneria', 'Buona per Roio'],
            'poles' => [
                'polo_coppito' => ['walk' => '90-120 min', 'bus' => '35-60 min', 'best' => false, 'name' => 'Polo Coppito'],
                'polo_roio' => ['walk' => '0-20 min', 'bus' => '0-10 min', 'best' => true, 'name' => 'Polo Roio / Ingegneria'],
                'polo_centro' => ['walk' => '55-80 min', 'bus' => '25-40 min', 'best' => false, 'name' => 'Polo DSU / Centro'],
            ],
            'note' => ''
        ],
        'paganica_tempera' => [
            'name' => 'Paganica/Tempera',
            'badges' => ['Richiede bus', 'Auto consigliata'],
            'poles' => [
                'polo_coppito' => ['walk' => '120-170 min', 'bus' => '45-70 min', 'best' => false, 'name' => 'Polo Coppito'],
                'polo_roio' => ['walk' => '130-190 min', 'bus' => '55-85 min', 'best' => false, 'name' => 'Polo Roio / Ingegneria'],
                'polo_centro' => ['walk' => '80-130 min', 'bus' => '35-55 min', 'best' => false, 'name' => 'Polo DSU / Centro'],
            ],
            'note' => ''
        ],
        'bazzano_est' => [
            'name' => 'Bazzano/Est',
            'badges' => ['Richiede bus', 'Auto consigliata'],
            'poles' => [
                'polo_coppito' => ['walk' => '120-160 min', 'bus' => '45-75 min', 'best' => false, 'name' => 'Polo Coppito'],
                'polo_roio' => ['walk' => '100-150 min', 'bus' => '50-80 min', 'best' => false, 'name' => 'Polo Roio / Ingegneria'],
                'polo_centro' => ['walk' => '70-110 min', 'bus' => '30-50 min', 'best' => false, 'name' => 'Polo DSU / Centro'],
            ],
            'note' => ''
        ],
        'sassa_preturo' => [
            'name' => 'Sassa/Preturo',
            'badges' => ['Buona in bus per Coppito', 'Auto consigliata'],
            'poles' => [
                'polo_coppito' => ['walk' => '80-140 min', 'bus' => '25-50 min', 'best' => false, 'name' => 'Polo Coppito'],
                'polo_roio' => ['walk' => '90-140 min', 'bus' => '45-75 min', 'best' => false, 'name' => 'Polo Roio / Ingegneria'],
                'polo_centro' => ['walk' => '85-140 min', 'bus' => '35-60 min', 'best' => false, 'name' => 'Polo DSU / Centro'],
            ],
            'note' => ''
        ],
        'frazioni_gran_sasso' => [
            'name' => 'Frazioni/Gran Sasso',
            'badges' => ['Auto consigliata', 'Distanza elevata'],
            'poles' => [
                'polo_coppito' => ['walk' => '180+ min', 'bus' => '60-90 min', 'best' => false, 'name' => 'Polo Coppito'],
                'polo_roio' => ['walk' => '180+ min', 'bus' => '75-110 min', 'best' => false, 'name' => 'Polo Roio / Ingegneria'],
                'polo_centro' => ['walk' => '120-240 min', 'bus' => '50-80 min', 'best' => false, 'name' => 'Polo DSU / Centro'],
            ],
            'note' => ''
        ]
    ];
}

function normalize_campus_filter_text(?string $value): string
{
    $value = mb_strtolower(trim((string) $value));
    $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if (is_string($transliterated) && $transliterated !== '') {
        $value = $transliterated;
    }
    $value = strtr($value, [
        'Ã ' => 'a', 'Ã¡' => 'a', 'Ã¢' => 'a', 'Ã¤' => 'a',
        'Ã¨' => 'e', 'Ã©' => 'e', 'Ãª' => 'e', 'Ã«' => 'e',
        'Ã¬' => 'i', 'Ã­' => 'i', 'Ã®' => 'i', 'Ã¯' => 'i',
        'Ã²' => 'o', 'Ã³' => 'o', 'Ã´' => 'o', 'Ã¶' => 'o',
        'Ã¹' => 'u', 'Ãº' => 'u', 'Ã»' => 'u', 'Ã¼' => 'u',
        'Ã‡' => 'c', 'Ã§' => 'c', '\'' => ' ', '/' => ' ', '-' => ' ',
    ]);
    return trim((string) preg_replace('/\s+/', ' ', $value));
}

function campus_recommended_zone_codes(string $poleCode): array
{
    return [
        'polo_coppito' => ['coppito_ospedale', 'pile_pettino', 'croce_rossa_santa_barbara'],
        'polo_roio' => ['roio_ingegneria', 'pile_pettino', 'centro'],
        'polo_centro' => ['centro', 'torrione_strinella', 'croce_rossa_santa_barbara'],
    ][$poleCode] ?? [];
}

function campus_zone_aliases(): array
{
    return [
        'centro' => ['centro', 'centro storico', 'duomo', 'dsu', 'economia'],
        'torrione_strinella' => ['torrione', 'strinella'],
        'croce_rossa_santa_barbara' => ['croce rossa', 'santa barbara'],
        'pile_pettino' => ['pile', 'pettino'],
        'coppito_ospedale' => ['coppito', 'universita', 'universitario', 'ospedale'],
        'roio_ingegneria' => ['roio', 'ingegneria', 'monteluco'],
    ];
}

function listing_matches_campus_filter(array $listing, string $poleCode): bool
{
    if ($poleCode === '') {
        return true;
    }

    $recommendedZones = campus_recommended_zone_codes($poleCode);
    if ($recommendedZones === []) {
        return false;
    }

    $zoneCode = (string) ($listing['neighborhood_code'] ?? '');
    if ($zoneCode !== '' && in_array($zoneCode, $recommendedZones, true)) {
        return true;
    }

    $haystack = normalize_campus_filter_text(implode(' ', [
        $listing['neighborhood_name'] ?? '',
        $listing['address'] ?? '',
        $listing['property_title'] ?? '',
    ]));
    if ($haystack === '') {
        return false;
    }

    $aliases = campus_zone_aliases();
    foreach ($recommendedZones as $zone) {
        foreach (($aliases[$zone] ?? []) as $alias) {
            if (str_contains($haystack, normalize_campus_filter_text($alias))) {
                return true;
            }
        }
    }

    return false;
}

function campus_filter_score(array $listing, string $poleCode): int
{
    $zoneData = get_zone_estimates()[(string) ($listing['neighborhood_code'] ?? '')] ?? null;
    $poleData = is_array($zoneData) ? ($zoneData['poles'][$poleCode] ?? null) : null;
    if (!is_array($poleData)) {
        return 999;
    }

    preg_match('/\d+/', (string) ($poleData['bus'] ?? ''), $matches);
    return isset($matches[0]) ? (int) $matches[0] : 999;
}
