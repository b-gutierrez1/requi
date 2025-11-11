<?php
/**
 * Script para probar la velocidad de creación de requisiciones
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\RequisicionService;
use App\Services\AutorizacionService;
use App\Services\NotificacionService;

echo "=== PRUEBA DE VELOCIDAD DE CREACIÓN ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Datos de prueba para una requisición simple
    $datosRequisicion = [
        'nombre_razon_social' => 'Proveedor Test Velocidad',
        'fecha' => date('Y-m-d'),
        'causal_compra' => 'Prueba de velocidad',
        'moneda' => 'GTQ',
        'forma_pago' => 'contado',
        'anticipo' => 0,
        'unidad_requirente' => 1, // Asumiendo que existe
        'justificacion' => 'Prueba de rendimiento del sistema',
        'datos_proveedor' => 'Proveedor de prueba para medir velocidad',
        'razon_seleccion' => 'Seleccionado para prueba de velocidad',
        
        // Items
        'items' => [
            [
                'descripcion' => 'Item de prueba 1',
                'cantidad' => 1,
                'precio_unitario' => 100.00,
                'total' => 100.00
            ]
        ],
        
        // Distribución (usar centro que sabemos que existe)
        'distribucion' => [
            [
                'centro_costo_id' => 1, // PARQUEO GENERAL
                'cuenta_contable_id' => 1,
                'porcentaje' => 100.00,
                'cantidad' => 100.00,
                'factura' => 'Factura 1'
            ]
        ],
        
        'monto_total' => 100.00
    ];
    
    $usuarioId = 107; // Usuario de prueba
    
    echo "1. Iniciando prueba de creación...\n";
    echo "   Datos: {$datosRequisicion['nombre_razon_social']}\n";
    echo "   Monto: Q " . number_format($datosRequisicion['monto_total'], 2) . "\n\n";
    
    // Medir tiempo de creación
    $tiempoInicio = microtime(true);
    
    // Crear el servicio
    $autorizacionService = new AutorizacionService(new NotificacionService());
    $requisicionService = new RequisicionService($autorizacionService);
    
    echo "2. Ejecutando creación...\n";
    
    // Crear la requisición
    $resultado = $requisicionService->crearRequisicion($datosRequisicion, $usuarioId);
    
    $tiempoFin = microtime(true);
    $tiempoTotal = $tiempoFin - $tiempoInicio;
    
    echo "\n3. Resultado:\n";
    
    if ($resultado['success']) {
        echo "   ✅ Requisición creada exitosamente\n";
        echo "   📋 ID de orden: {$resultado['orden_id']}\n";
        echo "   💰 Monto total: Q " . number_format($resultado['monto_total'], 2) . "\n";
        echo "   ⏱️  Tiempo total: " . number_format($tiempoTotal, 3) . " segundos\n";
        
        // Clasificar velocidad
        if ($tiempoTotal < 2) {
            echo "   🚀 Velocidad: EXCELENTE (< 2s)\n";
        } elseif ($tiempoTotal < 5) {
            echo "   ✅ Velocidad: BUENA (2-5s)\n";
        } elseif ($tiempoTotal < 10) {
            echo "   ⚠️  Velocidad: ACEPTABLE (5-10s)\n";
        } else {
            echo "   ❌ Velocidad: LENTA (> 10s)\n";
        }
        
        // Verificar que el flujo se creó correctamente
        echo "\n4. Verificando flujo de autorización...\n";
        
        $flujo = \App\Models\AutorizacionFlujo::porOrdenCompra($resultado['orden_id']);
        
        if ($flujo) {
            echo "   ✅ Flujo creado: ID {$flujo['id']}, Estado: {$flujo['estado']}\n";
        } else {
            echo "   ❌ Error: No se creó el flujo de autorización\n";
        }
        
    } else {
        echo "   ❌ Error creando requisición: {$resultado['error']}\n";
        echo "   ⏱️  Tiempo hasta error: " . number_format($tiempoTotal, 3) . " segundos\n";
    }
    
    echo "\n=== PRUEBA COMPLETADA ===\n";
    
    if ($resultado['success']) {
        echo "✅ El sistema está funcionando correctamente\n";
        echo "🎯 Tiempo de respuesta: " . number_format($tiempoTotal, 3) . "s\n";
        
        if ($tiempoTotal > 10) {
            echo "\n💡 SUGERENCIAS PARA MEJORAR VELOCIDAD:\n";
            echo "   - Verificar conexión a base de datos\n";
            echo "   - Revisar logs de errores en PHP\n";
            echo "   - Optimizar consultas SQL\n";
            echo "   - Aumentar recursos del servidor\n";
        }
    }
    
} catch (Exception $e) {
    $tiempoError = microtime(true) - $tiempoInicio;
    echo "ERROR CRÍTICO después de " . number_format($tiempoError, 3) . " segundos:\n";
    echo $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
