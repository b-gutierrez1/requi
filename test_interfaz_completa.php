<?php
/**
 * Test para simular completamente la interfaz de autorizaciones tal como la verá el usuario
 */

require_once 'vendor/autoload.php';

session_start();

// Simular la sesión exacta del usuario real
$_SESSION['user'] = [
    'id' => 107,
    'email' => 'bgutierrez@sp.iga.edu',
    'nombre' => 'Usuario Actual'
];

echo "=== TEST INTERFAZ COMPLETA DE AUTORIZACIONES ===\n";

try {
    $controller = new \App\Controllers\AutorizacionController();
    
    // Capturar la salida del método index (página principal de autorizaciones)
    ob_start();
    $controller->index();
    $output = ob_get_clean();
    
    // Analizar la salida
    if (strpos($output, 'Requisiciones Pendientes de Revisión') !== false) {
        echo "✅ La sección de revisiones aparece en la página\n";
        
        // Verificar si aparece la requisición 25
        if (strpos($output, 'Requisición #25') !== false) {
            echo "✅ La requisición 25 aparece en la interfaz\n";
        } else {
            echo "❌ La requisición 25 NO aparece en la interfaz\n";
        }
        
        // Contar cuántas requisiciones aparecen
        $count = preg_match_all('/Requisición #(\d+)/', $output, $matches);
        echo "✅ Total de requisiciones mostradas: $count\n";
        if (!empty($matches[1])) {
            echo "   IDs encontrados: " . implode(', ', $matches[1]) . "\n";
        }
        
    } else {
        echo "❌ La sección de revisiones NO aparece en la página\n";
        
        // Verificar si hay algún mensaje de error
        if (strpos($output, '403') !== false || strpos($output, 'sin permisos') !== false) {
            echo "❌ Parece haber un error de permisos\n";
        }
        
        // Mostrar una muestra de la salida para debug
        echo "Muestra de la salida HTML:\n";
        echo substr($output, 0, 500) . "...\n";
    }
    
    // Verificar que no hay errores fatales
    if (strpos($output, 'Fatal error') === false) {
        echo "✅ No hay errores fatales en la interfaz\n";
    } else {
        echo "❌ Se encontraron errores fatales\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . " línea " . $e->getLine() . "\n";
}

echo "\n=== INSTRUCCIONES PARA EL USUARIO ===\n";
echo "1. Ve a http://localhost:8000/autorizaciones\n";
echo "2. Deberías ver una sección 'Requisiciones Pendientes de Revisión'\n";
echo "3. La requisición #25 (prueba2, Q5,002.00) debería aparecer ahí\n";
echo "4. Puedes hacer clic en 'Revisar' para aprobar/rechazar\n";

echo "\n=== FIN TEST ===\n";
?>