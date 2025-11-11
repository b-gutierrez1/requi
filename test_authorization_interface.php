<?php
/**
 * Test para verificar que la interfaz de autorizaciones funciona correctamente
 */

require_once 'vendor/autoload.php';

session_start();

// Simular usuario logueado
$_SESSION['user'] = [
    'id' => 107,
    'email' => 'bgutierrez@sp.iga.edu',
    'nombre' => 'Usuario Actual'
];

echo "=== TEST INTERFAZ DE AUTORIZACIONES ===\n";

try {
    // Crear controlador de autorización
    $controller = new \App\Controllers\AutorizacionController();
    
    // Capturar la salida del método index
    ob_start();
    $controller->index();
    $output = ob_get_clean();
    
    // Verificar si contiene información de requisiciones pendientes
    if (strpos($output, 'requisiciones') !== false) {
        echo "✅ La interfaz se carga correctamente\n";
    } else {
        echo "❌ La interfaz no muestra contenido esperado\n";
    }
    
    // Verificar si no hay errores PHP
    if (strpos($output, 'Fatal error') === false && strpos($output, 'Warning') === false) {
        echo "✅ No se encontraron errores PHP\n";
    } else {
        echo "❌ Se encontraron errores PHP en la interfaz\n";
    }
    
    echo "✅ Test completado exitosamente\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . " línea " . $e->getLine() . "\n";
}

echo "\n=== FIN TEST ===\n";
?>