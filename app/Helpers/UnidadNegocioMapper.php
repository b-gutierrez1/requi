<?php
/**
 * UnidadNegocioMapper
 * 
 * Clase helper para mapear unidades de negocio a centros de costo y tipos de factura
 * según las reglas de negocio establecidas.
 * 
 * @package App\Helpers
 * @version 1.0
 */

namespace App\Helpers;

class UnidadNegocioMapper
{
    /**
     * Mapeo de códigos de unidad de negocio a centros de costo y facturas
     * 
     * @var array
     */
    private static $mapeo = [
        // FACTURA 1 (Comercial - Parqueo - Teatro)
        'ADPG' => ['unidad' => 'ADMINISTRACION', 'factura' => 1],
        'CUAC' => ['unidad' => 'ACTIVIDADES CULTURALES', 'factura' => 1],
        'MBOD' => ['unidad' => 'COMERCIAL', 'factura' => 1],
        'MDFI' => ['unidad' => 'COMERCIAL', 'factura' => 1],
        'MDIS' => ['unidad' => 'COMERCIAL', 'factura' => 1],
        'MLCO' => ['unidad' => 'COMERCIAL', 'factura' => 1],
        'MLXE' => ['unidad' => 'COMERCIAL', 'factura' => 1],
        'MLZ4' => ['unidad' => 'COMERCIAL', 'factura' => 1],

        // FACTURA 2 (Colegio)
        'COBA' => ['unidad' => 'COLEGIO', 'factura' => 2],
        'COBC' => ['unidad' => 'COLEGIO', 'factura' => 2],
        'COPC' => ['unidad' => 'COLEGIO', 'factura' => 2],
        'COSC' => ['unidad' => 'COLEGIO', 'factura' => 2],
        'PRIM' => ['unidad' => 'COLEGIO', 'factura' => 2],

        // FACTURA 3 (Administración - Cursos)
        'A001' => ['unidad' => 'CURSOS ADULTOS', 'factura' => 3],
        'A008' => ['unidad' => 'CURSOS ADULTOS', 'factura' => 3],
        'A010' => ['unidad' => 'CURSOS ADULTOS', 'factura' => 3],
        'A012' => ['unidad' => 'CURSOS ADULTOS', 'factura' => 3],
        'ADDG' => ['unidad' => 'ADMINISTRACION', 'factura' => 3],
        'ADED' => ['unidad' => 'ADMINISTRACION', 'factura' => 3],
        'ADFI' => ['unidad' => 'ADMINISTRACION', 'factura' => 3],
        'ADIT' => ['unidad' => 'ADMINISTRACION', 'factura' => 3],
        'ADMK' => ['unidad' => 'ADMINISTRACION', 'factura' => 3],
        'ADOG' => ['unidad' => 'ADMINISTRACION', 'factura' => 3],
        'ADOP' => ['unidad' => 'ADMINISTRACION', 'factura' => 3],
        'ADRH' => ['unidad' => 'ADMINISTRACION', 'factura' => 3],
        'ADSC' => ['unidad' => 'ADMINISTRACION', 'factura' => 3],
        'ADUA' => ['unidad' => 'ADMINISTRACION', 'factura' => 3],
        'CUBI' => ['unidad' => 'ACTIVIDADES CULTURALES', 'factura' => 3],
        'N001' => ['unidad' => 'CURSOS NIÑOS', 'factura' => 3],
        'GENO1' => ['unidad' => 'CENTRO DE COSTO GENERAL', 'factura' => 3],
    ];

    /**
     * Obtiene la centro de costo para un código de unidad de negocio
     * 
     * @param string $codigoCentro Código del unidad de negocio
     * @return string|null Nombre de la centro de costo o null si no existe
     */
    public static function getCentroCosto($codigoCentro)
    {
        $codigoCentro = strtoupper(trim($codigoCentro));
        return self::$mapeo[$codigoCentro]['unidad'] ?? null;
    }

    /**
     * Obtiene el tipo de factura para un código de unidad de negocio
     * 
     * @param string $codigoCentro Código del unidad de negocio
     * @return int|null Número de factura (1, 2, 3) o null si no existe
     */
    public static function getTipoFactura($codigoCentro)
    {
        $codigoCentro = strtoupper(trim($codigoCentro));
        return self::$mapeo[$codigoCentro]['factura'] ?? null;
    }

