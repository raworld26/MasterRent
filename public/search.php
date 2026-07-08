<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$normalizePrice = static function (string $value): string {
    if ($value === '' || !is_numeric($value)) {
        return '';
    }
    $price = (float) $value;
    return $price < 0 ? '' : (string) (int) round($price);
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

$allowedSorts = ['price_asc', 'price_desc', 'distance', 'newest'];
if (!in_array($filters['sort'], $allowedSorts, true)) {
    $filters['sort'] = 'price_asc';
}

$neighborhoods = neighborhoods_all();
$poles = poles_all();
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

// Chip filtri attivi: ogni chip è un link che RIMUOVE quel filtro.
$chipUrl = static function (array $remove) use ($filters): string {
    $params = array_filter($filters, static fn ($v): bool => $v !== '');
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
    $activeChips[] = '<a class="button-small button-secondary" href="' . e($chipUrl($remove)) . '">' . e($label) . ' ✕</a>';
};

if ($filters['q'] !== '') {
    $addChip('Ricerca: ' . $filters['q'], ['q']);
}
if ($filters['neighborhood_id'] !== '') {
    $l = $labelById($neighborhoods, $filters['neighborhood_id']);
    if ($l !== '') {
        $addChip($l, ['neighborhood_id']);
    }
}
if ($filters['pole_id'] !== '') {
    $l = $labelById($poles, $filters['pole_id']);
    if ($l !== '') {
        $addChip('Polo: ' . $l, ['pole_id']);
    }
}
if ($filters['type'] !== '') {
    $l = $labelById($types, $filters['type']);
    if ($l !== '') {
        $addChip($l, ['type']);
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

$results = rooms_search($filters);

// Filtro e ordinamento per vicinanza a un polo specifico (stime ZoneEstimates).
if ($filters['pole_id'] !== '') {
    $poleCode = '';
    foreach ($poles as $p) {
        if ((string) $p['id'] === $filters['pole_id']) {
            $poleCode = (string) $p['code'];
            break;
        }
    }
    if ($poleCode !== '') {
        $results = array_values(array_filter(
            $results,
            static fn (array $room): bool => listing_matches_campus_filter($room, $poleCode)
        ));
        usort($results, static fn (array $a, array $b): int => campus_filter_score($a, $poleCode) <=> campus_filter_score($b, $poleCode));
    } else {
        $results = [];
    }
}

$favIds = current_favorite_ids();
$roomCards = room_cards_html($results, $favIds);
if ($roomCards === '') {
    $roomCards = '<div class="empty-state search-empty"><p class="muted">Nessuna stanza trovata. Prova ad allargare la ricerca.</p></div>';
}

$resultsLabel = count($results) === 1 ? '1 stanza trovata' : count($results) . ' stanze trovate';
$activeFilterChips = $activeChips !== [] ? implode(' ', $activeChips) : '<span class="muted">Nessun filtro attivo</span>';

$content = render_template('search.html', [
    'action_url' => e(url_for('search.php')),
    'home_url' => e(url_for('index.php')),
    'q' => e($filters['q']),
    'price_min' => e($filters['price_min']),
    'price_max' => e($filters['price_max']),
    'neighborhood_options' => select_options($neighborhoods, $filters['neighborhood_id'], 'id', 'name', 'Tutti i quartieri'),
    'pole_options' => select_options($poles, $filters['pole_id'], 'id', 'name', 'Tutti i poli'),
    'type_options' => select_options($types, $filters['type'], 'id', 'name', 'Tutte le tipologie'),
    'furnished_checked' => $filters['furnished'] !== '' ? 'checked' : '',
    'sort_options' => select_options($sortItems, $filters['sort'], 'id', 'name'),
    'results_summary' => e($resultsLabel),
    'active_chips' => $activeFilterChips,
    'room_cards' => $roomCards,
]);

render_page('Cerca stanze', $content, ['body_class' => 'page-public']);
