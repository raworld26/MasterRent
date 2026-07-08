<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

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
    $rows .= '<article class="credit-card"><h3>' . e($street) . '</h3>'
        . '<p class="muted">L\'Aquila (AQ)</p></article>';
}

$body = '<div class="panel credits-note">'
    . '<p>Le fotografie usate nelle schede degli annunci demo sono immagini '
    . 'fornite localmente nella cartella Case del progetto e impiegate a solo scopo '
    . 'dimostrativo. Non indicano che gli immobili raffigurati siano '
    . 'realmente in affitto.</p>'
    . '</div><div class="credits-grid">' . $rows . '</div>';

$content = render_template('frontend/simple_page', [
    'page_title' => 'Crediti immagini',
    'page_intro' => 'Provenienza delle foto usate nelle schede demo.',
    'page_body' => $body,
]);

render_page_frontend('Crediti immagini', $content, ['body_class' => 'page-credits']);
