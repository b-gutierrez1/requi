<?php

namespace App\Repositories;

use App\Models\Model;
use PDO;

/**
 * Repositorio legacy que ahora delega a la tabla unificada `autorizaciones`.
 * Se mantiene por compatibilidad con controladores refactorizados que aún lo consumen.
 */
class AutorizacionRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Model::getConnection();
    }

    /**
     * Obtiene autorizaciones de una requisición filtradas por email del autorizador.
     */
    public function findByOrdenAndEmail(int $ordenId, string $email): array
    {
        $sql = "
            SELECT 
                a.*,
                cc.nombre AS centro_nombre,
                oc.nombre_razon_social,
                oc.monto_total,
                oc.fecha AS fecha_orden,
                CASE
                    WHEN JSON_EXTRACT(a.metadata, '$.porcentaje') IS NOT NULL
                        THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(a.metadata, '$.porcentaje')) AS DECIMAL(10,4))
                    ELSE NULL
                END AS porcentaje
            FROM autorizaciones a
            LEFT JOIN centro_de_costo cc ON a.centro_costo_id = cc.id
            LEFT JOIN requisiciones oc ON a.requisicion_id = oc.id
            WHERE a.requisicion_id = ?
              AND a.autorizador_email = ?
              AND a.tipo = 'centro_costo'
            ORDER BY a.id ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$ordenId, $email]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Obtiene todas las autorizaciones por centro de costo de una requisición.
     */
    public function findByOrden(int $ordenId): array
    {
        $sql = "
            SELECT 
                a.*,
                cc.nombre AS centro_nombre,
                oc.nombre_razon_social,
                oc.monto_total,
                oc.fecha AS fecha_orden,
                CASE
                    WHEN JSON_EXTRACT(a.metadata, '$.porcentaje') IS NOT NULL
                        THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(a.metadata, '$.porcentaje')) AS DECIMAL(10,4))
                    ELSE NULL
                END AS porcentaje
            FROM autorizaciones a
            LEFT JOIN centro_de_costo cc ON a.centro_costo_id = cc.id
            LEFT JOIN requisiciones oc ON a.requisicion_id = oc.id
            WHERE a.requisicion_id = ?
              AND a.tipo = 'centro_costo'
            ORDER BY a.id ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$ordenId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Busca una autorización por su ID.
     */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT 
                a.*,
                cc.nombre AS centro_nombre,
                oc.nombre_razon_social,
                oc.monto_total,
                CASE
                    WHEN JSON_EXTRACT(a.metadata, '$.porcentaje') IS NOT NULL
                        THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(a.metadata, '$.porcentaje')) AS DECIMAL(10,4))
                    ELSE NULL
                END AS porcentaje
            FROM autorizaciones a
            LEFT JOIN centro_de_costo cc ON a.centro_costo_id = cc.id
            LEFT JOIN requisiciones oc ON a.requisicion_id = oc.id
            WHERE a.id = ?
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Obtiene autorizaciones pendientes para un email.
     */
    public function findPendingByEmail(string $email): array
    {
        $sql = "
            SELECT 
                a.*,
                cc.nombre AS centro_nombre,
                oc.nombre_razon_social AS proveedor,
                oc.monto_total,
                oc.fecha AS fecha_orden,
                CASE
                    WHEN JSON_EXTRACT(a.metadata, '$.porcentaje') IS NOT NULL
                        THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(a.metadata, '$.porcentaje')) AS DECIMAL(10,4))
                    ELSE NULL
                END AS porcentaje
            FROM autorizaciones a
            LEFT JOIN centro_de_costo cc ON a.centro_costo_id = cc.id
            LEFT JOIN requisiciones oc ON a.requisicion_id = oc.id
            WHERE a.autorizador_email = ?
              AND a.estado = 'pendiente'
              AND a.tipo = 'centro_costo'
            ORDER BY oc.fecha DESC, a.id ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Métodos de creación/actualización ya no son soportados en este repositorio.
     * Se conserva la firma para compatibilidad pero se arroja una excepción.
     */
    public function create(array $data): int
    {
        throw new \BadMethodCallException('AutorizacionRepository::create ha sido deprecado. Utiliza AutorizacionCentroRepository.');
    }

    public function update(int $id, array $data): bool
    {
        throw new \BadMethodCallException('AutorizacionRepository::update ha sido deprecado. Utiliza AutorizacionCentroRepository.');
    }
}
