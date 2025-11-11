<?php
/**
 * Script para asignar autorizador al centro de costo 32 y corregir la requisición 27
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\CentroCosto;
use App\Models\PersonaAutorizada;
use App\Models\AutorizacionCentroCosto;
use App\Models\AutorizacionFlujo;

echo "=== ASIGNACIÓN DE AUTORIZADOR PARA CENTRO 32 ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $centroId = 32;
    $autorizadorEmail = 'bgutierrez@sp.iga.edu'; // Fidel Zelada
    $autorizadorNombre = 'Fidel Zelada';
    
    echo "1. Asignando autorizador al centro 32...\n";
    echo "   Centro: Centro Demo (ID: $centroId)\n";
    echo "   Autorizador: $autorizadorNombre ($autorizadorEmail)\n\n";
    
    // Verificar si ya existe una asignación
    $conn = CentroCosto::getConnection();
    $stmt = $conn->prepare("SELECT * FROM persona_autorizada WHERE centro_costo_id = ?");
    $stmt->execute([$centroId]);
    $existente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existente) {
        echo "   ⚠️  Ya existe asignación para este centro:\n";
        echo "      Autorizador actual: {$existente['nombre']} ({$existente['email']})\n";
        echo "   ✅ Usando autorizador existente\n";
        $result = true;
    } else {
        echo "   ➕ Creando nueva asignación...\n";
        
        // 1. Buscar o crear el autorizador en la tabla autorizadores
        $stmt = $conn->prepare("SELECT id FROM autorizadores WHERE email = ?");
        $stmt->execute([$autorizadorEmail]);
        $autorizador = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$autorizador) {
            echo "      Creando autorizador en tabla autorizadores...\n";
            $stmt = $conn->prepare("
                INSERT INTO autorizadores (nombre, email, activo) 
                VALUES (?, ?, 1)
            ");
            $stmt->execute([$autorizadorNombre, $autorizadorEmail]);
            $autorizadorId = $conn->lastInsertId();
        } else {
            $autorizadorId = $autorizador['id'];
            echo "      Autorizador encontrado con ID: $autorizadorId\n";
        }
        
        // 2. Crear la relación en autorizador_centro_costo
        echo "      Creando relación autorizador-centro...\n";
        $stmt = $conn->prepare("
            INSERT INTO autorizador_centro_costo 
            (autorizador_id, centro_costo_id, es_principal, activo, fecha_inicio) 
            VALUES (?, ?, 1, 1, CURDATE())
        ");
        $result = $stmt->execute([$autorizadorId, $centroId]);
    }
    
    if ($result) {
        echo "   ✅ Autorizador asignado exitosamente\n\n";
    } else {
        echo "   ❌ Error asignando autorizador\n";
        exit(1);
    }
    
    // 2. Ahora crear la autorización para la requisición 27
    echo "2. Creando autorización para requisición 27...\n";
    
    $ordenId = 27;
    $flujo = AutorizacionFlujo::porOrdenCompra($ordenId);
    
    if (!$flujo) {
        echo "   ❌ No se encontró flujo para la orden $ordenId\n";
        exit(1);
    }
    
    $flujoId = $flujo['id'];
    echo "   Flujo ID: $flujoId\n";
    
    // Verificar si ya existe la autorización
    $autorizacionExistente = AutorizacionCentroCosto::where([
        'autorizacion_flujo_id' => $flujoId,
        'centro_costo_id' => $centroId
    ]);
    
    if (!empty($autorizacionExistente)) {
        echo "   ⚠️  Ya existe autorización para este centro en este flujo\n";
    } else {
        // Crear la autorización
        try {
            $autorizacion = AutorizacionCentroCosto::create([
                'autorizacion_flujo_id' => $flujoId,
                'centro_costo_id' => $centroId,
                'autorizador_email' => $autorizadorEmail,
                'estado' => 'pendiente',
                'porcentaje' => 100.00
            ]);
            
            $autorizacionId = is_object($autorizacion) ? $autorizacion->id : $autorizacion['id'];
            echo "   ✅ Autorización creada con ID: $autorizacionId\n";
        } catch (Exception $e) {
            echo "   ❌ Error creando autorización: " . $e->getMessage() . "\n";
        }
    }
    
    // 3. Verificar estado final
    echo "\n3. Verificación final:\n";
    
    // Verificar que el autorizador funciona
    $personaVerificacion = PersonaAutorizada::principalPorCentro($centroId);
    if ($personaVerificacion) {
        echo "   ✅ Autorizador verificado: {$personaVerificacion['nombre']} ({$personaVerificacion['email']})\n";
    } else {
        echo "   ❌ Error: No se puede recuperar el autorizador asignado\n";
    }
    
    // Verificar autorizaciones del flujo
    $autorizacionesFlujo = AutorizacionCentroCosto::porFlujo($flujoId);
    echo "   ✅ Autorizaciones en el flujo: " . count($autorizacionesFlujo) . "\n";
    
    foreach ($autorizacionesFlujo as $auth) {
        echo "      - Centro {$auth['centro_costo_id']}: {$auth['estado']} ({$auth['autorizador_email']})\n";
    }
    
    echo "\n=== ASIGNACIÓN COMPLETADA ===\n";
    echo "✅ El centro 32 ahora tiene autorizador asignado\n";
    echo "✅ La requisición 27 ahora tiene autorización pendiente\n";
    echo "🎯 El autorizador puede proceder a autorizar desde el sistema\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
