<?php
/**
 * Debug para verificar permisos de revisor
 */

require_once 'vendor/autoload.php';

session_start();

echo "=== DEBUG PERMISOS REVISOR ===\n";

// Simular usuario logueado (usar el usuario que estés usando)
$_SESSION['user'] = [
    'id' => 107,
    'email' => 'bgutierrez@sp.iga.edu', // Cambiar por tu email real si es diferente
    'nombre' => 'Usuario Actual'
];

try {
    $usuarioEmail = $_SESSION['user']['email'];
    echo "Usuario email: $usuarioEmail\n";
    
    // Verificar usando Session helper
    echo "Es revisor (Session): " . (\App\Helpers\Session::isRevisor() ? 'SÍ' : 'NO') . "\n";
    
    // Verificar usando DashboardController logic
    $dashboard = new \App\Controllers\DashboardController();
    $reflectionClass = new \ReflectionClass($dashboard);
    $method = $reflectionClass->getMethod('isRevisorPorEmail');
    $method->setAccessible(true);
    $esRevisorPorEmail = $method->invokeArgs($dashboard, [$usuarioEmail]);
    echo "Es revisor por email (Dashboard): " . ($esRevisorPorEmail ? 'SÍ' : 'NO') . "\n";
    
    // Verificar si hay requisiciones pendientes de revisión
    $autorizacionService = new \App\Services\AutorizacionService();
    $pendientesRevision = $autorizacionService->getRequisicionesPendientesRevision();
    echo "Requisiciones pendientes de revisión: " . count($pendientesRevision) . "\n";
    
    foreach ($pendientesRevision as $i => $req) {
        echo "  [$i] ID: {$req['id']}, Proveedor: {$req['nombre_razon_social']}, Estado: {$req['estado_flujo']}\n";
    }
    
    // También verificar autorizaciones por centro de costo
    $autorizacionesPendientes = $autorizacionService->getAutorizacionesPendientes($usuarioEmail);
    echo "Autorizaciones pendientes por centro: " . count($autorizacionesPendientes) . "\n";
    
    foreach ($autorizacionesPendientes as $i => $auth) {
        echo "  [$i] Orden: {$auth['orden_id']}, Centro: {$auth['centro_nombre']}, Proveedor: {$auth['nombre_razon_social']}\n";
    }
    
    // Verificar todas las autorizaciones unificadas
    $todasAutorizaciones = $autorizacionService->getTodasAutorizacionesPendientes($usuarioEmail);
    echo "Todas las autorizaciones unificadas: " . count($todasAutorizaciones) . "\n";
    
    foreach ($todasAutorizaciones as $i => $auth) {
        $detalle = isset($auth['detalle']) ? $auth['detalle'] : 'N/A';
        echo "  [$i] Tipo: {$auth['tipo_flujo']}, Orden: {$auth['orden_id']}, Detalle: $detalle\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . " línea " . $e->getLine() . "\n";
}

echo "\n=== FIN DEBUG ===\n";
?>