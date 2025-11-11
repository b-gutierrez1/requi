<?php
/**
 * Script para debuggear permisos de usuario en la requisición 3
 */

session_start();

echo "<h1>Debug de Permisos para Requisición 3</h1>\n";

// Simular las variables que tendría la sesión
$usuarioEmail = $_SESSION['usuario']['email'] ?? 'NO_SET';
$usuarioActual = $_SESSION['usuario'] ?? null;

echo "<h2>1. Información de Sesión</h2>\n";
echo "<table border='1' cellpadding='5'>\n";
echo "<tr><th>Variable</th><th>Valor</th></tr>\n";
echo "<tr><td>Email de sesión</td><td>" . htmlspecialchars($usuarioEmail) . "</td></tr>\n";
echo "<tr><td>Sesión completa</td><td><pre>" . print_r($_SESSION, true) . "</pre></td></tr>\n";
echo "</table>\n";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=bd_prueba", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Simular la lógica de la vista para la requisición 3
    $flujoId = 3; // Requisición 3
    
    echo "<h2>2. Autorizaciones de Centro para Requisición 3</h2>\n";
    $stmt = $pdo->query("
        SELECT acc.*, cc.nombre as centro_nombre
        FROM autorizacion_centro_costo acc
        INNER JOIN centro_de_costo cc ON acc.centro_costo_id = cc.id
        WHERE acc.autorizacion_flujo_id = $flujoId
        ORDER BY cc.nombre ASC
    ");
    $autorizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>\n";
    echo "<tr><th>Centro</th><th>Autorizador Email</th><th>Estado</th><th>¿Es mi centro?</th><th>Puede autorizar?</th></tr>\n";
    
    foreach ($autorizaciones as $auth) {
        $autorizadorEmail = $auth['autorizador_email'];
        $estadoAutorizacion = $auth['estado'];
        $esMiCentro = ($usuarioEmail && $autorizadorEmail && strtolower(trim($autorizadorEmail)) === strtolower(trim($usuarioEmail)));
        $puedeAutorizar = ($estadoAutorizacion === 'pendiente' && $esMiCentro && $auth);
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($auth['centro_nombre']) . "</td>";
        echo "<td>" . htmlspecialchars($autorizadorEmail) . "</td>";
        echo "<td>" . $estadoAutorizacion . "</td>";
        echo "<td>" . ($esMiCentro ? '✅ SÍ' : '❌ NO') . "</td>";
        echo "<td>" . ($puedeAutorizar ? '✅ SÍ' : '❌ NO') . "</td>";
        echo "</tr>\n";
        
        // Debug detallado
        echo "<tr><td colspan='5' style='background-color: #f8f9fa; font-size: 12px;'>";
        echo "<strong>Debug:</strong> ";
        echo "usuarioEmail='$usuarioEmail', autorizadorEmail='$autorizadorEmail', ";
        echo "comparación: '" . strtolower(trim($autorizadorEmail)) . "' === '" . strtolower(trim($usuarioEmail)) . "' = " . ($esMiCentro ? 'true' : 'false');
        echo "</td></tr>\n";
    }
    echo "</table>\n";
    
    echo "<h2>3. Diagnóstico</h2>\n";
    
    if ($usuarioEmail === 'NO_SET') {
        echo "<div style='background-color: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;'>\n";
        echo "<h3>❌ PROBLEMA PRINCIPAL</h3>\n";
        echo "<p><strong>No hay email de usuario en la sesión.</strong></p>\n";
        echo "<p>La variable \$usuarioEmail no está definida, por lo que \$esMiCentro siempre será false.</p>\n";
        echo "<p><strong>Solución:</strong> Asegúrate de que el usuario esté correctamente autenticado.</p>\n";
        echo "</div>\n";
    } else {
        $tienePermisos = false;
        foreach ($autorizaciones as $auth) {
            $autorizadorEmail = $auth['autorizador_email'];
            $esMiCentro = strtolower(trim($autorizadorEmail)) === strtolower(trim($usuarioEmail));
            if ($esMiCentro && $auth['estado'] === 'pendiente') {
                $tienePermisos = true;
                break;
            }
        }
        
        if ($tienePermisos) {
            echo "<div style='background-color: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>\n";
            echo "<h3>✅ PERMISOS CORRECTOS</h3>\n";
            echo "<p>El usuario tiene permisos para autorizar al menos un centro.</p>\n";
            echo "</div>\n";
        } else {
            echo "<div style='background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>\n";
            echo "<h3>⚠️ SIN PERMISOS</h3>\n";
            echo "<p>El usuario '$usuarioEmail' no tiene permisos para autorizar ningún centro de esta requisición.</p>\n";
            echo "<p>Verifica que el email en la sesión coincida exactamente con los emails de autorizadores.</p>\n";
            echo "</div>\n";
        }
    }
    
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
pre { background-color: #f8f9fa; padding: 10px; overflow-x: auto; }
</style>