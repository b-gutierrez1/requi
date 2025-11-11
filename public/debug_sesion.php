<?php
/**
 * Script de debug de sesión - accesible desde el navegador
 */

session_start();

echo "<h1>Debug de Sesión - Usuario Actual</h1>\n";

echo "<h2>1. Información Completa de Sesión</h2>\n";
echo "<pre>" . print_r($_SESSION, true) . "</pre>\n";

echo "<h2>2. Verificación de Claves de Email</h2>\n";
echo "<table border='1' cellpadding='5'>\n";
echo "<tr><th>Clave</th><th>Valor</th><th>¿Usada por la vista?</th></tr>\n";

$claves_posibles = [
    'user_email' => 'SÍ - La vista busca esta',
    'email' => 'No',
    'usuario.email' => 'No',
    'user.email' => 'No'
];

foreach ($claves_posibles as $clave => $uso) {
    $valor = 'No existe';
    if (strpos($clave, '.') !== false) {
        $partes = explode('.', $clave);
        $valor = $_SESSION[$partes[0]][$partes[1]] ?? 'No existe';
    } else {
        $valor = $_SESSION[$clave] ?? 'No existe';
    }
    
    echo "<tr>";
    echo "<td><code>\$_SESSION['$clave']</code></td>";
    echo "<td>" . htmlspecialchars($valor) . "</td>";
    echo "<td>" . $uso . "</td>";
    echo "</tr>\n";
}
echo "</table>\n";

// Buscar cualquier email en la sesión
echo "<h2>3. Búsqueda de Emails en la Sesión</h2>\n";
function buscarEmails($array, $prefijo = '') {
    $emails = [];
    foreach ($array as $key => $value) {
        $clave_completa = $prefijo ? "$prefijo.$key" : $key;
        if (is_array($value)) {
            $emails = array_merge($emails, buscarEmails($value, $clave_completa));
        } elseif (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $emails[$clave_completa] = $value;
        }
    }
    return $emails;
}

$emails_encontrados = buscarEmails($_SESSION);

if (empty($emails_encontrados)) {
    echo "<p><strong>❌ No se encontraron emails en la sesión</strong></p>\n";
} else {
    echo "<table border='1' cellpadding='5'>\n";
    echo "<tr><th>Ubicación</th><th>Email</th></tr>\n";
    foreach ($emails_encontrados as $ubicacion => $email) {
        echo "<tr>";
        echo "<td><code>\$_SESSION['$ubicacion']</code></td>";
        echo "<td>" . htmlspecialchars($email) . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
}

// Verificar permisos específicos si hay email
$email_usuario = $_SESSION['user_email'] ?? null;
if (!$email_usuario && !empty($emails_encontrados)) {
    // Usar el primer email encontrado
    $email_usuario = array_values($emails_encontrados)[0];
}

if ($email_usuario) {
    echo "<h2>4. Verificación de Permisos para Requisición 3</h2>\n";
    echo "<p><strong>Email que se usará:</strong> $email_usuario</p>\n";
    
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
        echo "<tr><th>Centro</th><th>Autorizador</th><th>Estado</th><th>¿Puedes autorizar?</th></tr>\n";
        
        foreach ($autorizaciones as $auth) {
            $autorizadorEmail = $auth['autorizador_email'];
            $esMiCentro = (strtolower(trim($autorizadorEmail)) === strtolower(trim($email_usuario)));
            $puedeAutorizar = ($auth['estado'] === 'pendiente' && $esMiCentro);
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($auth['centro_nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($autorizadorEmail) . "</td>";
            echo "<td>" . $auth['estado'] . "</td>";
            echo "<td>" . ($puedeAutorizar ? '✅ SÍ' : '❌ NO') . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
    } catch (Exception $e) {
        echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>\n";
    }
} else {
    echo "<h2>4. Sin Email Detectado</h2>\n";
    echo "<p><strong>❌ No se puede verificar permisos sin email de usuario</strong></p>\n";
}

echo "<h2>5. Solución</h2>\n";
if (!$email_usuario) {
    echo "<div style='background-color: #f8d7da; padding: 15px; border: 1px solid #dc3545;'>\n";
    echo "<p><strong>Problema:</strong> No hay email en \$_SESSION['user_email'] ni en ninguna otra ubicación.</p>\n";
    echo "<p><strong>Necesitas:</strong></p>\n";
    echo "<ol><li>Cerrar sesión completamente</li><li>Volver a iniciar sesión como bguti@sp.iga.edu</li><li>Verificar que el login configure correctamente la sesión</li></ol>\n";
    echo "</div>\n";
} else {
    echo "<div style='background-color: #d4edda; padding: 15px; border: 1px solid #28a745;'>\n";
    echo "<p><strong>Email detectado:</strong> $email_usuario</p>\n";
    echo "<p>Ve a <code>http://localhost:8000/autorizaciones/3</code> y el botón debería aparecer.</p>\n";
    echo "</div>\n";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
pre { background-color: #f8f9fa; padding: 15px; overflow-x: auto; }
code { background-color: #f8f9fa; padding: 2px 4px; }
</style>