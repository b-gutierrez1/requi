<?php

namespace App\Models;

/**
 * Modelo principal de Requisiciones
 * 
 * Tabla: requisiciones
 * Esquema v3.0 - Simplificado y normalizado
 */
class Requisicion extends Model
{
    protected static $table = 'requisiciones';
    protected static $primaryKey = 'id';
    
    // Estados posibles (DEPRECADOS - usar EstadoHelper)
    // Mantenidos solo para compatibilidad temporal
    const ESTADO_BORRADOR = 'borrador';
    const ESTADO_PENDIENTE_REVISION = 'pendiente_revision';
    const ESTADO_PENDIENTE_AUTORIZACION = 'pendiente_autorizacion';
    const ESTADO_AUTORIZADA = 'autorizado'; // Cambiado para coincidir con flujo
    const ESTADO_RECHAZADA = 'rechazado'; // Cambiado para coincidir con flujo
    
    // Prioridades
    const PRIORIDAD_BAJA = 'baja';
    const PRIORIDAD_NORMAL = 'normal';
    const PRIORIDAD_ALTA = 'alta';
    const PRIORIDAD_URGENTE = 'urgente';
    
    protected static $fillable = [
        'numero_requisicion',
        'prioridad',
        'usuario_id',
        'unidad_requirente',
        'proveedor_nombre',
        'proveedor_nit',
        'proveedor_direccion',
        'proveedor_telefono',
        'moneda',
        'monto_total',
        'forma_pago',
        'anticipo',
        'fecha_solicitud',
        'fecha_limite',
        'causal_compra',
        'justificacion',
        'observaciones'
    ];

    protected $casts = [
        'monto_total' => 'decimal:2',
        'anticipo' => 'decimal:2',
        'fecha_solicitud' => 'date',
        'fecha_limite' => 'date',
        'fecha_completada' => 'datetime'
    ];

    /**
     * Obtiene los items de la requisición
     */
    public function items()
    {
        return $this->hasMany(RequisicionItem::class, 'requisicion_id');
    }

    /**
     * Obtiene la distribución por centros de costo
     */
    public function distribucionCentros()
    {
        return $this->hasMany(DistribucionCentro::class, 'requisicion_id');
    }

    /**
     * Obtiene las autorizaciones
     */
    public function autorizaciones()
    {
        return $this->hasMany(Autorizacion::class, 'requisicion_id');
    }

    /**
     * Obtiene el flujo de autorización asociado a esta requisición
     */
    public function autorizacionFlujo()
    {
        if (!$this->id) {
            return null;
        }
        
        $pdo = static::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM autorizacion_flujo WHERE requisicion_id = ? LIMIT 1");
        $stmt->execute([$this->id]);
        $flujoData = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$flujoData) {
            return null;
        }
        
        // Crear un objeto simple con los datos del flujo
        $flujo = new \stdClass();
        foreach ($flujoData as $key => $value) {
            $flujo->$key = $value;
        }
        
