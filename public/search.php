<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$geo = new GeoRepository();
$rooms = new RoomRepository();

$normalizePrice = static function (string $value): string {
    if ($value === '' || !is_numeric($value)) {
        return '';
    }

    $price = (float) $value;
    if ($price < 0) {
        return '';
    }

    return (string) (int) round($price);
};

$filters = [
    'q' => query_str('q'),
    'neighborhood_id' => query_str('neighborhood_id'),
    'pole_id' => query_str('pole_id'),
    'type' => query_str('type'),
    'price_min' => $normalizePrice(query_str('price_min')),
    'price_max' => $normalizePrice(query_str('price_max')),
    'furnished' => query_str('furnished') !== '' ? '1' : '',
    'sort' => query_str('sort', 'price_asc'),
];

if ($filters['price_min'] !== '' && $filters['price_max'] !== ''
    && (int) $filters['price_min'] > (int) $filters['price_max']) {
    [$filters['price_min'], $filters['price_max']] = [$filters['price_max'], $filters['price_min']];
}

$neighborhoods = $geo->allNeighborhoods();
$poles = $geo->allPoles();
$types = room_type_options();
$sortItems = [
    ['id' => 'price_asc', 'name' => 'Prezzo crescente'],
    ['id' => 'price_desc', 'name' => 'Prezzo decrescente'],
    ['id' => 'distance', 'name' => 'Distanza dai poli'],
    ['id' => 'newest', 'name' => 'Più recenti'],
];

$labelById = static function (array $items, string $id): string {
    foreach ($items as $item) {
        if ((string) $item['id'] === $id) {
            return (string) $item['name'];
        }
    }

    return '';
};

$chipUrl = static function (array $remove) use ($filters): string {
    $params = array_filter($filters, static fn ($value): bool => $value !== '');
    if (($params['sort'] ?? '') === 'price_asc') {
        unset($params['sort']);
    }
    foreach ($remove as $key) {
        unset($params[$key]);
    }

    $query = http_build_query($params);
    return url_for('search.php' . ($query !== '' ? '?' . $query : ''));
};

$activeChips = [];
$addChip = static function (string $label, array $remove) use (&$activeChips, $chipUrl): void {
    $activeChips[] = '<a class="filter-chip" href="' . e($chipUrl($remove)) . '" aria-label="Rimuovi filtro ' . e($label) . '">'
        . '<span>' . e($label) . '</span>'
        . '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>'
        . '</a>';
};

if ($filters['q'] !== '') {
    $addChip('Ricerca: ' . $filters['q'], ['q']);
}
if ($filters['neighborhood_id'] !== '') {
    $label = $labelById($neighborhoods, $filters['neighborhood_id']);
    if ($label !== '') {
        $addChip($label, ['neighborhood_id']);
    }
}
if ($filters['pole_id'] !== '') {
    $label = $labelById($poles, $filters['pole_id']);
    if ($label !== '') {
        $addChip('Polo: ' . $label, ['pole_id']);
    }
}
if ($filters['type'] !== '') {
    $label = $labelById($types, $filters['type']);
    if ($label !== '') {
        $addChip($label, ['type']);
    }
}
if ($filters['price_min'] !== '' || $filters['price_max'] !== '') {
    if ($filters['price_min'] !== '' && $filters['price_max'] !== '') {
        $priceLabel = '€ ' . $filters['price_min'] . ' - ' . $filters['price_max'];
    } elseif ($filters['price_min'] !== '') {
        $priceLabel = 'Da € ' . $filters['price_min'];
    } else {
        $priceLabel = 'Max € ' . $filters['price_max'];
    }
    $addChip($priceLabel, ['price_min', 'price_max']);
}
if ($filters['furnished'] !== '') {
    $addChip('Solo arredate', ['furnished']);
}

// Tipologia come chip selezionabili (radio): submit del form GET.
$typeChips = '';
$typeChoices = array_merge([['id' => '', 'name' => 'Tutte']], $types);
foreach ($typeChoices as $choice) {
    $checked = ((string) $choice['id'] === $filters['type']) ? ' checked' : '';
    $typeChips .= '<label class="chip-option">'
        . '<input type="radio" name="type" value="' . e((string) $choice['id']) . '"' . $checked . '>'
        . '<span>' . e($choice['name']) . '</span>'
        . '</label>';
}