    /**
     * Obtiene tanto la centro de costo como el tipo de factura
     * 
     * @param string $codigoCentro Código del unidad de negocio
     * @return array Array con 'unidad' y 'factura' o array vacío si no existe
     */
    public static function getMapeoCompleto($codigoCentro)
    {
        $codigoCentro = strtoupper(trim($codigoCentro));
        return self::$mapeo[$codigoCentro] ?? [];
    }

    /**
     * Verifica si existe un mapeo para el código dado
     * 
     * @param string $codigoCentro Código del unidad de negocio
     * @return bool True si existe mapeo, false si no
     */
    public static function existeMapeo($codigoCentro)
    {
        $codigoCentro = strtoupper(trim($codigoCentro));
        return isset(self::$mapeo[$codigoCentro]);
    }

    /**
     * Obtiene todos los unidades de negocio por tipo de factura
     * 
     * @param int $tipoFactura Número de factura (1, 2, 3)
     * @return array Array de códigos de unidad de negocio
     */
    public static function getCentrosPorFactura($tipoFactura)
    {
        $centros = [];
        foreach (self::$mapeo as $codigo => $datos) {
            if ($datos['factura'] === $tipoFactura) {
                $centros[] = $codigo;
            }
        }
        return $centros;
    }

    /**
     * Obtiene todos los unidades de negocio por centro de costo
     * 
     * @param string $centroCosto Nombre de la centro de costo
     * @return array Array de códigos de unidad de negocio
     */
    public static function getCentrosPorUnidad($centroCosto)
    {
        $centros = [];
        foreach (self::$mapeo as $codigo => $datos) {
            if ($datos['unidad'] === $centroCosto) {
                $centros[] = $codigo;
            }
        }
        return $centros;
    }

    /**
     * Obtiene todas las centros de costo únicas
     * 
     * @return array Array de nombres de centros de costo
     */
    public static function getUnidadesUnicas()
    {
        $unidades = [];
        foreach (self::$mapeo as $datos) {
            if (!in_array($datos['unidad'], $unidades)) {
                $unidades[] = $datos['unidad'];
            }
        }
        sort($unidades);
        return $unidades;
    }

    /**
     * Obtiene estadísticas del mapeo
     * 
     * @return array Array con estadísticas
     */
    public static function getEstadisticas()
    {
        $stats = [
            'total_centros' => count(self::$mapeo),
            'factura_1' => count(self::getCentrosPorFactura(1)),
            'factura_2' => count(self::getCentrosPorFactura(2)),
            'factura_3' => count(self::getCentrosPorFactura(3)),
            'unidades_unicas' => count(self::getUnidadesUnicas()),
        ];

        return $stats;
    }

    /**
     * Obtiene el mapeo completo para uso en JavaScript
     * 
     * @return array Mapeo completo para serializar a JSON
     */
    public static function getMapeoParaJS()
    {
        return self::$mapeo;
    }

    /**
     * Valida si una combinación centro-unidad-factura es correcta
     * 
     * @param string $codigoCentro Código del unidad de negocio
     * @param string $centroCosto Unidad de negocio
     * @param int $tipoFactura Tipo de factura
     * @return bool True si es válida, false si no
     */
    public static function validarCombinacion($codigoCentro, $centroCosto, $tipoFactura)
    {
        $mapeo = self::getMapeoCompleto($codigoCentro);
        
        if (empty($mapeo)) {
            return false;
        }

        return $mapeo['unidad'] === $centroCosto && $mapeo['factura'] === $tipoFactura;
    }

    /**
     * Obtiene descripción del tipo de factura
     * 
     * @param int $tipoFactura Número de factura (1, 2, 3)
     * @return string Descripción del tipo de factura
     */
    public static function getDescripcionFactura($tipoFactura)
    {
        $descripciones = [
            1 => 'Comercial - Parqueo - Teatro',
            2 => 'Colegio',
            3 => 'Administración - Cursos'
        ];

        return $descripciones[$tipoFactura] ?? 'Desconocido';
    }
}