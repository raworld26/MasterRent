<?php

declare(strict_types=1);

/*
 * MasterRent - Fase 2 (ricostruzione assistita da LLM)
 * Configurazione applicativa: identità, database e URL di base.
 *
 * NOTA: la Fase 2 usa un database dedicato così da poter
 * convivere con la Fase 1 (`masterrent`) sulla stessa installazione XAMPP.
 */

define('APP_NAME', 'MasterRent');
define('APP_TAGLINE', "Affitti universitari curati a L'Aquila");

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'uniaffitti');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/* Limiti per l'upload delle immagini degli annunci. */
define('UPLOAD_MAX_BYTES', 4 * 1024 * 1024); // 4 MB
define('UPLOAD_ALLOWED_MIME', 'image/jpeg,image/png,image/webp');

/* Servizi mappa usati per geocodifica indirizzi e calcolo distanze in Fase 2. */
define('MAP_GEOCODER_URL', getenv('MAP_GEOCODER_URL') ?: 'https://nominatim.openstreetmap.org/search');
define('MAP_ROUTER_TABLE_URL', getenv('MAP_ROUTER_TABLE_URL') ?: 'https://router.project-osrm.org/table/v1/driving');
define('MAP_HTTP_USER_AGENT', getenv('MAP_HTTP_USER_AGENT') ?: APP_NAME . '/1.0 local-project');

/*
 * Rilevamento automatico degli URL di base.
 * La document root è la cartella `public/`. BASE_URL punta a `.../public`,
 * ASSETS_URL alla cartella `public/assets`.
 */
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$baseUrl = '';

/*
 * Ancoriamo il base URL alla cartella `public/` REALE sul filesystem: dal path
 * dello script in esecuzione ricaviamo la sua posizione relativa dentro public/
 * e la rimuoviamo dalla SCRIPT_NAME. Funziona sia col server PHP integrato
 * (document root = public) sia con Apache in una sottocartella di htdocs,
 * e a qualunque profondità (es. /admin/index.php).
 */
$publicFsRoot = rtrim(str_replace('\\', '/', dirname(__DIR__) . '/public'), '/');
$scriptFs = str_replace('\\', '/', (string) realpath($_SERVER['SCRIPT_FILENAME'] ?? ''));

if ($scriptFs !== '' && stripos($scriptFs, $publicFsRoot) === 0) {
    $relative = substr($scriptFs, strlen($publicFsRoot)); // es. /admin/index.php
    if ($relative !== '' && strlen($scriptName) >= strlen($relative)
        && strcasecmp(substr($scriptName, -strlen($relative)), $relative) === 0) {
        $baseUrl = substr($scriptName, 0, strlen($scriptName) - strlen($relative));
    }
} else {
    // Ripiego: se l'URL contiene "/public", usalo come marcatore.
    $publicPos = strpos($scriptName, '/public');
    if ($publicPos !== false) {
        $baseUrl = substr($scriptName, 0, $publicPos) . '/public';
    }
}

$baseUrl = rtrim($baseUrl, '/');

$configuredBaseUrl = getenv('RENTMASTER_BASE_URL');
$configuredAssetsUrl = getenv('RENTMASTER_ASSETS_URL');

define('BASE_URL', $configuredBaseUrl ?: $baseUrl);
define('ASSETS_URL', $configuredAssetsUrl ?: rtrim(BASE_URL, '/') . '/assets');
define('UPLOADS_DIR', dirname(__DIR__) . '/public/assets/uploads');
define('UPLOADS_URL', rtrim(ASSETS_URL, '/') . '/uploads');
