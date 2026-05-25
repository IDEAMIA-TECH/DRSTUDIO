<?php
/**
 * Credenciales para scripts de instalación CLI (solo entorno local).
 * Lee variables de entorno o config.local.php — nunca hardcodear contraseñas aquí.
 */

function installDbCredentials(): array {
    $host = getenv('DRSTUDIO_DB_HOST') ?: 'localhost';
    $user = getenv('DRSTUDIO_DB_USER') ?: '';
    $pass = getenv('DRSTUDIO_DB_PASS') ?: '';
    $name = getenv('DRSTUDIO_DB_NAME') ?: 'dtstudio_main';

    $local = __DIR__ . '/config.local.php';
    if (is_readable($local)) {
        require $local;
        if (defined('DB_HOST')) {
            $host = DB_HOST;
        }
        if (defined('DB_USER')) {
            $user = DB_USER;
        }
        if (defined('DB_PASS')) {
            $pass = DB_PASS;
        }
        if (defined('DB_NAME')) {
            $name = DB_NAME;
        }
    }

    if ($user === '' || $pass === '') {
        throw new RuntimeException(
            'Configure DRSTUDIO_DB_USER y DRSTUDIO_DB_PASS (o cree includes/config.local.php) antes de ejecutar el instalador.'
        );
    }

    return [
        'host' => $host,
        'user' => $user,
        'pass' => $pass,
        'name' => $name,
    ];
}
