<?php
/**
 * Script para recrear autorizadores desde cero
 * Basado en la tabla proporcionada, sin duplicados
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\CentroCosto;
use App\Models\Autorizador;
use App\Models\UnidadNegocio;

try {
    $conn = CentroCosto::getConnection();
    $conn->beginTransaction();
    
    echo "========================================\n";
    echo "  RECREACIÓN DE AUTORIZADORES\n";
    echo "========================================\n\n";
    
    // ================================================================
    // 1. LIMPIAR TABLAS RELACIONADAS
    // ================================================================
    
    echo "1. Limpiando tablas relacionadas...\n";
    
    // Desactivar restricciones de clave foránea temporalmente
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Limpiar relaciones
    $conn->exec("DELETE FROM autorizador_centro_costo");
    echo "   ✓ Limpiada tabla autorizador_centro_costo\n";
    
    // Limpiar autorizadores (pero mantener los que ya existen si tienen email único)
    $conn->exec("DELETE FROM autorizadores WHERE email NOT IN ('bgutierrez@sp.iga.edu', 'bguti@sp.iga.edu')");
    echo "   ✓ Limpiada tabla autorizadores (excepto bgutierrez/bguti)\n";
    
    // Reactivar restricciones
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // ================================================================
    // 2. MAPEO DE DATOS DE LA TABLA
    // ================================================================
    
    $datos = [
        ['centro' => 'PARQUEO GENERAL', 'unidad' => 'ADMINISTRACION', 'autorizador' => 'Milton Santizo', 'email' => 'milton.santizo@iga.edu'],
        ['centro' => 'ACTIVIDADES CULTURALES', 'unidad' => 'ACTIVIDADES CULTURALES', 'autorizador' => 'Fidel Zelada', 'email' => 'fidel.zelada@iga.edu'],
        ['centro' => 'BODEGA', 'unidad' => 'COMERCIAL', 'autorizador' => 'Marlen Cruz', 'email' => 'marlen.cruz@iga.edu'],
        ['centro' => 'DISTRIBUCION FISICA', 'unidad' => 'COMERCIAL', 'autorizador' => 'Marlen Cruz', 'email' => 'marlen.cruz@iga.edu'],
        ['centro' => 'DISTRIBUIDORA', 'unidad' => 'COMERCIAL', 'autorizador' => 'Marlen Cruz', 'email' => 'marlen.cruz@iga.edu'],
        ['centro' => 'LIBRERIA COBAN', 'unidad' => 'COMERCIAL', 'autorizador' => 'Marlen Cruz', 'email' => 'marlen.cruz@iga.edu'],
        ['centro' => 'LIBRERIA QUETZALTENANGO', 'unidad' => 'COMERCIAL', 'autorizador' => 'Marlen Cruz', 'email' => 'marlen.cruz@iga.edu'],
        ['centro' => 'LIBRERIA ZONA 4', 'unidad' => 'COMERCIAL', 'autorizador' => 'Marlen Cruz', 'email' => 'marlen.cruz@iga.edu'],
        ['centro' => 'BASICOS', 'unidad' => 'COLEGIO', 'autorizador' => 'Odra Argueta', 'email' => 'odra.argueta@iga.edu'],
        ['centro' => 'BACHILLERATO', 'unidad' => 'COLEGIO', 'autorizador' => 'Odra Argueta', 'email' => 'odra.argueta@iga.edu'],
        ['centro' => 'PERITO CONTADOR', 'unidad' => 'COLEGIO', 'autorizador' => 'Odra Argueta', 'email' => 'odra.argueta@iga.edu'],
        ['centro' => 'SECRETARIADO', 'unidad' => 'COLEGIO', 'autorizador' => 'Odra Argueta', 'email' => 'odra.argueta@iga.edu'],
        ['centro' => 'PRIMARIA', 'unidad' => 'COLEGIO', 'autorizador' => 'Odra Argueta', 'email' => 'odra.argueta@iga.edu'],
        ['centro' => 'CURSOS ADULTOS Z.4', 'unidad' => 'CURSOS ADULTOS', 'autorizador' => 'Héctor Alvarado', 'email' => 'hector.alvarado@iga.edu'],
        ['centro' => 'CURSOS EMPRESARIALES', 'unidad' => 'CURSOS ADULTOS', 'autorizador' => 'Héctor Alvarado', 'email' => 'hector.alvarado@iga.edu'],
        ['centro' => 'CURSOS ADULTOS DEPARTAMENTOS', 'unidad' => 'CURSOS ADULTOS', 'autorizador' => 'Héctor Alvarado', 'email' => 'hector.alvarado@iga.edu'],
        ['centro' => 'PROGRAMAS EXTERNOS', 'unidad' => 'CURSOS ADULTOS', 'autorizador' => 'Héctor Alvarado', 'email' => 'hector.alvarado@iga.edu'],
        ['centro' => 'DIRECCION GENERAL', 'unidad' => 'ADMINISTRACION', 'autorizador' => 'Ana Sylvia Ramírez', 'email' => 'ana.ramirez@iga.edu'],
        ['centro' => 'EDUCATION USA', 'unidad' => 'ADMINISTRACION', 'autorizador' => 'Rodrigo Molina', 'email' => 'rodrigo.molina@iga.edu'],
        ['centro' => 'FINANZAS', 'unidad' => 'ADMINISTRACION', 'autorizador' => 'Milton Santizo', 'email' => 'milton.santizo@iga.edu'],
        ['centro' => 'SISTEMAS', 'unidad' => 'ADMINISTRACION', 'autorizador' => 'José Adan Arias', 'email' => 'jose.arias@iga.edu'],
        ['centro' => 'MERCADEO', 'unidad' => 'ADMINISTRACION', 'autorizador' => 'Rodrigo Molina', 'email' => 'rodrigo.molina@iga.edu'],
        ['centro' => 'ORGANIZACION Y PROCEDIMIENTOS', 'unidad' => 'ADMINISTRACION', 'autorizador' => 'Rodrigo Molina', 'email' => 'rodrigo.molina@iga.edu'],
        ['centro' => 'OPERACIONES', 'unidad' => 'ADMINISTRACION', 'autorizador' => 'Rodrigo Molina', 'email' => 'rodrigo.molina@iga.edu'],
        ['centro' => 'RECURSOS HUMANOS', 'unidad' => 'ADMINISTRACION', 'autorizador' => 'Rodrigo Molina', 'email' => 'rodrigo.molina@iga.edu'],
        ['centro' => 'SERVICIO AL CLIENTE', 'unidad' => 'ADMINISTRACION', 'autorizador' => 'Rodrigo Molina', 'email' => 'rodrigo.molina@iga.edu'],
        ['centro' => 'UNIDAD ACADEMICA', 'unidad' => 'ADMINISTRACION', 'autorizador' => 'Héctor Alvarado', 'email' => 'hector.alvarado@iga.edu'],
        ['centro' => 'BIBLIOTECA', 'unidad' => 'ACTIVIDADES CULTURALES', 'autorizador' => 'Mitchel Molina', 'email' => 'mitchel.molina@iga.edu'],
        ['centro' => 'CURSOS NIÑOS Y ADOLECENTES Z.4', 'unidad' => 'CURSOS NIÑOS', 'autorizador' => 'Héctor Alvarado', 'email' => 'hector.alvarado@iga.edu'],
        ['centro' => 'CENTRO DE COSTO GENERAL', 'unidad' => 'UNIDAD DE NEGOCIO GENERAL', 'autorizador' => 'Rodrigo Molina', 'email' => 'rodrigo.molina@iga.edu'],
    ];
    
    echo "\n2. Creando autorizadores únicos...\n";
    
    // Extraer autorizadores únicos
    $autorizadoresUnicos = [];
    foreach ($datos as $item) {
        $nombre = $item['autorizador'];
        if (!isset($autorizadoresUnicos[$nombre])) {
            $autorizadoresUnicos[$nombre] = $item['email'];
        }
    }
    
    // Crear o actualizar autorizadores
    $autorizadoresIds = [];
    foreach ($autorizadoresUnicos as $nombre => $email) {
        // Verificar si ya existe
        $sql = "SELECT id FROM autorizadores WHERE email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$email]);
        $existente = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($existente) {
            // Actualizar nombre si es necesario
            $sql = "UPDATE autorizadores SET nombre = ?, activo = 1 WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$nombre, $existente['id']]);
            $autorizadoresIds[$nombre] = $existente['id'];
            echo "   ✓ Actualizado: {$nombre} ({$email})\n";
        } else {
            // Crear nuevo
            $sql = "INSERT INTO autorizadores (nombre, email, activo) VALUES (?, ?, 1)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$nombre, $email]);
            $autorizadoresIds[$nombre] = $conn->lastInsertId();
            echo "   ✓ Creado: {$nombre} ({$email})\n";
        }
    }
    
    // Manejar emails bgutierrez y bguti
    $emailsEspeciales = ['bgutierrez@sp.iga.edu', 'bguti@sp.iga.edu'];
    foreach ($emailsEspeciales as $email) {
        $sql = "SELECT id FROM autorizadores WHERE email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$email]);
        $existente = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$existente) {
            // Crear autorizador con uno de estos emails si no existe
            $nombre = $email === 'bgutierrez@sp.iga.edu' ? 'B. Gutierrez' : 'B. Guti';
            $sql = "INSERT INTO autorizadores (nombre, email, activo) VALUES (?, ?, 1)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$nombre, $email]);
            echo "   ✓ Creado: {$nombre} ({$email})\n";
        }
    }
    
    echo "\n3. Creando relaciones autorizador-centro de costo...\n";
    
    // Obtener todos los centros de costo existentes
    $sql = "SELECT id, nombre FROM centro_de_costo";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $centrosExistentes = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    // Crear mapa de centros por nombre
    $centrosMap = [];
    foreach ($centrosExistentes as $centro) {
        $nombreNormalizado = strtoupper(trim($centro['nombre']));
        $centrosMap[$nombreNormalizado] = [
            'id' => $centro['id'],
            'nombre' => $centro['nombre']
        ];
    }
    
    // Crear relaciones
    $relacionesCreadas = 0;
    $centrosNoEncontrados = [];
    
    foreach ($datos as $item) {
        $nombreCentro = strtoupper(trim($item['centro']));
        $nombreAutorizador = $item['autorizador'];
        
        // Buscar centro de costo (búsqueda flexible)
        $centroId = null;
        if (isset($centrosMap[$nombreCentro])) {
            $centroId = $centrosMap[$nombreCentro]['id'];
        } else {
            // Intentar búsqueda parcial
            foreach ($centrosMap as $nombreMap => $centro) {
                if (strpos($nombreMap, $nombreCentro) !== false || strpos($nombreCentro, $nombreMap) !== false) {
                    $centroId = $centro['id'];
                    echo "   ℹ Centro encontrado por búsqueda parcial: {$item['centro']} -> {$centro['nombre']}\n";
                    break;
                }
            }
        }
        
        if (!$centroId) {
            $centrosNoEncontrados[] = $item['centro'];
            continue;
        }
        
        $autorizadorId = $autorizadoresIds[$nombreAutorizador] ?? null;
        
        if (!$autorizadorId) {
            echo "   ⚠ Autorizador no encontrado: {$nombreAutorizador}\n";
            continue;
        }
        
        // Verificar si ya existe la relación
        $sql = "SELECT id FROM autorizador_centro_costo 
                WHERE autorizador_id = ? AND centro_costo_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$autorizadorId, $centroId]);
        $existe = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$existe) {
            // Crear relación
            $sql = "INSERT INTO autorizador_centro_costo 
                    (autorizador_id, centro_costo_id, es_principal, activo) 
                    VALUES (?, ?, 1, 1)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$autorizadorId, $centroId]);
            $relacionesCreadas++;
        }
    }
    
    echo "   ✓ Creadas {$relacionesCreadas} relaciones\n";
    
    if (!empty($centrosNoEncontrados)) {
        echo "\n   ⚠ Centros no encontrados:\n";
        foreach ($centrosNoEncontrados as $centro) {
            echo "      - {$centro}\n";
        }
    }
    
    $conn->commit();
    
    echo "\n========================================\n";
    echo "  RESUMEN\n";
    echo "========================================\n\n";
    echo "Autorizadores creados/actualizados: " . count($autorizadoresIds) . "\n";
    echo "Relaciones creadas: {$relacionesCreadas}\n";
    echo "\n✓ Proceso completado exitosamente!\n";
    
} catch (\Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

