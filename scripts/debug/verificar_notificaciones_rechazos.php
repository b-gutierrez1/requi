<?php
// Verificar flujo de notificaciones y rechazos

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/app/Models/Model.php';
require_once __DIR__ . '/app/Services/AutorizacionService.php';
require_once __DIR__ . '/app/Services/NotificacionService.php';
require_once __DIR__ . '/app/Services/EmailService.php';

try {
    echo "=== VERIFICACIÓN DE FLUJO DE NOTIFICACIONES Y RECHAZOS ===\n\n";
    
    $pdo = \App\Models\Model::getConnection();
    $autorizacionService = new \App\Services\AutorizacionService();
    
    // CASO 1: Verificar notificaciones en cada paso
    echo "--- CASO 1: FLUJO DE NOTIFICACIONES NORMALES ---\n";
    
    // Buscar una requisición en pendiente_revision
    $stmt = $pdo->prepare("
        SELECT af.requisicion_id, r.numero_requisicion, r.usuario_id, af.id as flujo_id
        FROM autorizacion_flujo af
        JOIN requisiciones r ON af.requisicion_id = r.id
        WHERE af.estado = 'pendiente_revision'
        ORDER BY af.id DESC LIMIT 1
    ");
    $stmt->execute();
    $requisicion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($requisicion) {
        echo "✅ Requisición encontrada: {$requisicion['numero_requisicion']}\n";
        echo "Creada por usuario ID: {$requisicion['usuario_id']}\n\n";
        
        // Verificar qué métodos de notificación existen
        echo "Métodos de notificación disponibles:\n";
        $notifService = new \App\Services\NotificacionService();
        $reflection = new ReflectionClass($notifService);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            if (strpos($method->getName(), 'notificar') === 0) {
                echo "- {$method->getName()}\n";
            }
        }
        echo "\n";
    } else {
        echo "❌ No hay requisiciones en pendiente_revision\n\n";
    }
    
    // CASO 2: Verificar flujo de rechazo en revisión
    echo "--- CASO 2: FLUJO DE RECHAZO EN REVISIÓN ---\n";
    echo "Simulando rechazo en revisión (debe permitir edición)...\n\n";
    
    // Buscar método de rechazo en revisión
    $autorizacionReflection = new ReflectionClass($autorizacionService);
    $rechazarMethods = [];
    
    foreach ($autorizacionReflection->getMethods() as $method) {
        if (strpos($method->getName(), 'rechazar') === 0) {
            $rechazarMethods[] = $method->getName();
        }
    }
    
    echo "Métodos de rechazo disponibles:\n";
    foreach ($rechazarMethods as $method) {
        echo "- $method\n";
    }
    echo "\n";
    
    // CASO 3: Verificar flujo de rechazo en autorizaciones
    echo "--- CASO 3: FLUJO DE RECHAZO EN AUTORIZACIONES ---\n";
    echo "Simulando rechazo en autorizaciones (debe terminar flujo)...\n\n";
    
    // Verificar estados posibles en la tabla autorizacion_flujo
    $stmt = $pdo->prepare("
        SHOW COLUMNS FROM autorizacion_flujo LIKE 'estado'
    ");
    $stmt->execute();
    $estadoColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($estadoColumn) {
        echo "Estados posibles del flujo:\n";
        $estados = $estadoColumn['Type'];
        preg_match_all("/'([^']+)'/", $estados, $matches);
        
        foreach ($matches[1] as $estado) {
            echo "- $estado\n";
        }
        echo "\n";
    }
    
    // CASO 4: Verificar quien puede editar según el estado
    echo "--- CASO 4: PERMISOS DE EDICIÓN SEGÚN ESTADO ---\n";
    
    $estadosEdicion = [
        'borrador' => 'Creador puede editar',
        'pendiente_revision' => 'Solo lectura - en revisión',
        'rechazada_revision' => 'Creador puede editar',
        'pendiente_autorizacion' => 'Solo lectura - en autorización',
        'autorizada' => 'Solo lectura - completada',
        'rechazada' => 'Solo lectura - flujo terminado'
    ];
    
    foreach ($estadosEdicion as $estado => $permiso) {
        echo "- $estado: $permiso\n";
    }
    echo "\n";
    
    // CASO 5: Verificar notificaciones por estado
    echo "--- CASO 5: NOTIFICACIONES POR ESTADO ---\n";
    
    $notificacionesPorEstado = [
        'creada' => 'Revisor',
        'aprobada_revision' => 'Autorizadores especiales (si los hay) o centros',
        'aprobada_especiales' => 'Autorizadores de centros',
        'completamente_autorizada' => 'Creador',
        'rechazada_revision' => 'Creador (para edición)',
        'rechazada_autorizacion' => 'Revisor + Creador (flujo terminado)'
    ];
    
    foreach ($notificacionesPorEstado as $evento => $destinatario) {
        echo "- $evento → $destinatario\n";
    }
    echo "\n";
    
    // CASO 6: Verificar implementación actual
    echo "--- CASO 6: VERIFICAR IMPLEMENTACIÓN ACTUAL ---\n";
    
    // Verificar si existe método para rechazar revisión
    if (method_exists($autorizacionService, 'rechazarRevision')) {
        echo "✅ Método rechazarRevision existe\n";
        
        // Verificar qué hace cuando rechaza revisión
        $method = $autorizacionReflection->getMethod('rechazarRevision');
        echo "Parámetros: ";
        foreach ($method->getParameters() as $param) {
            echo $param->getName() . " ";
        }
        echo "\n";
    } else {
        echo "❌ Método rechazarRevision NO existe\n";
    }
    
    // Verificar si existe método para rechazar autorizaciones
    if (method_exists($autorizacionService, 'rechazarAutorizacion')) {
        echo "✅ Método rechazarAutorizacion existe\n";
    } else {
        echo "❌ Método rechazarAutorizacion NO existe\n";
    }
    
    echo "\n";
    
    // CASO 7: Probar flujo de rechazo real
    echo "--- CASO 7: TEST DE RECHAZO EN REVISIÓN ---\n";
    
    if ($requisicion) {
        echo "Probando rechazo de revisión para requisición {$requisicion['numero_requisicion']}...\n";
        
        try {
            if (method_exists($autorizacionService, 'rechazarRevision')) {
                // No ejecutar realmente, solo verificar estructura
                echo "✅ El método rechazarRevision está disponible\n";
                echo "Parámetros esperados: flujo_id, usuario_id, motivo\n";
                
                // Verificar qué pasa después del rechazo
                echo "Verificando efectos del rechazo en revisión:\n";
                echo "1. ¿Cambia estado a 'rechazada_revision'? → Verificar implementación\n";
                echo "2. ¿Notifica al creador? → Verificar implementación\n";
                echo "3. ¿Permite edición al creador? → Verificar lógica de permisos\n";
            }
        } catch (Exception $e) {
            echo "❌ Error verificando rechazo: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n--- RESUMEN DE VERIFICACIÓN ---\n";
    echo "✅ Estados y flujos identificados\n";
    echo "✅ Métodos de notificación listados\n";
    echo "✅ Lógica de permisos clarificada\n";
    echo "⚠️  Se requiere verificar implementación específica de:\n";
    echo "   - Notificaciones automáticas en cada paso\n";
    echo "   - Rechazo en revisión → permite edición\n";
    echo "   - Rechazo en autorizaciones → termina flujo\n";
    echo "   - Notificaciones a revisor + creador en rechazo final\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
?>