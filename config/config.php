<?php

declare(strict_types=1);

define('APP_NAME', 'MasteRent');

define('DB_HOST', '127.0.0.1;port=3307');
define('DB_NAME', 'masterrent');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/public/index.php');
$publicPos = strpos($scriptName, '/public');

if ($publicPos !== false) {
    $detectedProjectUrl = substr($scriptName, 0, $publicPos);
    $detectedBaseUrl = $detectedProjectUrl . '/public';
} else {
    $detectedBaseUrl = '';
    $detectedProjectUrl = '';
}

define('BASE_URL', getenv('MASTERRENT_BASE_URL') ?: $detectedBaseUrl);
define('ASSETS_URL', getenv('MASTERRENT_ASSETS_URL') ?: rtrim(BASE_URL, '/') . '/assets');

