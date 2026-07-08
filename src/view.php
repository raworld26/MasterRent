<?php

declare(strict_types=1);

/*
 * Livello di presentazione: wrapper attorno al motore template2.inc.php.
 * Ogni vista è un file .html in /templates con placeholder <[...]>,
 * cicli <[foreach]> e condizioni <[if!empty]>/<[ifempty]>.
 *
 * Sono previsti DUE layout distinti:
 *   - templates/frontend/layout.html  (portale pubblico, dominio housing)
 *   - templates/backend/layout.html   (shell di amministrazione)
 */

function template_path(string $template): string
{
    $template = ltrim(str_replace('\\', '/', $template), '/');

    if (substr($template, -5) === '.html') {
        $template = substr($template, 0, -5);
    }

    return TEMPLATES_PATH . '/' . $template;
}

/*
 * Esegue il rendering di un oggetto Template, fornendo al motore i dati
 * dell'utente corrente (placeholder user.*) attesi da template2.
 */
function render_string(Template $template): string
{
    $had = array_key_exists('user', $_SESSION);
    $previous = $_SESSION['user'] ?? null;

    $user = current_user();
    $_SESSION['user'] = [
        'username' => $user['email'] ?? '',
        'name' => $user['full_name'] ?? '',
        'surname' => '',
        'email' => $user['email'] ?? '',
    ];

    try {
        return $template->get();
    } finally {
        if ($had) {
            $_SESSION['user'] = $previous;
        } else {
            unset($_SESSION['user']);
        }
    }
}

/*
 * Renderizza un template "di contenuto" con soli placeholder scalari
 * e ne ritorna l'HTML (da inserire poi in un layout).
 */
function render_template(string $template, array $variables = []): string
{
    $view = new Template(template_path($template));

    foreach ($variables as $name => $value) {
        $view->setContent((string) $name, (string) $value);
    }

    return render_string($view);
}

/*
 * Renderizza un template che contiene un ciclo <[foreach]>: ogni elemento di
 * $rows è un array associativo i cui valori vengono associati ai placeholder
 * del ciclo. $scalars sono i placeholder fuori dal ciclo.
 * I valori devono già essere "escaped" dal chiamante dove necessario.
 */
function render_list(string $template, array $rows, array $scalars = []): string
{
    $view = new Template(template_path($template));

    foreach ($scalars as $name => $value) {
        $view->setContent((string) $name, (string) $value);
    }
    foreach ($rows as $row) {
        foreach ($row as $name => $value) {
            $view->setContent((string) $name, (string) $value);
        }
    }

    return render_string($view);
}

/* Placeholder comuni a entrambi i layout. */
function base_layout_vars(string $title, string $content): array
{
    $user = current_user();
    $fullName = (string) ($user['full_name'] ?? '');

    return [
        'app_name' => e(APP_NAME),
        'app_tagline' => e(APP_TAGLINE),
        'title' => e($title),
        'content' => $content,
        'base_url' => e(rtrim(BASE_URL, '/')),
        'assets_url' => e(rtrim(ASSETS_URL, '/')),
        'current_year' => e(date('Y')),
        'flashes' => render_flashes(),
        'user_name' => e($fullName),
        'user_email' => e($user['email'] ?? ''),
        'user_initial' => e(mb_strtoupper(mb_substr(trim($fullName) !== '' ? trim($fullName) : 'U', 0, 1))),
        'logout_url' => e(url_for('logout.php')),
        'logout_csrf' => csrf_field('logout'),
    ];
}

/*
 * Voci del menu utente del frontend, in base ai ruoli dell'utente corrente.
 * Restituisce HTML già "escaped" (link con icona), pronto per il layout.
 */
