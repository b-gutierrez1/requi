<?php

namespace App\Models;

/**
 * Modelo de Autorizaciones Unificado
 * 
 * Tabla: autorizaciones
 * Maneja todos los tipos de autorización en una sola tabla
 */
class Autorizacion extends Model
{
    protected static $table = 'autorizaciones';
    protected static $primaryKey = 'id';
    
    // Tipos de autorización
    const TIPO_REVISION = 'revision';
    const TIPO_CENTRO_COSTO = 'centro_costo';
    const TIPO_FORMA_PAGO = 'forma_pago';
    const TIPO_CUENTA_CONTABLE = 'cuenta_contable';
    
    // Estados
    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_APROBADA = 'aprobada';
    const ESTADO_RECHAZADA = 'rechazada';

    protected static $fillable = [
        'requisicion_id',
        'tipo',
        'nivel',
        'centro_costo_id',
        'autorizador_email',
        'autorizador_nombre',
        'estado',
        'fecha_vencimiento',
        'comentarios',
        'motivo_rechazo'
    ];

    protected $casts = [
        'fecha_asignacion' => 'datetime',
        'fecha_respuesta' => 'datetime',
        'fecha_vencimiento' => 'datetime',
        'ultimo_recordatorio' => 'datetime'
    ];

    /**
     * Relación con requisición
     */
    public function requisicion()
    {
        return $this->belongsTo(Requisicion::class, 'requisicion_id');
    }

    /**
     * Relación con centro de costo (si aplica)
     */
    public function centroCosto()
    {
        return $this->belongsTo(CentroDeCosto::class, 'centro_costo_id');
    }

