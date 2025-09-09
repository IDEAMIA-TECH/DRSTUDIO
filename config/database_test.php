<?php
/**
 * Configuración de Base de Datos para Tests - DT Studio
 * Usa SQLite para pruebas locales
 */

return [
    'default' => 'sqlite',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => __DIR__ . '/../database/test.db',
            'prefix' => '',
        ],
        'mysql' => [
            'driver' => 'mysql',
            'host' => '216.18.195.84',
            'port' => '3306',
            'database' => 'dtstudio_main',
            'username' => 'dtstudio_main',
            'password' => 'TkC6E7#o#Ds#m??5',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => 'InnoDB',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ],
    ],
];
