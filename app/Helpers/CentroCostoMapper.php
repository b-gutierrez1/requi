<?php
/**
 * CentroCostoMapper
 * 
 * Clase helper para mapear centros de costo a unidades de negocio y tipos de factura
 * según las reglas de negocio establecidas.
 * 
 * @package App\Helpers
 * @version 1.0
 */

namespace App\Helpers;

class CentroCostoMapper
{
    /**
     * Mapeo de códigos de centro de costo a unidades de negocio y facturas
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
        'GENO1' => ['unidad' => 'UNIDAD DE NEGOCIO GENERAL', 'factura' => 3],
    ];

    /**
     * Obtiene la unidad de negocio para un código de centro de costo
     * 
     * @param string $codigoCentro Código del centro de costo
     * @return string|null Nombre de la unidad de negocio o null si no existe
     */
    public static function getUnidadNegocio($codigoCentro)
    {
        $codigoCentro = strtoupper(trim($codigoCentro));
        return self::$mapeo[$codigoCentro]['unidad'] ?? null;
    }

    /**
     * Obtiene el tipo de factura para un código de centro de costo
     * 
     * @param string $codigoCentro Código del centro de costo
     * @return int|null Número de factura (1, 2, 3) o null si no existe
     */
    public static function getTipoFactura($codigoCentro)
    {
        $codigoCentro = strtoupper(trim($codigoCentro));
        return self::$mapeo[$codigoCentro]['factura'] ?? null;
    }

    /**
     * Obtiene tanto la unidad de negocio como el tipo de factura
     * 
     * @param string $codigoCentro Código del centro de costo
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
     * @param string $codigoCentro Código del centro de costo
     * @return bool True si existe mapeo, false si no
     */
    public static function existeMapeo($codigoCentro)
    {
        $codigoCentro = strtoupper(trim($codigoCentro));
        return isset(self::$mapeo[$codigoCentro]);
    }

    /**
     * Obtiene todos los centros de costo por tipo de factura
     * 
     * @param int $tipoFactura Número de factura (1, 2, 3)
     * @return array Array de códigos de centro de costo
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
     * Obtiene todos los centros de costo por unidad de negocio
     * 
     * @param string $unidadNegocio Nombre de la unidad de negocio
     * @return array Array de códigos de centro de costo
     */
    public static function getCentrosPorUnidad($unidadNegocio)
    {
        $centros = [];
        foreach (self::$mapeo as $codigo => $datos) {
            if ($datos['unidad'] === $unidadNegocio) {
                $centros[] = $codigo;
            }
        }
        return $centros;
    }

    /**
     * Obtiene todas las unidades de negocio únicas
     * 
     * @return array Array de nombres de unidades de negocio
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
     * @param string $codigoCentro Código del centro de costo
     * @param string $unidadNegocio Unidad de negocio
     * @param int $tipoFactura Tipo de factura
     * @return bool True si es válida, false si no
     */
    public static function validarCombinacion($codigoCentro, $unidadNegocio, $tipoFactura)
    {
        $mapeo = self::getMapeoCompleto($codigoCentro);
        
        if (empty($mapeo)) {
            return false;
        }

        return $mapeo['unidad'] === $unidadNegocio && $mapeo['factura'] === $tipoFactura;
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