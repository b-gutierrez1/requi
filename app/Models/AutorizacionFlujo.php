<?php
/**
 * Modelo AutorizacionFlujo
 * 
 * MODELO PRINCIPAL del sistema de autorizaciones.
 * Gestiona el flujo completo de autorización de una requisición:
 * - Revisión inicial
 * - Autorización especial por método de pago
 * - Autorización especial por cuenta contable
 * - Autorización por centros de costo
 * - Estado final
 * 
 * @package RequisicionesMVC\Models
 * @version 2.0
 */

namespace App\Models;

use App\Repositories\AutorizacionCentroRepository;

class AutorizacionFlujo extends Model
{
    protected static $table = 'autorizacion_flujo';
    protected static $primaryKey = 'id';
    protected static $timestamps = false;  // La tabla usa fecha_creacion, no created_at

    protected static $fillable = [
        'requisicion_id',
        'estado',
        'requiere_autorizacion_especial_pago',
        'requiere_autorizacion_especial_cuenta',
        'monto_total',
        'revisor_email',
        'revisor_comentario',
        'revisor_fecha',
        'motivo_rechazo',
        'observaciones_generales',
        'prioridad',
        'fecha_limite',
    ];

    protected static $guarded = ['id', 'fecha_inicio', 'fecha_completado'];

    /**
     * Estados posibles del flujo
     */
    const ESTADO_PENDIENTE_REVISION = 'pendiente_revision';
    const ESTADO_RECHAZADO_REVISION = 'rechazado_revision';
    const ESTADO_PENDIENTE_AUTORIZACION_PAGO = 'pendiente_autorizacion_pago';
    const ESTADO_PENDIENTE_AUTORIZACION_CUENTA = 'pendiente_autorizacion_cuenta';
    const ESTADO_PENDIENTE_AUTORIZACION_CENTROS = 'pendiente_autorizacion_centros';
    /**
     * @deprecated Estado legacy — el flujo ya nunca asigna este valor.
     *             Se conserva únicamente para compatibilidad con registros históricos
     *             en BD que puedan tener el valor 'pendiente_autorizacion'.
     *             No usar en código nuevo; usar ESTADO_PENDIENTE_AUTORIZACION_CENTROS.
     */
    const ESTADO_PENDIENTE_AUTORIZACION = 'pendiente_autorizacion'; // @deprecated usar ESTADO_PENDIENTE_AUTORIZACION_PAGO
    const ESTADO_RECHAZADO_AUTORIZACION = 'rechazado_autorizacion';
    const ESTADO_AUTORIZADO = 'autorizado';
    const ESTADO_RECHAZADO = 'rechazado';

    protected static ?AutorizacionCentroRepository $centroRepository = null;

    protected static function centrosRepo(): AutorizacionCentroRepository
    {
        if (!self::$centroRepository) {
            self::$centroRepository = new AutorizacionCentroRepository();
        }

        return self::$centroRepository;
    }

    /**
     * Obtiene la orden de compra asociada
     * 
     * @return array|null
     */
    public function ordenCompra()
    {
        if (!isset($this->attributes['requisicion_id'])) {
            return null;
        }

        return Requisicion::find($this->attributes['requisicion_id']);
    }

    /**
     * Obtiene las autorizaciones por centro de costo
     * 
     * @return array
     */
    public function autorizacionesCentros()
    {
        if (!isset($this->attributes['requisicion_id'])) {
            return [];
        }

        return self::centrosRepo()->getByRequisicion((int) $this->attributes['requisicion_id']);
    }

    /**
     * Obtiene el flujo de una requisición
     * 
     * @param int $requisicionId
     * @return array|null
     */
    public static function porRequisicion($requisicionId)
    {
        $sql = "SELECT * FROM autorizacion_flujo WHERE requisicion_id = ? LIMIT 1";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$requisicionId]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Obtiene el flujo por orden de compra (alias por compatibilidad legacy)
     * 
     * @param int $ordenCompraId ID de la requisición (anteriormente orden de compra)
     * @return array|null
     */
    public static function porOrdenCompra($ordenCompraId)
    {
        return self::porRequisicion($ordenCompraId);
    }

