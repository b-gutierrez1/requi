<?php
/**
 * Test que simula exactamente acceder a la URL real
 */

// Simular una petición GET a /autorizaciones
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/autorizaciones';
$_SERVER['HTTP_HOST'] = 'localhost:8000';

require_once 'vendor/autoload.php';

session_start();

// Verificar si hay sesión activa
if (empty($_SESSION['user'])) {
    echo "❌ No hay sesión activa. Por favor:\n";
    echo "1. Ve a http://localhost:8000/login\n";
    echo "2. Inicia sesión con tu usuario\n";
    echo "3. Luego ve a http://localhost:8000/autorizaciones\n";
    exit;
}

echo "=== TEST URL REAL: /autorizaciones ===\n";

try {
    $usuarioEmail = $_SESSION['user']['email'] ?? $_SESSION['user_email'] ?? 'N/A';
    echo "Usuario logueado: $usuarioEmail\n";
    
    // Simular exactamente lo que hace el router
    $controller = new \App\Controllers\AutorizacionController();
    
    echo "\n=== EJECUTANDO AutorizacionController::index() ===\n";
    
    // Capturar toda la salida
    ob_start();
    $controller->index();
    $html = ob_get_clean();
    
    // Analizar la respuesta
    if (empty($html)) {
        echo "❌ El controlador no devolvió HTML\n";
        exit;
    }
    
    echo "✅ Controlador ejecutado correctamente\n";
    echo "Longitud del HTML: " . strlen($html) . " caracteres\n";
    
    // Verificar contenido específico
    $checks = [
        'Requisiciones Pendientes de Revisión' => strpos($html, 'Requisiciones Pendientes de Revisión') !== false,
        'Requisición #25' => strpos($html, 'Requisición #25') !== false,
        'prueba2' => strpos($html, 'prueba2') !== false,
        'Q5,002.00' => strpos($html, 'Q5,002.00') !== false || strpos($html, 'Q5002.00') !== false,
        'btn-primary' => strpos($html, 'btn-primary') !== false,  // Botón de Revisar
        'Autorizaciones Pendientes' => strpos($html, 'Autorizaciones Pendientes') !== false
    ];
    
    echo "\n=== CONTENIDO ENCONTRADO ===\n";
    foreach ($checks as $item => $found) {
        echo ($found ? '✅' : '❌') . " $item\n";
    }
    
    // Contar secciones
    $seccionRevision = preg_match('/Requisiciones Pendientes de Revisión/', $html);
    $seccionCentros = preg_match('/centro de costo|Centro de Costo/', $html);
    
    echo "\n=== ESTRUCTURA DE LA PÁGINA ===\n";
    echo "Sección de Revisión: " . ($seccionRevision ? 'PRESENTE' : 'AUSENTE') . "\n";
    echo "Sección de Centros: " . ($seccionCentros ? 'PRESENTE' : 'AUSENTE') . "\n";
    
    // Si no aparece la sección de revisión, investigar por qué
    if (!$seccionRevision) {
        echo "\n❌ PROBLEMA: La sección de revisión no aparece\n";
        
        // Verificar si la variable llegó vacía
        if (strpos($html, 'empty($requisiciones_pendientes_revision)') !== false) {
            echo "Causa: La variable \$requisiciones_pendientes_revision está vacía\n";
        }
        
        // Mostrar un fragmento del HTML para debug
        echo "\nFragmento del HTML (primeros 1000 caracteres):\n";
        echo "----------------------------------------\n";
        echo substr($html, 0, 1000) . "...\n";
        echo "----------------------------------------\n";
    } else {
        echo "\n✅ ÉXITO: La sección de revisión está presente\n";
        
        if (!strpos($html, 'Requisición #25')) {
            echo "⚠️ NOTA: La requisición #25 no aparece en la lista\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . " línea " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN TEST ===\n";
?>