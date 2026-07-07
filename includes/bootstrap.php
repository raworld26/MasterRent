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
require_once INCLUDES_PATH . '/admin.php';

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
    $links = [
        ['index.php', 'Home'],
        ['search.php', 'Cerca stanze'],
    ];

    if (is_authenticated()) {
        $links[] = [dashboard_path_for_current_user(), 'Area riservata'];
    } else {
        $links[] = ['login.php', 'Accedi'];
        $links[] = ['register.php', 'Registrati'];
    }

    $html = '';
    foreach ($links as [$path, $label]) {
        $html .= '<a href="' . e(url_for($path)) . '">' . e($label) . '</a>';
    }

    if (is_authenticated()) {
        $html .= '<form method="POST" action="' . e(url_for('logout.php')) . '" class="inline-form nav-logout">'
            . csrf_field('logout')
            . '<button type="submit" class="link-button">Logout</button>'
            . '</form>';
    }

    return $html;
}

function render_page(string $title, string $content, array $variables = []): void
{
    $pageVariables = array_merge([
        'app_name' => e(APP_NAME),
        'title' => e($title),
        'content' => $content,
        'base_url' => e(rtrim(BASE_URL, '/')),
        'assets_url' => e(rtrim(ASSETS_URL, '/')),
        'nav_links' => main_nav_html(),
        'current_year' => e(date('Y')),
    ], $variables);

    $pageVariables['body_class'] = e((string) ($variables['body_class'] ?? ''));

    echo render_template('layout.html', $pageVariables);
}
