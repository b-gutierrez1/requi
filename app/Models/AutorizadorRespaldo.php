<?php
/**
 * Modelo AutorizadorRespaldo
 *
 * Gestiona autorizadores de respaldo temporales para centros de costo.
 * Se activan automáticamente por fechas cuando el autorizador principal no está disponible.
 *
 * ESTRUCTURA:
 * - autorizador_respaldo: Datos del respaldo (emails, fechas, motivo)
 * - autorizador_respaldo_centro: Relación N:M con centros de costo
 *
 * @package RequisicionesMVC\Models
 * @version 3.0
 */

namespace App\Models;

class AutorizadorRespaldo extends Model
{
    protected static $table = 'autorizador_respaldo';
    protected static $primaryKey = 'id';
    protected static $timestamps = false;

    protected static $fillable = [
        'autorizador_principal_email',
        'autorizador_respaldo_email',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'motivo',
        'fecha_creacion',
        'creado_por',
    ];

    protected static $guarded = ['id'];

    /**
     * Verifica si existe la tabla de relación (nueva estructura)
     *
     * @return bool
     */
    private static function tieneNuevaEstructura()
    {
        static $existe = null;
        if ($existe === null) {
            try {
                $sql = "SHOW TABLES LIKE 'autorizador_respaldo_centro'";
                $stmt = self::getConnection()->prepare($sql);
                $stmt->execute();
                $existe = $stmt->fetch() !== false;
            } catch (\Exception $e) {
                $existe = false;
            }
        }
        return $existe;
    }

