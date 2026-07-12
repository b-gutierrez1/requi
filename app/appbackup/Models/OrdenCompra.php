<?php
/**
 * DEPRECATED: OrdenCompra model - compatibility layer
 * 
 * This is a temporary compatibility layer during v3.0 migration.
 * All methods are delegated to the new Requisicion model.
 * 
 * @package RequisicionesMVC\Models
 * @version 3.0
 * @deprecated Use Requisicion model instead
 */

namespace App\Models;

/**
 * @deprecated Use Requisicion model instead
 */
class OrdenCompra extends Requisicion
{
    protected static $table = 'requisiciones'; // Point to new table
    
    /**
     * Override table name to point to requisiciones
     */
    public static function getTable()
    {
        return 'requisiciones';
    }
    
    // Field mapping for compatibility with old field names
    public function __get($key)
    {
        $fieldMap = [
            'nombre_razon_social' => 'proveedor_nombre',
            'nit' => 'proveedor_nit',
            'direccion' => 'proveedor_direccion', 
            'telefono' => 'proveedor_telefono',
            'fecha' => 'fecha_solicitud'
        ];
        
        if (isset($fieldMap[$key])) {
            return parent::__get($fieldMap[$key]);
        }
        
        return parent::__get($key);
    }
    
    public function __set($key, $value)
    {
        $fieldMap = [
            'nombre_razon_social' => 'proveedor_nombre',
            'nit' => 'proveedor_nit',
            'direccion' => 'proveedor_direccion',
            'telefono' => 'proveedor_telefono', 
            'fecha' => 'fecha_solicitud'
        ];
        
        if (isset($fieldMap[$key])) {
            return parent::__set($fieldMap[$key], $value);
        }
        
        return parent::__set($key, $value);
    }
    
    /**
     * Cuenta las requisiciones del mes actual
     */
    public static function countMesActual()
    {
        $pdo = static::getConnection();
        $sql = "
            SELECT COUNT(*) as total 
            FROM requisiciones 
            WHERE YEAR(fecha_solicitud) = YEAR(CURDATE()) 
            AND MONTH(fecha_solicitud) = MONTH(CURDATE())
        ";
        $stmt = $pdo->query($sql);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return (int) ($result['total'] ?? 0);
    }
    
    /**
     * Obtiene el monto total del mes actual
     */
    public static function montoTotalMes()
    {
        $pdo = static::getConnection();
        $sql = "
            SELECT COALESCE(SUM(monto_total), 0) as total 
            FROM requisiciones 
            WHERE YEAR(fecha_solicitud) = YEAR(CURDATE()) 
            AND MONTH(fecha_solicitud) = MONTH(CURDATE())
        ";
        $stmt = $pdo->query($sql);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return (float) ($result['total'] ?? 0);
    }
    
    /**
     * Obtiene estadísticas específicas para un usuario
     */
    public static function getEstadisticasUsuario($usuarioId)
    {
        $pdo = static::getConnection();
        
        // Estadísticas generales del usuario
        $sql = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'pendiente_autorizacion' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'autorizada' THEN 1 ELSE 0 END) as autorizadas,
                SUM(CASE WHEN estado = 'rechazada' THEN 1 ELSE 0 END) as rechazadas,
                COALESCE(SUM(monto_total), 0) as monto_total
            FROM requisiciones 
            WHERE usuario_id = ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuarioId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // Monto del mes actual
        $sqlMes = "
            SELECT COALESCE(SUM(monto_total), 0) as monto_mes_actual 
            FROM requisiciones 
            WHERE usuario_id = ?
            AND YEAR(fecha_solicitud) = YEAR(CURDATE()) 
            AND MONTH(fecha_solicitud) = MONTH(CURDATE())
        ";
        $stmtMes = $pdo->prepare($sqlMes);
        $stmtMes->execute([$usuarioId]);
        $resultMes = $stmtMes->fetch(\PDO::FETCH_ASSOC);
        
