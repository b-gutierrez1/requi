<?php
// Crear requisición específica con autorizaciones especiales

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/app/Models/Model.php';

try {
    echo "=== CREAR REQUISICIÓN CON AUTORIZACIONES ESPECIALES ===\n";
    
    $pdo = \App\Models\Model::getConnection();
    
    // PASO 1: Verificar configuración de autorizaciones especiales
    echo "--- PASO 1: Verificar configuración disponible ---\n";
    
    echo "Formas de pago que requieren autorización especial:\n";
    $stmt = $pdo->prepare("SELECT metodo_pago, autorizador_email FROM autorizadores_metodos_pago");
    $stmt->execute();
    $metodosEspeciales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($metodosEspeciales as $metodo) {
        echo "- {$metodo['metodo_pago']} → {$metodo['autorizador_email']}\n";
    }
    
    echo "\nCuentas contables que requieren autorización especial:\n";
    $stmt = $pdo->prepare("
        SELECT cc.id, cc.descripcion, acc.autorizador_email 
        FROM autorizadores_cuentas_contables acc
        JOIN cuenta_contable cc ON acc.cuenta_contable_id = cc.id
    ");
    $stmt->execute();
    $cuentasEspeciales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($cuentasEspeciales as $cuenta) {
        echo "- {$cuenta['descripcion']} (ID: {$cuenta['id']}) → {$cuenta['autorizador_email']}\n";
    }
    
    if (empty($metodosEspeciales) && empty($cuentasEspeciales)) {
        echo "❌ No hay configuraciones de autorizaciones especiales\n";
        echo "Creando configuraciones de prueba...\n";
        
        // Crear configuración de método de pago especial
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO autorizadores_metodos_pago 
            (metodo_pago, descripcion, autorizador_email) VALUES
            ('tarjeta_credito_especial', 'Tarjeta de Crédito Especial', 'bguti@sp.iga.edu')
        ");
        $stmt->execute();
        
        // Crear configuración de cuenta contable especial (usando cuenta 336)
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO autorizadores_cuentas_contables 
            (cuenta_contable_id, autorizador_email) VALUES
            (336, 'bgutierrez@sp.iga.edu')
        ");
        $stmt->execute();
        
        echo "✅ Configuraciones de prueba creadas\n\n";
        $formaPagoEspecial = 'tarjeta_credito_especial';
        $cuentaEspecial = 336;
    } else {
        $formaPagoEspecial = $metodosEspeciales[0]['metodo_pago'];
        $cuentaEspecial = $cuentasEspeciales[0]['id'] ?? 336;
        echo "\n";
    }
    
    // PASO 2: Crear requisición con configuraciones especiales
    echo "--- PASO 2: Crear requisición de prueba ---\n";
    
    $pdo->beginTransaction();
    
    // Crear requisición (el numero_requisicion se generará automáticamente basado en el ID)
    $stmt = $pdo->prepare("
        INSERT INTO requisiciones (
            proveedor_nombre, 
            forma_pago, 
            monto_total, 
            estado,
            usuario_id,
            fecha_solicitud,
            created_at, 
            updated_at
        ) VALUES ('Proveedor Test Autorizaciones Especiales', ?, 12500.00, 'pendiente_revision', 107, CURDATE(), NOW(), NOW())
    ");
    $stmt->execute([$formaPagoEspecial]);
    $requisicionId = $pdo->lastInsertId();
    
    // Actualizar numero_requisicion con el ID (igual que se muestra en "Mis Requisiciones")
    $numeroRequisicion = (string)$requisicionId;
    
    // Actualizar el numero_requisicion
    $stmt = $pdo->prepare("UPDATE requisiciones SET numero_requisicion = ? WHERE id = ?");
    $stmt->execute([$numeroRequisicion, $requisicionId]);
    
    echo "✅ Requisición creada: $numeroRequisicion (ID: $requisicionId)\n";
    echo "Forma de pago: $formaPagoEspecial\n";
    echo "Monto: $12,500.00\n\n";
    
    // Crear distribución de gastos (mezcla de cuenta especial y normal)
    $stmt = $pdo->prepare("
        INSERT INTO distribucion_gasto (
            requisicion_id, centro_costo_id, cuenta_contable_id, porcentaje, cantidad
        ) VALUES 
        (?, 1, ?, 60.00, 7500.00),
        (?, 2, 1, 40.00, 5000.00)
    ");
    $stmt->execute([$requisicionId, $cuentaEspecial, $requisicionId]);
    
    echo "Distribución de gastos creada:\n";
    echo "- 60% ($$7,500) → Centro 1, Cuenta $cuentaEspecial (especial)\n";
    echo "- 40% ($$5,000) → Centro 2, Cuenta 1 (normal)\n\n";
    
    // Crear flujo de autorización manualmente
    echo "--- PASO 3: Crear flujo de autorización ---\n";
    
    // Verificar si requiere autorizaciones especiales
    $requiereEspecialPago = false;
    $requiereEspecialCuenta = false;
    
    // Verificar forma de pago
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM autorizadores_metodos_pago WHERE metodo_pago = ?");
    $stmt->execute([$formaPagoEspecial]);
    $requiereEspecialPago = (int)$stmt->fetchColumn() > 0;
    
    // Verificar cuentas contables
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM distribucion_gasto dg
        JOIN autorizadores_cuentas_contables acc ON dg.cuenta_contable_id = acc.cuenta_contable_id
        WHERE dg.requisicion_id = ?
    ");
    $stmt->execute([$requisicionId]);
    $requiereEspecialCuenta = (int)$stmt->fetchColumn() > 0;
    
    // Crear el flujo manualmente
    $stmt = $pdo->prepare("
        INSERT INTO autorizacion_flujo (
            requisicion_id, 
            estado, 
            requiere_autorizacion_especial_pago, 
            requiere_autorizacion_especial_cuenta, 
            monto_total,
            fecha_creacion
        ) VALUES (?, 'pendiente_revision', ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $requisicionId, 
        $requiereEspecialPago ? 1 : 0, 
        $requiereEspecialCuenta ? 1 : 0, 
        12500.00
    ]);
    $flujoId = $pdo->lastInsertId();
    
    if ($flujoId) {
        echo "✅ Flujo de autorización creado: ID $flujoId\n";
        
        // Verificar configuración del flujo
        $stmt = $pdo->prepare("
            SELECT estado, requiere_autorizacion_especial_pago, requiere_autorizacion_especial_cuenta 
            FROM autorizacion_flujo WHERE id = ?
        ");
        $stmt->execute([$flujoId]);
        $flujo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Estado: {$flujo['estado']}\n";
        echo "Requiere autorización especial de pago: " . ($flujo['requiere_autorizacion_especial_pago'] ? 'SÍ' : 'NO') . "\n";
        echo "Requiere autorización especial de cuenta: " . ($flujo['requiere_autorizacion_especial_cuenta'] ? 'SÍ' : 'NO') . "\n";
        
        if ($flujo['requiere_autorizacion_especial_pago'] || $flujo['requiere_autorizacion_especial_cuenta']) {
            echo "✅ Flujo configurado correctamente para autorizaciones especiales\n";
        } else {
            echo "❌ Flujo no detectó autorizaciones especiales requeridas\n";
        }
        
    } else {
        echo "❌ Error creando flujo de autorización\n";
        $pdo->rollBack();
        exit;
    }
    
    $pdo->commit();
    
    echo "\n🎉 REQUISICIÓN CON AUTORIZACIONES ESPECIALES CREADA\n";
    echo "Requisición: $numeroRequisicion (ID: $requisicionId)\n";
    echo "Flujo: ID $flujoId\n";
    echo "Puedes usar esta requisición para probar el flujo completo\n";
    
    // Mostrar comando para probar
    echo "\nPara probar el flujo completo, ejecuta:\n";
    echo "php test_flujo_completo_especiales.php\n";
    echo "(usará automáticamente esta requisición)\n";
    
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
?>