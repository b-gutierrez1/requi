<?php
/**
 * Modelo Usuario
 * 
 * Representa a un usuario del sistema autenticado por Azure AD
 * 
 * @package RequisicionesMVC\Models
 * @version 2.0
 */

namespace App\Models;

class Usuario extends Model
{
    protected static $table = 'usuarios';
    protected static $primaryKey = 'id';
    protected static $timestamps = false; // La tabla usa 'fecha_creacion' en lugar de 'created_at'

    protected static $fillable = [
        'id',
        'nombre',
        'email',
        'password',
        'rol',
        'azure_id',
        'azure_email',
        'azure_display_name',
        'azure_first_name',
        'azure_last_name',
        'azure_job_title',
        'azure_department',
        'azure_groups',
        'is_admin',
        'is_revisor',
        'is_autorizador',
        'activo',
        'last_login',
        'username',
        'password_hash',
        'nombre_completo',
        'fecha_creacion',
        'fecha_ultimo_acceso',
        'intentos_fallidos',
        'bloqueado_hasta'
    ];

    protected static $guarded = [];

    /**
     * Buscar usuario por email de Azure
     * 
     * @param string $email
     * @return self|null
     */
    public static function findByEmail($email)
    {
        return self::first(['azure_email' => $email]);
    }

    /**
     * Buscar usuario por Azure ID
     * 
     * @param string $azureId
     * @return self|null
     */
    public static function findByAzureId($azureId)
    {
        return self::first(['azure_id' => $azureId]);
    }

    /**
     * Obtener todas las requisiciones del usuario
     * 
     * @return array
     */
    public function requisiciones()
    {
        return OrdenCompra::where(['usuario_id' => $this->id]);
    }

    /**
     * Obtener requisiciones pendientes de revisión (si es revisor)
     * 
     * @return array
     */
    public function requisicionesPendientesRevision()
    {
        if (!$this->is_revisor) {
            return [];
        }

        $sql = "
            SELECT oc.* 
            FROM orden_compra oc
            INNER JOIN autorizacion_flujo af ON oc.id = af.orden_compra_id
            WHERE af.estado = 'pendiente_revision'
            ORDER BY oc.fecha DESC
        ";

        $stmt = self::query($sql);
        $results = [];
        
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $results[] = new OrdenCompra($row);
        }
        
