<?php

namespace App\Repositories;

use App\Models\Model;
use App\Models\PersonaAutorizada;
use App\Models\AutorizadorRespaldo;
use PDO;

/**
 * Repositorio centralizado para manejar autorizaciones de centros de costo
 * utilizando exclusivamente la tabla unificada `autorizaciones`.
 */
class AutorizacionCentroRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Model::getConnection();
    }

    /**
     * Retorna todas las autorizaciones de centro asociadas a una requisición.
     */
    public function getByRequisicion(int $requisicionId): array
    {
        $sql = "
            SELECT 
                a.*,
                cc.nombre AS centro_nombre,
                CASE
                    WHEN JSON_EXTRACT(a.metadata, '$.porcentaje') IS NOT NULL
                        THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(a.metadata, '$.porcentaje')) AS DECIMAL(10,4))
                    ELSE NULL
                END AS porcentaje
            FROM autorizaciones a
            LEFT JOIN centro_de_costo cc ON a.centro_costo_id = cc.id
            WHERE a.requisicion_id = ?
              AND a.tipo = 'centro_costo'
            ORDER BY cc.nombre ASC, a.id ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$requisicionId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene autorizaciones pendientes para un autorizador (por email).
     */
    public function getPendingByEmail(string $email): array
    {
        $sql = "
            SELECT 
                a.*,
                cc.nombre AS centro_nombre,
                r.numero_requisicion,
                r.proveedor_nombre as nombre_razon_social,
                r.monto_total,
                r.fecha_solicitud as fecha,
                r.fecha_solicitud as fecha_orden,
                CASE
                    WHEN JSON_EXTRACT(a.metadata, '$.porcentaje') IS NOT NULL
                        THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(a.metadata, '$.porcentaje')) AS DECIMAL(10,4))
                    ELSE NULL
                END AS porcentaje
            FROM autorizaciones a
            INNER JOIN requisiciones r ON a.requisicion_id = r.id
            LEFT JOIN centro_de_costo cc ON a.centro_costo_id = cc.id
            WHERE a.autorizador_email = ?
              AND a.tipo = 'centro_costo'
              AND a.estado = 'pendiente'
            ORDER BY r.fecha_solicitud DESC, a.id ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve el número de autorizaciones pendientes para un autorizador (por email).
     */
    public function countPendingByEmail(string $email): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM autorizaciones
            WHERE autorizador_email = ?
              AND tipo = 'centro_costo'
              AND estado = 'pendiente'
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Verifica si existe al menos una autorización (en cualquier estado) para el email indicado.
     */
    public function existsByEmail(string $email): bool
    {
        $sql = "
            SELECT 1
            FROM autorizaciones
            WHERE autorizador_email = ?
              AND tipo = 'centro_costo'
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Obtiene una autorización específica por su ID.
     */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT 
                a.*,
                cc.nombre AS centro_nombre,
                r.numero_requisicion,
                r.proveedor_nombre as nombre_razon_social,
                r.monto_total,
                CASE
                    WHEN JSON_EXTRACT(a.metadata, '$.porcentaje') IS NOT NULL
                        THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(a.metadata, '$.porcentaje')) AS DECIMAL(10,4))
                    ELSE NULL
                END AS porcentaje
            FROM autorizaciones a
            LEFT JOIN centro_de_costo cc ON a.centro_costo_id = cc.id
            LEFT JOIN requisiciones r ON a.requisicion_id = r.id
            WHERE a.id = ?
              AND a.tipo = 'centro_costo'
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Marca una autorización como aprobada.
     */
    public function authorize(int $id, string $autorizadorEmail, string $comentario = ''): bool
    {
        $sql = "
            UPDATE autorizaciones
            SET estado = 'aprobada',
                comentarios = ?,
                fecha_respuesta = NOW()
            WHERE id = ?
              AND autorizador_email = ?
              AND tipo = 'centro_costo'
              AND estado = 'pendiente'
        ";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$comentario, $id, $autorizadorEmail]);
    }

    /**
     * Marca una autorización como rechazada.
     */
    public function reject(int $id, string $autorizadorEmail, string $motivo): bool
    {
        $sql = "
            UPDATE autorizaciones
            SET estado = 'rechazada',
                motivo_rechazo = ?,
                fecha_respuesta = NOW()
            WHERE id = ?
              AND autorizador_email = ?
              AND tipo = 'centro_costo'
              AND estado = 'pendiente'
        ";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$motivo, $id, $autorizadorEmail]);
    }

    /**
     * Verifica si todas las autorizaciones de centro de una requisición están aprobadas.
     */
    public function allAuthorized(int $requisicionId): bool
    {
        $sql = "
            SELECT 
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS pendientes,
                COUNT(*) AS total
            FROM autorizaciones
            WHERE requisicion_id = ?
              AND tipo = 'centro_costo'
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$requisicionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || (int)$row['total'] === 0) {
            return false;
        }

        return (int)$row['pendientes'] === 0;
    }

    /**
     * Verifica si alguna autorización de centro de una requisición fue rechazada.
     */
    public function hasRejected(int $requisicionId): bool
    {
        $sql = "
            SELECT COUNT(*)
            FROM autorizaciones
            WHERE requisicion_id = ?
              AND tipo = 'centro_costo'
              AND estado = 'rechazada'
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$requisicionId]);

        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Obtiene el progreso de autorizaciones de una requisición.
     */
    public function getProgress(int $requisicionId): array
    {
        $sql = "
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN estado = 'aprobada' THEN 1 ELSE 0 END) AS aprobadas,
                SUM(CASE WHEN estado = 'rechazada' THEN 1 ELSE 0 END) AS rechazadas,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS pendientes
            FROM autorizaciones
            WHERE requisicion_id = ?
              AND tipo = 'centro_costo'
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$requisicionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total' => 0,
            'aprobadas' => 0,
            'rechazadas' => 0,
            'pendientes' => 0,
        ];

        $total = (int)($row['total'] ?? 0);
        $row['porcentaje_completado'] = $total > 0
            ? round(((int)$row['aprobadas'] + (int)$row['rechazadas']) * 100 / $total, 2)
            : 0;

        return $row;
    }

    /**
     * Obtiene autorizaciones por estado específico.
     */
    public function getByEstado(int $requisicionId, string $estado): array
    {
        $sql = "
            SELECT 
                a.*,
                cc.nombre AS centro_nombre,
                CASE
                    WHEN JSON_EXTRACT(a.metadata, '$.porcentaje') IS NOT NULL
                        THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(a.metadata, '$.porcentaje')) AS DECIMAL(10,4))
                    ELSE NULL
                END AS porcentaje
            FROM autorizaciones a
            LEFT JOIN centro_de_costo cc ON a.centro_costo_id = cc.id
            WHERE a.requisicion_id = ?
              AND a.tipo = 'centro_costo'
              AND a.estado = ?
            ORDER BY cc.nombre ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$requisicionId, $estado]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene estadísticas agregadas para un centro de costo.
     */
    public function getStatsByCentro(int $centroCostoId): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total_autorizaciones,
                SUM(CASE WHEN estado = 'aprobada' THEN 1 ELSE 0 END) AS aprobadas,
                SUM(CASE WHEN estado = 'rechazada' THEN 1 ELSE 0 END) AS rechazadas,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS pendientes,
                AVG(
                    CAST(
                        JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.porcentaje')) AS DECIMAL(10,4)
                    )
                ) AS porcentaje_promedio
            FROM autorizaciones
            WHERE centro_costo_id = ?
              AND tipo = 'centro_costo'
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$centroCostoId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_autorizaciones' => 0,
            'aprobadas' => 0,
            'rechazadas' => 0,
            'pendientes' => 0,
            'porcentaje_promedio' => 0,
        ];
    }

    /**
     * Crea autorizaciones de centro de costo a partir de la distribución de gastos.
     */
    public function createFromDistribucion(int $requisicionId): void
    {
        // Obtener centros de costo de la distribución actual
        $sqlDistribucion = "SELECT DISTINCT centro_costo_id FROM distribucion_gasto WHERE requisicion_id = ?";
        $stmtDist = $this->pdo->prepare($sqlDistribucion);
        $stmtDist->execute([$requisicionId]);
        $centrosDistribucion = $stmtDist->fetchAll(PDO::FETCH_COLUMN);
        
        // Verificar si ya existen autorizaciones para esta requisición
        $sqlCheck = "
            SELECT centro_costo_id 
            FROM autorizaciones 
            WHERE requisicion_id = ? AND tipo = 'centro_costo'
        ";
        $stmtCheck = $this->pdo->prepare($sqlCheck);
        $stmtCheck->execute([$requisicionId]);
        $centrosExistentes = $stmtCheck->fetchAll(PDO::FETCH_COLUMN);
        
        // Si hay autorizaciones existentes, verificar que coincidan con la distribución
        if (!empty($centrosExistentes)) {
            sort($centrosDistribucion);
            sort($centrosExistentes);
            
            if ($centrosDistribucion == $centrosExistentes) {
                error_log("AutorizacionCentroRepository: Autorizaciones ya existen y coinciden para requisición $requisicionId");
                return; // Ya existen autorizaciones válidas
            } else {
                // Los centros no coinciden, eliminar las autorizaciones antiguas y recrear
                error_log("AutorizacionCentroRepository: Centros no coinciden, recreando autorizaciones para requisición $requisicionId");
                error_log("Distribución: " . json_encode($centrosDistribucion) . " vs Existentes: " . json_encode($centrosExistentes));
                
                $sqlDelete = "DELETE FROM autorizaciones WHERE requisicion_id = ? AND tipo = 'centro_costo'";
                $stmtDelete = $this->pdo->prepare($sqlDelete);
                $stmtDelete->execute([$requisicionId]);
            }
        }

        $sqlCentros = "
            SELECT 
                dg.centro_costo_id,
                SUM(dg.porcentaje) AS porcentaje_total
            FROM distribucion_gasto dg
            WHERE dg.requisicion_id = ?
            GROUP BY dg.centro_costo_id
        ";

        $stmtCentros = $this->pdo->prepare($sqlCentros);
        $stmtCentros->execute([$requisicionId]);
        $centros = $stmtCentros->fetchAll(PDO::FETCH_ASSOC);

        foreach ($centros as $centro) {
            $centroCostoId = (int)$centro['centro_costo_id'];
            $porcentaje = (float)$centro['porcentaje_total'];

            $autorizadores = [];

            $principal = PersonaAutorizada::principalPorCentro($centroCostoId);
            if ($principal && !empty($principal['email'])) {
                $autorizadores[] = [
                    'email' => $principal['email'],
                    'nombre' => $principal['nombre'] ?? null,
                    'es_respaldo' => false,
                    'motivo' => null,
                ];
            }

            $respaldo = AutorizadorRespaldo::activoPorCentro($centroCostoId);
            if ($respaldo && !empty($respaldo['autorizador_respaldo_email'])) {
                $autorizadores[] = [
                    'email' => $respaldo['autorizador_respaldo_email'],
                    'nombre' => $respaldo['nombre_respaldo'] ?? null,
                    'es_respaldo' => true,
                    'motivo' => $respaldo['motivo'] ?? null,
                ];
            }

            foreach ($autorizadores as $autorizador) {
                $metadata = json_encode([
                    'es_respaldo' => $autorizador['es_respaldo'],
                    'motivo_respaldo' => $autorizador['motivo'],
                    'porcentaje' => $porcentaje,
                ]);

                $sqlInsert = "
                    INSERT INTO autorizaciones
                    (requisicion_id, tipo, centro_costo_id, autorizador_email, autorizador_nombre, estado, metadata, created_at)
                    VALUES (?, 'centro_costo', ?, ?, ?, 'pendiente', ?, NOW())
                ";

                $stmtInsert = $this->pdo->prepare($sqlInsert);
                $stmtInsert->execute([
                    $requisicionId,
                    $centroCostoId,
                    $autorizador['email'],
                    $autorizador['nombre'],
                    $metadata,
                ]);
            }
        }
    }
}

