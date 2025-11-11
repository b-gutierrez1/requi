<?php
/**
 * Test final para verificar que la requisición 25 aparece en autorizaciones
 */

require_once 'vendor/autoload.php';

session_start();

// Simular usuario revisor
$_SESSION['user'] = [
    'id' => 107,
    'email' => 'bgutierrez@sp.iga.edu',
    'nombre' => 'Usuario Actual'
];

echo "=== TEST FINAL REQUISICIÓN 25 ===\n";

try {
    // Verificar directamente con AutorizacionService
    $autorizacionService = new \App\Services\AutorizacionService();
    $pendientesRevision = $autorizacionService->getRequisicionesPendientesRevision();
    
    echo "Requisiciones pendientes de revisión encontradas: " . count($pendientesRevision) . "\n";
    
    $encontrada25 = false;
    foreach ($pendientesRevision as $req) {
        if (isset($req['orden_id']) && $req['orden_id'] == 25) {
            $encontrada25 = true;
            echo "✅ REQUISICIÓN 25 ENCONTRADA:\n";
            echo "   - ID Flujo: " . $req['id'] . "\n";
            echo "   - ID Orden: " . $req['orden_id'] . "\n";
            echo "   - Proveedor: " . $req['nombre_razon_social'] . "\n";
            echo "   - Estado: " . $req['estado'] . "\n";
            echo "   - Monto: Q" . number_format($req['monto_total'], 2) . "\n";
            break;
        }
    }
    
    if (!$encontrada25) {
        echo "❌ Requisición 25 NO encontrada en la lista\n";
        echo "Requisiciones encontradas:\n";
        foreach ($pendientesRevision as $i => $req) {
            echo "  [$i] Flujo ID: " . $req['id'] . ", Orden ID: " . ($req['orden_id'] ?? 'N/A') . ", Proveedor: " . $req['nombre_razon_social'] . "\n";
        }
    } else {
        echo "✅ ÉXITO: La requisición 25 ahora aparece correctamente en autorizaciones para aprobar!\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN TEST ===\n";
?>