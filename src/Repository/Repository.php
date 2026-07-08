<?php

declare(strict_types=1);

/*
 * Classe base del livello di accesso ai dati.
 * I repository concreti incapsulano le query SQL di un'area di dominio,
 * mantenendole fuori dai controller e dai template.
 */
abstract class Repository
{
    protected function db(): PDO
    {
        return db();
    }
}
