<?php
/**
 * Script para verificar y unificar los estados de autorización en todo el sistema
 */

try {
    $pdo = new PDO("mysql:host=localhost;dbname=bd_prueba", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Análisis y Unificación de Estados de Autorización</h1>\n";
    
    // 1. Verificar qué estados existen actualmente en la BD
    echo "<h2>1. Estados actuales en la base de datos</h2>\n";
    $stmt = $pdo->query("SELECT estado, COUNT(*) as total FROM autorizacion_flujo GROUP BY estado ORDER BY estado");
    $estadosEnBD = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>\n";
    echo "<tr><th>Estado</th><th>Cantidad</th><th>Acción Recomendada</th></tr>\n";
    foreach ($estadosEnBD as $estado) {
        $recomendacion = "";
        if ($estado['estado'] === 'pendiente_autorizacion') {
            $recomendacion = "⚠️ CAMBIAR a 'pendiente_autorizacion_centros'";
        } elseif ($estado['estado'] === 'pendiente_autorizacion_centros') {
            $recomendacion = "✅ CORRECTO";
        } else {
            $recomendacion = "✅ OK";
        }
        
        echo "<tr>";
        echo "<td>" . $estado['estado'] . "</td>";
        echo "<td>" . $estado['total'] . "</td>";
        echo "<td>" . $recomendacion . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // 2. Verificar requisiciones que podrían estar afectadas
    echo "<h2>2. Requisiciones con estado 'pendiente_autorizacion'</h2>\n";
    $stmt = $pdo->query("
        SELECT af.id, af.orden_compra_id, af.estado, oc.nombre_razon_social, af.fecha_creacion
        FROM autorizacion_flujo af
        JOIN orden_compra oc ON af.orden_compra_id = oc.id
        WHERE af.estado = 'pendiente_autorizacion'
        ORDER BY af.fecha_creacion DESC
    ");
    $requisicionesAfectadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($requisicionesAfectadas) {
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>Flujo ID</th><th>Orden ID</th><th>Proveedor</th><th>Fecha</th></tr>\n";
        foreach ($requisicionesAfectadas as $req) {
            echo "<tr>";
            echo "<td>" . $req['id'] . "</td>";
            echo "<td>" . $req['orden_compra_id'] . "</td>";
            echo "<td>" . htmlspecialchars($req['nombre_razon_social']) . "</td>";
            echo "<td>" . $req['fecha_creacion'] . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        // 3. Script de corrección
        echo "<h2>3. Script de Corrección</h2>\n";
        echo "<p>Para corregir los estados inconsistentes, ejecuta este SQL:</p>\n";
        echo "<textarea rows='5' cols='80' readonly>\n";
        echo "UPDATE autorizacion_flujo \n";
        echo "SET estado = 'pendiente_autorizacion_centros' \n";
        echo "WHERE estado = 'pendiente_autorizacion' \n";
        echo "AND id IN (";
        $ids = array_column($requisicionesAfectadas, 'id');
        echo implode(', ', $ids);
        echo ");\n";
        echo "</textarea>\n";
        
        // Aplicar corrección automáticamente
        echo "<h3>¿Aplicar corrección automáticamente?</h3>\n";
        if (isset($_GET['aplicar']) && $_GET['aplicar'] === 'si') {
            $stmt = $pdo->prepare("UPDATE autorizacion_flujo SET estado = 'pendiente_autorizacion_centros' WHERE estado = 'pendiente_autorizacion'");
            $stmt->execute();
            $afectadas = $stmt->rowCount();
            
            echo "<div style='background-color: #d4edda; padding: 10px; border-left: 4px solid #28a745;'>\n";
            echo "<strong>✅ Corrección aplicada exitosamente</strong><br>\n";
            echo "Se actualizaron $afectadas registros.\n";
            echo "</div>\n";
        } else {
            echo "<a href='?aplicar=si' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Aplicar Corrección</a>\n";
        }
        
    } else {
        echo "<p>✅ No se encontraron requisiciones con estado inconsistente.</p>\n";
    }
    
    // 4. Verificar archivos de código que usen estados incorrectos
    echo "<h2>4. Recomendaciones para el código</h2>\n";
    echo "<div style='background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>\n";
    echo "<h3>⚠️ Para evitar futuros problemas:</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>Usa siempre las constantes</strong> definidas en AutorizacionFlujo.php en lugar de strings hardcodeados</li>\n";
    echo "<li><strong>Revisa estos archivos</strong> que contienen referencias a 'pendiente_autorizacion':</li>\n";
    echo "<ul>\n";
    echo "<li>AutorizacionCentroCosto.php (ya corregido)</li>\n";
    echo "<li>AutorizacionController.php</li>\n";
    echo "<li>RequisicionController.php</li>\n";
    echo "<li>EstadoHelper.php</li>\n";
    echo "</ul>\n";
    echo "<li><strong>Considera crear un enum o validador</strong> para estados permitidos</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    
} catch (PDOException $e) {
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>\n";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
h1, h2, h3 { color: #333; }
textarea { font-family: monospace; background-color: #f8f9fa; }
</style>