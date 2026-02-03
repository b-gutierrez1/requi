<?php
// Análisis de implementación de rechazos sin cargar dependencias

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    echo "=== ANÁLISIS DE IMPLEMENTACIÓN DE RECHAZOS ===\n\n";
    
    // CASO 1: Analizar rechazarRevision en AutorizacionService
    echo "--- CASO 1: RECHAZO EN REVISIÓN ---\n";
    
    $serviceFile = __DIR__ . '/app/Services/AutorizacionService.php';
    $serviceCode = file_get_contents($serviceFile);
    
    // Buscar el método rechazarRevision
    $pattern = '/public function rechazarRevision.*?\n\s*\{(.*?)\n\s*\}/s';
    if (preg_match($pattern, $serviceCode, $matches)) {
        $methodCode = $matches[1];
        
        echo "✅ Método rechazarRevision encontrado\n";
        
        // Verificar si llama a notificaciones correctas
        if (strpos($methodCode, 'notificarRechazoRevision') !== false) {
            echo "✅ Llama a notificarRechazoRevision (CORRECTO)\n";
        } elseif (strpos($methodCode, 'notificarRechazo') !== false) {
            echo "❌ Llama a notificarRechazo genérico (DEBERÍA ser notificarRechazoRevision)\n";
        } else {
            echo "❌ NO llama a notificaciones\n";
        }
        
        // Verificar si llama al modelo AutorizacionFlujo
        if (strpos($methodCode, 'AutorizacionFlujo::rechazarRevision') !== false) {
            echo "✅ Llama a AutorizacionFlujo::rechazarRevision\n";
        } else {
            echo "❌ NO llama al modelo\n";
        }
        
    } else {
        echo "❌ Método rechazarRevision NO encontrado\n";
    }
    
    // CASO 2: Analizar rechazarRevision en AutorizacionFlujo
    echo "\n--- CASO 2: RECHAZO EN MODELO AUTORIZACION_FLUJO ---\n";
    
    $flujoFile = __DIR__ . '/app/Models/AutorizacionFlujo.php';
    $flujoCode = file_get_contents($flujoFile);
    
    // Buscar el método estático rechazarRevision
    $pattern = '/public static function rechazarRevision.*?\n\s*\{(.*?)\n\s*\}/s';
    if (preg_match($pattern, $flujoCode, $matches)) {
        $methodCode = $matches[1];
        
        echo "✅ Método rechazarRevision en modelo encontrado\n";
        
        // Verificar estado que se asigna
        if (strpos($methodCode, 'ESTADO_RECHAZADO_REVISION') !== false || 
            strpos($methodCode, 'rechazado_revision') !== false) {
            echo "✅ Asigna estado rechazado_revision (CORRECTO)\n";
        } else {
            echo "❌ NO asigna estado correcto\n";
        }
        
        // Verificar si registra en historial
        if (strpos($methodCode, 'HistorialRequisicion::registrarRechazo') !== false) {
            echo "✅ Registra en historial\n";
        } else {
            echo "❌ NO registra en historial\n";
        }
        
    } else {
        echo "❌ Método rechazarRevision en modelo NO encontrado\n";
    }
    
    // CASO 3: Analizar rechazos en autorizaciones
    echo "\n--- CASO 3: RECHAZOS EN AUTORIZACIONES ---\n";
    
    $metodosRechazo = [
        'rechazarCentroCosto',
        'rechazarAutorizacionPago', 
        'rechazarAutorizacionCuenta'
    ];
    
    foreach ($metodosRechazo as $metodo) {
        echo "\nAnalizando $metodo:\n";
        
        $pattern = "/public function $metodo.*?\n\s*\{(.*?)(?=\n\s*public|\n\s*\}\s*\n\s*\}|\Z)/s";
        if (preg_match($pattern, $serviceCode, $matches)) {
            $methodCode = $matches[1];
            
            echo "✅ Método $metodo existe\n";
            
            // Verificar si marca flujo como rechazado
            if (strpos($methodCode, 'marcarComoRechazado') !== false ||
                strpos($methodCode, 'ESTADO_RECHAZADO') !== false ||
                strpos($methodCode, "'rechazado'") !== false) {
                echo "   ✅ Marca flujo como rechazado\n";
            } else {
                echo "   ❌ NO marca flujo como rechazado (FALTA IMPLEMENTAR)\n";
            }
            
            // Verificar si notifica
            if (strpos($methodCode, 'notificarRechazo') !== false) {
                echo "   ✅ Envía notificaciones\n";
            } else {
                echo "   ❌ NO envía notificaciones (FALTA IMPLEMENTAR)\n";
            }
            
        } else {
            echo "❌ Método $metodo NO encontrado\n";
        }
    }
    
    // CASO 4: Verificar constantes de estado en AutorizacionFlujo
    echo "\n--- CASO 4: ESTADOS EN AUTORIZACION_FLUJO ---\n";
    
    // Buscar constantes de estado
    $pattern = '/const\s+ESTADO_([A-Z_]+)\s*=\s*[\'"]([^\'"]+)[\'"]/';
    preg_match_all($pattern, $flujoCode, $matches);
    
    if (!empty($matches[1])) {
        echo "Estados definidos:\n";
        for ($i = 0; $i < count($matches[1]); $i++) {
            $constName = $matches[1][$i];
            $constValue = $matches[2][$i];
            echo "- ESTADO_$constName = '$constValue'\n";
        }
        
        // Verificar que existan los estados necesarios
        $estadosNecesarios = [
            'PENDIENTE_REVISION',
            'RECHAZADO_REVISION', 
            'PENDIENTE_AUTORIZACION',
            'RECHAZADO',
            'AUTORIZADO'
        ];
        
        echo "\nEstados necesarios:\n";
        foreach ($estadosNecesarios as $estado) {
            if (in_array($estado, $matches[1])) {
                echo "✅ ESTADO_$estado definido\n";
            } else {
                echo "❌ ESTADO_$estado NO definido\n";
            }
        }
        
    } else {
        echo "❌ No se encontraron constantes de estado\n";
    }
    
    // CASO 5: Analizar NotificacionService
    echo "\n--- CASO 5: SERVICIO DE NOTIFICACIONES ---\n";
    
    $notifFile = __DIR__ . '/app/Services/NotificacionService.php';
    if (file_exists($notifFile)) {
        $notifCode = file_get_contents($notifFile);
        
        $metodosNotif = [
            'notificarRechazoRevision' => 'Para rechazo en revisión (al creador)',
            'notificarRechazo' => 'Para rechazo en autorizaciones (revisor + creador)',
            'notificarNuevaRequisicion' => 'Al crear requisición (al revisor)',
            'notificarAprobacionRevision' => 'Al aprobar revisión (a autorizadores)',
            'notificarAutorizacionCompleta' => 'Al completar flujo (al creador)'
        ];
        
        foreach ($metodosNotif as $metodo => $proposito) {
            if (strpos($notifCode, "function $metodo") !== false) {
                echo "✅ $metodo existe - $proposito\n";
            } else {
                echo "❌ $metodo NO existe - $proposito\n";
            }
        }
        
    } else {
        echo "❌ NotificacionService.php no encontrado\n";
    }
    
    // REPORTE FINAL
    echo "\n=== REPORTE FINAL ===\n\n";
    
    echo "🎯 FLUJO CORRECTO SEGÚN ESPECIFICACIONES:\n\n";
    
    echo "1. RECHAZO EN REVISIÓN:\n";
    echo "   - Estado: pendiente_revision → rechazado_revision\n";
    echo "   - Acción: Regresa al creador para edición\n";
    echo "   - Notificación: Solo al creador (notificarRechazoRevision)\n";
    echo "   - Edición: ✅ Permitida al creador\n\n";
    
    echo "2. RECHAZO EN AUTORIZACIONES:\n";
    echo "   - Estado: pendiente_autorizacion → rechazado\n";
    echo "   - Acción: Flujo TERMINADO definitivamente\n";
    echo "   - Notificación: Al revisor + creador (notificarRechazo)\n";
    echo "   - Edición: ❌ NO permitida (flujo cerrado)\n\n";
    
    echo "3. NOTIFICACIONES POR PASO:\n";
    echo "   - Crear → Revisor\n";
    echo "   - Aprobar revisión → Autorizadores especiales/centros\n";
    echo "   - Aprobar especiales → Autorizadores centros\n";
    echo "   - Completar → Creador\n\n";
    
    echo "📋 PENDIENTES DE VERIFICACIÓN:\n";
    echo "- Verificar que rechazarRevision() llame a notificarRechazoRevision()\n";
    echo "- Implementar marcado de flujo como 'rechazado' en rechazos de autorizaciones\n";
    echo "- Verificar que notificarRechazo() notifique a revisor Y creador\n";
    echo "- Verificar permisos de edición según estado de la requisición\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
?>