$activeFilterChips = $activeChips !== []
    ? implode('', $activeChips)
    : '<span class="filter-chip filter-chip-muted">Nessun filtro attivo</span>';

$results = $rooms->search($filters);

if ($filters['pole_id'] !== '') {
    $poleCode = '';
    foreach ($poles as $p) {
        if ((string) $p['id'] === $filters['pole_id']) {
            $poleCode = $p['code'];
            break;
        }
    }
    
    if ($poleCode !== '') {
        $results = array_values(array_filter(
            $results,
            static fn (array $room): bool => listing_matches_campus_filter($room, $poleCode)
        ));
        usort($results, static function (array $a, array $b) use ($poleCode): int {
            return campus_filter_score($a, $poleCode) <=> campus_filter_score($b, $poleCode);
        });
    } else {
        $results = [];
    }
}
$favIds = current_favorite_ids();

$listHtml = $results === [] ? '' : render_room_grid($results, $favIds);
$emptyState = $results === []
    ? render_empty_state(
        'Nessuna stanza trovata',
        'Non ci sono stanze che corrispondono ai filtri scelti. Prova ad allargare la ricerca.',
        url_for('search.php'),
        'Azzera i filtri'
    )
    : '';

$mapCoords = [
    'Centro storico' => [42.3498, 13.3995],
    'Centro' => [42.3498, 13.3995],
    'Coppito' => [42.3653, 13.3512],
    'Roio' => [42.3275, 13.3850],
    'Pettino' => [42.3667, 13.3667],
    'Pile' => [42.3556, 13.3813],
    'Torrione' => [42.3582, 13.4116],
    'L\'Aquila' => [42.3498, 13.3995]
];
$mapCenter = $mapCoords['L\'Aquila'];
$zoom = 13;
if ($filters['neighborhood_id'] !== '') {
    $label = $labelById($neighborhoods, $filters['neighborhood_id']);
    if (isset($mapCoords[$label])) {
        $mapCenter = $mapCoords[$label];
        $zoom = 15;
    }
}
$markersData = [];
foreach ($results as $r) {
    $nName = $r['neighborhood_name'] ?? 'L\'Aquila';
    $coords = $mapCoords[$nName] ?? $mapCoords['L\'Aquila'];
    $lat = $coords[0] + (rand(-300, 300) / 100000);
    $lng = $coords[1] + (rand(-300, 300) / 100000);
    $markersData[] = [
        'lat' => $lat,
        'lng' => $lng,
        'title' => $r['name'],
        'price' => format_price($r['price_monthly']),
        'url' => url_for('room.php?id=' . $r['id'])
    ];
}

$content = render_template('frontend/search', [
    'search_url' => e(url_for('search.php')),
    'q' => e($filters['q']),
    'price_min' => e($filters['price_min']),
    'price_max' => e($filters['price_max']),
    'result_count' => (string) count($results),
    'active_filter_chips' => $activeFilterChips,
    'neighborhood_options' => select_options($neighborhoods, $filters['neighborhood_id'], 'id', 'name', 'Tutti i quartieri'),
    'pole_options' => select_options($poles, $filters['pole_id'], 'id', 'name', 'Tutti i poli'),
    'type_chips' => $typeChips,
    'furnished_checked' => $filters['furnished'] !== '' ? 'checked' : '',
    'sort_options' => select_options($sortItems, $filters['sort'], 'id', 'name'),
    'room_list' => $listHtml,
    'empty_state' => $emptyState,
    'map_embed' => e(osm_embed_url(42.3498, 13.3995, 0.045)),
    'map_center_lat' => $mapCenter[0],
    'map_center_lng' => $mapCenter[1],
    'map_zoom' => $zoom,
    'markers_json' => json_encode($markersData),
]);

render_page_frontend('Cerca stanze a L\'Aquila', $content, [
    'body_class' => 'page-search',
    'meta_description' => 'Cerca stanze e posti letto per studenti a L\'Aquila: filtra per quartiere, '
        . 'polo UNIVAQ, prezzo e tipologia, con distanza dalle aule sempre in vista.',
]);
