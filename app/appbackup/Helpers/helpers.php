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
