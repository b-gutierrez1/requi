<?php
/**
 * Modelo Autorizador
 * 
 * Representa un autorizador que puede gestionar múltiples unidades de negocio.
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
     * Obtiene todos los unidades de negocio asignados a este autorizador
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
                FROM autorizador_unidad_negocio acc
                INNER JOIN unidad_de_negocio cc ON cc.id = acc.unidad_negocio_id
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
     * Obtiene autorizadores de un unidad de negocio específico
     * 
     * @param int $unidadNegocioId
     * @param bool $soloPrincipal Si es true, solo devuelve el principal
     * @return array
     */
    public static function porUnidadNegocio($unidadNegocioId, $soloPrincipal = false)
    {
        $sql = "SELECT 
                    a.*,
                    acc.es_principal,
                    acc.unidad_negocio_id
                FROM autorizadores a
                INNER JOIN autorizador_unidad_negocio acc ON acc.autorizador_id = a.id
                WHERE acc.unidad_negocio_id = ?
                AND a.activo = 1
                AND acc.activo = 1";
        
        if ($soloPrincipal) {
            $sql .= " AND acc.es_principal = 1";
        }
        
        $sql .= " ORDER BY acc.es_principal DESC, a.nombre ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$unidadNegocioId]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el autorizador principal de un unidad de negocio
     * 
     * @param int $unidadNegocioId
     * @return array|null
     */
    public static function principalPorCentro($unidadNegocioId)
    {
        $sql = "SELECT 
                    a.*,
                    acc.unidad_negocio_id
                FROM autorizadores a
                INNER JOIN autorizador_unidad_negocio acc ON acc.autorizador_id = a.id
                WHERE acc.unidad_negocio_id = ?
                AND a.activo = 1
                AND acc.activo = 1
                AND acc.es_principal = 1
                ORDER BY a.id ASC
                LIMIT 1";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$unidadNegocioId]);
        
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
                LEFT JOIN autorizador_unidad_negocio acc ON acc.autorizador_id = a.id AND acc.activo = 1
                WHERE a.activo = 1
                GROUP BY a.id
                ORDER BY a.nombre ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Asigna un unidad de negocio a un autorizador
     * 
     * @param int $autorizadorId
     * @param int $unidadNegocioId
     * @param bool $esPrincipal
     * @return bool
     */
    public static function asignarCentro($autorizadorId, $unidadNegocioId, $esPrincipal = true)
    {
        try {
            $sql = "INSERT INTO autorizador_unidad_negocio (autorizador_id, unidad_negocio_id, es_principal, activo)
                    VALUES (?, ?, ?, 1)
                    ON DUPLICATE KEY UPDATE 
                        es_principal = VALUES(es_principal),
                        activo = VALUES(activo)";
            
            $stmt = self::getConnection()->prepare($sql);
            return $stmt->execute([$autorizadorId, $unidadNegocioId, $esPrincipal ? 1 : 0]);
        } catch (\Exception $e) {
            error_log("Error asignando centro a autorizador: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remueve un unidad de negocio de un autorizador
     * 
     * @param int $autorizadorId
     * @param int $unidadNegocioId
     * @return bool
     */
    public static function removerCentro($autorizadorId, $unidadNegocioId)
    {
        try {
            $sql = "UPDATE autorizador_unidad_negocio 
                    SET activo = 0 
                    WHERE autorizador_id = ? AND unidad_negocio_id = ?";
            
            $stmt = self::getConnection()->prepare($sql);
            return $stmt->execute([$autorizadorId, $unidadNegocioId]);
        } catch (\Exception $e) {
            error_log("Error removiendo centro de autorizador: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene unidades de negocio por email del autorizador
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
                INNER JOIN autorizador_unidad_negocio acc ON acc.autorizador_id = a.id
                INNER JOIN unidad_de_negocio cc ON cc.id = acc.unidad_negocio_id
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
     * @param int $unidadNegocioId
     * @return bool
     */
    public static function esAutorizadorDe($email, $unidadNegocioId)
    {
        $sql = "SELECT COUNT(*) as total
                FROM autorizadores a
                INNER JOIN autorizador_unidad_negocio acc ON acc.autorizador_id = a.id
                WHERE a.email = ?
                AND acc.unidad_negocio_id = ?
                AND a.activo = 1
                AND acc.activo = 1";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$email, $unidadNegocioId]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return ($result['total'] ?? 0) > 0;
    }
}







