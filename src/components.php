<?php

declare(strict_types=1);

/*
 * Componenti UI riutilizzabili (§ design system MasterRent).
 * Ogni helper renderizza il partial corrispondente in
 * templates/frontend/_components/ e ritorna HTML già "escaped":
 * i controller li usano al posto del markup inline duplicato.
 */

/* ---------------------------------------------------------------------------
 * Bottone (link con aspetto bottone).
 * $variant: '' | 'ghost' | 'danger' | 'small' | combinazioni ("ghost small").
 * ------------------------------------------------------------------------- */
function render_button(string $label, string $url, string $variant = '', string $iconSvg = ''): string
{
    $classes = [];
    foreach (preg_split('/\s+/', trim($variant)) ?: [] as $mod) {
        if ($mod !== '') {
            $classes[] = 'button-' . $mod;
        }
    }

    return render_template('frontend/_components/_button', [
        'btn_label' => e($label),
        'btn_url' => e($url),
        'btn_variant' => e(implode(' ', $classes)),
        'btn_icon' => $iconSvg, // SVG fidato, generato dal chiamante
    ]);
}

/* ---------------------------------------------------------------------------
 * Badge generico + badge di dominio (stato stanza / stato richiesta).
 * ------------------------------------------------------------------------- */
function render_badge(string $label, string $variant = 'muted'): string
{
    return render_template('frontend/_components/_badge', [
        'badge_label' => e($label),
        'badge_variant' => e('badge-' . $variant),
    ]);
}

function render_badge_room_status(string $status): string
{
    $variant = [
        'available' => 'success',
        'reserved' => 'warning',
        'unavailable' => 'muted',
    ][$status] ?? 'muted';

    return render_badge(room_status_label($status), $variant);
}

function render_badge_booking_status(string $status): string
{
    $variant = [
        'visit_requested' => 'warning',
        'approved_pending_deposit' => 'info',
        'rejected' => 'danger',
        'cancellation_requested' => 'warning',
        'completed' => 'muted',
        'deposit_paid' => 'success',
        'withdrawn' => 'muted',
    ][$status] ?? 'muted';

    return render_badge(booking_status_label($status), $variant);
}

/* ---------------------------------------------------------------------------
 * Campo di input standard (etichetta + input). Per select/textarea i template
 * di pagina restano più espressivi; questo copre i casi semplici duplicati.
 * ------------------------------------------------------------------------- */
function render_field(string $label, string $name, string $type = 'text', string $value = '', string $attrs = ''): string
{
    return render_template('frontend/_components/_field', [
        'field_label' => e($label),
        'field_name' => e($name),
        'field_type' => e($type),
        'field_value' => e($value),
        'field_attrs' => $attrs, // attributi fidati scritti dal chiamante (required, maxlength…)
    ]);
}

/* ---------------------------------------------------------------------------
 * Empty state: illustrazione + titolo + testo + azione facoltativa.
 * $icon: chiave dell'icona (search, heart, home, inbox).
 * ------------------------------------------------------------------------- */
function render_empty_state(string $title, string $text, string $actionUrl = '', string $actionLabel = '', string $icon = 'search'): string
{
    $icons = [
        'search' => '<circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m20 20-3.5-3.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>',
        'home' => '<path d="M4 11 12 4l8 7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 10v9h12v-9" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>',
        'inbox' => '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>',
    ];

    $action = '';
    if ($actionUrl !== '' && $actionLabel !== '') {
        $action = '<a class="button" href="' . e($actionUrl) . '">' . e($actionLabel) . '</a>';
    }

    return render_template('frontend/_components/_empty_state', [
        'empty_title' => e($title),
        'empty_text' => e($text),
        'empty_icon' => $icons[$icon] ?? $icons['search'],
        'empty_action' => $action,
    ]);
}

/* ---------------------------------------------------------------------------
 * Toast/flash inline (fallback senza JavaScript; toasts.js li promuove).
 * ------------------------------------------------------------------------- */
function render_toast(string $message, string $type = 'info'): string
{
    $type = in_array($type, ['success', 'danger', 'info', 'warning'], true) ? $type : 'info';

    return render_template('frontend/_components/_toast', [
        'toast_message' => e($message),
        'toast_type' => $type,
    ]);
}

/* ---------------------------------------------------------------------------
 * Stepper del flusso di prenotazione:
 * Visita richiesta · Approvata · Caparra · Prenotata.
 * Gestisce anche i flussi interrotti (rifiutata / ritirata).
 * ------------------------------------------------------------------------- */
function render_stepper(string $currentBookingState): string
{
    $labels = ['Visita richiesta', 'Approvata', 'Caparra', 'Prenotata'];
    $halted = false;

    switch ($currentBookingState) {
        case 'approved_pending_deposit':
            $current = 2; // la caparra è il passo da compiere ora
            break;
        case 'deposit_paid':
            $current = 3;
            break;
        case 'cancellation_requested':
            $current = 3;
            $labels[3] = 'Disdetta richiesta';
            break;
        case 'completed':
            $current = 3;
            $labels[3] = 'Conclusa';
            break;
        case 'rejected':
            $current = 1;
            $labels[1] = 'Rifiutata';
            $halted = true;
            break;
        case 'withdrawn':
            $current = 1;
            $labels[1] = 'Ritirata';
            $halted = true;
            break;
        case 'visit_requested':
        default:
            $current = 0;
    }

    $check = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>';
    $completedFlow = in_array($currentBookingState, ['deposit_paid', 'completed'], true);

    $rows = [];
    foreach ($labels as $i => $label) {
        $state = '';
        $dot = (string) ($i + 1);
        if ($i < $current) {
            $state = 'is-done';
            $dot = $check;
        } elseif ($i === $current) {
            $state = 'is-current' . ($completedFlow ? ' is-done' : '');
            if ($completedFlow) {
                $dot = $check;
            }
        }
        if ($halted && $i > $current) {
            $state = ''; // i passi successivi restano spenti
        }

        $rows[] = [
            'step_label' => e($label),
            'step_state' => $state,
            'step_dot' => $dot,
            'step_aria' => $i === $current ? 'aria-current="step"' : '',
        ];
    }

    return render_list('frontend/_components/_stepper', $rows, [
        'stepper_modifier' => $halted ? 'stepper--halted' : '',
        'stepper_current_label' => e($labels[$current]),
    ]);
}

/* ---------------------------------------------------------------------------
 * Banner di stato pagina (es. stanza non più disponibile).
 * $tone: warning | danger | info.
 * ------------------------------------------------------------------------- */
function render_banner(string $title, string $text, string $tone = 'warning'): string
{
    $tone = in_array($tone, ['warning', 'danger', 'info'], true) ? $tone : 'warning';

    return '<div class="banner banner--' . $tone . '" role="status">'
        . '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>'
        . '<div><strong>' . e($title) . '</strong><p>' . e($text) . '</p></div>'
        . '</div>';
}

/* ---------------------------------------------------------------------------
 * Griglia di card annuncio: renderizza _card_room.html per ogni stanza
 * dentro il contenitore .room-grid (usato da home, ricerca, preferiti).
 * ------------------------------------------------------------------------- */
function render_room_grid(array $rooms, ?array $favIds = null): string
{
    $cards = '';
    foreach (room_card_rows($rooms, $favIds) as $row) {
        $cards .= render_template('frontend/_components/_card_room', $row);
    }

    return render_template('frontend/room_list', ['cards' => $cards]);
}