        return [
            'total' => (int) ($result['total'] ?? 0),
            'pendientes' => (int) ($result['pendientes'] ?? 0),
            'autorizadas' => (int) ($result['autorizadas'] ?? 0),
            'rechazadas' => (int) ($result['rechazadas'] ?? 0),
            'monto_total' => (float) ($result['monto_total'] ?? 0),
            'monto_mes_actual' => (float) ($resultMes['monto_mes_actual'] ?? 0)
        ];
    }
    
    /**
     * Obtiene requisiciones recientes por usuario
     */
    public static function recentesPorUsuario($usuarioId, $limit = 5)
    {
        $pdo = static::getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM requisiciones 
            WHERE usuario_id = ? 
            ORDER BY fecha_solicitud DESC, id DESC 
            LIMIT ?
        ");
        $stmt->execute([$usuarioId, $limit]);
        
        $results = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $model = new static();
            foreach ($row as $key => $value) {
                $model->setAttribute($key, $value);
            }
            $model->original = $model->attributes ?? [];
            $results[] = $model;
        }
        
        return $results;
    }
    
    /**
     * Obtiene requisiciones por usuario y mes
     */
    public static function porUsuarioYMes($usuarioId, $mes)
    {
        $pdo = static::getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM requisiciones 
            WHERE usuario_id = ? 
            AND DATE_FORMAT(fecha_solicitud, '%Y-%m') = ?
            ORDER BY fecha_solicitud DESC
        ");
        $stmt->execute([$usuarioId, $mes]);
        
        $results = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $model = new static();
            foreach ($row as $key => $value) {
                $model->setAttribute($key, $value);
            }
            $model->original = $model->attributes ?? [];
            $results[] = $model;
        }
        
        return $results;
    }
    
    /**
     * Obtiene estadísticas generales del sistema
     */
    public static function getEstadisticasGenerales()
    {
        $pdo = static::getConnection();
        
        $sql = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'borrador' THEN 1 ELSE 0 END) as borrador,
                SUM(CASE WHEN estado = 'pendiente_revision' THEN 1 ELSE 0 END) as pendiente_revision,
                SUM(CASE WHEN estado = 'pendiente_autorizacion' THEN 1 ELSE 0 END) as pendiente_autorizacion,
                SUM(CASE WHEN estado = 'autorizada' THEN 1 ELSE 0 END) as autorizadas,
                SUM(CASE WHEN estado = 'rechazada' THEN 1 ELSE 0 END) as rechazadas,
                COALESCE(SUM(monto_total), 0) as monto_total,
                COALESCE(AVG(monto_total), 0) as monto_promedio
            FROM requisiciones
        ";
        $stmt = $pdo->query($sql);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return [
            'total' => (int) ($result['total'] ?? 0),
            'borrador' => (int) ($result['borrador'] ?? 0),
            'pendiente_revision' => (int) ($result['pendiente_revision'] ?? 0),
            'pendiente_autorizacion' => (int) ($result['pendiente_autorizacion'] ?? 0),
            'autorizadas' => (int) ($result['autorizadas'] ?? 0),
            'rechazadas' => (int) ($result['rechazadas'] ?? 0),
            'monto_total' => (float) ($result['monto_total'] ?? 0),
            'monto_promedio' => (float) ($result['monto_promedio'] ?? 0)
        ];
    }
    
    /**
     * Obtiene requisiciones actualizadas recientemente
     */
    public static function actualizadasReciente($usuarioId, $horas = 24)
    {
        $pdo = static::getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM requisiciones 
            WHERE usuario_id = ? 
            AND updated_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
            ORDER BY updated_at DESC
        ");
        $stmt->execute([$usuarioId, $horas]);
        
        $results = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $model = new static();
            foreach ($row as $key => $value) {
                $model->setAttribute($key, $value);
            }
            $model->original = $model->attributes ?? [];
            $results[] = $model;
        }
        
        return $results;
    }
    
    /**
     * Obtiene las requisiciones más recientes del sistema
     */
    public static function recientes($limit = 10)
    {
        $pdo = static::getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM requisiciones 
            ORDER BY fecha_solicitud DESC, id DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        
        $results = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $model = new static();
            foreach ($row as $key => $value) {
                $model->setAttribute($key, $value);
            }
            $model->original = $model->attributes ?? [];
            $results[] = $model;
        }
        
        return $results;
    }
    
    /**
     * Ejecuta una consulta SQL personalizada
     */
    public static function query($sql, $params = [])
    {
        $pdo = static::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}