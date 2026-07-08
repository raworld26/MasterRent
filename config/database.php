<?php

declare(strict_types=1);

/*
 * Connessione al database (PDO, singleton).
 * Gli errori diventano eccezioni così da essere gestiti dal livello applicativo.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        error_log('[MasterRent] Database connection failed: ' . $exception->getMessage());
        http_response_code(500);
        exit('Connessione al database non disponibile. Verifica che MySQL sia attivo e che il database "' . DB_NAME . '" sia stato importato.');
    }

    return $pdo;
}