    /**
     * Verifica si está pendiente
     */
    public function estaPendiente(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    /**
     * Verifica si está aprobada
     */
    public function estaAprobada(): bool
    {
        return $this->estado === self::ESTADO_APROBADA;
    }

    /**
     * Verifica si está rechazada
     */
    public function estaRechazada(): bool
    {
        return $this->estado === self::ESTADO_RECHAZADA;
    }

    /**
     * Verifica si está vencida
     */
    public function estaVencida(): bool
    {
        return $this->fecha_vencimiento && $this->fecha_vencimiento < now() && $this->estaPendiente();
    }

    /**
     * Obtiene los días para vencer
     */
    public function diasParaVencer(): int
    {
        if (!$this->fecha_vencimiento || !$this->estaPendiente()) {
            return 0;
        }

        $diff = $this->fecha_vencimiento->diffInDays(now(), false);
        return (int) $diff;
    }

    /**
     * Aprueba la autorización
     */
    public function aprobar(string $comentarios = null, int $usuarioId = null): bool
    {
        if (!$this->estaPendiente()) {
            throw new \Exception("Solo se pueden aprobar autorizaciones pendientes");
        }

        $this->estado = self::ESTADO_APROBADA;
        $this->fecha_respuesta = now();
        $this->comentarios = $comentarios;
        $this->calcularTiempoRespuesta();

        $resultado = $this->save();

        if ($resultado) {
            // Registrar en historial
            HistorialRequisicion::registrarCambio(
                $this->requisicion_id,
                'aprobada',
                'pendiente',
                'aprobada',
                $usuarioId,
                "Autorización {$this->tipo} aprobada: {$comentarios}"
            );

            // Verificar si se puede completar la requisición
            $this->verificarCompletitudRequisicion();
        }

        return $resultado;
    }

    /**
     * Rechaza la autorización
     */
    public function rechazar(string $motivo, int $usuarioId = null): bool
    {
        if (!$this->estaPendiente()) {
            throw new \Exception("Solo se pueden rechazar autorizaciones pendientes");
        }

        $this->estado = self::ESTADO_RECHAZADA;
        $this->fecha_respuesta = now();
        $this->motivo_rechazo = $motivo;
        $this->calcularTiempoRespuesta();

        $resultado = $this->save();

        if ($resultado) {
            // Registrar en historial
            HistorialRequisicion::registrarCambio(
                $this->requisicion_id,
                'rechazada',
                'pendiente',
                'rechazada',
                $usuarioId,
                "Autorización {$this->tipo} rechazada: {$motivo}"
            );

            // Rechazar toda la requisición si se rechaza cualquier autorización
            $requisicion = $this->requisicion;
            if ($requisicion && !$requisicion->estaRechazada()) {
                $requisicion->cambiarEstado(Requisicion::ESTADO_RECHAZADA, $usuarioId, $motivo);
            }
        }

        return $resultado;
    }

    /**
     * Calcula el tiempo de respuesta en segundos
     */
    private function calcularTiempoRespuesta(): void
    {
        if ($this->fecha_asignacion && $this->fecha_respuesta) {
            $this->tiempo_respuesta = $this->fecha_respuesta->diffInSeconds($this->fecha_asignacion);
        }
    }

    /**
     * Verifica si todas las autorizaciones están completas para completar la requisición
     */
    private function verificarCompletitudRequisicion(): void
    {
        $requisicion = $this->requisicion;
        if (!$requisicion || !$requisicion->estaPendienteAutorizacion()) {
            return;
        }

        $pendientes = static::where('requisicion_id', $this->requisicion_id)
            ->where('estado', self::ESTADO_PENDIENTE)
            ->count();

        if ($pendientes === 0) {
            $requisicion->cambiarEstado(Requisicion::ESTADO_AUTORIZADA);
        }
    }

    /**
     * Obtiene autorizaciones pendientes para un email
     */
    public static function pendientesParaAutorizador(string $email): array
    {
        $pdo = static::getConnection();
        $stmt = $pdo->prepare("
            SELECT a.*, r.numero_requisicion, r.proveedor_nombre, r.monto_total,
                   cc.nombre as centro_costo_nombre,
                   dc.monto as monto_centro
            FROM autorizaciones a
            JOIN requisiciones r ON a.requisicion_id = r.id
            LEFT JOIN centro_de_costo cc ON a.centro_costo_id = cc.id
            LEFT JOIN distribucion_centros dc ON a.requisicion_id = dc.requisicion_id 
                AND a.centro_costo_id = dc.centro_costo_id
            WHERE a.autorizador_email = ? 
            AND a.estado = 'pendiente'
            ORDER BY a.fecha_vencimiento ASC
        ");
        $stmt->execute([$email]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene autorizaciones por tipo
     */
    public static function porTipo(string $tipo, string $estado = null): array
    {
        $sql = "SELECT * FROM autorizaciones WHERE tipo = ?";
        $params = [$tipo];

        if ($estado) {
            $sql .= " AND estado = ?";
            $params[] = $estado;
        }

        $sql .= " ORDER BY fecha_asignacion DESC";

        $pdo = static::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene autorizaciones vencidas
     */
    public static function vencidas(): array
    {
        $pdo = static::getConnection();
        $stmt = $pdo->prepare("
            SELECT a.*, r.numero_requisicion, r.proveedor_nombre, r.monto_total
            FROM autorizaciones a
            JOIN requisiciones r ON a.requisicion_id = r.id
            WHERE a.estado = 'pendiente' 
            AND a.fecha_vencimiento < NOW()
            ORDER BY a.fecha_vencimiento ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtiene autorizaciones próximas a vencer
     */
    public static function proximasAVencer(int $diasAlerta = 1): array
    {
        $pdo = static::getConnection();
        $stmt = $pdo->prepare("
            SELECT a.*, r.numero_requisicion, r.proveedor_nombre, r.monto_total
            FROM autorizaciones a
            JOIN requisiciones r ON a.requisicion_id = r.id
            WHERE a.estado = 'pendiente' 
            AND a.fecha_vencimiento > NOW()
            AND a.fecha_vencimiento <= DATE_ADD(NOW(), INTERVAL ? DAY)
            ORDER BY a.fecha_vencimiento ASC
        ");
        $stmt->execute([$diasAlerta]);
        return $stmt->fetchAll();
    }

    /**
     * Envía recordatorio
     */
    public function enviarRecordatorio(): bool
    {
        if (!$this->estaPendiente()) {
            return false;
        }

        // Actualizar contador de recordatorios
        $this->notificaciones_enviadas++;
        $this->ultimo_recordatorio = now();
        
        $resultado = $this->save();

        if ($resultado) {
            // Aquí iría la lógica de envío de email
            // NotificationService::sendReminder($this);
            error_log("Recordatorio enviado a {$this->autorizador_email} para autorización {$this->id}");
        }

        return $resultado;
    }

    /**
     * Crea autorizaciones masivas por centro de costo
     */
    public static function crearPorCentrosCosto(int $requisicionId, array $centrosCosto): array
    {
        $autorizaciones = [];

        foreach ($centrosCosto as $centroCosto) {
            // Obtener autorizador del centro
            $autorizador = static::obtenerAutorizadorCentroCosto($centroCosto['centro_costo_id']);

            $autorizacion = static::create([
                'requisicion_id' => $requisicionId,
                'tipo' => self::TIPO_CENTRO_COSTO,
                'centro_costo_id' => $centroCosto['centro_costo_id'],
                'autorizador_email' => $autorizador['email'],
                'autorizador_nombre' => $autorizador['nombre'],
                'fecha_vencimiento' => date('Y-m-d H:i:s', strtotime('+3 days'))
            ]);

            $autorizaciones[] = $autorizacion;
        }

        return $autorizaciones;
    }

    /**
     * Obtiene autorizador para un centro de costo
     */
    private static function obtenerAutorizadorCentroCosto(int $centroCostoId): array
    {
        $pdo = static::getConnection();
        $table = \App\Models\PersonaAutorizada::getTable();
        $stmt = $pdo->prepare("
            SELECT pa.email, pa.nombre 
            FROM {$table} pa 
            WHERE pa.centro_costo_id = ? AND pa.activo = 1
            LIMIT 1
        ");
        $stmt->execute([$centroCostoId]);
        $resultado = $stmt->fetch();

        if ($resultado) {
            return $resultado;
        }

        // Fallback
        return [
            'email' => 'admin@sistema.com',
            'nombre' => 'Administrador del Sistema'
        ];
    }

    /**
     * Obtiene estadísticas de autorizaciones
     */
    public static function estadisticas(): array
    {
        $pdo = static::getConnection();
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'aprobada' THEN 1 ELSE 0 END) as aprobadas,
                SUM(CASE WHEN estado = 'rechazada' THEN 1 ELSE 0 END) as rechazadas,
                SUM(CASE WHEN estado = 'pendiente' AND fecha_vencimiento < NOW() THEN 1 ELSE 0 END) as vencidas,
                AVG(tiempo_respuesta) as tiempo_promedio_respuesta
            FROM autorizaciones
        ");
        $stmt->execute();
        return $stmt->fetch();
    }
}