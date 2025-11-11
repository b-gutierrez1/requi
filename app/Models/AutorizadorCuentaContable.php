<?php
/**
 * Modelo AutorizadorCuentaContable
 * 
 * Gestiona autorizadores especiales según la cuenta contable.
 * Algunas cuentas (como donaciones) requieren autorización especial
 * y se ignora el centro de costo asociado.
 * 
 * @package RequisicionesMVC\Models
 * @version 2.0
 */

namespace App\Models;

class AutorizadorCuentaContable extends Model
{
    protected static $table = 'autorizadores_cuentas_contables';
    protected static $primaryKey = 'id';
    protected static $timestamps = false;

    protected static $fillable = [
        'cuenta_contable_id',
        'autorizador_email',
        'autorizador_nombre',
        'descripcion',
        'activo',
        'prioridad',
        'ignora_centro_costo',
    ];

    protected static $guarded = ['id'];

    /**
     * Obtiene la cuenta contable asociada
     * 
     * @return array|null
     */
    public function cuentaContable()
    {
        if (!isset($this->attributes['cuenta_contable_id'])) {
            return null;
        }

        return CuentaContable::find($this->attributes['cuenta_contable_id']);
    }

    /**
     * Obtiene autorizador por cuenta contable
     * 
     * @param int $cuentaContableId
     * @return array|null
     */
    public static function porCuentaContable($cuentaContableId)
    {
        $sql = "SELECT * FROM " . static::$table . " 
                WHERE cuenta_contable_id = ? 
                ORDER BY id ASC 
                LIMIT 1";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$cuentaContableId]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Verifica si una cuenta contable requiere autorización especial
     * 
     * @param int $cuentaContableId
     * @return bool
     */
    public static function requiereAutorizacionEspecial($cuentaContableId)
    {
        $sql = "SELECT COUNT(*) as total 
                FROM " . static::$table . " 
                WHERE cuenta_contable_id = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$cuentaContableId]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return ($result['total'] ?? 0) > 0;
    }

    /**
     * Verifica si se debe ignorar el centro de costo
     * 
     * @param int $cuentaContableId
     * @return bool
     */
    public static function ignoraCentroCosto($cuentaContableId)
    {
        try {
            $autorizador = self::porCuentaContable($cuentaContableId);
            
            if (!$autorizador) {
                return false;
            }
            
            return ($autorizador['ignora_centro_costo'] ?? 0) == 1;
        } catch (\Exception $e) {
            // Si la columna no existe, asumir que no ignora centros
            return false;
        }
    }

    /**
     * Obtiene todos los autorizadores activos
     * 
     * @return array
     */
    public static function todosActivos()
    {
        $sql = "SELECT acc.*, 
                    cc.codigo as cuenta_codigo,
                    cc.descripcion as cuenta_descripcion
                FROM " . static::$table . " acc
                INNER JOIN cuenta_contable cc ON acc.cuenta_contable_id = cc.id
                ORDER BY cc.codigo ASC, acc.id ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene autorizaciones por email del autorizador
     * 
     * @param string $email
     * @return array
     */
    public static function porEmail($email)
    {
        $sql = "SELECT acc.*, 
                    cc.codigo as cuenta_codigo,
                    cc.descripcion as cuenta_descripcion
                FROM " . static::$table . " acc
                INNER JOIN cuenta_contable cc ON acc.cuenta_contable_id = cc.id
                WHERE acc.autorizador_email = ? 
                ORDER BY cc.codigo ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$email]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Verifica si un email es autorizador de una cuenta
     * 
     * @param string $email
     * @param int $cuentaContableId
     * @return bool
     */
    public static function esAutorizadorDe($email, $cuentaContableId)
    {
        $sql = "SELECT COUNT(*) as total 
                FROM " . static::$table . " 
                WHERE autorizador_email = ? 
                AND cuenta_contable_id = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$email, $cuentaContableId]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return ($result['total'] ?? 0) > 0;
    }