        return $flujo;
    }

    /**
     * Magic method to handle field name compatibility with v2.0
     */
    public function __get($key)
    {
        // Field mapping for v2.0 compatibility
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

    /**
     * Obtiene el historial de cambios
     */
    public function historial()
    {
        return $this->hasMany(HistorialRequisicion::class, 'requisicion_id')->orderBy('fecha_cambio', 'desc');
    }

    /**
     * Obtiene los adjuntos
     */
    public function adjuntos()
    {
        return $this->hasMany(RequisicionAdjunto::class, 'requisicion_id');
    }

    /**
     * Obtiene el usuario solicitante
     */
    public function usuario()
    {
        if (!$this->usuario_id) {
            return null;
        }
        
        $pdo = static::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$this->usuario_id]);
        $userData = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$userData) {
            return null;
        }
        
        // Crear un objeto simple con los datos del usuario
        $usuario = new \stdClass();
        foreach ($userData as $key => $value) {
            $usuario->$key = $value;
        }
        
        return $usuario;
    }
    
    /**
     * Obtiene el estado real de la requisición basado en el flujo de autorización
     */
    public function getEstadoReal()
    {
        // Delegar al EstadoHelper que maneja la lógica centralizada
        return \App\Helpers\EstadoHelper::getEstado($this->id);
    }

    /**
     * Verifica si la requisición está en estado borrador
     */
    public function esBorrador(): bool
    {
        return $this->getEstadoReal() === self::ESTADO_BORRADOR;
    }

    /**
     * Verifica si está pendiente de revisión
     */
    public function estaPendienteRevision(): bool
    {
        return $this->getEstadoReal() === self::ESTADO_PENDIENTE_REVISION;
    }

    /**
     * Verifica si está pendiente de autorización
     */
    public function estaPendienteAutorizacion(): bool
    {
        return $this->getEstadoReal() === self::ESTADO_PENDIENTE_AUTORIZACION;
    }

    /**
     * Verifica si está autorizada
     */
    public function estaAutorizada(): bool
    {
        return $this->getEstadoReal() === self::ESTADO_AUTORIZADA;
    }

    /**
     * Verifica si fue rechazada
     */
    public function estaRechazada(): bool
    {
        return $this->getEstadoReal() === self::ESTADO_RECHAZADA;
    }

    /**
     * Obtiene todas las autorizaciones pendientes
     */
    public function autorizacionesPendientes()
    {
        return $this->autorizaciones()->where('estado', 'pendiente');
    }

    /**
     * Obtiene el progreso de autorización
     */
    public function progresoAutorizacion(): array
    {
        $autorizaciones = $this->autorizaciones;
        $total = $autorizaciones->count();
        
        if ($total === 0) {
            return [
                'total' => 0,
                'pendientes' => 0,
                'aprobadas' => 0,
                'rechazadas' => 0,
                'porcentaje_completado' => 0
            ];
        }

        $pendientes = $autorizaciones->where('estado', 'pendiente')->count();
        $aprobadas = $autorizaciones->where('estado', 'aprobada')->count();
        $rechazadas = $autorizaciones->where('estado', 'rechazada')->count();

        return [
            'total' => $total,
            'pendientes' => $pendientes,
            'aprobadas' => $aprobadas,
            'rechazadas' => $rechazadas,
            'porcentaje_completado' => ($total - $pendientes) / $total * 100
        ];
    }

    /**
     * Cambia el estado de la requisición - OBSOLETO
     * El estado ahora se maneja a través del flujo de autorización
     */
    /*
    public function cambiarEstado(string $nuevoEstado, int $usuarioId = null, string $comentarios = null): bool
    {
        // OBSOLETO - El estado ahora se maneja en AutorizacionFlujo
        // Usar EstadoHelper::getEstadoReal() para obtener estado actual
        return false;
    }

    private function mapearEstadoAAccion(string $estado): string
    {
        // OBSOLETO - El estado ahora se maneja en AutorizacionFlujo
        return 'editada';
    }
    */

    /**
     * Envía a revisión
     */
    public function enviarARevision(int $usuarioId): bool
    {
        if (!$this->esBorrador()) {
            throw new \Exception("Solo se pueden enviar a revisión requisiciones en borrador");
        }

        // Crear autorización de revisión
        Autorizacion::create([
            'requisicion_id' => $this->id,
            'tipo' => 'revision',
            'autorizador_email' => config('sistema.email_revisor_default', 'revisor@sistema.com'),
            'autorizador_nombre' => 'Revisor del Sistema',
            'fecha_vencimiento' => date('Y-m-d H:i:s', strtotime('+2 days'))
        ]);

        return $this->cambiarEstado(self::ESTADO_PENDIENTE_REVISION, $usuarioId);
    }

    /**
     * Aprueba la revisión
     */
    public function aprobarRevision(int $usuarioId, string $comentarios = null): bool
    {
        if (!$this->estaPendienteRevision()) {
            throw new \Exception("La requisición no está pendiente de revisión");
        }

        // Marcar autorización de revisión como aprobada
        $autorizacionRevision = $this->autorizaciones()
            ->where('tipo', 'revision')
            ->where('estado', 'pendiente')
            ->first();

        if ($autorizacionRevision) {
            $autorizacionRevision->aprobar($comentarios);
        }

        return $this->cambiarEstado(self::ESTADO_PENDIENTE_AUTORIZACION, $usuarioId, $comentarios);
    }

    /**
     * Rechaza la revisión
     */
    public function rechazarRevision(int $usuarioId, string $motivo): bool
    {
        if (!$this->estaPendienteRevision()) {
            throw new \Exception("La requisición no está pendiente de revisión");
        }

        // Marcar autorización de revisión como rechazada
        $autorizacionRevision = $this->autorizaciones()
            ->where('tipo', 'revision')
            ->where('estado', 'pendiente')
            ->first();

        if ($autorizacionRevision) {
            $autorizacionRevision->rechazar($motivo);
        }

        return $this->cambiarEstado(self::ESTADO_RECHAZADA, $usuarioId, $motivo);
    }

    /**
     * Crea autorizaciones por centros de costo
     */
    private function crearAutorizacionesCentrosCosto(): void
    {
        foreach ($this->distribucionCentros as $distribucion) {
            // Obtener autorizador del centro de costo
            $autorizador = $this->obtenerAutorizadorCentroCosto($distribucion->centro_costo_id);

            Autorizacion::create([
                'requisicion_id' => $this->id,
                'tipo' => 'centro_costo',
                'centro_costo_id' => $distribucion->centro_costo_id,
                'autorizador_email' => $autorizador['email'],
                'autorizador_nombre' => $autorizador['nombre'],
                'fecha_vencimiento' => date('Y-m-d H:i:s', strtotime('+3 days'))
            ]);
        }
    }

    /**
     * Obtiene el autorizador para un centro de costo
     */
    private function obtenerAutorizadorCentroCosto(int $centroCostoId): array
    {
        // Buscar en tabla persona_autorizada por centro de costo
        $pdo = static::getConnection();
        $table = \App\Models\PersonaAutorizada::getTable();
        $stmt = $pdo->prepare("
            SELECT pa.email, pa.nombre 
            FROM {$table} pa 
            JOIN centro_de_costo cc ON pa.centro_costo_id = cc.id 
            WHERE cc.id = ? AND pa.activo = 1
            LIMIT 1
        ");
        $stmt->execute([$centroCostoId]);
        $resultado = $stmt->fetch();

        if ($resultado) {
            return $resultado;
        }

        // Fallback a autorizador por defecto
        return [
            'email' => config('sistema.email_autorizador_default', 'admin@sistema.com'),
            'nombre' => 'Administrador del Sistema'
        ];
    }

    /**
     * Verifica si se puede autorizar completamente
     */
    public function puedeAutorizarCompleta(): bool
    {
        if (!$this->estaPendienteAutorizacion()) {
            return false;
        }

        $pendientes = $this->autorizacionesPendientes()->count();
        return $pendientes === 0;
    }

    /**
     * Autoriza completamente la requisición - OBSOLETO
     * Usar AutorizacionFlujo::aprobarTodo() en su lugar
     */
    public function autorizarCompleta(int $usuarioId): bool
    {
        // OBSOLETO - El estado ahora se maneja en AutorizacionFlujo
        // Usar AutorizacionFlujo::aprobarTodo() para autorizar completamente
        $flujo = $this->getFlujoAutorizacion();
        if ($flujo) {
            return $flujo->aprobarTodo($usuarioId);
        }
        return false;
    }

    /**
     * Busca requisiciones por estado - OBSOLETO
     * Usar EstadoHelper y flujo de autorización en su lugar
     */
    /*
    public static function porEstado(string $estado, int $limite = 20): array
    {
        // OBSOLETO - El estado ahora se maneja en AutorizacionFlujo
        // Usar métodos del EstadoHelper para filtrar por estado
        return [];
    }
    */

    /**
     * Busca requisiciones pendientes para un autorizador
     */
    public static function pendientesParaAutorizador(string $email): array
    {
        $pdo = static::getConnection();
        $stmt = $pdo->prepare("
            SELECT DISTINCT r.* 
            FROM requisiciones r
            JOIN autorizaciones a ON r.id = a.requisicion_id
            WHERE a.autorizador_email = ? 
            AND a.estado = 'pendiente'
            ORDER BY r.fecha_solicitud DESC
        ");
        $stmt->execute([$email]);
        return $stmt->fetchAll();
    }

    /**
     * Busca requisiciones por usuario
     */
    public static function porUsuario(?int $usuarioId): array
    {
        // Si no hay usuario, devolver array vacío
        if ($usuarioId === null) {
            return [];
        }
        
        $pdo = static::getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM requisiciones 
            WHERE usuario_id = ? 
            ORDER BY fecha_solicitud DESC
        ");
        $stmt->execute([$usuarioId]);
        
        $results = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $model = new static();
            // Asignar todos los atributos directamente, incluyendo el ID
            foreach ($row as $key => $value) {
                $model->setAttribute($key, $value);
            }
            $model->original = $model->attributes ?? [];
            $results[] = $model;
        }
        
        return $results;
    }

    /**
     * Obtiene todas las requisiciones
     * 
     * @return array Array de objetos Requisicion
     */
    public static function all(): array
    {
        try {
            $pdo = static::getConnection();
            $stmt = $pdo->prepare("
                SELECT * FROM requisiciones 
                ORDER BY fecha_solicitud DESC
            ");
            $stmt->execute();
            
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
        } catch (\Exception $e) {
            error_log("Error en Requisicion::all: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca requisiciones por término
     * 
     * @param string $termino Término de búsqueda
     * @return array Array de objetos Requisicion
     */
    public static function buscar($termino): array
    {
        try {
            $pdo = static::getConnection();
            $termino = '%' . $termino . '%';
            $stmt = $pdo->prepare("
                SELECT * FROM requisiciones 
                WHERE numero_requisicion LIKE ?
                   OR proveedor_nombre LIKE ?
                   OR justificacion LIKE ?
                ORDER BY fecha_solicitud DESC
            ");
            $stmt->execute([$termino, $termino, $termino]);
            
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
        } catch (\Exception $e) {
            error_log("Error en Requisicion::buscar: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Override insert method to handle numero_requisicion
     * 
     * Como numero_requisicion necesita un valor (no puede ser NULL),
     * usamos el ID directamente como valor (se actualizará después del INSERT)
     */
    protected function insert()
    {
        $numeroRequisicionVacío = empty($this->getAttribute('numero_requisicion'));
        
        // Si no tiene numero_requisicion, usar un valor temporal (el ID se asignará después)
        // Como no conocemos el ID aún, usamos un valor temporal que luego se actualiza
        if ($numeroRequisicionVacío) {
            // Usar timestamp como valor temporal único
            $this->setAttribute('numero_requisicion', (string)time());
        }
        
        // Ejecutar el INSERT
        $resultado = parent::insert();
        
        // Después del INSERT, si usamos valor temporal, actualizar con el ID real
        // (pero como las vistas muestran solo el ID, no es crítico actualizar)
        if ($resultado && $numeroRequisicionVacío) {
            $id = $this->getAttribute(static::$primaryKey);
            if ($id) {
                // Actualizar numero_requisicion con el ID (igual que se muestra en "Mis Requisiciones")
                // Simplemente usar el ID como numero_requisicion, sin formato adicional
                $this->setAttribute('numero_requisicion', (string)$id);
                
                // Actualizar en la base de datos
                $pdo = static::getConnection();
                $stmt = $pdo->prepare("UPDATE " . static::getTable() . " SET numero_requisicion = ? WHERE " . static::$primaryKey . " = ?");
                $stmt->execute([(string)$id, $id]);
                
                // Actualizar también el atributo original
                if (!isset($this->original)) {
                    $this->original = [];
                }
                $this->original['numero_requisicion'] = (string)$id;
            }
        }
        
        return $resultado;
    }

    /**
     * Override create method
     * 
     * El numero_requisicion se genera automáticamente en insert() usando el ID
     * 
     * @param array $attributes Atributos de la requisición
     * @return static Nueva instancia de Requisicion
     */
    public static function create(array $attributes)
    {
        // numero_requisicion se genera automáticamente en insert() basado en el ID
        // Si se proporciona manualmente, se respeta
        return parent::create($attributes);
    }

    /**
     * Obtiene estadísticas de un usuario
     * 
     * @param int $usuarioId ID del usuario
     * @return array Estadísticas del usuario
     */
    public static function getEstadisticasUsuario($usuarioId)
    {
        try {
            $pdo = static::getConnection();
            
            // Estadísticas generales usando el flujo de autorización
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN af.estado IN ('pendiente_revision', 'pendiente_autorizacion_pago', 'pendiente_autorizacion_cuenta', 'pendiente_autorizacion_centros', 'pendiente_autorizacion') THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN af.estado = 'autorizado' THEN 1 ELSE 0 END) as autorizadas,
                    SUM(CASE WHEN af.estado IN ('rechazado_revision', 'rechazado_autorizacion', 'rechazado') THEN 1 ELSE 0 END) as rechazadas,
                    COALESCE(SUM(r.monto_total), 0) as monto_total
                FROM requisiciones r
                LEFT JOIN autorizacion_flujo af ON r.id = af.requisicion_id
                WHERE r.usuario_id = ?
            ");
            $stmt->execute([$usuarioId]);
            $stats = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            // Monto del mes actual
            $mesActual = date('Y-m');
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(monto_total), 0) as monto_mes_actual
                FROM requisiciones
                WHERE usuario_id = ?
                AND DATE_FORMAT(fecha_solicitud, '%Y-%m') = ?
            ");
            $stmt->execute([$usuarioId, $mesActual]);
            $mesStats = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return [
                'total' => (int)($stats['total'] ?? 0),
                'pendientes' => (int)($stats['pendientes'] ?? 0),
                'autorizadas' => (int)($stats['autorizadas'] ?? 0),
                'rechazadas' => (int)($stats['rechazadas'] ?? 0),
                'monto_total' => (float)($stats['monto_total'] ?? 0),
                'monto_mes_actual' => (float)($mesStats['monto_mes_actual'] ?? 0)
            ];
        } catch (\Exception $e) {
            error_log("Error en Requisicion::getEstadisticasUsuario: " . $e->getMessage());
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
     * Obtiene estadísticas generales del sistema
     * 
     * @return array Estadísticas generales
     */
    public static function getEstadisticasGenerales()
    {
        try {
            $pdo = static::getConnection();
            
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(DISTINCT r.id) as total,
                    SUM(CASE WHEN af.estado IN ('pendiente_revision') THEN 1 ELSE 0 END) as pendientes_revision,
                    SUM(CASE WHEN af.estado IN ('pendiente_autorizacion_pago', 'pendiente_autorizacion_cuenta', 'pendiente_autorizacion_centros', 'pendiente_autorizacion') THEN 1 ELSE 0 END) as pendientes_autorizacion,
                    SUM(CASE WHEN af.estado = 'autorizado' AND DATE(r.fecha_completada) = CURDATE() THEN 1 ELSE 0 END) as autorizadas_hoy,
                    COALESCE(SUM(CASE WHEN DATE_FORMAT(r.fecha_solicitud, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m') THEN r.monto_total ELSE 0 END), 0) as monto_total_mes
                FROM requisiciones r
                LEFT JOIN autorizacion_flujo af ON r.id = af.requisicion_id
            ");
            $stmt->execute();
            $stats = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            // Usuarios activos (que han creado requisiciones)
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT usuario_id) as usuarios_activos
                FROM requisiciones
                WHERE fecha_solicitud >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            $usersStats = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            // Tiempo promedio de autorización
            $stmt = $pdo->prepare("
                SELECT AVG(TIMESTAMPDIFF(HOUR, fecha_creacion, fecha_completado)) as tiempo_promedio
                FROM autorizacion_flujo
                WHERE estado = 'completado'
                AND fecha_completado IS NOT NULL
                AND fecha_completado >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            $timeStats = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return [
                'total' => (int)($stats['total'] ?? 0),
                'pendientes_revision' => (int)($stats['pendientes_revision'] ?? 0),
                'pendientes_autorizacion' => (int)($stats['pendientes_autorizacion'] ?? 0),
                'autorizadas_hoy' => (int)($stats['autorizadas_hoy'] ?? 0),
                'monto_total_mes' => (float)($stats['monto_total_mes'] ?? 0),
                'usuarios_activos' => (int)($usersStats['usuarios_activos'] ?? 0),
                'tiempo_promedio' => (float)($timeStats['tiempo_promedio'] ?? 0)
            ];
        } catch (\Exception $e) {
            error_log("Error en Requisicion::getEstadisticasGenerales: " . $e->getMessage());
            return [
                'total' => 0,
                'pendientes_revision' => 0,
                'pendientes_autorizacion' => 0,
                'autorizadas_hoy' => 0,
                'monto_total_mes' => 0,
                'usuarios_activos' => 0,
                'tiempo_promedio' => 0
            ];
        }
    }

    /**
     * Obtiene requisiciones de un usuario por mes
     * 
     * @param int $usuarioId ID del usuario
     * @param string $mes Mes en formato Y-m (ej: 2024-01)
     * @return array Array de objetos Requisicion
     */
    public static function porUsuarioYMes($usuarioId, $mes)
    {
        try {
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
        } catch (\Exception $e) {
            error_log("Error en Requisicion::porUsuarioYMes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene las requisiciones más recientes de un usuario
     * 
     * @param int $usuarioId ID del usuario
     * @param int $limite Número máximo de requisiciones
     * @return array Array de objetos Requisicion
     */
    public static function recentesPorUsuario($usuarioId, $limite = 5)
    {
        try {
            $pdo = static::getConnection();
            $stmt = $pdo->prepare("
                SELECT * FROM requisiciones 
                WHERE usuario_id = ? 
                ORDER BY fecha_solicitud DESC, id DESC
                LIMIT ?
            ");
            $stmt->execute([$usuarioId, $limite]);
            
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
        } catch (\Exception $e) {
            error_log("Error en Requisicion::recentesPorUsuario: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene requisiciones actualizadas recientemente de un usuario
     * 
     * @param int $usuarioId ID del usuario
     * @param int $horas Número de horas hacia atrás
     * @return array Array de objetos Requisicion
     */
    public static function actualizadasReciente($usuarioId, $horas = 24)
    {
        try {
            $pdo = static::getConnection();
            $stmt = $pdo->prepare("
                SELECT * FROM requisiciones 
                WHERE usuario_id = ? 
                AND (updated_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                     OR fecha_solicitud >= DATE_SUB(NOW(), INTERVAL ? HOUR))
                ORDER BY COALESCE(updated_at, fecha_solicitud) DESC
            ");
            $stmt->execute([$usuarioId, $horas, $horas]);
            
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
        } catch (\Exception $e) {
            error_log("Error en Requisicion::actualizadasReciente: " . $e->getMessage());
            return [];
        }
    }
}