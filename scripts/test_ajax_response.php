<?php
// Script para probar respuestas AJAX sin contaminar con output

require_once __DIR__ . '/../vendor/autoload.php';

// Simular petición AJAX
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
$_SERVER['REQUEST_METHOD'] = 'POST';

// Limpiar cualquier output buffer
while (ob_get_level()) {
    ob_end_clean();
}

// Suprimir errores
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(0);

// Iniciar buffer limpio
ob_start();

// Simular respuesta exitosa
$response = [
    'success' => true,
    'message' => 'Operación completada exitosamente',
    'data' => [
        'flujo_estado' => 'autorizado',
        'orden_id' => 29
    ]
];

// Configurar cabeceras si no se han enviado
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
}

// Limpiar buffer y enviar JSON
ob_end_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);

// Verificar que no hay output adicional
$output = ob_get_contents();
if ($output) {
    error_log("Output adicional detectado: " . bin2hex($output));
}






