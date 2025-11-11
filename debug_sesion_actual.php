<?php
/**
 * Script para verificar la sesión actual del usuario bguti
 */

session_start();

echo "<h1>Verificación de Sesión - Usuario bguti</h1>\n";

echo "<h2>1. Estado de la Sesión</h2>\n";
echo "<table border='1' cellpadding='5'>\n";
echo "<tr><th>Variable</th><th>Valor</th></tr>\n";
echo "<tr><td>Session ID</td><td>" . session_id() . "</td></tr>\n";
echo "<tr><td>Session Status</td><td>" . session_status() . "</td></tr>\n";
echo "<tr><td>Datos completos</td><td><pre>" . print_r($_SESSION, true) . "</pre></td></tr>\n";
echo "</table>\n";

// Verificar email específicamente
$usuarioEmail = null;
if (isset($_SESSION['usuario']['email'])) {
    $usuarioEmail = $_SESSION['usuario']['email'];
} elseif (isset($_SESSION['user']['email'])) {
    $usuarioEmail = $_SESSION['user']['email'];
} elseif (isset($_SESSION['email'])) {
    $usuarioEmail = $_SESSION['email'];
}

echo "<h2>2. Email del Usuario Detectado</h2>\n";
echo "<p><strong>Email encontrado:</strong> " . ($usuarioEmail ?? 'NO DETECTADO') . "</p>\n";

if ($usuarioEmail) {
    echo "<h2>3. Verificación de Permisos para Requisición 3</h2>\n";
    
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=bd_prueba", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->query("
            SELECT acc.*, cc.nombre as centro_nombre
            FROM autorizacion_centro_costo acc
            INNER JOIN centro_de_costo cc ON acc.centro_costo_id = cc.id
            WHERE acc.autorizacion_flujo_id = 3
            ORDER BY cc.nombre ASC
        ");
        $autorizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>Centro</th><th>Autorizador Requerido</th><th>Estado</th><th>¿Coincide con tu email?</th><th>¿Puedes autorizar?</th></tr>\n";
        
        $puedeAutorizar = false;
        foreach ($autorizaciones as $auth) {
            $autorizadorEmail = $auth['autorizador_email'];
            $estadoAutorizacion = $auth['estado'];
            $esMiCentro = (strtolower(trim($autorizadorEmail)) === strtolower(trim($usuarioEmail)));
            $puedeEstaAutorizacion = ($estadoAutorizacion === 'pendiente' && $esMiCentro);
            
            if ($puedeEstaAutorizacion) $puedeAutorizar = true;
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($auth['centro_nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($autorizadorEmail) . "</td>";
            echo "<td><span class='badge badge-" . ($estadoAutorizacion === 'pendiente' ? 'warning' : 'success') . "'>" . $estadoAutorizacion . "</span></td>";
            echo "<td>" . ($esMiCentro ? '✅ SÍ' : '❌ NO') . "</td>";
            echo "<td>" . ($puedeEstaAutorizacion ? '🎯 SÍ PUEDES' : '❌ No') . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        if ($puedeAutorizar) {
            echo "<div style='background-color: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0;'>\n";
            echo "<h3>✅ ¡PERFECTO!</h3>\n";
            echo "<p>Tu usuario <strong>$usuarioEmail</strong> tiene permisos para autorizar en la requisición 3.</p>\n";
            echo "<p>El botón de 'Autorizar' debería aparecer en <code>http://localhost:8000/autorizaciones/3</code></p>\n";
            echo "</div>\n";
        } else {
            echo "<div style='background-color: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 20px 0;'>\n";
            echo "<h3>❌ PROBLEMA</h3>\n";
            echo "<p>Tu email <strong>$usuarioEmail</strong> no coincide con ningún autorizador de la requisición 3.</p>\n";
            echo "<p>Necesitas iniciar sesión con uno de los emails autorizadores mostrados arriba.</p>\n";
            echo "</div>\n";
        }
        
    } catch (PDOException $e) {
        echo "<p><strong>Error de BD:</strong> " . $e->getMessage() . "</p>\n";
    }
} else {
    echo "<div style='background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;'>\n";
    echo "<h3>⚠️ SIN AUTENTICACIÓN</h3>\n";
    echo "<p>No se detectó email de usuario en la sesión. Asegúrate de estar correctamente logueado.</p>\n";
    echo "</div>\n";
}

echo "<h2>4. Instrucciones</h2>\n";
echo "<ol>\n";
echo "<li>Asegúrate de estar logueado como <code>bguti@sp.iga.edu</code></li>\n";
echo "<li>Ve a <code>http://localhost:8000/autorizaciones/3</code></li>\n";
echo "<li>Busca el centro 'PARQUEO GENERAL'</li>\n";
echo "<li>Debería aparecer el botón 'Autorizar' para ese centro</li>\n";
echo "</ol>\n";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
h1, h2, h3 { color: #333; }
pre { background-color: #f8f9fa; padding: 10px; overflow-x: auto; max-height: 300px; }
.badge { padding: 4px 8px; border-radius: 4px; color: white; }
.badge-warning { background-color: #ffc107; color: #212529; }
.badge-success { background-color: #28a745; }
code { background-color: #f8f9fa; padding: 2px 4px; border-radius: 3px; }
</style>