<?php
// public/router.php
// Este script actua como un "front controller" para el servidor embebido de PHP,
// simulando el comportamiento de mod_rewrite de Apache.

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// Si la URI no es la raíz y apunta a un archivo que existe físicamente en el
// directorio 'public' (como un CSS, JS o imagen), se sirve directamente.
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Para cualquier otra petición, se carga el index.php principal de la aplicación,
// permitiendo que el router de la aplicación maneje la ruta.
require_once __DIR__ . '/index.php';