        return $results;
    }

    /**
     * Obtener requisiciones pendientes de autorización (si es autorizador)
     * 
     * @return array
     */
    public function requisicionesPendientesAutorizacion()
    {
        $sql = "
            SELECT DISTINCT oc.* 
            FROM orden_compra oc
            INNER JOIN autorizacion_flujo af ON oc.id = af.orden_compra_id
            INNER JOIN autorizacion_centro_costo acc ON af.id = acc.autorizacion_flujo_id
            WHERE af.estado = 'pendiente_autorizacion'
            AND acc.autorizador_email = :email
            AND acc.fecha_autorizacion IS NULL
            ORDER BY oc.fecha DESC
        ";

        $stmt = self::query($sql, ['email' => $this->azure_email]);
        $results = [];
        
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $results[] = new OrdenCompra($row);
        }
        
        return $results;
    }

    /**
     * Verificar si el usuario es administrador
     * 
     * @return bool
     */
    public function isAdmin()
    {
        return (bool) $this->is_admin;
    }

    /**
     * Verificar si el usuario es revisor
     * 
     * @return bool
     */
    public function isRevisor()
    {
        return (bool) $this->is_revisor;
    }

    /**
     * Verificar si el usuario es autorizador
     * 
     * @return bool
     */
    public function isAutorizador()
    {
        return (bool) $this->is_autorizador;
    }

    /**
     * Verificar si el usuario está activo
     * 
     * @return bool
     */
    public function isActivo()
    {
        return (bool) $this->activo;
    }

    /**
     * Actualizar último login
     * 
     * @return bool
     */
    public function updateLastLogin()
    {
        $this->last_login = date('Y-m-d H:i:s');
        return $this->save();
    }

    /**
     * Obtener nombre completo
     * 
     * @return string
     */
    public function getNombreCompleto()
    {
        if ($this->azure_first_name && $this->azure_last_name) {
            return $this->azure_first_name . ' ' . $this->azure_last_name;
        }
        
        return $this->azure_display_name ?? $this->azure_email;
    }

    /**
     * Obtener iniciales
     * 
     * @return string
     */
    public function getIniciales()
    {
        $nombre = $this->getNombreCompleto();
        $palabras = explode(' ', $nombre);
        $iniciales = '';
        
        foreach ($palabras as $palabra) {
            if (!empty($palabra)) {
                $iniciales .= strtoupper(substr($palabra, 0, 1));
            }
            if (strlen($iniciales) >= 2) break;
        }
        
        return $iniciales ?: substr($nombre, 0, 2);
    }

    /**
     * Obtener rol principal del usuario
     * 
     * @return string
     */
    public function getRol()
    {
        if ($this->isAdmin()) return 'admin';
        if ($this->isRevisor()) return 'revisor';
        if ($this->isAutorizador()) return 'autorizador';
        return 'usuario';
    }

    /**
     * Sincronizar datos desde Azure AD
     * 
     * @param array $azureData
     * @return bool
     */
    public function syncFromAzure(array $azureData)
    {
        $mapping = [
            'id' => 'azure_id',
            'mail' => 'azure_email',
            'displayName' => 'azure_display_name',
            'givenName' => 'azure_first_name',
            'surname' => 'azure_last_name',
            'jobTitle' => 'azure_job_title',
            'department' => 'azure_department',
        ];

        foreach ($mapping as $azureKey => $dbKey) {
            if (isset($azureData[$azureKey])) {
                $this->setAttribute($dbKey, $azureData[$azureKey]);
            }
        }

        return $this->save();
    }

    /**
     * Contar total de usuarios
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
     * Contar usuarios activos
     * 
     * @return int
     */
    public static function countActivos()
    {
        $stmt = self::query("SELECT COUNT(*) as total FROM " . self::getTable() . " WHERE activo = 1");
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Obtener usuarios administradores
     * 
     * @return array
     */
    public static function admins()
    {
        return self::where(['is_admin' => 1]);
    }

    /**
     * Obtener usuarios revisores
     * 
     * @return array
     */
    public static function revisores()
    {
        return self::where(['is_revisor' => 1]);
    }

    /**
     * Obtener usuarios activos
     * 
     * @return array
     */
    public static function activos()
    {
        return self::where(['activo' => 1]);
    }

    /**
     * Obtener usuarios con paginación
     * 
     * @param int $page Página actual
     * @param int $limit Límite por página
     * @return array
     */
    public static function paginate($page = 1, $limit = 20)
    {
        $offset = ($page - 1) * $limit;
        
        $stmt = self::query("SELECT * FROM " . self::getTable() . " 
                            ORDER BY id DESC 
                            LIMIT :limit OFFSET :offset", 
                            ['limit' => $limit, 'offset' => $offset]);
        
        $results = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $results[] = new self($row);
        }
        
        return $results;
    }

    /**
     * Busca un usuario por email
     * 
     * @param string $email Email del usuario
     * @return array|null Usuario encontrado o null
     */
    public static function porEmail($email)
    {
        try {
            $db = self::getDB();
            $stmt = $db->prepare("
                SELECT * 
                FROM usuarios 
                WHERE azure_email = ? 
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return $result ?: null;
        } catch (\Exception $e) {
            error_log("Error buscando usuario por email: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener usuarios recientes
     * 
     * @param int $limite Número máximo de usuarios a retornar (default: 5)
     * @return array Array de objetos Usuario
     */
    public static function getRecientes($limite = 5)
    {
        try {
            $db = self::getConnection();
            $stmt = $db->prepare("SELECT * FROM usuarios ORDER BY id DESC LIMIT ?");
            $stmt->execute([(int)$limite]);
            
            $results = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $results[] = new self($row);
            }
            
            return $results;
        } catch (\Exception $e) {
            error_log("Error obteniendo usuarios recientes: " . $e->getMessage());
            return [];
        }
    }

}
