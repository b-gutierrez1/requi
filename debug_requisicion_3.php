<?php
/**
 * Script para diagnosticar el problema con la requisición 3
 */

try {
    // Conexión a la base de datos
    $pdo = new PDO("mysql:host=localhost;dbname=bd_prueba", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Diagnóstico de Requisición 3 - Problema con Autorizadores</h1>\n";
    
    // 1. Verificar que la requisición 3 existe
    echo "<h2>1. Información básica de la Requisición 3</h2>\n";
    $stmt = $pdo->prepare("SELECT * FROM orden_compra WHERE id = 3");
    $stmt->execute();
    $requisicion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($requisicion) {
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>Campo</th><th>Valor</th></tr>\n";
        foreach ($requisicion as $campo => $valor) {
            echo "<tr><td>" . htmlspecialchars($campo) . "</td><td>" . htmlspecialchars($valor) . "</td></tr>\n";
        }
        echo "</table>\n";
        
        $estado = $requisicion['estado'];
        $centro_costo_id = $requisicion['centro_costo'];
        echo "<p><strong>Estado actual:</strong> $estado</p>\n";
        echo "<p><strong>Centro de costo:</strong> $centro_costo_id</p>\n";
    } else {
        echo "<p><strong>ERROR:</strong> No se encontró la requisición 3</p>\n";
        exit;
    }
    
    // 2. Verificar el historial de la requisición
    echo "<h2>2. Historial de la Requisición 3</h2>\n";
    $stmt = $pdo->prepare("SELECT * FROM historial_requisicion WHERE orden_compra_id = 3 ORDER BY fecha_cambio DESC");
    $stmt->execute();
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($historial) {
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>Fecha</th><th>Estado Anterior</th><th>Estado Nuevo</th><th>Usuario</th><th>Comentarios</th></tr>\n";
        foreach ($historial as $registro) {
            echo "<tr>";
            echo "<td>" . $registro['fecha_cambio'] . "</td>";
            echo "<td>" . htmlspecialchars($registro['estado_anterior']) . "</td>";
            echo "<td>" . htmlspecialchars($registro['estado_nuevo']) . "</td>";
            echo "<td>" . htmlspecialchars($registro['usuario_id']) . "</td>";
            echo "<td>" . htmlspecialchars($registro['comentarios']) . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p>No se encontró historial para la requisición 3</p>\n";
    }
    
    // 3. Verificar flujo de autorizaciones
    echo "<h2>3. Flujo de Autorizaciones de la Requisición 3</h2>\n";
    $stmt = $pdo->prepare("SELECT * FROM autorizacion_flujo WHERE orden_compra_id = 3 ORDER BY nivel_autorizacion");
    $stmt->execute();
    $autorizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($autorizaciones) {
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>ID</th><th>Nivel</th><th>Persona ID</th><th>Estado</th><th>Fecha Autorización</th><th>Comentarios</th></tr>\n";
        foreach ($autorizaciones as $auth) {
            echo "<tr>";
            echo "<td>" . $auth['id'] . "</td>";
            echo "<td>" . $auth['nivel_autorizacion'] . "</td>";
            echo "<td>" . $auth['persona_autorizada_id'] . "</td>";
            echo "<td>" . $auth['estado'] . "</td>";
            echo "<td>" . ($auth['fecha_autorizacion'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($auth['comentarios'] ?? '') . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p><strong>PROBLEMA ENCONTRADO:</strong> No se encontraron autorizaciones en el flujo para la requisición 3</p>\n";
    }
    
    // 4. Verificar configuración de autorizadores para el centro de costo
    echo "<h2>4. Autorizadores Configurados para el Centro de Costo $centro_costo_id</h2>\n";
    $stmt = $pdo->prepare("
        SELECT acc.*, pa.nombre, pa.email, pa.activo 
        FROM autorizacion_centro_costo acc
        JOIN persona_autorizada pa ON acc.persona_autorizada_id = pa.id
        WHERE acc.centro_costo_id = ?
        ORDER BY acc.nivel_autorizacion
    ");
    $stmt->execute([$centro_costo_id]);
    $autorizadores_configurados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($autorizadores_configurados) {
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>ID Config</th><th>Nivel</th><th>Persona ID</th><th>Nombre</th><th>Email</th><th>Activo</th><th>Monto Mín</th><th>Monto Máx</th></tr>\n";
        foreach ($autorizadores_configurados as $config) {
            echo "<tr>";
            echo "<td>" . $config['id'] . "</td>";
            echo "<td>" . $config['nivel_autorizacion'] . "</td>";
            echo "<td>" . $config['persona_autorizada_id'] . "</td>";
            echo "<td>" . htmlspecialchars($config['nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($config['email']) . "</td>";
            echo "<td>" . ($config['activo'] ? 'Sí' : 'No') . "</td>";
            echo "<td>" . ($config['monto_minimo'] ?? 'N/A') . "</td>";
            echo "<td>" . ($config['monto_maximo'] ?? 'N/A') . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        echo "<p><strong>Total de autorizadores configurados:</strong> " . count($autorizadores_configurados) . "</p>\n";
    } else {
        echo "<p><strong>PROBLEMA ENCONTRADO:</strong> No hay autorizadores configurados para el centro de costo $centro_costo_id</p>\n";
    }
    
    // 5. Verificar monto total de la requisición
    echo "<h2>5. Detalle de Montos de la Requisición 3</h2>\n";
    $stmt = $pdo->prepare("SELECT SUM(cantidad * costo_unitario) as total FROM detalle_items WHERE orden_compra_id = 3");
    $stmt->execute();
    $monto_total = $stmt->fetchColumn();
    
    echo "<p><strong>Monto total de la requisición:</strong> $" . number_format($monto_total, 2) . "</p>\n";
    
    // Verificar si el monto está dentro de los rangos de autorización
    if ($autorizadores_configurados && $monto_total) {
        echo "<h3>Autorizadores Aplicables por Monto:</h3>\n";
        foreach ($autorizadores_configurados as $config) {
            $aplica = true;
            $razon = "";
            
            if ($config['monto_minimo'] !== null && $monto_total < $config['monto_minimo']) {
                $aplica = false;
                $razon = "Monto menor al mínimo (" . $config['monto_minimo'] . ")";
            }
            
            if ($config['monto_maximo'] !== null && $monto_total > $config['monto_maximo']) {
                $aplica = false;
                $razon = "Monto mayor al máximo (" . $config['monto_maximo'] . ")";
            }
            
            if (!$config['activo']) {
                $aplica = false;
                $razon = "Autorizador inactivo";
            }
            
            $status = $aplica ? "✅ SÍ" : "❌ NO";
            echo "<p>Nivel " . $config['nivel_autorizacion'] . " - " . $config['nombre'] . ": $status";
            if (!$aplica) echo " ($razon)";
            echo "</p>\n";
        }
    }
    
    // 6. Verificar logs del sistema
    echo "<h2>6. Conclusiones y Recomendaciones</h2>\n";
    
    if (empty($autorizaciones)) {
        echo "<div style='background-color: #ffcccb; padding: 10px; border: 1px solid #ff0000;'>\n";
        echo "<h3>❌ PROBLEMA IDENTIFICADO</h3>\n";
        echo "<p>La requisición 3 no tiene autorizaciones generadas automáticamente.</p>\n";
        echo "<p><strong>Posibles causas:</strong></p>\n";
        echo "<ul>\n";
        echo "<li>Fallo en el proceso de generación automática de autorizaciones</li>\n";
        echo "<li>Error en el trigger o script que debe crear las autorizaciones</li>\n";
        echo "<li>Problema con la configuración de autorizadores para el centro de costo</li>\n";
        echo "</ul>\n";
        
        if (empty($autorizadores_configurados)) {
            echo "<p><strong>CAUSA PRINCIPAL:</strong> No hay autorizadores configurados para el centro de costo $centro_costo_id</p>\n";
        }
        
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