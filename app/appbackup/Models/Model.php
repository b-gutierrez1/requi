<?php
/**
 * Clase Base Model
 * 
 * Proporciona funcionalidad común para todos los modelos de la aplicación.
 * Incluye métodos para CRUD, validaciones, relaciones y más.
 * 
 * @package RequisicionesMVC\Models
 * @version 2.0
 */

namespace App\Models;

use PDO;
use PDOException;

abstract class Model implements \JsonSerializable
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    protected static $connection;

    /**
     * Nombre de la tabla (debe ser definido en clases hijas)
     * @var string
     */
    protected static $table;

    /**
     * Clave primaria de la tabla
     * @var string
     */
    protected static $primaryKey = 'id';

    /**
     * Timestamps automáticos
     * @var bool
     */
    protected static $timestamps = false;

    /**
     * Campos que se pueden asignar masivamente
     * @var array
     */
    protected static $fillable = [];

    /**
     * Campos que NO se pueden asignar masivamente
     * @var array
     */
    protected static $guarded = ['id'];

    /**
     * Atributos del modelo
     * @var array
     */
    protected $attributes = [];

    /**
     * Atributos originales (para detectar cambios)
     * @var array
     */
    protected $original = [];

    /**
     * Relaciones cargadas
     * @var array
     */
    protected $relations = [];

    /**
     * Constructor
     * 
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
        $this->original = $this->attributes;
    }

    /**
     * Obtiene la conexión a la base de datos
     * 
     * @return PDO
     */
    public static function getConnection()
    {
        if (!self::$connection) {
            $configFile = require __DIR__ . '/../../config/database.php';
            $config = $configFile['connections']['mysql'];
            
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            self::$connection = new PDO(
                $dsn,
                $config["username"],
                $config["password"],
                $config["options"]
            );
            // Habilitar buffer para consultas MySQL y evitar HY000 2014
            if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
                @self::$connection->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            }
        }

        return self::$connection;
    }

    /**
     * Obtiene el nombre de la tabla
     * 
     * @return string
     */
    public static function getTable()
    {
        return static::$table;
    }

    /**
     * Buscar por ID
     * 
     * @param mixed $id
     * @return static|null
     */
    public static function find($id)
    {
        $table = static::getTable();
        $pk = static::$primaryKey;
        
        $sql = "SELECT * FROM {$table} WHERE {$pk} = :id LIMIT 1";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            return null;
        }
        
        $model = new static();
        
        // Asignar todos los atributos directamente, incluyendo el ID
        foreach ($data as $key => $value) {
            $model->setAttribute($key, $value);
        }
        $model->original = $model->attributes;
        
        return $model;
    }

    /**
     * Buscar por ID o lanzar excepción
     * 
     * @param mixed $id
     * @return static
     * @throws \Exception
     */
    public static function findOrFail($id)
    {
        $model = static::find($id);
        
        if (!$model) {
            throw new \Exception("Registro no encontrado en " . static::getTable());
        }
        
        return $model;
    }

    /**
     * Obtener todos los registros
     * 
     * @return array
     */
    public static function all()
    {
        $table = static::getTable();
        $sql = "SELECT * FROM {$table}";
        $stmt = self::getConnection()->query($sql);
        
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $model = new static();
            // Asignar todos los atributos directamente, incluyendo el ID
            foreach ($row as $key => $value) {
                $model->setAttribute($key, $value);
            }
            $model->original = $model->attributes;
            $results[] = $model;
        }
        
        return $results;
    }

    /**
     * Cuenta todos los registros de la tabla
     * 
     * @return int
     */
    public static function count()
    {
        $table = static::getTable();
        $sql = "SELECT COUNT(*) as total FROM {$table}";
        $stmt = self::getConnection()->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Buscar por condiciones
     * 
     * @param array $conditions
     * @return array
     */
    public static function where(array $conditions)
    {
        $table = static::getTable();
        $where = [];
        $params = [];
        
        foreach ($conditions as $key => $value) {
            $where[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }
        
        $sql = "SELECT * FROM {$table} WHERE " . implode(' AND ', $where);
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = new static($row);
        }
        
        return $results;
    }

    /**
     * Buscar el primer registro que coincida
     * 
     * @param array $conditions
     * @return static|null
     */
    public static function first(array $conditions = [])
    {
        $table = static::getTable();
        
        if (empty($conditions)) {
            $sql = "SELECT * FROM {$table} LIMIT 1";
            $stmt = self::getConnection()->query($sql);
        } else {
            $where = [];
            $params = [];
            
            foreach ($conditions as $key => $value) {
                $where[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
            
            $sql = "SELECT * FROM {$table} WHERE " . implode(' AND ', $where) . " LIMIT 1";
            $stmt = self::getConnection()->prepare($sql);
            $stmt->execute($params);
        }
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? new static($data) : null;
    }

    /**
     * Guardar el modelo (insert o update)
     * 
     * @return bool
     */
    public function save()
    {
        if ($this->exists()) {
            return $this->update();
        } else {
            return $this->insert();
        }
    }

    /**
     * Insertar un nuevo registro
     * 
     * @return bool
     */
    protected function insert()
    {
        $table = static::getTable();
        $fillable = $this->getFillableAttributes();
        
        if (static::$timestamps) {
            $fillable['created_at'] = date('Y-m-d H:i:s');
            $fillable['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $columns = array_keys($fillable);
        $values = array_values($fillable);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        
        $stmt = self::getConnection()->prepare($sql);
        $result = $stmt->execute($values);
        
        if ($result) {
            $this->setAttribute(static::$primaryKey, self::getConnection()->lastInsertId());
            $this->original = $this->attributes;
        }
        
        return $result;
    }

    /**
     * Actualizar un registro existente
     * 
     * @return bool
     */
    protected function update()
    {
        $table = static::getTable();
        $pk = static::$primaryKey;
        $fillable = $this->getFillableAttributes();
        
        if (static::$timestamps) {
            $fillable['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $set = [];
        $params = [];
        
        foreach ($fillable as $key => $value) {
            $set[] = "{$key} = ?";
            $params[] = $value;
        }
        
        $params[] = $this->getAttribute($pk);
        
        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s = ?",
            $table,
            implode(', ', $set),
            $pk
        );
        
        $stmt = self::getConnection()->prepare($sql);
        $result = $stmt->execute($params);
        
        if ($result) {
            $this->original = $this->attributes;
        }
        
        return $result;
    }

    /**
     * Eliminar el registro
     * 
     * @return bool
     */
    public function delete()
    {
        $table = static::getTable();
        $pk = static::$primaryKey;
        
        $sql = "DELETE FROM {$table} WHERE {$pk} = ?";
        $stmt = self::getConnection()->prepare($sql);
        
        return $stmt->execute([$this->getAttribute($pk)]);
    }

    /**
     * Eliminar por ID
     * 
     * @param mixed $id
     * @return bool
     */
    public static function destroy($id)
    {
        $model = static::find($id);
        
        if ($model) {
            return $model->delete();
        }
        
        return false;
    }

    /**
     * Crear un nuevo registro
     * 
     * @param array $attributes
     * @return static
     */
    public static function create(array $attributes)
    {
        $model = new static($attributes);
        $model->save();
        
        return $model;
    }

    /**
     * Actualizar un registro por ID
     * 
     * @param mixed $id
     * @param array $attributes
     * @return bool
     */
    public static function updateById($id, array $attributes)
    {
        $model = static::find($id);
        if (!$model) {
            return false;
        }
        
        $model->fill($attributes);
        return $model->save();
    }

    /**
     * Llenar atributos
     * 
     * @param array $attributes
     * @return $this
     */
    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        
        return $this;
    }

    /**
     * Verificar si un campo es fillable
     * 
     * @param string $key
     * @return bool
     */
    protected function isFillable($key)
    {
        // Si está en guarded, no es fillable
        if (in_array($key, static::$guarded)) {
            return false;
        }
        
        // Si fillable está vacío, todos son fillables (excepto guarded)
        if (empty(static::$fillable)) {
            return true;
        }
        
        // Si está en fillable, es fillable
        return in_array($key, static::$fillable);
    }

    /**
     * Obtener atributos fillables
     * 
     * @return array
     */
    protected function getFillableAttributes()
    {
        $fillable = [];
        
        foreach ($this->attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $fillable[$key] = $value;
            }
        }
        
        return $fillable;
    }

    /**
     * Establecer un atributo
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Obtener un atributo
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getAttribute($key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Verificar si el modelo existe en la BD
     * 
     * @return bool
     */
    public function exists()
    {
        $pk = static::$primaryKey;
        return isset($this->attributes[$pk]) && $this->attributes[$pk] !== null;
    }

    /**
     * Verificar si el modelo ha sido modificado
     * 
     * @return bool
     */
    public function isDirty()
    {
        return $this->attributes !== $this->original;
    }

    /**
     * Obtener los cambios del modelo
     * 
     * @return array
     */
    public function getChanges()
    {
        $changes = [];
        
        foreach ($this->attributes as $key => $value) {
            if (!isset($this->original[$key]) || $this->original[$key] !== $value) {
                $changes[$key] = [
                    'old' => $this->original[$key] ?? null,
                    'new' => $value,
                ];
            }
        }
        
        return $changes;
    }

    /**
     * Convertir el modelo a array
     * 
     * @return array
     */
    public function toArray()
    {
        return array_merge($this->attributes, $this->relations);
    }

    /**
     * Convertir el modelo a JSON
     * 
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * Implementación de JsonSerializable
     * 
     * @return array
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * Magic getter
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Magic setter
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Magic isset
     */
    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Recargar el modelo desde la BD
     * 
     * @return $this
     */
    public function refresh()
    {
        $pk = static::$primaryKey;
        $id = $this->getAttribute($pk);
        
        if ($id) {
            $fresh = static::find($id);
            if ($fresh) {
                $this->attributes = $fresh->attributes;
                $this->original = $fresh->attributes;
            }
        }
        
        return $this;
    }

    /**
     * Ejecutar una consulta SQL directa
     * 
     * @param string $sql
     * @param array $params
     * @return \PDOStatement
     */
    public static function query($sql, array $params = [])
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt;
    }

    /**
     * Comenzar una transacción
     * 
     * @return bool
     */
    public static function beginTransaction()
    {
        return self::getConnection()->beginTransaction();
    }

    /**
     * Confirmar una transacción
     * 
     * @return bool
     */
    public static function commit()
    {
        return self::getConnection()->commit();
    }

    /**
     * Revertir una transacción
     * 
     * @return bool
     */
    public static function rollback()
    {
        return self::getConnection()->rollBack();
    }
}