    /**
     * Obtiene los centros de costo de este respaldo
     *
     * @return array
     */
    public function centrosCosto()
    {
        if (!isset($this->attributes['id'])) {
            return [];
        }

        if (self::tieneNuevaEstructura()) {
            $sql = "SELECT
                        cc.*,
                        arc.fecha_asignacion
                    FROM autorizador_respaldo_centro arc
                    INNER JOIN centro_de_costo cc ON cc.id = arc.centro_costo_id
                    WHERE arc.respaldo_id = ?
                    ORDER BY cc.nombre ASC";

            $stmt = self::getConnection()->prepare($sql);
            $stmt->execute([$this->attributes['id']]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        // Fallback: estructura antigua con centro_costo_id directo
        if (isset($this->attributes['centro_costo_id'])) {
            $centro = CentroCosto::find($this->attributes['centro_costo_id']);
            return $centro ? [$centro] : [];
        }

        return [];
    }

    /**
     * Obtiene respaldos por centro de costo
     *
     * @param int $centroCostoId
     * @return array
     */
    public static function porCentroCosto($centroCostoId)
    {
        if (self::tieneNuevaEstructura()) {
            $sql = "SELECT ar.*
                    FROM autorizador_respaldo ar
                    INNER JOIN autorizador_respaldo_centro arc ON arc.respaldo_id = ar.id
                    WHERE arc.centro_costo_id = ?
                    ORDER BY ar.fecha_inicio DESC";
        } else {
            $sql = "SELECT * FROM autorizador_respaldo
                    WHERE centro_costo_id = ?
                    ORDER BY fecha_inicio DESC";
        }

        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$centroCostoId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el respaldo activo actual de un centro de costo
     * CRÍTICO: Este método es usado por AutorizacionCentroRepository
     *
     * @param int $centroCostoId
     * @return array|null
     */
    public static function activoPorCentro($centroCostoId)
    {
        if (self::tieneNuevaEstructura()) {
            $sql = "SELECT ar.*, arc.centro_costo_id
                    FROM autorizador_respaldo ar
                    INNER JOIN autorizador_respaldo_centro arc ON arc.respaldo_id = ar.id
                    WHERE arc.centro_costo_id = ?
                    AND ar.estado = 'activo'
                    AND ar.fecha_inicio <= CURDATE()
                    AND (ar.fecha_fin IS NULL OR ar.fecha_fin >= CURDATE())
                    LIMIT 1";
        } else {
            $sql = "SELECT * FROM autorizador_respaldo
                    WHERE centro_costo_id = ?
                    AND estado = 'activo'
                    AND fecha_inicio <= CURDATE()
                    AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())
                    LIMIT 1";
        }

        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$centroCostoId]);

        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Obtiene todos los respaldos activos (agrupados, sin duplicados)
     *
     * @return array
     */
    public static function todosActivos()
    {
        if (self::tieneNuevaEstructura()) {
            $sql = "SELECT ar.*,
                        GROUP_CONCAT(DISTINCT cc.nombre ORDER BY cc.nombre SEPARATOR ', ') as centros_nombres,
                        GROUP_CONCAT(DISTINCT cc.id ORDER BY cc.id SEPARATOR ',') as centros_ids,
                        COUNT(DISTINCT arc.centro_costo_id) as total_centros
                    FROM autorizador_respaldo ar
                    LEFT JOIN autorizador_respaldo_centro arc ON arc.respaldo_id = ar.id
                    LEFT JOIN centro_de_costo cc ON cc.id = arc.centro_costo_id
                    WHERE ar.estado = 'activo'
                    AND ar.fecha_inicio <= CURDATE()
                    AND (ar.fecha_fin IS NULL OR ar.fecha_fin >= CURDATE())
                    GROUP BY ar.id
                    ORDER BY ar.fecha_inicio ASC";
        } else {
            $sql = "SELECT ar.*, cc.nombre as centro_nombre
                    FROM autorizador_respaldo ar
                    INNER JOIN centro_de_costo cc ON ar.centro_costo_id = cc.id
                    WHERE ar.estado = 'activo'
                    AND ar.fecha_inicio <= CURDATE()
                    AND (ar.fecha_fin IS NULL OR ar.fecha_fin >= CURDATE())
                    ORDER BY ar.fecha_inicio ASC";
        }

        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todos los respaldos (agrupados, sin duplicados) para el listado admin
     *
     * @param string|null $filtroEstado Filtrar por estado (activo, vencido, proximo)
     * @return array
     */
    public static function todosAgrupados($filtroEstado = null)
    {
        if (self::tieneNuevaEstructura()) {
            $sql = "SELECT ar.*,
                        GROUP_CONCAT(DISTINCT cc.nombre ORDER BY cc.nombre SEPARATOR ', ') as centros_nombres,
                        GROUP_CONCAT(DISTINCT cc.id ORDER BY cc.id SEPARATOR ',') as centros_ids,
                        COUNT(DISTINCT arc.centro_costo_id) as total_centros
                    FROM autorizador_respaldo ar
                    LEFT JOIN autorizador_respaldo_centro arc ON arc.respaldo_id = ar.id
                    LEFT JOIN centro_de_costo cc ON cc.id = arc.centro_costo_id
                    GROUP BY ar.id
                    ORDER BY ar.fecha_inicio DESC, ar.id DESC";
        } else {
            // Estructura antigua: agrupar por campos únicos
            $sql = "SELECT
                        MIN(ar.id) as id,
                        ar.autorizador_principal_email,
                        ar.autorizador_respaldo_email,
                        ar.fecha_inicio,
                        ar.fecha_fin,
                        ar.estado,
                        ar.motivo,
                        ar.fecha_creacion,
                        ar.creado_por,
                        GROUP_CONCAT(DISTINCT cc.nombre ORDER BY cc.nombre SEPARATOR ', ') as centros_nombres,
                        GROUP_CONCAT(DISTINCT cc.id ORDER BY cc.id SEPARATOR ',') as centros_ids,
                        COUNT(DISTINCT ar.centro_costo_id) as total_centros
                    FROM autorizador_respaldo ar
                    LEFT JOIN centro_de_costo cc ON ar.centro_costo_id = cc.id
                    GROUP BY
                        ar.autorizador_principal_email,
                        ar.autorizador_respaldo_email,
                        ar.fecha_inicio,
                        ar.fecha_fin,
                        ar.estado,
                        ar.motivo,
                        ar.creado_por
                    ORDER BY ar.fecha_inicio DESC";
        }

        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();

        $respaldos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Agregar nombres de autorizadores
        foreach ($respaldos as &$respaldo) {
            // Nombre del respaldo
            if (!empty($respaldo['autorizador_respaldo_email'])) {
                $sqlNombre = "SELECT nombre FROM autorizadores WHERE email = ? LIMIT 1";
                $stmtNombre = self::getConnection()->prepare($sqlNombre);
                $stmtNombre->execute([$respaldo['autorizador_respaldo_email']]);
                $result = $stmtNombre->fetch(\PDO::FETCH_ASSOC);
                $respaldo['autorizador_respaldo_nombre'] = $result['nombre'] ?? null;
            }

            // Nombre del principal
            if (!empty($respaldo['autorizador_principal_email'])) {
                $sqlNombre = "SELECT nombre FROM autorizadores WHERE email = ? LIMIT 1";
                $stmtNombre = self::getConnection()->prepare($sqlNombre);
                $stmtNombre->execute([$respaldo['autorizador_principal_email']]);
                $result = $stmtNombre->fetch(\PDO::FETCH_ASSOC);
                $respaldo['autorizador_principal_nombre'] = $result['nombre'] ?? null;
            }
        }
        unset($respaldo);

        return $respaldos;
    }

    /**
     * Obtiene respaldos por email del autorizador de respaldo
     *
     * @param string $email
     * @return array
     */
    public static function porEmailRespaldo($email)
    {
        if (self::tieneNuevaEstructura()) {
            $sql = "SELECT ar.*,
                        cc.nombre as centro_nombre,
                        cc.codigo as centro_codigo,
                        arc.centro_costo_id
                    FROM autorizador_respaldo ar
                    INNER JOIN autorizador_respaldo_centro arc ON arc.respaldo_id = ar.id
                    INNER JOIN centro_de_costo cc ON cc.id = arc.centro_costo_id
                    WHERE ar.autorizador_respaldo_email = ?
                    AND ar.estado = 'activo'
                    AND ar.fecha_inicio <= CURDATE()
                    AND (ar.fecha_fin IS NULL OR ar.fecha_fin >= CURDATE())
                    ORDER BY cc.nombre ASC";
        } else {
            $sql = "SELECT ar.*, cc.nombre as centro_nombre, cc.codigo as centro_codigo
                    FROM autorizador_respaldo ar
                    INNER JOIN centro_de_costo cc ON ar.centro_costo_id = cc.id
                    WHERE ar.autorizador_respaldo_email = ?
                    AND ar.estado = 'activo'
                    AND ar.fecha_inicio <= CURDATE()
                    AND (ar.fecha_fin IS NULL OR ar.fecha_fin >= CURDATE())
                    ORDER BY cc.nombre ASC";
        }

        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$email]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Verifica si hay respaldo activo para un centro en una fecha
     *
     * @param int $centroCostoId
     * @param string $fecha
     * @return bool
     */
    public static function hayRespaldoEnFecha($centroCostoId, $fecha = null)
    {
        if (!$fecha) {
            $fecha = date('Y-m-d');
        }

        if (self::tieneNuevaEstructura()) {
            $sql = "SELECT COUNT(*) as total
                    FROM autorizador_respaldo ar
                    INNER JOIN autorizador_respaldo_centro arc ON arc.respaldo_id = ar.id
                    WHERE arc.centro_costo_id = ?
                    AND ar.estado = 'activo'
                    AND ar.fecha_inicio <= ?
                    AND (ar.fecha_fin IS NULL OR ar.fecha_fin >= ?)";
        } else {
            $sql = "SELECT COUNT(*) as total
                    FROM autorizador_respaldo
                    WHERE centro_costo_id = ?
                    AND estado = 'activo'
                    AND fecha_inicio <= ?
                    AND (fecha_fin IS NULL OR fecha_fin >= ?)";
        }

        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$centroCostoId, $fecha, $fecha]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return ($result['total'] ?? 0) > 0;
    }

    /**
     * Verifica si ya existe un respaldo activo/programado con fechas solapadas
     * para la misma combinación de autorizador principal + centro de costo.
     *
     * Solapamiento ocurre cuando los rangos [A_inicio, A_fin] y [B_inicio, B_fin]
     * se intersecan: A_inicio <= B_fin AND (A_fin IS NULL OR A_fin >= B_inicio)
     *
     * @param string $principalEmail
     * @param string $fechaInicio      Inicio del nuevo rango (Y-m-d)
     * @param string|null $fechaFin    Fin del nuevo rango (Y-m-d) o null = abierto
     * @param array $centrosCostoIds   Centros de costo a verificar
     * @param int|null $excluirId      ID de respaldo a excluir (para edición)
     * @return array  Lista de centro_costo_id que ya tienen un respaldo solapado
     */
    public static function hayDuplicadoActivo(
        string $principalEmail,
        string $fechaInicio,
        ?string $fechaFin,
        array $centrosCostoIds,
        ?int $excluirId = null
    ): array {
        if (empty($centrosCostoIds)) {
            return [];
        }

        $pdo = self::getConnection();
        $placeholders = implode(',', array_fill(0, count($centrosCostoIds), '?'));

        // Condición de solapamiento:
        //   nuevo_inicio <= existente_fin (o existente_fin es NULL)
        //   AND (nuevo_fin IS NULL OR nuevo_fin >= existente_inicio)
        if (self::tieneNuevaEstructura()) {
            $sql = "SELECT arc.centro_costo_id
                    FROM autorizador_respaldo ar
                    INNER JOIN autorizador_respaldo_centro arc ON arc.respaldo_id = ar.id
                    WHERE ar.autorizador_principal_email = ?
                    AND ar.estado IN ('activo', 'programado')
                    AND arc.centro_costo_id IN ($placeholders)
                    AND ar.fecha_inicio <= ?
                    AND (ar.fecha_fin IS NULL OR ar.fecha_fin >= ?)";
        } else {
            $sql = "SELECT centro_costo_id
                    FROM autorizador_respaldo
                    WHERE autorizador_principal_email = ?
                    AND estado IN ('activo', 'programado')
                    AND centro_costo_id IN ($placeholders)
                    AND fecha_inicio <= ?
                    AND (fecha_fin IS NULL OR fecha_fin >= ?)";
        }

        // nuevo_inicio <= existente_fin  →  existente_fin >= nuevo_inicio
        // nuevo_fin >= existente_inicio  →  existente_inicio <= nuevo_fin
        // When new fechaFin is null (open-ended), it overlaps with any existing start
        $overlapEnd = $fechaFin ?? '9999-12-31';

        $params = array_merge(
            [$principalEmail],
            $centrosCostoIds,
            [$overlapEnd, $fechaInicio]
        );

        if ($excluirId !== null) {
            $sql .= " AND ar.id != ?";
            $params[] = $excluirId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'centro_costo_id');
    }

    /**
     * Crea un respaldo con múltiples centros de costo
     *
     * @param array $data Datos del respaldo
     * @param array $centrosCostoIds IDs de centros de costo
     * @return int|false ID del respaldo creado o false si falla
     * @throws \RuntimeException si hay respaldos activos solapados para algún centro
     */
    public static function crearConCentros(array $data, array $centrosCostoIds)
    {
        $pdo = self::getConnection();

        // Validar duplicados antes de iniciar la transacción
        $solapados = self::hayDuplicadoActivo(
            $data['autorizador_principal_email'],
            $data['fecha_inicio'],
            $data['fecha_fin'] ?? null,
            $centrosCostoIds
        );

        if (!empty($solapados)) {
            throw new \RuntimeException(
                'Ya existe un respaldo activo o programado con fechas solapadas para los centros de costo: '
                . implode(', ', $solapados)
            );
        }

        try {
            $pdo->beginTransaction();

            // Crear el respaldo principal
            $sql = "INSERT INTO autorizador_respaldo
                    (autorizador_principal_email, autorizador_respaldo_email, fecha_inicio, fecha_fin, estado, motivo, fecha_creacion, creado_por)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['autorizador_principal_email'],
                $data['autorizador_respaldo_email'],
                $data['fecha_inicio'],
                $data['fecha_fin'],
                $data['estado'] ?? 'activo',
                $data['motivo'] ?? null,
                $data['creado_por'] ?? null
            ]);

            $respaldoId = $pdo->lastInsertId();

            // Si existe la tabla de relación, insertar los centros
            if (self::tieneNuevaEstructura() && !empty($centrosCostoIds)) {
                $sqlCentro = "INSERT INTO autorizador_respaldo_centro (respaldo_id, centro_costo_id) VALUES (?, ?)";
                $stmtCentro = $pdo->prepare($sqlCentro);

                foreach ($centrosCostoIds as $centroId) {
                    $stmtCentro->execute([$respaldoId, $centroId]);
                }
            }

            $pdo->commit();
            return $respaldoId;

        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log("Error creando respaldo con centros: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza un respaldo y sus centros de costo
     *
     * @param int $id ID del respaldo
     * @param array $data Datos del respaldo
     * @param array $centrosCostoIds IDs de centros de costo
     * @return bool
     */
    public static function actualizarConCentros($id, array $data, array $centrosCostoIds)
    {
        $pdo = self::getConnection();

        // Validar duplicados excluyendo el registro actual
        $solapados = self::hayDuplicadoActivo(
            $data['autorizador_principal_email'],
            $data['fecha_inicio'],
            $data['fecha_fin'] ?? null,
            $centrosCostoIds,
            (int) $id
        );

        if (!empty($solapados)) {
            throw new \RuntimeException(
                'Ya existe un respaldo activo o programado con fechas solapadas para los centros de costo: '
                . implode(', ', $solapados)
            );
        }

        try {
            $pdo->beginTransaction();

            // Actualizar datos del respaldo
            $sql = "UPDATE autorizador_respaldo SET
                    autorizador_principal_email = ?,
                    autorizador_respaldo_email = ?,
                    fecha_inicio = ?,
                    fecha_fin = ?,
                    estado = ?,
                    motivo = ?
                    WHERE id = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['autorizador_principal_email'],
                $data['autorizador_respaldo_email'],
                $data['fecha_inicio'],
                $data['fecha_fin'],
                $data['estado'] ?? 'activo',
                $data['motivo'] ?? null,
                $id
            ]);

            // Si existe la tabla de relación, actualizar centros
            if (self::tieneNuevaEstructura()) {
                // Eliminar centros anteriores
                $sqlDelete = "DELETE FROM autorizador_respaldo_centro WHERE respaldo_id = ?";
                $stmtDelete = $pdo->prepare($sqlDelete);
                $stmtDelete->execute([$id]);

                // Insertar nuevos centros
                if (!empty($centrosCostoIds)) {
                    $sqlCentro = "INSERT INTO autorizador_respaldo_centro (respaldo_id, centro_costo_id) VALUES (?, ?)";
                    $stmtCentro = $pdo->prepare($sqlCentro);

                    foreach ($centrosCostoIds as $centroId) {
                        $stmtCentro->execute([$id, $centroId]);
                    }
                }
            }

            $pdo->commit();
            return true;

        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log("Error actualizando respaldo con centros: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina un respaldo y sus relaciones
     *
     * @param int $id ID del respaldo
     * @return bool
     */
    public static function eliminarConCentros($id)
    {
        $pdo = self::getConnection();

        try {
            $pdo->beginTransaction();

            // Si existe la tabla de relación, eliminar centros primero
            if (self::tieneNuevaEstructura()) {
                $sqlCentros = "DELETE FROM autorizador_respaldo_centro WHERE respaldo_id = ?";
                $stmtCentros = $pdo->prepare($sqlCentros);
                $stmtCentros->execute([$id]);
            }

            // Eliminar respaldo
            $sql = "DELETE FROM autorizador_respaldo WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);

            $pdo->commit();
            return $stmt->rowCount() > 0;

        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log("Error eliminando respaldo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene los IDs de centros de costo de un respaldo
     *
     * @param int $respaldoId
     * @return array
     */
    public static function obtenerCentrosIds($respaldoId)
    {
        if (self::tieneNuevaEstructura()) {
            $sql = "SELECT centro_costo_id FROM autorizador_respaldo_centro WHERE respaldo_id = ?";
            $stmt = self::getConnection()->prepare($sql);
            $stmt->execute([$respaldoId]);
            return array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'centro_costo_id');
        }

        // Estructura antigua: buscar todos los registros con mismos datos
        $respaldo = self::find($respaldoId);
        if (!$respaldo) {
            return [];
        }

        $data = is_object($respaldo) ? $respaldo->toArray() : $respaldo;

        $sql = "SELECT DISTINCT centro_costo_id FROM autorizador_respaldo
                WHERE autorizador_principal_email = ?
                AND autorizador_respaldo_email = ?
                AND fecha_inicio = ?
                AND fecha_fin = ?";

        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([
            $data['autorizador_principal_email'],
            $data['autorizador_respaldo_email'],
            $data['fecha_inicio'],
            $data['fecha_fin']
        ]);

        return array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'centro_costo_id');
    }

    /**
     * Activa un respaldo
     *
     * @param int $id
     * @return bool
     */
    public static function activar($id)
    {
        return self::update($id, ['estado' => 'activo']);
    }

    /**
     * Desactiva un respaldo
     *
     * @param int $id
     * @return bool
     */
    public static function desactivar($id)
    {
        return self::update($id, ['estado' => 'inactivo']);
    }

    /**
     * Completa un respaldo (cuando termina su período)
     *
     * @param int $id
     * @return bool
     */
    public static function completar($id)
    {
        return self::update($id, ['estado' => 'completado']);
    }

    /**
     * Verifica si el respaldo está vigente hoy
     *
     * @return bool
     */
    public function estaVigente()
    {
        $hoy = date('Y-m-d');

        if ($this->attributes['estado'] !== 'activo') {
            return false;
        }

        $inicio = $this->attributes['fecha_inicio'] ?? null;
        $fin = $this->attributes['fecha_fin'] ?? null;

        if (!$inicio) {
            return false;
        }

        return $hoy >= $inicio && ($fin === null || $hoy <= $fin);
    }

    /**
     * Obtiene días restantes del respaldo
     *
     * @return int
     */
    public function diasRestantes()
    {
        if (!$this->estaVigente()) {
            return 0;
        }

        $hoy = new \DateTime();
        $fin = new \DateTime($this->attributes['fecha_fin']);
        $diff = $hoy->diff($fin);

        return $diff->days;
    }

    /**
     * Actualiza estados de respaldos (ejecutar en cron)
     *
     * @return array Resultado de la actualización
     */
    public static function actualizarEstados()
    {
        $hoy = date('Y-m-d');

        // Desactivar respaldos vencidos
        $sqlVencidos = "UPDATE autorizador_respaldo
                        SET estado = 'completado'
                        WHERE estado = 'activo'
                        AND fecha_fin < ?";

        $stmt = self::getConnection()->prepare($sqlVencidos);
        $stmt->execute([$hoy]);
        $vencidos = $stmt->rowCount();

        // Activar respaldos que inician hoy
        $sqlIniciar = "UPDATE autorizador_respaldo
                       SET estado = 'activo'
                       WHERE estado = 'programado'
                       AND fecha_inicio <= ?
                       AND (fecha_fin IS NULL OR fecha_fin >= ?)";

        $stmt = self::getConnection()->prepare($sqlIniciar);
        $stmt->execute([$hoy, $hoy]);
        $activados = $stmt->rowCount();

        return [
            'vencidos' => $vencidos,
            'activados' => $activados
        ];
    }

    /**
     * Obtiene respaldos próximos a vencer
     *
     * @param int $dias Días de anticipación
     * @return array
     */
    public static function proximosAVencer($dias = 3)
    {
        if (self::tieneNuevaEstructura()) {
            $sql = "SELECT ar.*,
                        GROUP_CONCAT(DISTINCT cc.nombre ORDER BY cc.nombre SEPARATOR ', ') as centros_nombres
                    FROM autorizador_respaldo ar
                    LEFT JOIN autorizador_respaldo_centro arc ON arc.respaldo_id = ar.id
                    LEFT JOIN centro_de_costo cc ON cc.id = arc.centro_costo_id
                    WHERE ar.estado = 'activo'
                    AND ar.fecha_fin BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL ? DAY)
                    GROUP BY ar.id
                    ORDER BY ar.fecha_fin ASC";
        } else {
            $sql = "SELECT ar.*, cc.nombre as centro_nombre
                    FROM autorizador_respaldo ar
                    INNER JOIN centro_de_costo cc ON ar.centro_costo_id = cc.id
                    WHERE ar.estado = 'activo'
                    AND ar.fecha_fin BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL ? DAY)
                    ORDER BY ar.fecha_fin ASC";
        }

        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$dias]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene estadísticas de respaldos
     *
     * @return array
     */
    public static function getEstadisticas()
    {
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'activo' AND fecha_inicio <= CURDATE() AND (fecha_fin IS NULL OR fecha_fin >= CURDATE()) THEN 1 ELSE 0 END) as activos,
                    SUM(CASE WHEN estado = 'programado' OR (estado = 'activo' AND fecha_inicio > CURDATE()) THEN 1 ELSE 0 END) as programados,
                    SUM(CASE WHEN estado = 'completado' OR (fecha_fin IS NOT NULL AND fecha_fin < CURDATE()) THEN 1 ELSE 0 END) as completados,
                    SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos
                FROM autorizador_respaldo";

        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [
            'total' => 0,
            'activos' => 0,
            'programados' => 0,
            'completados' => 0,
            'inactivos' => 0
        ];
    }

    /**
     * Valida que no haya conflictos de fechas para el mismo autorizador principal
     *
     * @param string $principalEmail
     * @param string $fechaInicio
     * @param string $fechaFin
     * @param int $excluirId ID a excluir en la validación
     * @return bool
     */
    public static function hayConflictoFechas($principalEmail, $fechaInicio, $fechaFin, $excluirId = null)
    {
        // Solapamiento estándar entre [fechaInicio, fechaFin] y [existente.inicio, existente.fin]:
        //   existente.inicio <= nuevo.fin_efectivo  AND  (existente.fin IS NULL OR existente.fin >= nuevo.inicio)
        // Donde nuevo.fin_efectivo = fechaFin ?? '9999-12-31' para cubrir respaldos sin fecha de fin.
        $finEfectivo = $fechaFin ?? '9999-12-31';

        $sql = "SELECT COUNT(*) as total
                FROM autorizador_respaldo
                WHERE autorizador_principal_email = ?
                AND estado IN ('activo', 'programado')
                AND fecha_inicio <= ?
                AND (fecha_fin IS NULL OR fecha_fin >= ?)";

        $params = [$principalEmail, $finEfectivo, $fechaInicio];

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