    /**
     * Obtiene cuentas contables que requieren autorización especial
     * 
     * @return array
     */
    public static function cuentasConAutorizacion()
    {
        $sql = "SELECT DISTINCT cc.id, cc.codigo, cc.descripcion
                FROM " . static::$table . " acc
                INNER JOIN cuenta_contable cc ON acc.cuenta_contable_id = cc.id
                ORDER BY cc.codigo ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Activa o desactiva un autorizador
     * 
     * @param int $id
     * @param bool $activo
     * @return bool
     */
    public static function setActivo($id, $activo = true)
    {
        return self::update($id, ['activo' => $activo ? 1 : 0]);
    }

    /**
     * Obtiene autorizaciones pendientes para un autorizador
     * 
     * @param string $email
     * @return array
     */
    public static function autorizacionesPendientes($email)
    {
        $sql = "SELECT 
                    oc.*,
                    af.estado,
                    cc.codigo as cuenta_codigo,
                    cc.descripcion as cuenta_descripcion,
                    acc.descripcion as motivo_autorizacion
                FROM orden_compra oc
                INNER JOIN autorizacion_flujo af ON oc.id = af.orden_compra_id
                INNER JOIN distribucion_gasto dg ON oc.id = dg.orden_compra_id
                INNER JOIN autorizadores_cuentas_contables acc ON dg.cuenta_contable_id = acc.cuenta_contable_id
                INNER JOIN cuenta_contable cc ON acc.cuenta_contable_id = cc.id
                WHERE acc.autorizador_email = ?
                AND af.estado = 'pendiente_autorizacion_cuenta'
                GROUP BY oc.id
                ORDER BY oc.fecha DESC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$email]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Cuenta autorizaciones pendientes para un email
     * 
     * @param string $email
     * @return int
     */
    public static function contarPendientes($email)
    {
        $sql = "SELECT COUNT(DISTINCT oc.id) as total
                FROM orden_compra oc
                INNER JOIN autorizacion_flujo af ON oc.id = af.orden_compra_id
                INNER JOIN distribucion_gasto dg ON oc.id = dg.orden_compra_id
                INNER JOIN autorizadores_cuentas_contables acc ON dg.cuenta_contable_id = acc.cuenta_contable_id
                WHERE acc.autorizador_email = ?
                AND af.estado = 'pendiente_autorizacion_cuenta'";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$email]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Obtiene las cuentas especiales configuradas (ej: donaciones)
     * 
     * @return array
     */
    public static function cuentasEspeciales()
    {
        $sql = "SELECT acc.*, 
                    cc.codigo as cuenta_codigo,
                    cc.descripcion as cuenta_descripcion
                FROM " . static::$table . " acc
                INNER JOIN cuenta_contable cc ON acc.cuenta_contable_id = cc.id
                WHERE acc.ignora_centro_costo = 1
                ORDER BY cc.codigo ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene estadísticas de autorizaciones por cuenta
     * 
     * @param int $cuentaContableId
     * @return array
     */
    public static function getEstadisticasCuenta($cuentaContableId)
    {
        $sql = "SELECT 
                    COUNT(DISTINCT dg.orden_compra_id) as total_requisiciones,
                    SUM(CASE WHEN af.estado = 'autorizado' THEN 1 ELSE 0 END) as autorizadas,
                    SUM(CASE WHEN af.estado = 'rechazado' THEN 1 ELSE 0 END) as rechazadas,
                    SUM(CASE WHEN af.estado = 'pendiente_autorizacion' THEN 1 ELSE 0 END) as pendientes,
                    SUM(dg.cantidad) as monto_total
                FROM distribucion_gasto dg
                INNER JOIN orden_compra oc ON dg.orden_compra_id = oc.id
                INNER JOIN autorizacion_flujo af ON oc.id = af.orden_compra_id
                WHERE dg.cuenta_contable_id = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$cuentaContableId]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [
            'total_requisiciones' => 0,
            'autorizadas' => 0,
            'rechazadas' => 0,
            'pendientes' => 0,
            'monto_total' => 0
        ];
    }

    /**
     * Obtiene todas las configuraciones con detalles
     * 
     * @return array
     */
    public static function todasConfiguraciones()
    {
        $sql = "SELECT 
                    cc.codigo,
                    cc.descripcion as cuenta_nombre,
                    GROUP_CONCAT(acc.autorizador_email ORDER BY acc.id) as autorizadores,
                    COUNT(*) as cantidad_autorizadores,
                    MAX(acc.ignora_centro_costo) as ignora_centro_costo
                FROM " . static::$table . " acc
                INNER JOIN cuenta_contable cc ON acc.cuenta_contable_id = cc.id
                GROUP BY cc.id, cc.codigo, cc.descripcion
                ORDER BY cc.codigo ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Valida si existe duplicado
     * 
     * @param int $cuentaContableId
     * @param string $email
     * @param int $excluirId ID a excluir en la validación
     * @return bool
     */
    public static function existeDuplicado($cuentaContableId, $email, $excluirId = null)
    {
        $sql = "SELECT COUNT(*) as total 
                FROM " . static::$table . " 
                WHERE cuenta_contable_id = ? 
                AND autorizador_email = ?";
        
        $params = [$cuentaContableId, $email];
        
        if ($excluirId) {
            $sql .= " AND id != ?";
            $params[] = $excluirId;
        }
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return ($result['total'] ?? 0) > 0;
    }
}
