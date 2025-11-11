<?php
/**
 * Script corregido para diagnosticar el problema con la requisición 3
 */

try {
    $pdo = new PDO("mysql:host=localhost;dbname=bd_prueba", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Diagnóstico de Requisición 3 - Problema con Autorizadores</h1>\n";
    
    // 1. Verificar que la requisición 3 existe
    echo "<h2>1. Información básica de la Requisición 3</h2>\n";
    $stmt = $pdo->prepare("SELECT oc.*, af.estado, af.fecha_creacion as fecha_flujo FROM orden_compra oc LEFT JOIN autorizacion_flujo af ON oc.id = af.orden_compra_id WHERE oc.id = 3");
    $stmt->execute();
    $requisicion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($requisicion) {
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>Campo</th><th>Valor</th></tr>\n";
        echo "<tr><td>ID Requisición</td><td>" . $requisicion['id'] . "</td></tr>\n";
        echo "<tr><td>Proveedor</td><td>" . htmlspecialchars($requisicion['nombre_razon_social']) . "</td></tr>\n";
        echo "<tr><td>Fecha</td><td>" . $requisicion['fecha'] . "</td></tr>\n";
        echo "<tr><td>Monto Total</td><td>$" . number_format($requisicion['monto_total'], 2) . "</td></tr>\n";
        echo "<tr><td>Usuario ID</td><td>" . $requisicion['usuario_id'] . "</td></tr>\n";
        echo "<tr><td>Unidad Requirente</td><td>" . $requisicion['unidad_requirente'] . "</td></tr>\n";
        echo "<tr><td>Estado del Flujo</td><td>" . ($requisicion['estado'] ?? 'SIN FLUJO') . "</td></tr>\n";
        echo "<tr><td>Fecha Creación Flujo</td><td>" . ($requisicion['fecha_flujo'] ?? 'SIN FLUJO') . "</td></tr>\n";
        echo "</table>\n";
        
        $monto_total = $requisicion['monto_total'];
        $unidad_requirente = $requisicion['unidad_requirente'];
        $usuario_id = $requisicion['usuario_id'];
        $estado_flujo = $requisicion['estado'];
    } else {
        echo "<p><strong>ERROR:</strong> No se encontró la requisición 3</p>\n";
        exit;
    }
    
    // 2. Verificar el flujo de autorización
    echo "<h2>2. Flujo de Autorización</h2>\n";
    $stmt = $pdo->prepare("SELECT * FROM autorizacion_flujo WHERE orden_compra_id = 3");
    $stmt->execute();
    $flujo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($flujo) {
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>Campo</th><th>Valor</th></tr>\n";
        foreach ($flujo as $campo => $valor) {
            echo "<tr><td>" . htmlspecialchars($campo) . "</td><td>" . htmlspecialchars($valor ?? 'NULL') . "</td></tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p><strong>❌ PROBLEMA CRÍTICO:</strong> No existe flujo de autorización para la requisición 3</p>\n";
    }
    
    // 3. Verificar detalle de items
    echo "<h2>3. Items de la Requisición</h2>\n";
    $stmt = $pdo->prepare("SELECT * FROM detalle_items WHERE orden_compra_id = 3");
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($items) {
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>ID</th><th>Descripción</th><th>Cantidad</th><th>Costo Unitario</th><th>Total</th></tr>\n";
        $total_items = 0;
        foreach ($items as $item) {
            $subtotal = $item['cantidad'] * $item['costo_unitario'];
            $total_items += $subtotal;
            echo "<tr>";
            echo "<td>" . $item['id'] . "</td>";
            echo "<td>" . htmlspecialchars($item['descripcion']) . "</td>";
            echo "<td>" . $item['cantidad'] . "</td>";
            echo "<td>$" . number_format($item['costo_unitario'], 2) . "</td>";
            echo "<td>$" . number_format($subtotal, 2) . "</td>";
            echo "</tr>\n";
        }
        echo "<tr><td colspan='4'><strong>TOTAL</strong></td><td><strong>$" . number_format($total_items, 2) . "</strong></td></tr>\n";
        echo "</table>\n";
    } else {
        echo "<p>No se encontraron items para la requisición 3</p>\n";
    }
    
    // 4. Verificar distribución de gastos (para encontrar centros de costo)
    echo "<h2>4. Distribución de Gastos</h2>\n";
    $stmt = $pdo->prepare("
        SELECT dg.*, cc.nombre as centro_costo_nombre, cu.nombre as cuenta_contable_nombre 
        FROM distribucion_gasto dg
        LEFT JOIN centro_de_costo cc ON dg.centro_costo_id = cc.id
        LEFT JOIN cuenta_contable cu ON dg.cuenta_contable_id = cu.id
        WHERE dg.orden_compra_id = 3
    ");
    $stmt->execute();
    $distribuciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($distribuciones) {
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>ID</th><th>Centro de Costo</th><th>Cuenta Contable</th><th>Monto</th><th>Porcentaje</th></tr>\n";
        $centros_costo = [];
        foreach ($distribuciones as $dist) {
            echo "<tr>";
            echo "<td>" . $dist['id'] . "</td>";
            echo "<td>" . $dist['centro_costo_id'] . " - " . htmlspecialchars($dist['centro_costo_nombre'] ?? 'Sin nombre') . "</td>";
            echo "<td>" . $dist['cuenta_contable_id'] . " - " . htmlspecialchars($dist['cuenta_contable_nombre'] ?? 'Sin nombre') . "</td>";
            echo "<td>$" . number_format($dist['monto'], 2) . "</td>";
            echo "<td>" . $dist['porcentaje'] . "%</td>";
            echo "</tr>\n";
            
            $centros_costo[] = $dist['centro_costo_id'];
        }
        echo "</table>\n";
        
        // 5. Para cada centro de costo, verificar autorizadores configurados
        echo "<h2>5. Autorizadores Configurados por Centro de Costo</h2>\n";
        $centros_costo = array_unique($centros_costo);
        
        foreach ($centros_costo as $centro_id) {
            echo "<h3>Centro de Costo ID: $centro_id</h3>\n";
            
            $stmt = $pdo->prepare("
                SELECT acc.*, pa.nombre, pa.email, pa.activo 
                FROM autorizacion_centro_costo acc
                JOIN persona_autorizada pa ON acc.persona_autorizada_id = pa.id
                WHERE acc.centro_costo_id = ?
                ORDER BY acc.nivel_autorizacion
            ");
            $stmt->execute([$centro_id]);
            $autorizadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($autorizadores) {
                echo "<table border='1' cellpadding='5'>\n";
                echo "<tr><th>Nivel</th><th>Persona</th><th>Email</th><th>Activo</th><th>Monto Mín</th><th>Monto Máx</th><th>Aplica?</th></tr>\n";
                foreach ($autorizadores as $auth) {
                    // Verificar si aplica para este monto
                    $aplica = true;
                    $razon = "";
                    
                    if (!$auth['activo']) {
                        $aplica = false;
                        $razon = "Inactivo";
                    } elseif ($auth['monto_minimo'] !== null && $monto_total < $auth['monto_minimo']) {
                        $aplica = false;
                        $razon = "Monto menor";
                    } elseif ($auth['monto_maximo'] !== null && $monto_total > $auth['monto_maximo']) {
                        $aplica = false;
                        $razon = "Monto mayor";
                    }
                    
                    $clase = $aplica ? "success" : "error";
                    $status = $aplica ? "✅ SÍ" : "❌ NO ($razon)";
                    
                    echo "<tr>";
                    echo "<td>" . $auth['nivel_autorizacion'] . "</td>";
                    echo "<td>" . htmlspecialchars($auth['nombre']) . "</td>";
                    echo "<td>" . htmlspecialchars($auth['email']) . "</td>";
                    echo "<td>" . ($auth['activo'] ? 'Sí' : 'No') . "</td>";
                    echo "<td>" . ($auth['monto_minimo'] ?? 'N/A') . "</td>";
                    echo "<td>" . ($auth['monto_maximo'] ?? 'N/A') . "</td>";
                    echo "<td class='$clase'>$status</td>";
                    echo "</tr>\n";
                }
                echo "</table>\n";
            } else {
                echo "<p class='error'>❌ No hay autorizadores configurados para este centro de costo</p>\n";
            }
        }
        
    } else {
        echo "<p><strong>❌ PROBLEMA:</strong> No se encontró distribución de gastos para la requisición 3</p>\n";
    }
    
    // 6. Verificar historial
    echo "<h2>6. Historial de la Requisición</h2>\n";
    $stmt = $pdo->prepare("SELECT * FROM historial_requisicion WHERE orden_compra_id = 3 ORDER BY fecha DESC");
    $stmt->execute();
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($historial) {
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>Fecha</th><th>Tipo Evento</th><th>Usuario</th><th>Descripción</th></tr>\n";
        foreach ($historial as $evento) {
            echo "<tr>";
            echo "<td>" . $evento['fecha'] . "</td>";
            echo "<td>" . $evento['tipo_evento'] . "</td>";
            echo "<td>" . $evento['usuario_email'] . "</td>";
            echo "<td>" . htmlspecialchars($evento['descripcion']) . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p>No se encontró historial para la requisición 3</p>\n";
    }
    
    // 7. Conclusiones
    echo "<h2>7. Diagnóstico y Conclusiones</h2>\n";
    
    if (!$flujo) {
        echo "<div style='background-color: #ffebee; padding: 15px; border-left: 4px solid #f44336; margin: 10px 0;'>\n";
        echo "<h3>❌ PROBLEMA PRINCIPAL IDENTIFICADO</h3>\n";
        echo "<p><strong>La requisición 3 NO tiene un flujo de autorización creado.</strong></p>\n";
        echo "<p>Esto significa que el proceso automático de creación de autorizaciones falló.</p>\n";
        echo "</div>\n";
        
        if (empty($distribuciones)) {
            echo "<div style='background-color: #fff3e0; padding: 15px; border-left: 4px solid #ff9800; margin: 10px 0;'>\n";
            echo "<h3>⚠️ CAUSA POSIBLE</h3>\n";
            echo "<p>No existe distribución de gastos para la requisición 3.</p>\n";
            echo "<p>Sin distribución de gastos, el sistema no puede determinar qué centros de costo y autorizadores aplicar.</p>\n";
            echo "</div>\n";
        } else {
            echo "<div style='background-color: #e8f5e8; padding: 15px; border-left: 4px solid #4caf50; margin: 10px 0;'>\n";
            echo "<h3>✅ DISTRIBUCIÓN DE GASTOS ENCONTRADA</h3>\n";
            echo "<p>La distribución de gastos existe, por lo que el problema está en el proceso de creación del flujo.</p>\n";
            echo "</div>\n";
        }
    } else {
        echo "<div style='background-color: #e8f5e8; padding: 15px; border-left: 4px solid #4caf50; margin: 10px 0;'>\n";
        echo "<h3>✅ FLUJO ENCONTRADO</h3>\n";
        echo "<p>La requisición 3 SÍ tiene un flujo de autorización creado.</p>\n";
        echo "<p>Estado actual: <strong>" . $estado_flujo . "</strong></p>\n";
        echo "</div>\n";
    }
    
} catch (PDOException $e) {
    echo "<p><strong>Error de conexión:</strong> " . $e->getMessage() . "</p>\n";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    h1, h2, h3 { color: #333; }
    .error { color: red; font-weight: bold; }
    .success { color: green; font-weight: bold; }
</style>