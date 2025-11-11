<?php
// Configurar el directorio base
$baseDir = dirname(__DIR__);
require_once $baseDir . '/app/Helpers/functions.php';
require_once $baseDir . '/app/Helpers/Config.php';

// Inicializar conexión a base de datos
try {
    $config = require $baseDir . '/config/database.php';
    $dbConfig = $config['connections']['mysql'];
    
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);
    
} catch (Exception $e) {
    die("Error de conexión: " . $e->getMessage());
}

session_start();

// Obtener el usuario actual o usar uno por defecto para debug
$usuarioEmail = '';
if (isset($_SESSION['usuario']['email'])) {
    $usuarioEmail = $_SESSION['usuario']['email'];
} else {
    // Usuario por defecto para debug
    $usuarioEmail = 'bgutierrez@sp.iga.edu'; // Cambia por tu email real
    echo "<div style='background: yellow; padding: 10px; margin: 10px 0;'>⚠️ <strong>Modo Debug:</strong> No hay sesión activa, usando email: $usuarioEmail</div>";
}

echo "<h1>🔍 DEBUG AUTORIZACIÓN 2</h1>";
echo "<p><strong>Usuario actual:</strong> {$usuarioEmail}</p>";

try {
    // Buscar la orden de compra ID 2
    $stmt = $pdo->prepare("SELECT * FROM orden_compra WHERE id = ?");
    $stmt->execute([2]);
    $orden = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$orden) {
        echo "<div style='color: red;'>❌ No se encontró la orden de compra con ID 2</div>";
        exit;
    }
    
    echo "<h2>📄 Información de la Orden:</h2>";
    echo "<p><strong>ID:</strong> {$orden['id']}</p>";
    echo "<p><strong>Estado:</strong> " . ($orden['estado'] ?? 'N/A') . "</p>";
    echo "<p><strong>Proveedor:</strong> " . ($orden['nombre_razon_social'] ?? 'N/A') . "</p>";
    echo "<p><strong>Usuario creador:</strong> " . ($orden['usuario_id'] ?? 'N/A') . "</p>";
    
    // Obtener las distribuciones de gasto
    $stmt = $pdo->prepare("
        SELECT dg.*, cc.nombre as centro_nombre 
        FROM distribucion_gasto dg 
        LEFT JOIN centro_de_costo cc ON dg.centro_costo_id = cc.id 
        WHERE dg.orden_compra_id = ?
    ");
    $stmt->execute([2]);
    $distribuciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Primero ver qué columnas existen en las tablas
    echo "<h2>🔍 Estructura de Tablas:</h2>";
    
    $stmt = $pdo->prepare("DESCRIBE orden_compra");
    $stmt->execute();
    $columnasOrden = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h4>Columnas de orden_compra:</h4>";
    foreach ($columnasOrden as $col) {
        echo "<span style='background: #f0f0f0; padding: 2px 6px; margin: 2px; border-radius: 3px;'>{$col['Field']}</span> ";
    }
    echo "<br><br>";
    
    $stmt = $pdo->prepare("DESCRIBE autorizacion_centro_costo");
    $stmt->execute();
    $columnasAuth = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h4>Columnas de autorizacion_centro_costo:</h4>";
    foreach ($columnasAuth as $col) {
        echo "<span style='background: #f0f0f0; padding: 2px 6px; margin: 2px; border-radius: 3px;'>{$col['Field']}</span> ";
    }
    echo "<br><br>";

    echo "<h2>💰 Distribuciones de Gasto:</h2>";
    foreach ($distribuciones as $dist) {
        echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px; border-radius: 8px;'>";
        echo "<p><strong>Centro de Costo:</strong> {$dist['centro_nombre']} (ID: {$dist['centro_costo_id']})</p>";
        echo "<p><strong>Monto:</strong> Q" . number_format($dist['monto'] ?? 0, 2) . "</p>";
        
        // Buscar la autorización para este centro
        $autorizacionQuery = "
            SELECT acc.*, 
                   pa.email as autorizador_email,
                   pa.nombre as autorizador_nombre
            FROM autorizacion_centro_costo acc
            LEFT JOIN persona_autorizada pa ON acc.autorizador_id = pa.id
            WHERE acc.orden_compra_id = ? AND acc.centro_costo_id = ?
        ";
        
        // Ya tenemos $pdo definido arriba
        $stmt = $pdo->prepare($autorizacionQuery);
        $stmt->execute([$orden['id'], $dist['centro_costo_id']]);
        $autorizacion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($autorizacion) {
            echo "<h4>✅ Autorización encontrada:</h4>";
            echo "<p><strong>ID Autorización:</strong> {$autorizacion['id']}</p>";
            echo "<p><strong>Estado:</strong> <span style='color: blue;'>{$autorizacion['estado']}</span></p>";
            echo "<p><strong>Autorizador asignado:</strong> {$autorizacion['autorizador_email']}</p>";
            echo "<p><strong>Nombre autorizador:</strong> {$autorizacion['autorizador_nombre']}</p>";
            echo "<p><strong>Fecha autorización:</strong> " . ($autorizacion['fecha_autorizacion'] ?? 'N/A') . "</p>";
            echo "<p><strong>Comentarios:</strong> " . ($autorizacion['comentarios'] ?? 'N/A') . "</p>";
            
            // Verificar si el usuario actual es el autorizador
            $autorizadorEmail = $autorizacion['autorizador_email'] ?? null;
            $esMiCentro = ($usuarioEmail && $autorizadorEmail && strtolower(trim($autorizadorEmail)) === strtolower(trim($usuarioEmail)));
            
            echo "<h4>🔐 Verificación de Permisos:</h4>";
            echo "<p><strong>Email usuario actual:</strong> '$usuarioEmail'</p>";
            echo "<p><strong>Email autorizador requerido:</strong> '$autorizadorEmail'</p>";
            echo "<p><strong>¿Coinciden?:</strong> " . ($esMiCentro ? '✅ SÍ' : '❌ NO') . "</p>";
            
            // Mostrar condiciones para el botón
            echo "<h4>📋 Condiciones para mostrar botón AUTORIZAR:</h4>";
            echo "<p>1. Estado = 'pendiente': " . ($autorizacion['estado'] === 'pendiente' ? '✅ SÍ' : "❌ NO (actual: {$autorizacion['estado']})") . "</p>";
            echo "<p>2. Es mi centro: " . ($esMiCentro ? '✅ SÍ' : '❌ NO') . "</p>";
            echo "<p>3. Autorización existe: ✅ SÍ</p>";
            
            $mostrarBoton = ($autorizacion['estado'] === 'pendiente' && $esMiCentro);
            echo "<p><strong>¿Debe mostrar botón?:</strong> " . ($mostrarBoton ? '✅ SÍ' : '❌ NO') . "</p>";
            
        } else {
            echo "<h4>❌ NO se encontró autorización para este centro</h4>";
            echo "<p style='color: red;'>Esto significa que falta crear la autorización en la tabla autorizacion_centro_costo</p>";
        }
        
        echo "</div>";
    }
    
    // Verificar el flujo general
    echo "<h2>🔄 Estado del Flujo General:</h2>";
    $stmt = $pdo->prepare("SELECT * FROM autorizacion_flujo WHERE orden_compra_id = ? ORDER BY fecha_estado DESC LIMIT 1");
    $stmt->execute([$orden['id']]);
    $flujo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($flujo) {
        echo "<p><strong>Estado del flujo:</strong> {$flujo['estado']}</p>";
        echo "<p><strong>Fecha:</strong> {$flujo['fecha_estado']}</p>";
        echo "<p><strong>Comentarios:</strong> {$flujo['comentarios']}</p>";
    } else {
        echo "<p style='color: red;'>❌ No se encontró flujo de autorización</p>";
    }
    
    // Mostrar todos los autorizadores para debugg
    echo "<h2>👥 Todos los Autorizadores del Sistema:</h2>";
    $autorizadoresQuery = "SELECT id, email, nombre, activo FROM persona_autorizada ORDER BY email";
    $stmt = $pdo->prepare($autorizadoresQuery);
    $stmt->execute();
    $autorizadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($autorizadores as $aut) {
        $esActual = (strtolower(trim($aut['email'])) === strtolower(trim($usuarioEmail)));
        echo "<p" . ($esActual ? " style='background: yellow; font-weight: bold;'" : "") . ">";
        echo "📧 {$aut['email']} - {$aut['nombre']} - " . ($aut['activo'] ? '✅ Activo' : '❌ Inactivo');
        if ($esActual) echo " 👈 <strong>ESTE ERES TÚ</strong>";
        echo "</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    max-width: 1000px; 
    margin: 0 auto; 
    padding: 20px; 
    line-height: 1.6;
}
h1, h2, h4 { color: #2c3e50; }
p { margin: 5px 0; }
div { margin-bottom: 10px; }
</style>

<p><a href="/autorizaciones/2" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">🔙 Volver a Autorización 2</a></p>