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
    protected $table = 'requisiciones';
    protected $primaryKey = 'id';
    
    // Estados posibles
    const ESTADO_BORRADOR = 'borrador';
    const ESTADO_PENDIENTE_REVISION = 'pendiente_revision';
    const ESTADO_PENDIENTE_AUTORIZACION = 'pendiente_autorizacion';
    const ESTADO_AUTORIZADA = 'autorizada';
    const ESTADO_RECHAZADA = 'rechazada';
    
    // Prioridades
    const PRIORIDAD_BAJA = 'baja';
    const PRIORIDAD_NORMAL = 'normal';
    const PRIORIDAD_ALTA = 'alta';
    const PRIORIDAD_URGENTE = 'urgente';
    
    protected $fillable = [
        'numero_requisicion',
        'estado',
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
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Verifica si la requisición está en estado borrador
     */
    public function esBorrador(): bool
    {
        return $this->estado === self::ESTADO_BORRADOR;
    }

    /**
     * Verifica si está pendiente de revisión
     */
    public function estaPendienteRevision(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE_REVISION;
    }

    /**
     * Verifica si está pendiente de autorización
     */
    public function estaPendienteAutorizacion(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE_AUTORIZACION;
    }

    /**
     * Verifica si está autorizada
     */
    public function estaAutorizada(): bool
    {
        return $this->estado === self::ESTADO_AUTORIZADA;
    }

    /**
     * Verifica si fue rechazada
     */
    public function estaRechazada(): bool
    {
        return $this->estado === self::ESTADO_RECHAZADA;
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
     * Cambia el estado de la requisición
     */
    public function cambiarEstado(string $nuevoEstado, int $usuarioId = null, string $comentarios = null): bool
    {
        $estadoAnterior = $this->estado;
        
        $this->estado = $nuevoEstado;
        if ($nuevoEstado === self::ESTADO_AUTORIZADA) {
            $this->fecha_completada = date('Y-m-d H:i:s');
        }
        
        $resultado = $this->save();
        
        if ($resultado) {
            // Registrar en historial
            HistorialRequisicion::registrarCambio(
                $this->id,
                $this->mapearEstadoAAccion($nuevoEstado),
                $estadoAnterior,
                $nuevoEstado,
                $usuarioId,
                $comentarios
            );
        }
        
        return $resultado;
    }

    /**
     * Mapea estado a acción para el historial
     */
    private function mapearEstadoAAccion(string $estado): string
    {
        $mapeo = [
            self::ESTADO_PENDIENTE_REVISION => 'enviada_revision',
            self::ESTADO_PENDIENTE_AUTORIZACION => 'enviada_autorizacion',
            self::ESTADO_AUTORIZADA => 'completada',
            self::ESTADO_RECHAZADA => 'rechazada'
        ];
        
        return $mapeo[$estado] ?? 'editada';
    }

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
        $stmt = $pdo->prepare("
            SELECT pa.email, pa.nombre 
            FROM persona_autorizada pa 
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
     * Autoriza completamente la requisición
     */
    public function autorizarCompleta(int $usuarioId): bool
    {
        if (!$this->puedeAutorizarCompleta()) {
            throw new \Exception("No se puede autorizar completamente");
        }

        return $this->cambiarEstado(self::ESTADO_AUTORIZADA, $usuarioId);
    }

    /**
     * Busca requisiciones por estado
     */
    public static function porEstado(string $estado, int $limite = 20): array
    {
        $pdo = static::getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM requisiciones 
            WHERE estado = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$estado, $limite]);
        return $stmt->fetchAll();
    }

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
     * Genera número de requisición único
     */
    public static function generarNumeroRequisicion(): string
    {
        $año = date('Y');
        $pdo = static::getConnection();
        
        // Obtener el último número del año
        $stmt = $pdo->prepare("
            SELECT numero_requisicion 
            FROM requisiciones 
            WHERE numero_requisicion LIKE ? 
            ORDER BY numero_requisicion DESC 
            LIMIT 1
        ");
        $stmt->execute(["{$año}-%"]);
        $ultimo = $stmt->fetchColumn();

        if ($ultimo) {
            $numero = (int) substr($ultimo, -4) + 1;
        } else {
            $numero = 1;
        }

        return sprintf('%s-%04d', $año, $numero);
    }
}