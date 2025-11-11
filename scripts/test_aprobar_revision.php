<?php
/**
 * Script para probar la aprobación de revisiones
 */

// Limpiar cualquier output
while (ob_get_level()) {
    ob_end_clean();
}

require_once __DIR__ . '/../vendor/autoload.php';

echo "=== PRUEBA DE APROBACIÓN DE REVISIÓN ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Buscar una requisición en estado de revisión
    $conn = \App\Models\Model::getConnection();
    
    echo "1. Buscando requisiciones en revisión...\n";
    
    $sql = "SELECT af.*, oc.nombre_razon_social 
            FROM autorizacion_flujo af 
            INNER JOIN orden_compra oc ON af.orden_compra_id = oc.id 
            WHERE af.estado = 'pendiente_revision' 
            ORDER BY af.id DESC 
            LIMIT 5";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $flujos = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    if (empty($flujos)) {
        echo "   ❌ No se encontraron requisiciones en revisión\n";
        echo "   Creando una requisición de prueba...\n";
        
        // Aquí podrías crear una requisición de prueba si es necesario
        exit(0);
    }
    
    echo "   ✅ Encontradas " . count($flujos) . " requisiciones en revisión\n\n";
    
    foreach ($flujos as $flujo) {
        echo "   Flujo ID: {$flujo['id']}\n";
        echo "   Orden ID: {$flujo['orden_compra_id']}\n";
        echo "   Proveedor: {$flujo['nombre_razon_social']}\n";
        echo "   Estado: {$flujo['estado']}\n";
        echo "\n";
    }
    
    // Tomar el primer flujo
    $flujoId = $flujos[0]['id'];
    $ordenId = $flujos[0]['orden_compra_id'];
    
    echo "2. Probando aprobación del flujo $flujoId (Orden $ordenId)...\n";
    
    // Simular usuario revisor
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['user_id'] = 107; // Usuario de prueba
    $_SESSION['user_email'] = 'test@example.com';
    $_SESSION['user_name'] = 'Usuario Test';
    $_SESSION['is_admin'] = 1;
    $_SESSION['is_revisor'] = 1;
    
    $autorizacionService = new \App\Services\AutorizacionService();
    
    echo "   Ejecutando aprobarRevision()...\n";
    
    try {
        $resultado = $autorizacionService->aprobarRevision($ordenId, 107, 'Aprobación de prueba');
        
        echo "   Resultado recibido:\n";
        echo "   " . json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        
        if ($resultado['success']) {
            echo "\n   ✅ Aprobación exitosa\n";
        } else {
            echo "\n   ❌ Error en aprobación: {$resultado['error']}\n";
        }
        
    } catch (\Exception $e) {
        echo "   ❌ Excepción capturada:\n";
        echo "   Mensaje: " . $e->getMessage() . "\n";
        echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "   Stack trace:\n";
        echo "   " . str_replace("\n", "\n   ", $e->getTraceAsString()) . "\n";
    }
    
    echo "\n3. Verificando estado del flujo después de la aprobación...\n";
    
    $sql = "SELECT * FROM autorizacion_flujo WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$flujoId]);
    $flujoActualizado = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    if ($flujoActualizado) {
        echo "   Estado actual: {$flujoActualizado['estado']}\n";
        echo "   Revisor ID: " . ($flujoActualizado['revisor_id'] ?? 'NULL') . "\n";
        echo "   Fecha revisión: " . ($flujoActualizado['fecha_revision'] ?? 'NULL') . "\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR GENERAL: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== PRUEBA COMPLETADA ===\n";