    /**
     * Crea el flujo inicial de autorización
     * 
     * @param int $ordenCompraId
     * @return int|false ID del flujo o false
     */
    public static function iniciarFlujo($ordenCompraId)
    {
        try {
            $requisicion = Requisicion::find($ordenCompraId);
            if (!$requisicion) {
                throw new \Exception("Requisición no encontrada");
            }

            // Verificar si requiere autorizaciones especiales
            $requiereEspecialPago = AutorizadorMetodoPago::requiereAutorizacionEspecial($requisicion->forma_pago);
            
            // Verificar si alguna cuenta contable requiere autorización especial
            $distribuciones = DistribucionGasto::porRequisicion($ordenCompraId);
            $requiereEspecialCuenta = false;
            
            foreach ($distribuciones as $dist) {
                if (AutorizadorCuentaContable::requiereAutorizacionEspecial($dist['cuenta_contable_id'])) {
                    $requiereEspecialCuenta = true;
                    break;
                }
            }

            $flujo = self::create([
                'requisicion_id'                    => $ordenCompraId,
                'estado'                            => self::ESTADO_PENDIENTE_REVISION,
                'requiere_autorizacion_especial_pago'   => $requiereEspecialPago ? 1 : 0,
                'requiere_autorizacion_especial_cuenta' => $requiereEspecialCuenta ? 1 : 0,
                'monto_total'                       => $requisicion->monto_total ?? 0,
            ]);

            return $flujo ? ($flujo->id ?? $flujo['id']) : false;
        } catch (\Exception $e) {
            error_log("Error iniciando flujo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Aprobar en nivel de revisión
     * 
     * @param int $id
     * @param int $usuarioId
     * @param string $comentario
     * @return bool
     */
    public static function aprobarRevision($id, $usuarioId, $comentario = '', array $asignaciones = [])
    {
        $pdo = self::getConnection();
        try {
            $flujo = self::find($id);
            if (!$flujo) {
                return false;
            }

            $flujoArray = is_object($flujo) ? $flujo->toArray() : $flujo;
            $estado = $flujoArray['estado'];

            if ($estado !== self::ESTADO_PENDIENTE_REVISION) {
                return false;
            }

            $requiereEspecialPago   = $flujoArray['requiere_autorizacion_especial_pago'];
            $requiereEspecialCuenta = $flujoArray['requiere_autorizacion_especial_cuenta'];

            // Flujo secuencial: pago → cuenta → centros
            if ($requiereEspecialPago) {
                $nuevoEstado = self::ESTADO_PENDIENTE_AUTORIZACION_PAGO;
            } elseif ($requiereEspecialCuenta) {
                $nuevoEstado = self::ESTADO_PENDIENTE_AUTORIZACION_CUENTA;
            } else {
                $nuevoEstado = self::ESTADO_PENDIENTE_AUTORIZACION_CENTROS;
            }

            $revisorEmail = null;
            if ($usuarioId) {
                $revisor = \App\Models\Usuario::find($usuarioId);
                $revisorEmail = $revisor ? ($revisor->email ?? $revisor->azure_email ?? null) : null;
            }

            // Transacción única: cambio de estado + creación de autorizaciones son atómicos.
            // Si falla la creación de autorizaciones, el estado NO queda actualizado.
            $pdo->beginTransaction();

            self::updateById($id, [
                'estado'             => $nuevoEstado,
                'revisor_email'      => $revisorEmail,
                'revisor_comentario' => $comentario ?: null,
                'revisor_fecha'      => date('Y-m-d H:i:s'),
            ]);

            self::crearAutorizacionesEspeciales($id, $flujoArray, $asignaciones);

            $pdo->commit();

            $ordenCompraId = $flujoArray['requisicion_id'];
            HistorialRequisicion::registrarAprobacion(
                $ordenCompraId,
                $usuarioId,
                'Revisión',
                $comentario
            );

            return true;

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error aprobando revisión flujo #{$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Aprobar revisión (método simplificado para testing)
     * 
     * @param int $id
     * @param string $comentario
     * @return bool
     */
    public static function aprobarRevisionSimple($id, $comentario = '')
    {
        // Usar usuario 0 como sistema si no se proporciona
        return self::aprobarRevision($id, 0, $comentario);
    }

    /**
     * Rechazar en nivel de revisión
     * 
     * @param int $id
     * @param int $usuarioId
     * @param string $motivo
     * @return bool
     */
    public static function rechazarRevision($id, $usuarioId, $motivo)
    {
        try {
            $flujo = self::find($id);
            if (!$flujo) {
                return false;
            }

            self::updateById($id, [
                'estado' => self::ESTADO_RECHAZADO_REVISION,
                'fecha_completado' => date('Y-m-d H:i:s'),
            ]);

            $ordenCompraId = is_object($flujo) ? $flujo->requisicion_id : $flujo['requisicion_id'];
            HistorialRequisicion::registrarRechazo(
                $ordenCompraId,
                $usuarioId,
                $motivo
            );

            return true;
        } catch (\Exception $e) {
            error_log("Error rechazando revisión: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica y actualiza el estado del flujo según las autorizaciones
     * 
     * @param int $id
     * @return bool
     */
    public static function verificarYActualizarEstado($id)
    {
        $pdo = self::getConnection();
        try {
            $pdo->beginTransaction();

            // SELECT FOR UPDATE: bloquea el registro durante la transacción
            // para evitar race conditions cuando dos autorizadores actúan simultáneamente.
            $stmt = $pdo->prepare(
                "SELECT * FROM autorizacion_flujo WHERE id = ? FOR UPDATE"
            );
            $stmt->execute([$id]);
            $flujo = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$flujo) {
                $pdo->rollBack();
                return false;
            }

            $estado = $flujo['estado'];
            $estadosAutorizacionPendiente = [
                self::ESTADO_PENDIENTE_AUTORIZACION,
                self::ESTADO_PENDIENTE_AUTORIZACION_PAGO,
                self::ESTADO_PENDIENTE_AUTORIZACION_CUENTA,
                self::ESTADO_PENDIENTE_AUTORIZACION_CENTROS,
            ];

            if (!in_array($estado, $estadosAutorizacionPendiente)) {
                $pdo->rollBack();
                return false;
            }

            $requisicionId = (int) $flujo['requisicion_id'];
            if (!$requisicionId) {
                $pdo->rollBack();
                return false;
            }

            // Verificar si hay rechazos
            if (self::centrosRepo()->hasRejected($requisicionId)) {
                self::updateById($id, [
                    'estado'           => self::ESTADO_RECHAZADO,
                    'fecha_completado' => date('Y-m-d H:i:s'),
                ]);
                $pdo->commit();
                return true;
            }

            $requiereCuenta = $flujo['requiere_autorizacion_especial_cuenta'];

            // Contar autorizaciones especiales pendientes dentro de la misma transacción
            $stmt = $pdo->prepare("
                SELECT tipo, COUNT(*) as pendientes
                FROM autorizaciones
                WHERE requisicion_id = ?
                  AND tipo IN ('forma_pago', 'cuenta_contable')
                  AND estado = 'pendiente'
                GROUP BY tipo
            ");
            $stmt->execute([$requisicionId]);
            $especialesPendientes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $pagosPendientes   = false;
            $cuentasPendientes = false;
            foreach ($especialesPendientes as $pendiente) {
                if ($pendiente['tipo'] === 'forma_pago')     $pagosPendientes   = true;
                if ($pendiente['tipo'] === 'cuenta_contable') $cuentasPendientes = true;
            }

            $updated = false;

            // Flujo secuencial: pago → cuenta → centros
            if ($estado === self::ESTADO_PENDIENTE_AUTORIZACION_PAGO) {
                if (!$pagosPendientes) {
                    // Pago listo: ¿siguiente es cuenta o centros?
                    $nuevoEstado = ($requiereCuenta && $cuentasPendientes)
                        ? self::ESTADO_PENDIENTE_AUTORIZACION_CUENTA
                        : self::ESTADO_PENDIENTE_AUTORIZACION_CENTROS;
                    self::updateById($id, ['estado' => $nuevoEstado]);
                    $updated = true;
                }
            } elseif ($estado === self::ESTADO_PENDIENTE_AUTORIZACION_CUENTA) {
                if (!$cuentasPendientes) {
                    // Cuenta lista: siempre va a centros
                    self::updateById($id, ['estado' => self::ESTADO_PENDIENTE_AUTORIZACION_CENTROS]);
                    $updated = true;
                }
            } elseif (!$pagosPendientes && !$cuentasPendientes) {
                if (self::centrosRepo()->allAuthorized($requisicionId)) {
                    self::updateById($id, [
                        'estado'           => self::ESTADO_AUTORIZADO,
                        'fecha_completado' => date('Y-m-d H:i:s'),
                    ]);
                    $updated = true;
                }
            }

            $pdo->commit();
            return $updated;

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error verificando estado flujo #{$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marca un flujo como rechazado
     *
     * @param int $id
     * @param string|null $motivo
     * @return bool
     */
    public static function marcarComoRechazado($id, $motivo = null)
    {
        try {
            return self::updateById($id, [
                'estado' => self::ESTADO_RECHAZADO,
                'fecha_completado' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            error_log("Error marcando flujo como rechazado: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene flujos por estado
     * 
     * @param string $estado
     * @param int $limit
     * @return array
     */
    public static function porEstado($estado, $limit = null)
    {
        $instance = new static();
        
        $sql = "SELECT af.*, r.proveedor_nombre as nombre_razon_social, r.monto_total, r.fecha_solicitud as fecha,
                       r.numero_requisicion, r.fecha_solicitud as fecha_orden,
                       u.azure_display_name as usuario_nombre
                FROM autorizacion_flujo af
                INNER JOIN requisiciones r ON af.requisicion_id = r.id
                LEFT JOIN usuarios u ON r.usuario_id = u.id
                WHERE af.estado = ?
                ORDER BY r.fecha_solicitud DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$estado]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene estadísticas generales de flujos
     * 
     * @return array
     */
    public static function getEstadisticas()
    {
        $instance = new static();
        
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'pendiente_revision' THEN 1 ELSE 0 END) as pendientes_revision,
                    SUM(CASE WHEN estado = 'pendiente_autorizacion' THEN 1 ELSE 0 END) as pendientes_autorizacion,
                    SUM(CASE WHEN estado = 'autorizado' THEN 1 ELSE 0 END) as autorizados,
                    SUM(CASE WHEN estado = 'rechazado' THEN 1 ELSE 0 END) as rechazados,
                    AVG(DATEDIFF(fecha_completado, fecha_inicio)) as dias_promedio_completado
                FROM {$instance->table}";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [
            'total' => 0,
            'pendientes_revision' => 0,
            'pendientes_autorizacion' => 0,
            'autorizados' => 0,
            'rechazados' => 0,
            'dias_promedio_completado' => 0
        ];
    }

    /**
     * Obtiene flujos atrasados (más de X días pendientes)
     * 
     * @param int $dias
     * @return array
     */
    public static function atrasados($dias = 3)
    {
        $instance = new static();
        
        $sql = "SELECT af.*, r.proveedor_nombre as nombre_razon_social, r.fecha_solicitud as fecha,
                    DATEDIFF(NOW(), af.fecha_inicio) as dias_pendiente
                FROM {$instance->table} af
                INNER JOIN requisiciones r ON af.requisicion_id = r.id
                WHERE af.estado IN ('pendiente_revision', 'pendiente_autorizacion')
                AND DATEDIFF(NOW(), af.fecha_inicio) > ?
                ORDER BY dias_pendiente DESC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$dias]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el badge de estado
     * 
     * @return array
     */
    public function getEstadoBadge()
    {
        $estado = $this->attributes['estado'] ?? 'pendiente_revision';
        
        $badges = [
            'pendiente_revision' => ['class' => 'badge-warning', 'text' => 'Pendiente Revisión'],
            'pendiente_autorizacion' => ['class' => 'badge-info', 'text' => 'Pendiente Autorización'],
            'autorizado' => ['class' => 'badge-success', 'text' => 'Autorizado'],
            'rechazado' => ['class' => 'badge-danger', 'text' => 'Rechazado'],
        ];

        return $badges[$estado] ?? $badges['pendiente_revision'];
    }

    /**
     * Verifica si el flujo está en estado final
     * 
     * @return bool
     */
    public function estaCompleto()
    {
        $estado = $this->attributes['estado'] ?? '';
        return in_array($estado, [self::ESTADO_AUTORIZADO, self::ESTADO_RECHAZADO]);
    }

    /**
     * Obtiene el progreso del flujo (porcentaje completado)
     * 
     * @return int
     */
    public function getProgreso()
    {
        $estado = $this->attributes['estado'] ?? '';
        
        $progresos = [
            'pendiente_revision' => 25,
            'pendiente_autorizacion' => 50,
            'autorizado' => 100,
            'rechazado' => 100,
        ];
        
        return $progresos[$estado] ?? 0;
    }

    /**
     * Obtiene tiempo transcurrido desde el inicio
     * 
     * @return int Días transcurridos
     */
    public function diasDesdeInicio()
    {
        if (!isset($this->attributes['fecha_inicio'])) {
            return 0;
        }

        $inicio = new \DateTime($this->attributes['fecha_inicio']);
        $ahora = new \DateTime();
        $diff = $inicio->diff($ahora);
        
        return $diff->days;
    }

    /**
     * Obtiene el tiempo de completado
     * 
     * @return int|null Días para completar o null si no está completado
     */
    public function diasParaCompletar()
    {
        if (!$this->estaCompleto() || !isset($this->attributes['fecha_inicio']) || !isset($this->attributes['fecha_completado'])) {
            return null;
        }

        $inicio = new \DateTime($this->attributes['fecha_inicio']);
        $fin = new \DateTime($this->attributes['fecha_completado']);
        $diff = $inicio->diff($fin);
        
        return $diff->days;
    }

    /**
     * Cancela el flujo
     * 
     * @param int $id
     * @param int $usuarioId
     * @param string $motivo
     * @return bool
     */
    public static function cancelar($id, $usuarioId, $motivo)
    {
        try {
            $flujo = self::find($id);
            if (!$flujo) {
                return false;
            }

            self::updateById($id, [
                'estado' => self::ESTADO_RECHAZADO,
                'fecha_completado' => date('Y-m-d H:i:s'),
            ]);

            $ordenCompraId = is_object($flujo) ? $flujo->requisicion_id : $flujo['requisicion_id'];
            HistorialRequisicion::registrar(
                $ordenCompraId,
                'cancelacion',
                "Flujo cancelado: {$motivo}",
                $usuarioId
            );

            return true;
        } catch (\Exception $e) {
            error_log("Error cancelando flujo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reinicia el flujo (útil para correcciones)
     * 
     * @param int $id
     * @param int $usuarioId
     * @return bool
     */
    public static function reiniciar($id, $usuarioId)
    {
        try {
            $flujo = self::find($id);
            if (!$flujo) {
                return false;
            }

            self::updateById($id, [
                'estado' => self::ESTADO_PENDIENTE_REVISION,
                'fecha_completado' => null,
            ]);

            $ordenCompraId = is_object($flujo) ? $flujo->requisicion_id : $flujo['requisicion_id'];
            HistorialRequisicion::registrar(
                $ordenCompraId,
                'reinicio',
                "Flujo reiniciado",
                $usuarioId
            );

            return true;
        } catch (\Exception $e) {
            error_log("Error reiniciando flujo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crea autorizaciones especiales después de aprobar la revisión.
     *
     * Fix A: los registros de centro se crean SIEMPRE aquí, aunque haya especiales
     * pendientes, para que el modal pueda guardar la asignación manual inmediatamente.
     * Los guards en AutorizacionService evitan duplicación posterior.
     *
     * @param int   $flujoId
     * @param array $flujo
     * @param array $asignaciones  Mapa [centro_costo_id => email] para centros manuales.
     */
    private static function crearAutorizacionesEspeciales($flujoId, $flujo, array $asignaciones = [])
    {
        error_log("=== CREANDO AUTORIZACIONES ESPECIALES ===");
        error_log("Flujo ID: $flujoId");

        $requiereEspecialPago   = is_object($flujo) ? $flujo->requiere_autorizacion_especial_pago   : $flujo['requiere_autorizacion_especial_pago'];
        $requiereEspecialCuenta = is_object($flujo) ? $flujo->requiere_autorizacion_especial_cuenta : $flujo['requiere_autorizacion_especial_cuenta'];
        $ordenCompraId          = is_object($flujo) ? $flujo->requisicion_id                        : $flujo['requisicion_id'];

        error_log("Requiere autorización especial de pago: " . ($requiereEspecialPago ? 'SÍ' : 'NO'));
        error_log("Requiere autorización especial de cuenta: " . ($requiereEspecialCuenta ? 'SÍ' : 'NO'));

        $pdo = self::getConnection();

        if ($requiereEspecialPago) {
            self::crearAutorizacionEspecialPago($flujoId, $ordenCompraId, $pdo);
        }

        if ($requiereEspecialCuenta) {
            self::crearAutorizacionEspecialCuentas($flujoId, $ordenCompraId, $pdo);
        }

        // Fix A: crear registros de centro siempre, usando asignaciones del revisor si las hay.
        self::centrosRepo()->createFromDistribucion((int) $ordenCompraId, $asignaciones);

        error_log("✅ Autorizaciones especiales y de centro creadas exitosamente");
    }

    /**
     * Crea autorización especial por método de pago
     */
    private static function crearAutorizacionEspecialPago($flujoId, $ordenCompraId, $pdo)
    {
        // Obtener información de la requisición para saber la forma de pago
        $stmt = $pdo->prepare("SELECT forma_pago FROM requisiciones WHERE id = ?");
        $stmt->execute([$ordenCompraId]);
        $orden = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$orden) {
            error_log("⚠️ No se encontró la requisición $ordenCompraId para autorización de pago");
            return;
        }

        // Buscar autorizadores principales para esa forma de pago
        $stmt = $pdo->prepare("
            SELECT autorizador_email
            FROM autorizadores_metodos_pago
            WHERE metodo_pago = ?
              AND activo = 1
        ");
        $stmt->execute([$orden['forma_pago']]);
        $autorizadoresPrincipales = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        // Buscar autorizadores de respaldo activos (solo para forma de pago podemos usar respaldos generales)
        $autorizadoresRespaldo = [];
        if (!empty($autorizadoresPrincipales)) {
            $placeholders = str_repeat('?,', count($autorizadoresPrincipales) - 1) . '?';
            $stmt = $pdo->prepare("
                SELECT DISTINCT autorizador_respaldo_email 
                FROM autorizador_respaldo 
                WHERE autorizador_principal_email IN ($placeholders)
                AND estado = 'activo' 
                AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())
                AND fecha_inicio <= CURDATE()
            ");
            $stmt->execute($autorizadoresPrincipales);
            $autorizadoresRespaldo = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        }

        // Combinar autorizadores principales + respaldos
        $todosAutorizadores = array_merge($autorizadoresPrincipales, $autorizadoresRespaldo);
        $todosAutorizadores = array_unique($todosAutorizadores); // Eliminar duplicados

        if (empty($todosAutorizadores)) {
            // Verificar si hay autorizadores configurados pero inactivos
            $stmtInactivos = $pdo->prepare(
                "SELECT COUNT(*) FROM autorizadores_metodos_pago WHERE metodo_pago = ? AND activo = 0"
            );
            $stmtInactivos->execute([$orden['forma_pago']]);
            $hayInactivos = (int)$stmtInactivos->fetchColumn() > 0;

            if ($hayInactivos) {
                throw new \Exception(
                    "El autorizador especial asignado actualmente se encuentra inactivo. " .
                    "Favor de validar la configuración antes de continuar."
                );
            }

            // Sin autorizadores configurados del todo — no aplica autorización especial
            return;
        }

        // Crear autorizaciones en la nueva tabla 'autorizaciones'
        $stmt = $pdo->prepare("
            INSERT INTO autorizaciones 
            (requisicion_id, tipo, autorizador_email, estado, metadata, created_at)
            VALUES (?, 'forma_pago', ?, 'pendiente', ?, NOW())
        ");

        foreach ($todosAutorizadores as $autorizador) {
            $esRespaldo = in_array($autorizador, $autorizadoresRespaldo);
            $metadata = json_encode([
                'forma_pago' => $orden['forma_pago'],
                'tipo_especial' => 'metodo_pago',
                'es_respaldo' => $esRespaldo,
                'autorizador_principal' => $esRespaldo ? 'respaldo_activo' : 'principal'
            ]);

            $stmt->execute([$ordenCompraId, $autorizador, $metadata]);
            $tipo = $esRespaldo ? 'RESPALDO' : 'PRINCIPAL';
            error_log("✅ Autorización de forma de pago creada para: $autorizador ($tipo)");
        }
    }

    /**
     * Crea autorizaciones especiales por cuenta contable
     */
    private static function crearAutorizacionEspecialCuentas($flujoId, $ordenCompraId, $pdo)
    {
        // Obtener cuentas contables que requieren autorización especial para esta orden
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                dg.cuenta_contable_id,
                cc.descripcion as cuenta_nombre,
                acc.autorizador_email
            FROM distribucion_gasto dg
            JOIN cuenta_contable cc ON dg.cuenta_contable_id = cc.id
            JOIN autorizadores_cuentas_contables acc ON cc.id = acc.cuenta_contable_id
            WHERE dg.requisicion_id = ?
              AND acc.activo = 1
        ");
        $stmt->execute([$ordenCompraId]);
        $cuentasEspeciales = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($cuentasEspeciales)) {
            // Verificar si hay autorizadores configurados pero inactivos
            $stmtInactivos = $pdo->prepare("
                SELECT COUNT(*) FROM distribucion_gasto dg
                JOIN autorizadores_cuentas_contables acc ON dg.cuenta_contable_id = acc.cuenta_contable_id
                WHERE dg.requisicion_id = ? AND acc.activo = 0
            ");
            $stmtInactivos->execute([$ordenCompraId]);
            $hayInactivos = (int)$stmtInactivos->fetchColumn() > 0;

            if ($hayInactivos) {
                throw new \Exception(
                    "El autorizador especial asignado actualmente se encuentra inactivo. " .
                    "Favor de validar la configuración antes de continuar."
                );
            }

            // Sin autorizadores configurados del todo — no aplica autorización especial
            return;
        }

        // Agrupar por cuenta contable y obtener todos los autorizadores (principales + respaldo)
        $cuentasConAutorizadores = [];
        foreach ($cuentasEspeciales as $cuenta) {
            $cuentaId = $cuenta['cuenta_contable_id'];
            
            if (!isset($cuentasConAutorizadores[$cuentaId])) {
                $cuentasConAutorizadores[$cuentaId] = [
                    'cuenta_nombre' => $cuenta['cuenta_nombre'],
                    'autorizadores_principales' => [],
                    'autorizadores_respaldo' => []
                ];
            }
            
            $cuentasConAutorizadores[$cuentaId]['autorizadores_principales'][] = $cuenta['autorizador_email'];
        }

        // Para cada cuenta, buscar autorizadores de respaldo
        foreach ($cuentasConAutorizadores as $cuentaId => &$cuentaData) {
            $autorizadoresPrincipales = $cuentaData['autorizadores_principales'];
            
            if (!empty($autorizadoresPrincipales)) {
                $placeholders = str_repeat('?,', count($autorizadoresPrincipales) - 1) . '?';
                $stmt = $pdo->prepare("
                    SELECT DISTINCT autorizador_respaldo_email 
                    FROM autorizador_respaldo 
                    WHERE autorizador_principal_email IN ($placeholders)
                    AND estado = 'activo' 
                    AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())
                    AND fecha_inicio <= CURDATE()
                ");
                $stmt->execute($autorizadoresPrincipales);
                $cuentaData['autorizadores_respaldo'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            }
        }

        // Crear autorizaciones en la nueva tabla 'autorizaciones'
        $stmt = $pdo->prepare("
            INSERT INTO autorizaciones 
            (requisicion_id, tipo, cuenta_contable_id, autorizador_email, estado, metadata, created_at)
            VALUES (?, 'cuenta_contable', ?, ?, 'pendiente', ?, NOW())
        ");

        foreach ($cuentasConAutorizadores as $cuentaId => $cuentaData) {
            // Combinar autorizadores principales + respaldo
            $todosAutorizadores = array_merge(
                $cuentaData['autorizadores_principales'], 
                $cuentaData['autorizadores_respaldo']
            );
            $todosAutorizadores = array_unique($todosAutorizadores);

            foreach ($todosAutorizadores as $autorizador) {
                $esRespaldo = in_array($autorizador, $cuentaData['autorizadores_respaldo']);
                $metadata = json_encode([
                    'cuenta_nombre' => $cuentaData['cuenta_nombre'],
                    'cuenta_contable_id' => $cuentaId,
                    'tipo_especial' => 'cuenta_contable',
                    'es_respaldo' => $esRespaldo,
                    'autorizador_principal' => $esRespaldo ? 'respaldo_activo' : 'principal'
                ]);

                $stmt->execute([
                    $ordenCompraId,
                    $cuentaId,
                    $autorizador,
                    $metadata
                ]);
                
                $tipo = $esRespaldo ? 'RESPALDO' : 'PRINCIPAL';
                error_log("✅ Autorización de cuenta contable creada para: $autorizador ($tipo) (Cuenta: " . $cuentaData['cuenta_nombre'] . ")");
            }
        }
    }
}