function frontend_user_menu_links(): string
{
    if (!is_authenticated()) {
        return '';
    }

    $icon = static fn (string $paths): string =>
        '<svg viewBox="0 0 24 24" aria-hidden="true">' . $paths . '</svg>';

    $items = [];

    if (user_has_group('admin')) {
        $items[] = [url_for('admin/index.php'), 'Pannello amministrazione',
            $icon('<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>')];
    }
    if (user_has_group('landlord')) {
        $items[] = [url_for('landlord/index.php'), 'I miei annunci',
            $icon('<path d="M4 11 12 4l8 7"/><path d="M6 10v9h12v-9"/>')];
        $items[] = [url_for('landlord/bookings.php'), 'Richieste ricevute',
            $icon('<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8Z"/>')];
        $items[] = [url_for('landlord/property_form.php'), 'Pubblica un annuncio',
            $icon('<path d="M12 5v14M5 12h14"/>')];
    }
    if (user_has_group('student')) {
        $items[] = [url_for('account/index.php'), 'La mia area',
            $icon('<path d="M20 21a8 8 0 1 0-16 0"/><circle cx="12" cy="7" r="4"/>')];
        $items[] = [url_for('account/bookings.php'), 'Le mie richieste',
            $icon('<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8Z"/>')];
        $items[] = [url_for('account/my-house.php'), 'La mia casa',
            $icon('<path d="M4 11 12 4l8 7"/><path d="M6 10v9h12v-9"/><path d="M10 19v-5h4v5"/>')];
        $items[] = [url_for('account/favorites.php'), 'I miei preferiti',
            $icon('<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/>')];
        $items[] = [url_for('account/profile.php'), 'Profilo',
            $icon('<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3h.1a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5h.1a1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9v.1a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>')];
    }

    $html = '';
    foreach ($items as [$url, $label, $svg]) {
        $html .= '<a href="' . e($url) . '">' . $svg . e($label) . '</a>';
    }

    return $html;
}

/*
 * Pagina del portale pubblico (frontend).
 */
function render_page_frontend(string $title, string $content, array $variables = []): void
{
    $layout = new Template(template_path('frontend/layout'));

    // Navigazione: evidenzia la voce corrente con aria-current.
    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = basename(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
    $ariaCurrent = 'aria-current="page"';

    $defaultDescription = APP_TAGLINE
        . '. Stanze e posti letto per studenti UNIVAQ: confronta zone e prezzi, '
        . 'prenota la visita e blocca la stanza con una caparra simulata.';

    $vars = array_merge(base_layout_vars($title, $content), [
        'home_url' => e(url_for('index.php')),
        'search_url' => e(url_for('search.php')),
        'login_url' => e(url_for('login.php')),
        'register_url' => e(url_for('register.php')),
        'my_area_url' => e(is_authenticated() ? role_home_url() : url_for('login.php')),
        'favorites_url' => e(url_for('account/favorites.php')),
        'is_student' => user_has_group('student') ? '1' : '',
        'user_menu_links' => frontend_user_menu_links(),
        'csrf_meta' => e(csrf_token('favorite')),
        'favorite_endpoint' => e(url_for('favorite.php')),
        'credits_url' => e(url_for('credits.php')),
        'neighborhoods_datalist' => neighborhoods_datalist_html(),
        'body_class' => e((string) ($variables['body_class'] ?? '')),
        'meta_description' => e((string) ($variables['meta_description'] ?? $defaultDescription)),
        'og_image' => e(asset_url('img/og-image.svg')),
        'aria_home' => ($script === 'index.php' && !in_array($dir, ['account', 'landlord', 'admin'], true)) ? $ariaCurrent : '',
        'aria_search' => $script === 'search.php' ? $ariaCurrent : '',
        'aria_fav' => $script === 'favorites.php' ? $ariaCurrent : '',
        'aria_area' => in_array($dir, ['account', 'landlord'], true) && $script === 'index.php' ? $ariaCurrent : '',
    ], $variables);

    foreach ($vars as $name => $value) {
        $layout->setContent((string) $name, (string) $value);
    }

    echo render_string($layout);
}

/*
 * Pagina dell'area di amministrazione (backend) con sidebar dinamica.
 * $activeCode = code del servizio della pagina corrente (per evidenziare il menu).
 */
function render_page_backend(string $title, string $content, array $variables = [], string $activeCode = ''): void
{
    $layout = new Template(template_path('backend/layout'));

    $vars = array_merge(base_layout_vars($title, $content), [
        'dashboard_url' => e(url_for('admin/index.php')),
        'site_url' => e(url_for('index.php')),
        'logout_url' => e(url_for('logout.php')),
        'body_class' => e((string) ($variables['body_class'] ?? '')),
    ], $variables);

    foreach ($vars as $name => $value) {
        $layout->setContent((string) $name, (string) $value);
    }

    // Voci di menu generate dai servizi accessibili: alimentano il <[foreach]>.
    foreach (backend_menu_items($activeCode) as $item) {
        $layout->setContent('menu_label', e($item['name']));
        $layout->setContent('menu_url', e($item['url']));
        $layout->setContent('menu_active', $item['active'] ? 'is-active' : '');
    }

    echo render_string($layout);
}
