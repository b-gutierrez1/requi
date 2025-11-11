<?php
/**
 * Debug para verificar qué variables llegan a la vista de autorizaciones
 */

require_once 'vendor/autoload.php';

session_start();

// Simular usuario revisor
$_SESSION['user'] = [
    'id' => 107,
    'email' => 'bgutierrez@sp.iga.edu',
    'nombre' => 'Usuario Actual'
];

echo "=== DEBUG VISTA AUTORIZACIONES ===\n";

try {
    // Simular exactamente lo que hace el controlador
    $controller = new \App\Controllers\AutorizacionController();
    $autorizacionService = new \App\Services\AutorizacionService();
    
    $usuarioEmail = 'bgutierrez@sp.iga.edu';
    
    // Obtener los datos exactos que se pasan a la vista
    $requisicionesPendientesRevision = [];
    
    // Verificar si es revisor usando la misma lógica del controlador
    $esRevisorSession = \App\Helpers\Session::isRevisor();
    echo "Session::isRevisor(): " . ($esRevisorSession ? 'SÍ' : 'NO') . "\n";
    
    // Usar reflexión para acceder al método privado isRevisorPorEmail
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('isRevisorPorEmail');
    $method->setAccessible(true);
    $esRevisorPorEmail = $method->invokeArgs($controller, [$usuarioEmail]);
    echo "isRevisorPorEmail(): " . ($esRevisorPorEmail ? 'SÍ' : 'NO') . "\n";
    
    $esRevisor = $esRevisorSession || $esRevisorPorEmail;
    echo "Es revisor final: " . ($esRevisor ? 'SÍ' : 'NO') . "\n";
    
    if ($esRevisor) {
        $requisicionesPendientesRevision = $autorizacionService->getRequisicionesPendientesRevision();
        echo "Requisiciones obtenidas: " . count($requisicionesPendientesRevision) . "\n";
        
        if (empty($requisicionesPendientesRevision)) {
            echo "❌ PROBLEMA: La lista está vacía aunque es revisor\n";
        } else {
            echo "✅ Lista de requisiciones:\n";
            foreach ($requisicionesPendientesRevision as $i => $req) {
                echo "  [$i] ID: " . ($req['orden_id'] ?? 'N/A') . ", Proveedor: " . ($req['nombre_razon_social'] ?? 'N/A') . "\n";
            }
        }
    } else {
        echo "❌ PROBLEMA: El usuario no es considerado revisor\n";
    }
    
    echo "\nVariable que se pasa a la vista:\n";
    echo "empty(\$requisiciones_pendientes_revision): " . (empty($requisicionesPendientesRevision) ? 'SÍ (no se muestra sección)' : 'NO (se muestra sección)') . "\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . " línea " . $e->getLine() . "\n";
}

echo "\n=== FIN DEBUG ===\n";
?>