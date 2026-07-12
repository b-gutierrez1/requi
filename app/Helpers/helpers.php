<?php
/**
 * Funciones helper globales
 */

if (!function_exists('url')) {
    /**
     * Genera una URL completa con el base path
     * 
     * @param string $path
     * @return string
     */
    function url($path) {
        return \App\Helpers\Redirect::url($path);
    }
}

if (!function_exists('asset')) {
    /**
     * Genera una URL para assets (CSS, JS, imágenes)
     * 
     * @param string $path
     * @return string
     */
    function asset($path) {
        return url($path);
    }
}

if (!function_exists('sanitizarSegmentoNombre')) {
    /**
     * Sanitiza un segmento de nombre de archivo para uso en Content-Disposition.
     * Transliteración de acentos, elimina caracteres inválidos, colapsa guiones.
     */
    function sanitizarSegmentoNombre(string $texto, int $maxLen = 50): string
    {
        // Transliterar caracteres con acento a ASCII
        $mapa = [
            'á'=>'a','à'=>'a','ä'=>'a','â'=>'a','ã'=>'a',
            'é'=>'e','è'=>'e','ë'=>'e','ê'=>'e',
            'í'=>'i','ì'=>'i','ï'=>'i','î'=>'i',
            'ó'=>'o','ò'=>'o','ö'=>'o','ô'=>'o','õ'=>'o',
            'ú'=>'u','ù'=>'u','ü'=>'u','û'=>'u',
            'ñ'=>'n','ç'=>'c',
            'Á'=>'A','À'=>'A','Ä'=>'A','Â'=>'A','Ã'=>'A',
            'É'=>'E','È'=>'E','Ë'=>'E','Ê'=>'E',
            'Í'=>'I','Ì'=>'I','Ï'=>'I','Î'=>'I',
            'Ó'=>'O','Ò'=>'O','Ö'=>'O','Ô'=>'O','Õ'=>'O',
            'Ú'=>'U','Ù'=>'U','Ü'=>'U','Û'=>'U',
            'Ñ'=>'N','Ç'=>'C',
        ];
        $texto = strtr($texto, $mapa);

        // Solo letras, dígitos, guión, guión bajo y punto
        $texto = preg_replace('/[^A-Za-z0-9\-_.]/', '_', $texto);

        // Colapsar guiones y underscores consecutivos
        $texto = preg_replace('/[_\-]{2,}/', '_', $texto);

        // Quitar underscores al inicio/fin
        $texto = trim($texto, '_-.');

        // Truncar respetando el límite
        return mb_substr($texto, 0, $maxLen);
    }
}

if (!function_exists('generarNombreDescarga')) {
    /**
     * Genera el nombre de descarga con formato: REQ-{id}_{proveedor}_{nombre_original}
     *
     * @param int    $reqId         ID de la requisición
     * @param string $proveedor     Nombre del proveedor / razón social
     * @param string $nombreOriginal Nombre original del archivo (con extensión)
     * @return string  Nombre listo para Content-Disposition
     */
    function generarNombreDescarga(int $reqId, string $proveedor, string $nombreOriginal): string
    {
        // Extraer extensión del nombre original
        $ext      = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $baseName = pathinfo($nombreOriginal, PATHINFO_FILENAME);

        $segProveedor = sanitizarSegmentoNombre($proveedor, 40);
        $segNombre    = sanitizarSegmentoNombre($baseName,  60);
        $segExt       = $ext ? '.' . preg_replace('/[^a-z0-9]/', '', $ext) : '';

        // Si el proveedor quedó vacío (ej: NIT solamente), omitir ese segmento
        if ($segProveedor === '') {
            return "REQ-{$reqId}_{$segNombre}{$segExt}";
        }

        // Si el nombre de archivo quedó vacío, usar fallback
        if ($segNombre === '') {
            $segNombre = 'documento';
        }

        return "REQ-{$reqId}_{$segProveedor}_{$segNombre}{$segExt}";
    }
}

if (!function_exists('redirect')) {
    /**
     * Helper para redirecciones
     * 
     * @param string $path
     * @return \App\Helpers\Redirect
     */
    function redirect($path = null) {
        if ($path === null) {
            return new \App\Helpers\Redirect('/');
        }
        return \App\Helpers\Redirect::to($path);
    }
}
