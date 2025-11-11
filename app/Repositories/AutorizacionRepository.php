<?php

namespace App\Repositories;

use App\Models\Model;
use PDO;

/**
 * Repositorio para manejo de datos de autorizaciones
 * 
 * Responsabilidad: Abstraer el acceso a datos y consultas SQL
 */
class AutorizacionRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Model::getConnection();
    }

    /**
     * Busca autorización por orden y email del autorizador
     */
    public function findByOrdenAndEmail(int $ordenId, string $email): array
    {
        $sql = "
            SELECT acc.*, 
                   cc.nombre as centro_nombre,
                   pa.email as autorizador_email,
                   pa.nombre as autorizador_nombre
            FROM autorizacion_centro_costo acc
            LEFT JOIN centro_de_costo cc ON acc.centro_costo_id = cc.id
            LEFT JOIN persona_autorizada pa ON acc.autorizador_id = pa.id
            LEFT JOIN autorizacion_flujo af ON acc.autorizacion_flujo_id = af.id
            WHERE af.orden_compra_id = ? AND pa.email = ?
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$ordenId, $email]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca todas las autorizaciones de una orden
     */
    public function findByOrden(int $ordenId): array
    {
        $sql = "
            SELECT acc.*, 
                   cc.nombre as centro_nombre,
                   pa.email as autorizador_email,
                   pa.nombre as autorizador_nombre,
                   af.orden_compra_id
            FROM autorizacion_centro_costo acc
            LEFT JOIN centro_de_costo cc ON acc.centro_costo_id = cc.id
            LEFT JOIN persona_autorizada pa ON acc.autorizador_id = pa.id
            LEFT JOIN autorizacion_flujo af ON acc.autorizacion_flujo_id = af.id
            WHERE af.orden_compra_id = ?
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$ordenId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea una nueva autorización
     */
    public function create(array $data): int
    {
        $sql = "
            INSERT INTO autorizacion_centro_costo 
            (autorizacion_flujo_id, centro_costo_id, autorizador_id, estado, porcentaje) 
            VALUES (?, ?, ?, ?, ?)
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['autorizacion_flujo_id'],
            $data['centro_costo_id'],
            $data['autorizador_id'],
            $data['estado'] ?? 'pendiente',
            $data['porcentaje']
        ]);
        
        return $this->pdo->lastInsertId();
    }

    /**
     * Actualiza una autorización
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        
        foreach ($data as $field => $value) {
            $fields[] = "$field = ?";
            $values[] = $value;
        }
        $values[] = $id;
        
        $sql = "UPDATE autorizacion_centro_costo SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Busca autorización por ID
     */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT acc.*, 
                   cc.nombre as centro_nombre,
                   pa.email as autorizador_email,
                   pa.nombre as autorizador_nombre,
                   af.orden_compra_id
            FROM autorizacion_centro_costo acc
            LEFT JOIN centro_de_costo cc ON acc.centro_costo_id = cc.id
            LEFT JOIN persona_autorizada pa ON acc.autorizador_id = pa.id
            LEFT JOIN autorizacion_flujo af ON acc.autorizacion_flujo_id = af.id
            WHERE acc.id = ?
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Obtiene autorizaciones pendientes por email
     */
    public function findPendingByEmail(string $email): array
    {
        $sql = "
            SELECT acc.*, 
                   cc.nombre as centro_nombre,
                   pa.email as autorizador_email,
                   af.orden_compra_id,
                   oc.nombre_razon_social as proveedor,
                   oc.monto_total
            FROM autorizacion_centro_costo acc
            LEFT JOIN centro_de_costo cc ON acc.centro_costo_id = cc.id
            LEFT JOIN persona_autorizada pa ON acc.autorizador_id = pa.id
            LEFT JOIN autorizacion_flujo af ON acc.autorizacion_flujo_id = af.id
            LEFT JOIN orden_compra oc ON af.orden_compra_id = oc.id
            WHERE pa.email = ? AND acc.estado = 'pendiente'
            ORDER BY af.fecha_inicio DESC
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}