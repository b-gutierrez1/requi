<?php
/**
 * Modelo OrdenCompra
 * 
 * Representa una requisición de compra en el sistema
 * 
 * @package RequisicionesMVC\Models
 * @version 2.0
 */

namespace App\Models;

class OrdenCompra extends Model
{
    protected static $table = 'orden_compra';
    protected static $primaryKey = 'id';
    protected static $timestamps = false;

    protected static $fillable = [
        'id',
        'usuario_id',
        'nombre_razon_social',
        'unidad_requirente',
        'fecha',
        'causal_compra',
        'moneda',
        'forma_pago',
        'anticipo',
        'elaborado_por',
        'firma_elaborado_por',
        'director_requirente',
        'firma_director_requirente',
        'razon_seleccion',
        'datos_proveedor',
        'monto_total',
        'estado',
    ];

    /**
     * Campos protegidos contra asignación masiva
     * @var array
     */
    protected static $guarded = [];

    /**
     * Obtener el estado real de la requisición basado en el flujo de autorización
     *
     * @return string
     */
    public function getEstadoReal()
    {
        $flujo = $this->autorizacionFlujo();

        if (!$flujo) {
            return 'borrador';
        }

        $estadoFlujo = is_object($flujo) ? ($flujo->estado ?? 'borrador') : ($flujo['estado'] ?? 'borrador');

        switch ($estadoFlujo) {
            case 'pendiente_revision':
                return 'pendiente_revision';
            case 'pendiente_autorizacion':
            case 'pendiente_autorizacion_centros':
            case 'pendiente_autorizacion_pago':
            case 'pendiente_autorizacion_cuenta':
                return 'pendiente_autorizacion';
            case 'rechazado_revision':
            case 'rechazado_autorizacion':
            case 'rechazado':
                return 'rechazado';
            case 'autorizado':
                return 'autorizado';
            default:
                return 'borrador';
        }
    }

