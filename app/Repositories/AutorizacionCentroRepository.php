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
     *
     * Solo devuelve autorizaciones cuyo turno ya llegó: no existe ninguna autorización
     * del mismo centro/requisición con nivel inferior todavía pendiente.
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
            INNER JOIN autorizacion_flujo af ON af.requisicion_id = a.requisicion_id
            LEFT JOIN centro_de_costo cc ON a.centro_costo_id = cc.id
            WHERE a.autorizador_email = ?
              AND a.tipo = 'centro_costo'
              AND a.estado = 'pendiente'
              AND af.estado NOT IN ('rechazado', 'cancelado')
              AND NOT EXISTS (
                  SELECT 1 FROM autorizaciones a2
                  WHERE a2.requisicion_id = a.requisicion_id
                    AND a2.centro_costo_id = a.centro_costo_id
                    AND a2.tipo = 'centro_costo'
                    AND a2.nivel < a.nivel
                    AND a2.estado = 'pendiente'
              )
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
            FROM autorizaciones a
            INNER JOIN autorizacion_flujo af ON af.requisicion_id = a.requisicion_id
            WHERE a.autorizador_email = ?
              AND a.tipo = 'centro_costo'
              AND a.estado = 'pendiente'
              AND af.estado NOT IN ('rechazado', 'cancelado')
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
     *
     * @throws \RuntimeException si hay una autorización de nivel anterior todavía pendiente
     *                           para el mismo centro/requisición (aprobación fuera de orden).
     */
    public function authorize(int $id, string $autorizadorEmail, string $comentario = ''): bool
    {
        // Guard de orden: bloquea aprobaciones fuera de turno desde backend.
        $sqlGuard = "
            SELECT COUNT(*)
            FROM autorizaciones a2
            INNER JOIN autorizaciones a1 ON a1.id = ?
            WHERE a2.requisicion_id = a1.requisicion_id
              AND a2.centro_costo_id = a1.centro_costo_id
              AND a2.tipo = 'centro_costo'
              AND a2.nivel < a1.nivel
              AND a2.estado = 'pendiente'
        ";
        $stmtGuard = $this->pdo->prepare($sqlGuard);
        $stmtGuard->execute([$id]);
        if ((int)$stmtGuard->fetchColumn() > 0) {
            throw new \RuntimeException('El autorizador de nivel anterior aún no ha aprobado este centro de costo.');
        }

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
     *
     * Soporta múltiples autorizadores secuenciales por centro: el campo `nivel` en
     * autorizaciones se popula con `acc.orden` de autorizador_centro_costo, de modo
     * que nivel=1 aprueba primero y nivel=2 no puede actuar hasta que nivel=1 apruebe.
     * Cada autorizador en la secuencia trae su propio respaldo activo (mismo nivel).
     *
     * @param int   $requisicionId
     * @param array $asignaciones  Mapa [centro_costo_id => autorizador_email] para asignación
     *                             manual por el revisor. Cuando se usa, se inserta un único
     *                             registro con nivel=1 (sin secuencia).
     */
    public function createFromDistribucion(int $requisicionId, array $asignaciones = []): void
    {
        // Centros de costo presentes en la distribución
        $stmtDist = $this->pdo->prepare(
            "SELECT DISTINCT centro_costo_id FROM distribucion_gasto WHERE requisicion_id = ?"
        );
        $stmtDist->execute([$requisicionId]);
        $centrosDistribucion = array_map('intval', $stmtDist->fetchAll(PDO::FETCH_COLUMN));

        // Autorizaciones ya existentes: comparar pares (centro_costo_id, autorizador_email)
        $stmtCheck = $this->pdo->prepare(
            "SELECT DISTINCT centro_costo_id
             FROM autorizaciones
             WHERE requisicion_id = ? AND tipo = 'centro_costo'"
        );
        $stmtCheck->execute([$requisicionId]);
        $centrosExistentes = array_map('intval', $stmtCheck->fetchAll(PDO::FETCH_COLUMN));

        if (!empty($centrosExistentes)) {
            $distSorted = $centrosDistribucion;
            $existSorted = $centrosExistentes;
            sort($distSorted);
            sort($existSorted);

            if ($distSorted === $existSorted) {
                error_log("AutorizacionCentroRepository: autorizaciones ya existen para requisición $requisicionId");
                return;
            }

            error_log("AutorizacionCentroRepository: centros no coinciden, recreando para requisición $requisicionId");
            $this->pdo->prepare(
                "DELETE FROM autorizaciones WHERE requisicion_id = ? AND tipo = 'centro_costo'"
            )->execute([$requisicionId]);
        }

        // Porcentaje por centro
        $stmtCentros = $this->pdo->prepare("
            SELECT centro_costo_id, SUM(porcentaje) AS porcentaje_total
            FROM distribucion_gasto
            WHERE requisicion_id = ?
            GROUP BY centro_costo_id
        ");
        $stmtCentros->execute([$requisicionId]);
        $centros = $stmtCentros->fetchAll(PDO::FETCH_ASSOC);

        $sqlInsert = "
            INSERT INTO autorizaciones
                (requisicion_id, tipo, centro_costo_id, autorizador_email, autorizador_nombre,
                 estado, nivel, metadata, created_at)
            VALUES (?, 'centro_costo', ?, ?, ?, 'pendiente', ?, ?, NOW())
        ";
        $stmtInsert = $this->pdo->prepare($sqlInsert);

        foreach ($centros as $centro) {
            $centroCostoId = (int)$centro['centro_costo_id'];
            $porcentaje    = (float)$centro['porcentaje_total'];

            // Asignación manual del revisor: nivel=1, sin secuencia
            if (!empty($asignaciones[$centroCostoId])) {
                $emailManual = $asignaciones[$centroCostoId];
                $metadata = json_encode([
                    'es_respaldo'       => false,
                    'motivo_respaldo'   => null,
                    'porcentaje'        => $porcentaje,
                    'asignacion_manual' => true,
                ]);
                $stmtInsert->execute([$requisicionId, $centroCostoId, $emailManual, null, 1, $metadata]);
                continue;
            }

            // Autorizadores configurados para este centro, ordenados por acc.orden
            $autorizadoresOrdenados = PersonaAutorizada::todosPorCentro($centroCostoId);

            foreach ($autorizadoresOrdenados as $autorizador) {
                $nivel = (int)$autorizador['orden'];

                $metadata = json_encode([
                    'es_respaldo'     => false,
                    'motivo_respaldo' => null,
                    'porcentaje'      => $porcentaje,
                    'orden'           => $nivel,
                ]);
                $stmtInsert->execute([
                    $requisicionId,
                    $centroCostoId,
                    $autorizador['email'],
                    $autorizador['nombre'],
                    $nivel,
                    $metadata,
                ]);
            }

            // El respaldo es por centro (reemplaza al autorizador de nivel=1)
            $respaldo = AutorizadorRespaldo::activoPorCentro($centroCostoId);
            if ($respaldo && !empty($respaldo['autorizador_respaldo_email'])) {
                $metadataRespaldo = json_encode([
                    'es_respaldo'     => true,
                    'motivo_respaldo' => $respaldo['motivo'] ?? null,
                    'porcentaje'      => $porcentaje,
                    'orden'           => 1,
                ]);
                $stmtInsert->execute([
                    $requisicionId,
                    $centroCostoId,
                    $respaldo['autorizador_respaldo_email'],
                    null,
                    1,
                    $metadataRespaldo,
                ]);
            }
        }
    }
}

