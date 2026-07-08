<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

/*
 * Le foto degli annunci demo provengono dalla cartella locale Case e sono
 * copiate in public/assets/uploads/case, usate a solo scopo dimostrativo.
 */
$streets = [
    'Contrada Sant\'Elia 4',
    'Corso Vittorio Emanuele II',
    'Via Delle Nocelle 85',
    'Via Gennaro Manna 33',
    'Via Goriano Valle 47',
    'Via Nicola Lombardi 12',
    'Via Uruguay 6',
];

$rows = '';
foreach ($streets as $street) {
    $rows .= '<li><span class="item-title">' . e($street) . '</span><span class="item-meta">L\'Aquila (AQ)</span></li>';
}

$content = '<section class="dashboard-shell">'
    . '<header class="dashboard-header"><div><p class="eyebrow">Informazioni</p><h1>Crediti immagini</h1></div>'
    . '<a class="button-secondary" href="' . e(url_for('index.php')) . '">Home</a></header>'
    . '<section class="panel">'
    . '<p>Le fotografie usate nelle schede degli annunci demo sono immagini fornite localmente '
    . 'nella cartella Case del progetto e impiegate a solo scopo dimostrativo. Non indicano che gli '
    . 'immobili raffigurati siano realmente in affitto.</p>'
    . '<ul class="item-list">' . $rows . '</ul>'
    . '</section>'
    . '</section>';

render_page('Crediti immagini', $content, ['body_class' => 'page-public']);
