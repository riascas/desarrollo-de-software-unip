<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';


use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->load();

function getBaseUrl(){
    // Asignar las variables de entorno a las propiedades
    $projectFolder = PHP_SAPI === 'cli-server'
        ? 'frontend'
        : 'TP-Programacion-WEB/frontend';

    $currentDir = dirname(__DIR__);
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    return rtrim($protocol . $host . '/' . $projectFolder, '/') . '/';
}
define('BASE_URL', getBaseUrl());

/* Agregar <?php echo BASE_URL?> cuando se utilice una ruta  */