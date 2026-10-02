<?php
/**
 * Modelo UnidadRequirente
 * 
 * Representa las unidades requirentes que solicitan las requisiciones.
 * Cada orden de compra está asociada a una unidad requirente.
 * 
 * @package RequisicionesMVC\Models
 * @version 2.0
 */

namespace App\Models;

class UnidadRequirente extends Model
{
    protected static $table = 'unidad_requirente';
    protected static $primaryKey = 'id';
    protected static $timestamps = false;

    protected static $fillable = [
        'nombre',
        'codigo',
        'descripcion',
        'responsable',
        'email',
        'activo',
    ];

    protected static $guarded = ['id'];

    /**
     * Obtiene las órdenes de compra de esta unidad requirente
     * 
     * @return array
     */
    public function ordenesCompra()
    {
        $sql = "SELECT * FROM requisiciones WHERE unidad_requirente = ? ORDER BY fecha DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$this->attributes['id']]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todas las unidades requirentes activas
     * 
     * @return array
     */
    public static function activas()
    {
        $sql = "SELECT * FROM " . static::$table . " WHERE activo = 1 ORDER BY nombre ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Catalogo para los formularios de requisicion: una opcion por unidad de
     * negocio, con el nombre y el estado de la unidad de negocio.
     *
     * La unidad requirente es espejo de la unidad de negocio (relacion 1 a 1
     * por unidad_negocio_id, que es UNIQUE). Nombre y activo se leen de
     * unidad_de_negocio para que desactivar una unidad en /admin/centros la
     * cierre tambien aqui, sin depender de que las dos tablas esten al dia.
     * El value sigue siendo el id de unidad_requirente, que es lo que guarda
     * requisiciones.unidad_requirente.
     *
     * @return array
     */
    public static function paraFormulario()
    {
        $sql = "SELECT ur.id, un.nombre, un.activo, ur.unidad_negocio_id
                FROM unidad_requirente ur
                INNER JOIN unidad_de_negocio un ON un.id = ur.unidad_negocio_id
                ORDER BY un.activo DESC, un.nombre ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Crea o actualiza la unidad requirente espejo de una unidad de negocio.
     *
     * Se llama desde el panel de unidades de negocio al crear, editar o
     * activar/desactivar, para que las dos listas no se vuelvan a desfasar.
     *
     * @param int $unidadNegocioId
     * @return void
     */
    public static function sincronizarConUnidadNegocio($unidadNegocioId)
    {
        $sql = "INSERT INTO unidad_requirente (nombre, unidad_negocio_id, activo)
                SELECT un.nombre, un.id, un.activo
                FROM unidad_de_negocio un
                WHERE un.id = ?
                ON DUPLICATE KEY UPDATE
                    nombre = VALUES(nombre),
                    activo = VALUES(activo)";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([(int)$unidadNegocioId]);
    }

    /**
     * Busca unidades requirentes por nombre o código
     *
     * @param string $termino
     * @return array
     */
    public static function buscar($termino)
    {
        $sql = "SELECT * FROM " . static::$table . " 
                WHERE (nombre LIKE ? OR codigo LIKE ?) 
                AND activo = 1 
                ORDER BY nombre ASC";
        
        $stmt = self::getConnection()->prepare($sql);
        $search = "%{$termino}%";
        $stmt->execute([$search, $search]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene unidad requirente por código
     * 
     * @param string $codigo
     * @return array|null
     */
    public static function porCodigo($codigo)
    {
        $sql = "SELECT * FROM " . static::$table . " WHERE codigo = ? LIMIT 1";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$codigo]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Activa o desactiva la unidad requirente
     * 
     * @param bool $activo
     * @return bool
     */
    public function setActivo($activo = true)
    {
        return self::update($this->attributes['id'], ['activo' => $activo ? 1 : 0]);
    }

    /**
     * Cuenta las requisiciones de esta unidad
     * 
     * @param string $estado Estado específico o null para todas
     * @return int
     */
    public function contarRequisiciones($estado = null)
    {
        $sql = "SELECT COUNT(*) as total
                FROM requisiciones oc
                LEFT JOIN autorizacion_flujo af ON oc.id = af.requisicion_id
                WHERE oc.unidad_requirente = ?";
        
        $params = [$this->attributes['id']];
        
        if ($estado) {
            $sql .= " AND af.estado = ?";
            $params[] = $estado;
        }
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Obtiene el total gastado por esta unidad
     * 
     * @param string $fechaInicio
     * @param string $fechaFin
     * @return float
     */
    public function getTotalGastado($fechaInicio = null, $fechaFin = null)
    {
        $sql = "SELECT SUM(oc.monto_total) as total
                FROM requisiciones oc
                WHERE oc.unidad_requirente = ?";
        
        $params = [$this->attributes['id']];
        
        if ($fechaInicio && $fechaFin) {
            $sql .= " AND oc.fecha BETWEEN ? AND ?";
            $params[] = $fechaInicio;
            $params[] = $fechaFin;
        }
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Obtiene estadísticas de la unidad requirente
     * 
     * @return array
     */
    public function getEstadisticas()
    {
        $sql = "SELECT 
                    COUNT(*) as total_requisiciones,
                    SUM(oc.monto_total) as monto_total,
                    AVG(oc.monto_total) as monto_promedio,
                    SUM(CASE WHEN af.estado = 'pendiente_revision' THEN 1 ELSE 0 END) as pendientes_revision,
                    SUM(CASE WHEN af.estado = 'pendiente_autorizacion' THEN 1 ELSE 0 END) as pendientes_autorizacion,
                    SUM(CASE WHEN af.estado = 'autorizado' THEN 1 ELSE 0 END) as autorizadas,
                    SUM(CASE WHEN af.estado = 'rechazado' THEN 1 ELSE 0 END) as rechazadas
                FROM requisiciones oc
                LEFT JOIN autorizacion_flujo af ON oc.id = af.requisicion_id
                WHERE oc.unidad_requirente = ?";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$this->attributes['id']]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [
            'total_requisiciones' => 0,
            'monto_total' => 0,
            'monto_promedio' => 0,
            'pendientes_revision' => 0,
            'pendientes_autorizacion' => 0,
            'autorizadas' => 0,
            'rechazadas' => 0
        ];
    }

    /**
     * Obtiene las últimas requisiciones de la unidad
     * 
     * @param int $limit
     * @return array
     */
    public function getUltimasRequisiciones($limit = 10)
    {
        $sql = "SELECT oc.*, af.estado
                FROM requisiciones oc
                LEFT JOIN autorizacion_flujo af ON oc.id = af.requisicion_id
                WHERE oc.unidad_requirente = ?
                ORDER BY oc.fecha DESC
                LIMIT ?";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$this->attributes['id'], $limit]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Verifica si tiene requisiciones pendientes
     * 
     * @return bool
     */
    public function tienePendientes()
    {
        $sql = "SELECT COUNT(*) as total
                FROM requisiciones oc
                INNER JOIN autorizacion_flujo af ON oc.id = af.requisicion_id
                WHERE oc.unidad_requirente = ?
                AND af.estado IN ('pendiente_revision', 'pendiente_autorizacion')";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$this->attributes['id']]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return ($result['total'] ?? 0) > 0;
    }
}
