<?php
/**
 * Modelo ArchivoAdjunto
 * 
 * Gestiona los archivos adjuntos de las requisiciones.
 * Cada requisición puede tener múltiples archivos (cotizaciones, documentos, etc.)
 * 
 * @package RequisicionesMVC\Models
 * @version 2.0
 */

namespace App\Models;

use App\Helpers\Config;

class ArchivoAdjunto extends Model
{
    protected static $table = 'archivos_adjuntos';
    protected static $primaryKey = 'id';
    protected static $timestamps = true;
    protected static $timestampFields = ['fecha_subida'];

    protected static $fillable = [
        'orden_compra_id',
        'nombre_original',
        'nombre_archivo',
        'tipo_archivo',
        'ruta_archivo',
        'tamano',
        'descripcion',
    ];

    protected static $guarded = ['id', 'fecha_subida'];

    /**
     * Obtiene la orden de compra asociada
     * 
     * @return array|null
     */
    public function ordenCompra()
    {
        if (!isset($this->attributes['orden_compra_id'])) {
            return null;
        }

        return OrdenCompra::find($this->attributes['orden_compra_id']);
    }

    /**
     * Obtiene todos los archivos de una orden de compra
     * 
     * @param int $ordenCompraId
     * @return array
     */
    public static function porOrdenCompra($ordenCompraId)
    {
        $sql = "SELECT * FROM " . static::$table . " 
                WHERE orden_compra_id = ? 
                ORDER BY fecha_creacion DESC";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$ordenCompraId]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Sube un archivo y crea el registro
     * 
     * @param array $archivo Array de $_FILES
     * @param int $ordenCompraId
     * @return int|false ID del registro o false
     */
    public static function subirArchivo($archivo, $ordenCompraId)
    {
        try {
            // Validar archivo
            if ($archivo['error'] !== UPLOAD_ERR_OK) {
                throw new \Exception('Error al subir el archivo');
            }

            // Obtener configuración
            $uploadPath = Config::get('app.uploads.path');
            $maxSize = Config::get('app.uploads.max_size');
            $allowedTypes = Config::get('app.uploads.allowed_types');

            // Validar tamaño
            if ($archivo['size'] > $maxSize) {
                throw new \Exception('El archivo excede el tamaño máximo permitido');
            }

            // Validar tipo
            if (!in_array($archivo['type'], $allowedTypes)) {
                throw new \Exception('Tipo de archivo no permitido');
            }

            // Crear directorio si no existe
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            // Generar nombre único
            $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
            $nombreArchivo = uniqid() . '.' . $extension;
            $rutaCompleta = $uploadPath . '/' . $nombreArchivo;

            // Mover archivo
            if (!move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
                throw new \Exception('Error al mover el archivo');
            }

            // Crear registro
            return self::create([
                'orden_compra_id' => $ordenCompraId,
                'nombre_original' => $archivo['name'],
                'nombre_archivo' => $nombreArchivo,
                'tipo_archivo' => $archivo['type'],
                'ruta_archivo' => $rutaCompleta,
                'tamano' => $archivo['size'],
            ]);
        } catch (\Exception $e) {
            error_log("Error subiendo archivo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina el archivo físico y el registro
     * 
     * @param int $id
     * @return bool
     */
    public static function eliminarArchivo($id)
    {
        try {
            $archivo = self::find($id);
            
            if (!$archivo) {
                return false;
            }

            // Eliminar archivo físico
            if (isset($archivo['ruta_archivo']) && file_exists($archivo['ruta_archivo'])) {
                unlink($archivo['ruta_archivo']);
            }

            // Eliminar registro
            return self::delete($id);
        } catch (\Exception $e) {
            error_log("Error eliminando archivo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el tamaño formateado del archivo
     * 
     * @return string
     */
    public function getTamanoFormateado()
    {
        if (!isset($this->attributes['tamano'])) {
            return '0 B';
        }

        $bytes = $this->attributes['tamano'];
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Obtiene la extensión del archivo
     * 
     * @return string
     */
    public function getExtension()
    {
        if (!isset($this->attributes['nombre_archivo'])) {
            return '';
        }

        return strtoupper(pathinfo($this->attributes['nombre_archivo'], PATHINFO_EXTENSION));
    }

    /**
     * Obtiene el ícono según el tipo de archivo
     * 
     * @return string Clase de ícono FontAwesome
     */
    public function getIcono()
    {
        $extension = strtolower($this->getExtension());
        
        $iconos = [
            'pdf' => 'fa-file-pdf',
            'doc' => 'fa-file-word',
            'docx' => 'fa-file-word',
            'xls' => 'fa-file-excel',
            'xlsx' => 'fa-file-excel',
            'jpg' => 'fa-file-image',
            'jpeg' => 'fa-file-image',
            'png' => 'fa-file-image',
            'zip' => 'fa-file-zipper',
            'rar' => 'fa-file-zipper',
        ];

        return $iconos[$extension] ?? 'fa-file';
    }

    /**
     * Verifica si el archivo existe físicamente
     * 
     * @return bool
     */
    public function existeArchivo()
    {
        return isset($this->attributes['ruta_archivo']) && 
               file_exists($this->attributes['ruta_archivo']);
    }

    /**
     * Descarga el archivo (devuelve la ruta)
     * 
     * @return string|false
     */
    public function getRutaDescarga()
    {
        if (!$this->existeArchivo()) {
            return false;
        }

        return $this->attributes['ruta_archivo'];
    }

    /**
     * Valida si es un tipo de imagen
     * 
     * @return bool
     */
    public function esImagen()
    {
        $extension = strtolower($this->getExtension());
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif']);
    }

    /**
     * Cuenta archivos por orden
     * 
     * @param int $ordenCompraId
     * @return int
     */
    public static function contarPorOrden($ordenCompraId)
    {
        $instance = new static();
        
        $sql = "SELECT COUNT(*) as total FROM {$instance->table} WHERE orden_compra_id = ?";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$ordenCompraId]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Obtiene el tamaño total de archivos de una orden
     * 
     * @param int $ordenCompraId
     * @return int Bytes
     */
    public static function getTamanoTotalOrden($ordenCompraId)
    {
        $instance = new static();
        
        $sql = "SELECT SUM(tamano) as total FROM {$instance->table} WHERE orden_compra_id = ?";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$ordenCompraId]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
}
