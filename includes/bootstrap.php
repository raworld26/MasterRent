<?php // è il punto di "accensione" dell'applicazione

declare(strict_types=1);

define('PROJECT_ROOT', dirname(__DIR__));
define('CONFIG_PATH', PROJECT_ROOT . '/config');
define('INCLUDES_PATH', PROJECT_ROOT . '/includes');
define('TEMPLATES_PATH', PROJECT_ROOT . '/templates');

require_once CONFIG_PATH . '/config.php';
require_once INCLUDES_PATH . '/security.php';

start_secure_session();

require_once CONFIG_PATH . '/database.php';
require_once PROJECT_ROOT . '/template2.inc.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/permissions.php';
require_once INCLUDES_PATH . '/helpers.php';
require_once INCLUDES_PATH . '/catalog.php';
require_once INCLUDES_PATH . '/admin_data.php';
require_once INCLUDES_PATH . '/engagement.php';
require_once INCLUDES_PATH . '/ZoneEstimates.php';

function template_path(string $template): string
{
    $template = ltrim(str_replace('\\', '/', $template), '/');

    if (substr($template, -5) === '.html') {
        $template = substr($template, 0, -5);
    }

    return TEMPLATES_PATH . '/' . $template;
}

function render_template(string $template, array $variables = []): string
{
    $view = new Template(template_path($template));

    foreach ($variables as $name => $value) {
        $view->setContent((string) $name, (string) $value);
    }

    return template_output($view);
}

function template_output(Template $template): string
{
    $hadTemplateUser = array_key_exists('user', $_SESSION);
    $previousTemplateUser = $_SESSION['user'] ?? null;
    $templateUser = is_array($previousTemplateUser) ? $previousTemplateUser : [];

    $_SESSION['user'] = array_merge([
        'username' => $_SESSION['user_email'] ?? '',
        'name' => $_SESSION['user_full_name'] ?? '',
        'surname' => '',
        'email' => $_SESSION['user_email'] ?? '',
    ], $templateUser);

    try {
        return $template->get();
    } finally {
        if ($hadTemplateUser) {
            $_SESSION['user'] = $previousTemplateUser;
        } else {
            unset($_SESSION['user']);
        }
    }
}

function main_nav_html(): string
{
    $html = '<a href="' . e(url_for('index.php')) . '">Home</a>';
    $html .= '<a href="' . e(url_for('search.php')) . '">Cerca stanze</a>';

    if (is_authenticated()) {
        $html .= '<a href="' . e(role_home_url()) . '">Area riservata</a>';
        if (user_has_group('student')) {
            $html .= '<a href="' . e(url_for('account/my-house.php')) . '">La mia casa</a>';
            $html .= '<a href="' . e(url_for('account/favorites.php')) . '">Preferiti</a>';
        }
        // Logout via POST + CSRF (evita il logout tramite semplice link/immagine).
        $html .= '<form method="post" action="' . e(url_for('logout.php')) . '" class="nav-logout-form">'
            . csrf_field('logout')
            . '<button type="submit" class="nav-logout button-link">Logout</button>'
            . '</form>';
    } else {
        $html .= '<a href="' . e(url_for('login.php')) . '">Accedi</a>';
        $html .= '<a href="' . e(url_for('register.php')) . '">Registrati</a>';
    }

    return $html;
}

/*
 * Menu del backend: voci generate dai servizi accessibili all'utente
 * (area=backend e is_menu_item=1). Stessa logica del backend di Fase 2.
 */
function admin_menu_html(string $activeCode = ''): string
{
    $user = current_user();
    if ($user === null) {
        return '';
    }

    $html = '';
    foreach (user_services((int) $user['id']) as $s) {
        if (($s['area'] ?? '') !== 'backend' || (int) ($s['is_menu_item'] ?? 0) !== 1) {
            continue;
        }
        $active = ($s['code'] ?? '') === $activeCode ? ' class="is-active"' : '';
        $html .= '<a' . $active . ' href="' . e(url_for(ltrim((string) $s['path'], '/'))) . '">' . e($s['name']) . '</a>';
    }

    return $html;
}

/* Pagina di amministrazione: contenuto + menu laterale dei servizi backend. */
function render_admin_page(string $title, string $content, string $activeCode = ''): void
{
    $body = '<div class="admin-layout">'
        . '<nav class="admin-side-panel" aria-label="Menu amministrazione">'
        . '<p class="eyebrow">Amministrazione</p>'
        . admin_menu_html($activeCode)
        . '<a href="' . e(url_for('index.php')) . '">Vai al sito</a>'
        . '</nav>'
        . '<div class="admin-main">' . $content . '</div>'
        . '</div>';

    render_page($title, $body, ['body_class' => 'page-admin']);
}

function render_page(string $title, string $content, array $variables = []): void
{
    $flashes = render_flashes();

    $pageVariables = array_merge([
        'app_name' => e(APP_NAME),
        'title' => e($title),
        'content' => $flashes . $content,
        'base_url' => e(rtrim(BASE_URL, '/')),
        'assets_url' => e(rtrim(ASSETS_URL, '/')),
        'nav_links' => main_nav_html(),
        'current_year' => e(date('Y')),
    ], $variables);

    $pageVariables['body_class'] = e((string) ($variables['body_class'] ?? ''));

    echo render_template('layout.html', $pageVariables);
}