    /**
     * Contar total de requisiciones
     *
     * @return int
     */
    public static function count()
    {
        $stmt = self::query("SELECT COUNT(*) as total FROM " . self::getTable());
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Contar requisiciones creadas en el mes actual
     *
     * @return int
     */
    public static function countMesActual()
    {
        $stmt = self::query("
            SELECT COUNT(*) as total
            FROM " . self::getTable() . "
            WHERE DATE_FORMAT(fecha, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
        ");
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Obtener el monto total de requisiciones en el mes actual
     *
     * @return float
     */
    public static function montoTotalMes()
    {
        $stmt = self::query("
            SELECT SUM(monto_total) as total
            FROM " . self::getTable() . "
            WHERE DATE_FORMAT(fecha, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
        ");
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (float)($result['total'] ?? 0.0);
    }

    /**
     * Obtener requisiciones más recientes
     *
     * @param int $limit
     * @return array
     */
    public static function recientes($limit = 10)
    {
        $stmt = self::query("
            SELECT *
            FROM " . self::getTable() . "
            ORDER BY fecha DESC, id DESC
            LIMIT :limit
        ", ['limit' => (int)$limit], false);

        $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
        $stmt->execute();

        $requisiciones = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $requisiciones[] = new self($row);
        }

        return $requisiciones;
    }

    /**
     * Obtener el usuario que creó la requisición
     * 
     * @return Usuario|null
     */
    public function usuario()
    {
        return Usuario::find($this->usuario_id);
    }

    /**
     * Obtener los items de la requisición
     * 
     * @return array
     */
    public function items()
    {
        return DetalleItem::where(['orden_compra_id' => $this->id]);
    }

    /**
     * Obtener la distribución de gastos
     * 
     * @return array
     */
    public function distribucionGasto()
    {
        return DistribucionGasto::where(['orden_compra_id' => $this->id]);
    }

    /**
     * Obtener el flujo de autorización
     * 
     * @return AutorizacionFlujo|null
     */
    public function autorizacionFlujo()
    {
        return AutorizacionFlujo::first(['orden_compra_id' => $this->id]);
    }

    /**
     * Obtener archivos adjuntos
     * 
     * @return array
     */
    public function archivos()
    {
        return ArchivoAdjunto::where(['orden_compra_id' => $this->id]);
    }

    /**
     * Obtener historial de cambios
     * 
     * @return array
     */
    public function historial()
    {
        $sql = "SELECT * FROM historial_requisicion 
                WHERE orden_compra_id = :id 
                ORDER BY fecha_cambio DESC";
        
        $stmt = self::query($sql, ['id' => $this->id]);
        $results = [];
        
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $results[] = new HistorialRequisicion($row);
        }
        
        return $results;
    }

    /**
     * Obtener facturas asociadas
     * 
     * @return array
     */
    public function facturas()
    {
        return Factura::where(['orden_compra_id' => $this->id]);
    }

    /**
     * Obtener la unidad requirente
     * 
     * @return UnidadRequirente|null
     */
    public function unidadRequirente()
    {
        return UnidadRequirente::find($this->unidad_requirente);
    }

    /**
     * Calcular el monto total desde los items
     * 
     * @return float
     */
    public function calcularMontoTotal()
    {
        $items = $this->items();
        $total = 0;
        
        foreach ($items as $item) {
            $total += $item->total;
        }
        
        return $total;
    }

    /**
     * Actualizar el monto total
     * 
     * @return bool
     */
    public function actualizarMontoTotal()
    {
        $this->monto_total = $this->calcularMontoTotal();
        return $this->save();
    }

    /**
     * Obtener el número de orden formateado
     * 
     * @return string
     */
    public function getNumeroOrden()
    {
        return str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verificar si la requisición está pendiente de revisión
     * 
     * @return bool
     */
    public function isPendienteRevision()
    {
        $flujo = $this->autorizacionFlujo();
        return $flujo && $flujo->estado === 'pendiente_revision';
    }

    /**
     * Verificar si la requisición está pendiente de autorización
     * 
     * @return bool
     */
    public function isPendienteAutorizacion()
    {
        $flujo = $this->autorizacionFlujo();
        return $flujo && $flujo->estado === 'pendiente_autorizacion';
    }

    /**
     * Verificar si la requisición está autorizada
     * 
     * @return bool
     */
    public function isAutorizada()
    {
        $flujo = $this->autorizacionFlujo();
        return $flujo && $flujo->estado === 'autorizado';
    }

    /**
     * Verificar si la requisición está rechazada
     * 
     * @return bool
     */
    public function isRechazada()
    {
        $flujo = $this->autorizacionFlujo();
        return $flujo && $flujo->estado === 'rechazado';
    }

    /**
     * Obtener el estado actual
     * 
     * @return string
     */
    public function getEstado()
    {
        $flujo = $this->autorizacionFlujo();
        return $flujo ? $flujo->estado : 'desconocido';
    }

    /**
     * Obtener el badge HTML del estado
     * 
     * @return string
     */
    public function getEstadoBadge()
    {
        $estado = $this->getEstado();
        
        $badges = [
            'pendiente_revision' => '<span class="badge badge-warning">Pendiente Revisión</span>',
            'pendiente_autorizacion' => '<span class="badge badge-info">Pendiente Autorización</span>',
            'autorizado' => '<span class="badge badge-success">Autorizada</span>',
            'rechazado' => '<span class="badge badge-danger">Rechazada</span>',
        ];
        
        return $badges[$estado] ?? '<span class="badge badge-secondary">Desconocido</span>';
    }

    /**
     * Verificar si el usuario puede editar esta requisición
     * 
     * @param Usuario $usuario
     * @return bool
     */
    public function puedeEditar(Usuario $usuario)
    {
        // Solo el creador puede editar
        if ($this->usuario_id !== $usuario->id) {
            return false;
        }
        
        // Solo si está rechazada
        return $this->isRechazada();
    }

    /**
     * Verificar si el usuario puede ver esta requisición
     * 
     * @param Usuario $usuario
     * @return bool
     */
    public function puedeVer(Usuario $usuario)
    {
        // Admins pueden ver todo
        if ($usuario->isAdmin()) {
            return true;
        }
        
        // El creador puede ver sus propias requisiciones
        if ($this->usuario_id === $usuario->id) {
            return true;
        }
        
        // Revisores pueden ver requisiciones pendientes de revisión
        if ($usuario->isRevisor() && $this->isPendienteRevision()) {
            return true;
        }
        
        // Autorizadores pueden ver si les corresponde autorizar
        if ($usuario->isAutorizador()) {
            $flujo = $this->autorizacionFlujo();
            if ($flujo) {
                $autorizaciones = $flujo->autorizacionesCentroCosto();
                foreach ($autorizaciones as $auth) {
                    if ($auth->autorizador_email === $usuario->azure_email) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }

    /**
     * Registrar un evento en el historial
     * 
     * @param string $evento
     * @param string $descripcion
     * @param int|null $usuarioId
     * @return bool
     */
    public function registrarHistorial($evento, $descripcion, $usuarioId = null)
    {
        return HistorialRequisicion::create([
            'orden_compra_id' => $this->id,
            'usuario_id' => $usuarioId,
            'evento' => $evento,
            'descripcion' => $descripcion,
            'fecha_cambio' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Obtener requisiciones por estado
     * 
     * @param string $estado
     * @return array
     */
    public static function porEstado($estado)
    {
        $sql = "
            SELECT oc.* 
            FROM orden_compra oc
            INNER JOIN autorizacion_flujo af ON oc.id = af.orden_compra_id
            WHERE af.estado = :estado
            ORDER BY oc.fecha DESC
        ";
        
        $stmt = self::query($sql, ['estado' => $estado]);
        $results = [];
        
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $results[] = new self($row);
        }
        
        return $results;
    }

    /**
     * Obtener requisiciones del usuario
     * 
     * @param int $usuarioId
     * @return array
     */
    public static function porUsuario($usuarioId)
    {
        $sql = "SELECT * FROM orden_compra 
                WHERE usuario_id = :usuario_id 
                ORDER BY fecha DESC";
        
        $stmt = self::query($sql, ['usuario_id' => $usuarioId]);
        $results = [];
        
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $results[] = new self($row);
        }
        
        return $results;
    }

    /**
     * Obtener requisiciones recientes del usuario
     * 
     * @param int $usuarioId ID del usuario
     * @param int $limite Número máximo de requisiciones a retornar (default: 5)
     * @return array Array de objetos OrdenCompra
     */
    public static function recentesPorUsuario($usuarioId, $limite = 5)
    {
        $sql = "SELECT * FROM orden_compra 
                WHERE usuario_id = :usuario_id 
                ORDER BY fecha DESC, id DESC 
                LIMIT :limite";
        
        $stmt = self::query($sql, [
            'usuario_id' => $usuarioId,
            'limite' => (int)$limite
        ]);
        
        $results = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $results[] = new self($row);
        }
        
        return $results;
    }

    /**
     * Obtener requisiciones de un usuario por mes
     * 
     * @param int $usuarioId ID del usuario
     * @param string $mes Mes en formato 'Y-m' (ejemplo: '2025-10')
     * @return array Array de objetos OrdenCompra
     */
    public static function porUsuarioYMes($usuarioId, $mes)
    {
        $sql = "SELECT * FROM orden_compra 
                WHERE usuario_id = :usuario_id 
                AND DATE_FORMAT(fecha, '%Y-%m') = :mes
                ORDER BY fecha DESC";
        
        $stmt = self::query($sql, [
            'usuario_id' => $usuarioId,
            'mes' => $mes
        ]);
        
        $results = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $results[] = new self($row);
        }
        
        return $results;
    }

    /**
     * Obtener estadísticas de la requisición
     * 
     * @return array
     */
    public function getEstadisticas()
    {
        $items = $this->items();
        $distribucion = $this->distribucionGasto();
        
        return [
            'total_items' => count($items),
            'monto_total' => $this->monto_total,
            'centros_costo' => count($distribucion),
            'archivos_adjuntos' => count($this->archivos()),
            'estado' => $this->getEstado(),
            'dias_desde_creacion' => $this->diasDesdeCreacion(),
        ];
    }

    /**
     * Calcular días desde la creación
     * 
     * @return int
     */
    public function diasDesdeCreacion()
    {
        $fecha = new \DateTime($this->fecha);
        $hoy = new \DateTime();
        $diff = $hoy->diff($fecha);
        
        return $diff->days;
    }

    /**
     * Formatear monto con moneda
     * 
     * @return string
     */
    public function getMontoFormateado()
    {
        $simbolo = $this->moneda === 'GTQ' ? 'Q' : '$';
        return $simbolo . ' ' . number_format($this->monto_total, 2);
    }

    /**
     * Obtener estadísticas de un usuario específico
     * 
     * @param int $usuarioId
     * @return array
     */
    public static function getEstadisticasUsuario($usuarioId)
    {
        try {
            $db = self::getConnection();
            
            // Total de requisiciones del usuario
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM orden_compra WHERE usuario_id = ?");
            $stmt->execute([$usuarioId]);
            $total = $stmt->fetch(\PDO::FETCH_ASSOC)['total'];

            
            // Requisiciones por estado
            $stmt = $db->prepare("
                SELECT 
                    estado,
                    COUNT(*) as cantidad,
                    SUM(monto_total) as monto
                FROM orden_compra 
                WHERE usuario_id = ? 
                GROUP BY estado
            ");
            $stmt->execute([$usuarioId]);
            $estados = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            
            // Monto total
            $stmt = $db->prepare("SELECT SUM(monto_total) as monto_total FROM orden_compra WHERE usuario_id = ?");
            $stmt->execute([$usuarioId]);
            $montoTotal = $stmt->fetch(\PDO::FETCH_ASSOC)['monto_total'] ?? 0;

            
            // Monto del mes actual
            $stmt = $db->prepare("
                SELECT SUM(monto_total) as monto_mes 
                FROM orden_compra 
                WHERE usuario_id = ? 
                AND YEAR(fecha) = YEAR(CURDATE()) 
                AND MONTH(fecha) = MONTH(CURDATE())
            ");
            $stmt->execute([$usuarioId]);
            $montoMes = $stmt->fetch(\PDO::FETCH_ASSOC)['monto_mes'] ?? 0;

            
            // Organizar estadísticas por estado
            $stats = [
                'total' => (int)$total,
                'pendientes' => 0,
                'autorizadas' => 0,
                'rechazadas' => 0,
                'monto_total' => (float)$montoTotal,
                'monto_mes_actual' => (float)$montoMes
            ];
            
            foreach ($estados as $estado) {
                switch ($estado['estado']) {
                    case 'pendiente':
                        $stats['pendientes'] = (int)$estado['cantidad'];
                        break;
                    case 'autorizada':
                        $stats['autorizadas'] = (int)$estado['cantidad'];
                        break;
                    case 'rechazada':
                        $stats['rechazadas'] = (int)$estado['cantidad'];
                        break;
                }
            }
            
            return $stats;
            
        } catch (\Exception $e) {
            error_log("Error obteniendo estadísticas de usuario: " . $e->getMessage());
            return [
                'total' => 0,
                'pendientes' => 0,
                'autorizadas' => 0,
                'rechazadas' => 0,
                'monto_total' => 0,
                'monto_mes_actual' => 0
            ];
        }
    }

    /**
     * Obtener estadísticas generales del sistema (para admin)
     * 
     * @return array Estadísticas globales del sistema
     */
    public static function getEstadisticasGenerales()
    {
        try {
            $db = self::getConnection();
            
            // Total de requisiciones
            $stmt = $db->query("SELECT COUNT(*) as total FROM orden_compra");
            $total = $stmt->fetch(\PDO::FETCH_ASSOC)['total'];
            
            // Requisiciones por estado
            $stmt = $db->query("
                SELECT 
                    estado,
                    COUNT(*) as cantidad
                FROM orden_compra 
                GROUP BY estado
            ");
            $estados = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Autorizadas hoy
            $stmt = $db->query("
                SELECT COUNT(*) as autorizadas_hoy 
                FROM orden_compra 
                WHERE estado = 'autorizado' 
                AND DATE(fecha) = CURDATE()
            ");
            $autorizadasHoy = $stmt->fetch(\PDO::FETCH_ASSOC)['autorizadas_hoy'];
            
            // Monto total del mes
            $stmt = $db->query("
                SELECT SUM(monto_total) as monto_mes 
                FROM orden_compra 
                WHERE YEAR(fecha) = YEAR(CURDATE()) 
                AND MONTH(fecha) = MONTH(CURDATE())
            ");
            $montoMes = $stmt->fetch(\PDO::FETCH_ASSOC)['monto_mes'] ?? 0;
            
            // Usuarios activos (que han creado requisiciones este mes)
            $stmt = $db->query("
                SELECT COUNT(DISTINCT usuario_id) as usuarios_activos 
                FROM orden_compra 
                WHERE YEAR(fecha) = YEAR(CURDATE()) 
                AND MONTH(fecha) = MONTH(CURDATE())
            ");
            $usuariosActivos = $stmt->fetch(\PDO::FETCH_ASSOC)['usuarios_activos'];
            
            // Organizar estadísticas
            $stats = [
                'total' => (int)$total,
                'pendientes_revision' => 0,
                'pendientes_autorizacion' => 0,
                'autorizadas' => 0,
                'rechazadas' => 0,
                'autorizadas_hoy' => (int)$autorizadasHoy,
                'monto_total_mes' => (float)$montoMes,
                'usuarios_activos' => (int)$usuariosActivos,
                'tiempo_promedio' => 0 // TODO: calcular tiempo promedio de autorización
            ];
            
            // Procesar estados
            foreach ($estados as $estado) {
                switch ($estado['estado']) {
                    case 'pendiente_revision':
                        $stats['pendientes_revision'] = (int)$estado['cantidad'];
                        break;
                    case 'pendiente_autorizacion':
                        $stats['pendientes_autorizacion'] = (int)$estado['cantidad'];
                        break;
                    case 'autorizado':
                        $stats['autorizadas'] = (int)$estado['cantidad'];
                        break;
                    case 'rechazado':
                        $stats['rechazadas'] = (int)$estado['cantidad'];
                        break;
                }
            }
            
            return $stats;
            
        } catch (\Exception $e) {
            error_log("Error obteniendo estadísticas generales: " . $e->getMessage());
            return [
                'total' => 0,
                'pendientes_revision' => 0,
                'pendientes_autorizacion' => 0,
                'autorizadas' => 0,
                'rechazadas' => 0,
                'autorizadas_hoy' => 0,
                'monto_total_mes' => 0,
                'usuarios_activos' => 0,
                'tiempo_promedio' => 0
            ];
        }
    }

    /**
     * Obtener requisiciones actualizadas recientemente
     * 
     * @param int $usuarioId ID del usuario
     * @param int $horas Número de horas hacia atrás (default: 24)
     * @return array Array de objetos OrdenCompra
     */
    public static function actualizadasReciente($usuarioId, $horas = 24)
    {
        $sql = "SELECT oc.* FROM orden_compra oc
                INNER JOIN historial_requisicion hr ON oc.id = hr.orden_compra_id
                WHERE oc.usuario_id = :usuario_id 
                AND hr.fecha_cambio >= DATE_SUB(NOW(), INTERVAL :horas HOUR)
                GROUP BY oc.id
                ORDER BY hr.fecha_cambio DESC
                LIMIT 10";
        
        $stmt = self::query($sql, [
            'usuario_id' => $usuarioId,
            'horas' => (int)$horas
        ]);
        
        $results = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $results[] = new self($row);
        }
        
        return $results;
    }
}
