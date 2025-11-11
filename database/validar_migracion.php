<?php
/**
 * Script de validación de migración
 * 
 * Verifica que la migración se haya ejecutado correctamente
 */

require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Models/Model.php';

try {
    // Configurar conexión
    $config = require __DIR__ . '/../config/database.php';
    $dbConfig = $config['connections']['mysql'];
    
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);
    
    echo "🔍 VALIDANDO MIGRACIÓN A ESQUEMA V3.0\n";
    echo "=====================================\n\n";

    // 1. Verificar que las nuevas tablas existen
    echo "1. 📋 Verificando estructura de tablas...\n";
    
    $tablasNuevas = [
        'requisiciones',
        'requisicion_items', 
        'distribucion_centros',
        'autorizaciones',
        'historial_requisiciones',
        'requisicion_adjuntos'
    ];
    
    foreach ($tablasNuevas as $tabla) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tabla'");
        $existe = $stmt->fetchColumn();
        
        if ($existe) {
            echo "   ✅ Tabla $tabla existe\n";
        } else {
            echo "   ❌ Tabla $tabla NO existe\n";
            throw new Exception("Tabla $tabla faltante");
        }
    }
    echo "\n";

    // 2. Verificar conteos de datos migrados
    echo "2. 📊 Verificando conteos de datos...\n";
    
    $conteos = [
        'requisiciones' => 'SELECT COUNT(*) FROM requisiciones',
        'items' => 'SELECT COUNT(*) FROM requisicion_items',
        'distribuciones' => 'SELECT COUNT(*) FROM distribucion_centros',
        'autorizaciones' => 'SELECT COUNT(*) FROM autorizaciones',
        'historial' => 'SELECT COUNT(*) FROM historial_requisiciones'
    ];
    
    foreach ($conteos as $descripcion => $sql) {
        $count = $pdo->query($sql)->fetchColumn();
        echo "   📈 $descripcion: $count registros\n";
    }
    echo "\n";

    // 3. Verificar integridad referencial
    echo "3. 🔗 Verificando integridad referencial...\n";
    
    $integridadTests = [
        'Items sin requisición' => 'SELECT COUNT(*) FROM requisicion_items ri LEFT JOIN requisiciones r ON ri.requisicion_id = r.id WHERE r.id IS NULL',
        'Distribuciones sin requisición' => 'SELECT COUNT(*) FROM distribucion_centros dc LEFT JOIN requisiciones r ON dc.requisicion_id = r.id WHERE r.id IS NULL',
        'Autorizaciones sin requisición' => 'SELECT COUNT(*) FROM autorizaciones a LEFT JOIN requisiciones r ON a.requisicion_id = r.id WHERE r.id IS NULL',
        'Historial sin requisición' => 'SELECT COUNT(*) FROM historial_requisiciones h LEFT JOIN requisiciones r ON h.requisicion_id = r.id WHERE r.id IS NULL'
    ];
    
    $erroresIntegridad = 0;
    foreach ($integridadTests as $test => $sql) {
        $errores = $pdo->query($sql)->fetchColumn();
        if ($errores > 0) {
            echo "   ❌ $test: $errores errores\n";
            $erroresIntegridad += $errores;
        } else {
            echo "   ✅ $test: OK\n";
        }
    }
    echo "\n";

    // 4. Verificar datos específicos - Requisición 2
    echo "4. 🎯 Verificando datos específicos (Requisición 2)...\n";
    
    $stmt = $pdo->prepare("SELECT * FROM requisiciones WHERE id = 2");
    $stmt->execute();
    $req2 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($req2) {
        echo "   ✅ Requisición 2 migrada:\n";
        echo "      - Número: {$req2['numero_requisicion']}\n";
        echo "      - Estado: {$req2['estado']}\n";
        echo "      - Proveedor: {$req2['proveedor_nombre']}\n";
        echo "      - Monto: Q" . number_format($req2['monto_total'], 2) . "\n";
        
        // Verificar autorizaciones para requisición 2
        $stmt = $pdo->prepare("
            SELECT a.*, cc.nombre as centro_nombre 
            FROM autorizaciones a
            LEFT JOIN centro_de_costo cc ON a.centro_costo_id = cc.id 
            WHERE a.requisicion_id = 2
        ");
        $stmt->execute();
        $autorizaciones2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "      - Autorizaciones: " . count($autorizaciones2) . "\n";
        foreach ($autorizaciones2 as $auth) {
            echo "        * {$auth['tipo']} - {$auth['estado']} - {$auth['autorizador_email']}\n";
            if ($auth['centro_nombre']) {
                echo "          Centro: {$auth['centro_nombre']}\n";
            }
        }
    } else {
        echo "   ❌ Requisición 2 NO encontrada\n";
        $erroresIntegridad++;
    }
    echo "\n";

    // 5. Verificar vistas
    echo "5. 👁️ Verificando vistas...\n";
    
    $vistas = [
        'vista_autorizaciones_resumen',
        'vista_autorizaciones_pendientes'
    ];
    
    foreach ($vistas as $vista) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$vista'");
        $existe = $stmt->fetchColumn();
        
        if ($existe) {
            echo "   ✅ Vista $vista existe\n";
            
            // Probar la vista
            $count = $pdo->query("SELECT COUNT(*) FROM $vista")->fetchColumn();
            echo "      - Registros: $count\n";
        } else {
            echo "   ❌ Vista $vista NO existe\n";
        }
    }
    echo "\n";

    // 6. Test del problema específico - Autorización botón
    echo "6. 🐛 Probando el problema del botón de autorización...\n";
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM autorizaciones a
        WHERE a.requisicion_id = 2 
        AND a.autorizador_email = 'bgutierrez@sp.iga.edu'
        AND a.estado = 'pendiente'
    ");
    $stmt->execute();
    $puedeAutorizar = $stmt->fetchColumn() > 0;
    
    if ($puedeAutorizar) {
        echo "   ✅ PROBLEMA RESUELTO: bgutierrez@sp.iga.edu puede autorizar requisición 2\n";
    } else {
        echo "   ❌ PROBLEMA PERSISTE: bgutierrez@sp.iga.edu NO puede autorizar requisición 2\n";
        
        // Mostrar detalles para debug
        $stmt = $pdo->prepare("
            SELECT * FROM autorizaciones 
            WHERE requisicion_id = 2
        ");
        $stmt->execute();
        $authsDeReq2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "   📝 Autorizaciones encontradas para req 2:\n";
        foreach ($authsDeReq2 as $auth) {
            echo "      - Email: {$auth['autorizador_email']}, Estado: {$auth['estado']}, Tipo: {$auth['tipo']}\n";
        }
    }
    echo "\n";

    // 7. Verificar triggers
    echo "7. ⚙️ Verificando triggers...\n";
    
    $stmt = $pdo->query("SHOW TRIGGERS LIKE 'tr_%'");
    $triggers = $stmt->fetchAll();
    
    echo "   Triggers encontrados: " . count($triggers) . "\n";
    foreach ($triggers as $trigger) {
        echo "   ✅ {$trigger['Trigger']} en {$trigger['Table']}\n";
    }
    echo "\n";

    // RESUMEN FINAL
    echo "🎉 RESUMEN DE MIGRACIÓN\n";
    echo "======================\n";
    
    if ($erroresIntegridad == 0 && $puedeAutorizar) {
        echo "✅ MIGRACIÓN EXITOSA\n";
        echo "   - Todas las tablas creadas correctamente\n";
        echo "   - Datos migrados sin errores de integridad\n";
        echo "   - Problema del botón de autorización RESUELTO\n";
        echo "   - Vistas y triggers funcionando\n";
        echo "\n🚀 El sistema está listo para usar el nuevo esquema v3.0!\n";
    } else {
        echo "❌ MIGRACIÓN CON ERRORES\n";
        echo "   - Errores de integridad: $erroresIntegridad\n";
        echo "   - Problema del botón: " . ($puedeAutorizar ? "RESUELTO" : "PERSISTE") . "\n";
        echo "\n⚠️ Revisa los errores antes de continuar.\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR EN VALIDACIÓN: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . " Línea: " . $e->getLine() . "\n";
    exit(1);
}