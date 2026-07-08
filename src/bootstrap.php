<?php

declare(strict_types=1);

/*
 * Punto di accensione dell'applicazione (Fase 2).
 * Ogni controller in /public include questo file come prima cosa.
 */

define('PROJECT_ROOT', dirname(__DIR__));
define('SRC_PATH', PROJECT_ROOT . '/src');
define('CONFIG_PATH', PROJECT_ROOT . '/config');
define('TEMPLATES_PATH', PROJECT_ROOT . '/templates');

require_once CONFIG_PATH . '/config.php';
require_once SRC_PATH . '/security.php';

start_secure_session();

require_once CONFIG_PATH . '/database.php';
require_once PROJECT_ROOT . '/template2.inc.php';

/*
 * Autoloader minimale (niente Composer/framework): risolve solo le classi
 * del livello dati in src/Repository/.
 */
spl_autoload_register(static function (string $class): void {
    $file = SRC_PATH . '/Repository/' . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

require_once SRC_PATH . '/auth.php';
require_once SRC_PATH . '/permissions.php';
require_once SRC_PATH . '/view.php';
require_once SRC_PATH . '/helpers.php';
require_once SRC_PATH . '/components.php';
require_once SRC_PATH . '/MapDistanceService.php';
