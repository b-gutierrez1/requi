<?php
/**
 * Modelo Autorizador
 * 
 * Representa un autorizador que puede gestionar múltiples centros de costo.
 * Reemplaza la lógica antigua de persona_autorizada con relación 1:N.
 * 
 * @package RequisicionesMVC\Models
 * @version 3.0
 */

namespace App\Models;

class Autorizador extends Model
{
    protected static $table = 'autorizadores';
    protected static $primaryKey = 'id';
    protected static $timestamps = false;

    protected static $fillable = [
        'nombre',
        'email',
        'activo'
    ];

    protected static $guarded = ['id', 'fecha_creacion', 'fecha_actualizacion'];

    /**
     * Obtiene todos los centros de costo asignados a este autorizador
     * 
     * @return array
     */
    public function centrosCosto()
    {
        if (!isset($this->attributes['id'])) {
            return [];
        }

        $sql = "SELECT 
                    cc.*,
                    acc.es_principal,
                    acc.activo AS asignacion_activa
                FROM autorizador_centro_costo acc
                INNER JOIN centro_de_costo cc ON cc.id = acc.centro_costo_id
                WHERE acc.autorizador_id = ?
                AND acc.activo = 1
                ORDER BY cc.nombre ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$this->attributes['id']]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca autorizador por email
     * 
     * @param string $email
     * @return array|null
     */
    public static function porEmail($email)
    {
        $sql = "SELECT * FROM autorizadores WHERE email = ? AND activo = 1 LIMIT 1";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$email]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Obtiene autorizadores de un centro de costo específico
     * 
     * @param int $centroCostoId
     * @param bool $soloPrincipal Si es true, solo devuelve el principal
     * @return array
     */
    public static function porCentroCosto($centroCostoId, $soloPrincipal = false)
    {
        $sql = "SELECT 
                    a.*,
                    acc.es_principal,
                    acc.centro_costo_id
                FROM autorizadores a
                INNER JOIN autorizador_centro_costo acc ON acc.autorizador_id = a.id
                WHERE acc.centro_costo_id = ?
                AND a.activo = 1
                AND acc.activo = 1";
        
        if ($soloPrincipal) {
            $sql .= " AND acc.es_principal = 1";
        }
        
        $sql .= " ORDER BY acc.es_principal DESC, a.nombre ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$centroCostoId]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el autorizador principal de un centro de costo
     * 
     * @param int $centroCostoId
     * @return array|null
     */
    public static function principalPorCentro($centroCostoId)
    {
        $sql = "SELECT 
                    a.*,
                    acc.centro_costo_id
                FROM autorizadores a
                INNER JOIN autorizador_centro_costo acc ON acc.autorizador_id = a.id
                WHERE acc.centro_costo_id = ?
                AND a.activo = 1
                AND acc.activo = 1
                AND acc.es_principal = 1
                ORDER BY a.id ASC
                LIMIT 1";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$centroCostoId]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Obtiene todos los autorizadores activos
     * 
     * @return array
     */
    public static function todosActivos()
    {
        $sql = "SELECT 
                    a.*,
                    COUNT(acc.id) AS total_centros
                FROM autorizadores a
                LEFT JOIN autorizador_centro_costo acc ON acc.autorizador_id = a.id AND acc.activo = 1
                WHERE a.activo = 1
                GROUP BY a.id
                ORDER BY a.nombre ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Asigna un centro de costo a un autorizador
     * 
     * @param int $autorizadorId
     * @param int $centroCostoId
     * @param bool $esPrincipal
     * @return bool
     */
    public static function asignarCentro($autorizadorId, $centroCostoId, $esPrincipal = true)
    {
        try {
            $sql = "INSERT INTO autorizador_centro_costo (autorizador_id, centro_costo_id, es_principal, activo)
                    VALUES (?, ?, ?, 1)
                    ON DUPLICATE KEY UPDATE 
                        es_principal = VALUES(es_principal),
                        activo = VALUES(activo)";
            
            $stmt = self::getConnection()->prepare($sql);
            return $stmt->execute([$autorizadorId, $centroCostoId, $esPrincipal ? 1 : 0]);
        } catch (\Exception $e) {
            error_log("Error asignando centro a autorizador: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remueve un centro de costo de un autorizador
     * 
     * @param int $autorizadorId
     * @param int $centroCostoId
     * @return bool
     */
    public static function removerCentro($autorizadorId, $centroCostoId)
    {
        try {
            $sql = "UPDATE autorizador_centro_costo 
                    SET activo = 0 
                    WHERE autorizador_id = ? AND centro_costo_id = ?";
            
            $stmt = self::getConnection()->prepare($sql);
            return $stmt->execute([$autorizadorId, $centroCostoId]);
        } catch (\Exception $e) {
            error_log("Error removiendo centro de autorizador: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene centros de costo por email del autorizador
     * 
     * @param string $email
     * @return array
     */
    public static function centrosCostoPorEmail($email)
    {
        $sql = "SELECT 
                    cc.*,
                    acc.es_principal
                FROM autorizadores a
                INNER JOIN autorizador_centro_costo acc ON acc.autorizador_id = a.id
                INNER JOIN centro_de_costo cc ON cc.id = acc.centro_costo_id
                WHERE a.email = ?
                AND a.activo = 1
                AND acc.activo = 1
                ORDER BY cc.nombre ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$email]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Verifica si un email es autorizador de un centro específico
     * 
     * @param string $email
     * @param int $centroCostoId
     * @return bool
     */
    public static function esAutorizadorDe($email, $centroCostoId)
    {
        $sql = "SELECT COUNT(*) as total
                FROM autorizadores a
                INNER JOIN autorizador_centro_costo acc ON acc.autorizador_id = a.id
                WHERE a.email = ?
                AND acc.centro_costo_id = ?
                AND a.activo = 1
                AND acc.activo = 1";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$email, $centroCostoId]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return ($result['total'] ?? 0) > 0;
    }
}







