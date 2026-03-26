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
    protected static $timestamps = false;  // La tabla solo tiene created_at auto

    protected static $fillable = [
        'requisicion_id',
        'nombre_original',
        'nombre_archivo',
        'tipo_mime',
        'ruta_archivo',
        'tamano_bytes',
        'descripcion',
    ];

    protected static $guarded = ['id', 'created_at'];

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
     * Obtiene todos los archivos de una requisición
     * 
     * @param int $requisicionId
     * @return array
     */
    public static function porRequisicion($requisicionId)
    {
        return self::porOrdenCompra($requisicionId);
    }

    /**
     * Obtiene todos los archivos de una orden de compra (alias legacy)
     * 
     * @param int $ordenCompraId
     * @return array
     * @deprecated Usar porRequisicion() en su lugar
     */
    public static function porOrdenCompra($ordenCompraId)
    {
        $sql = "SELECT * FROM " . static::$table . " 
                WHERE requisicion_id = ? 
                ORDER BY created_at DESC";
        
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
        error_log("=== DEBUG ArchivoAdjunto::subirArchivo ===");
        error_log("Archivo: " . json_encode($archivo));
        error_log("Orden ID: " . $ordenCompraId);
        
        try {
            // Validar archivo
            if ($archivo['error'] !== UPLOAD_ERR_OK) {
                error_log("Error de upload: " . $archivo['error']);
                throw new \Exception('Error al subir el archivo: ' . $archivo['error']);
            }
            
            error_log("Upload OK, continuando...");

            // Obtener configuración
            $uploadPath = Config::get('app.uploads.path');
            $maxSize = Config::get('app.uploads.max_size');
            $allowedTypes = Config::get('app.uploads.allowed_types');
            
            error_log("Configuración:");
            error_log("  Upload path: " . $uploadPath);
            error_log("  Max size: " . $maxSize);
            error_log("  Allowed types: " . json_encode($allowedTypes));

            // Validar tamaño
            error_log("Validando tamaño: " . $archivo['size'] . " vs " . $maxSize);
            if ($archivo['size'] > $maxSize) {
                error_log("Archivo excede tamaño máximo");
                throw new \Exception('El archivo excede el tamaño máximo permitido');
            }

            // Validar tipo — dos capas: magic bytes + finfo (nunca confiar en $archivo['type'])
            error_log("Validando tipo (magic bytes + finfo): " . $archivo['tmp_name']);
            if (!self::validateMagicBytes($archivo['tmp_name'], $allowedTypes)) {
                $finfo    = new \finfo(FILEINFO_MIME_TYPE);
                $realMime = $finfo->file($archivo['tmp_name']);
                error_log("Tipo no permitido. MIME real: {$realMime}. Tipos permitidos: " . implode(', ', $allowedTypes));
                throw new \Exception('Tipo de archivo no permitido: ' . $realMime);
            }

            // Obtener MIME real del servidor para almacenar
            $finfoStore = new \finfo(FILEINFO_MIME_TYPE);
            $realMimeStore = $finfoStore->file($archivo['tmp_name']);

            error_log("Validaciones OK, MIME real: {$realMimeStore}, creando archivo...");

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
                'requisicion_id' => $ordenCompraId,
                'nombre_original' => $archivo['name'],
                'nombre_archivo' => $nombreArchivo,
                'tipo_mime' => $realMimeStore,
                'ruta_archivo' => $rutaCompleta,
                'tamano_bytes' => $archivo['size'],
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
        if (!isset($this->attributes['tamano_bytes'])) {
            return '0 B';
        }

        $bytes = $this->attributes['tamano_bytes'];
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
        
        $sql = "SELECT COUNT(*) as total FROM {$instance->table} WHERE requisicion_id = ?";
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

        $sql = "SELECT SUM(tamano_bytes) as total FROM {$instance->table} WHERE requisicion_id = ?";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([$ordenCompraId]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Valida el archivo por magic bytes (firma binaria real) y, como capa
     * secundaria, por finfo para tipos no cubiertos en la tabla de firmas.
     *
     * Nunca confía en el MIME type declarado por el cliente.
     *
     * @param string $tmpPath       Ruta temporal del archivo subido
     * @param array  $allowedMimes  Lista de MIME types permitidos (desde config)
     * @return bool
     */
    private static function validateMagicBytes(string $tmpPath, array $allowedMimes): bool
    {
        // Mapa de MIME type => firmas binarias conocidas (magic bytes)
        $mimeToMagic = [
            'application/pdf' => ["\x25\x50\x44\x46"],          // %PDF
            'image/jpeg'      => ["\xFF\xD8\xFF"],
            'image/png'       => ["\x89\x50\x4E\x47\x0D\x0A\x1A\x0A"], // \x89PNG\r\n\x1a\n
            'image/gif'       => ["GIF87a", "GIF89a"],
            'application/zip' => ["\x50\x4B\x03\x04"],           // PK.. (también xlsx/docx)
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => ["\x50\x4B\x03\x04"],
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => ["\x50\x4B\x03\x04"],
            'application/msword'  => ["\xD0\xCF\x11\xE0"],       // OLE2 compound (doc/xls)
            'application/vnd.ms-excel' => ["\xD0\xCF\x11\xE0"],
        ];

        // Leer los primeros 10 bytes del archivo real
        $header = @file_get_contents($tmpPath, false, null, 0, 10);
        if ($header === false) {
            return false;
        }

        // MIME real según el servidor (finfo)
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($tmpPath);

        foreach ($allowedMimes as $mime) {
            if (isset($mimeToMagic[$mime])) {
                // Verificar magic bytes primero
                foreach ($mimeToMagic[$mime] as $magic) {
                    if (str_starts_with($header, $magic)) {
                        return true;
                    }
                }
            } else {
                // Para tipos sin magic bytes registrados, confiar en finfo
                if ($realMime === $mime) {
                    return true;
                }
            }
        }

        return false;
    }
}
