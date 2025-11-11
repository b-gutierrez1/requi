<?php

namespace App\Controllers;

use App\Helpers\View;
use App\Helpers\Redirect;
use App\Services\AutorizacionService;
use App\Repositories\AutorizacionRepository;
use App\DTOs\AutorizacionDTO;

/**
 * Controlador refactorizado con arquitectura limpia
 * 
 * Responsabilidades:
 * - Manejo de requests HTTP
 * - Validación de entrada
 * - Delegación a servicios
 * - Formateo de respuestas
 */
class AutorizacionControllerLimpio extends Controller
{
    private AutorizacionService $autorizacionService;
    private AutorizacionRepository $autorizacionRepository;

    public function __construct()
    {
        parent::__construct();
        $this->autorizacionService = new AutorizacionService();
        $this->autorizacionRepository = new AutorizacionRepository();
    }

    /**
     * Lista las autorizaciones pendientes del usuario
     */
    public function index()
    {
        $usuarioEmail = $this->getUsuarioEmail();
        
        // Obtener datos usando repositorio
        $autorizacionesPendientes = $this->autorizacionRepository->findPendingByEmail($usuarioEmail);
        
        // Convertir a DTOs
        $autorizacionesDTO = array_map(function($auth) {
            return new AutorizacionDTO($auth);
        }, $autorizacionesPendientes);

        View::render('autorizaciones/index', [
            'autorizaciones' => $autorizacionesDTO,
            'title' => 'Mis Autorizaciones Pendientes'
        ]);
    }

    /**
     * Muestra el detalle de una autorización
     */
    public function show($id)
    {
        // Verificar permisos usando servicio
        $permisos = $this->autorizacionService->puedeAutorizar($id, $this->getUsuarioEmail());
        
        if (!$permisos['puede_autorizar']) {
            Redirect::to('/autorizaciones')
                ->withError($permisos['motivo_rechazo'])
                ->send();
        }

        // Obtener datos usando servicio
        $resumen = $this->autorizacionService->obtenerResumenAutorizaciones($id);
        $autorizaciones = $this->autorizacionRepository->findByOrden($id);

        View::render('autorizaciones/show', [
            'resumen' => $resumen,
            'autorizaciones' => $autorizaciones,
            'title' => 'Autorizar Requisición #' . $id
        ]);
    }

    /**
     * Aprueba una autorización
     */
    public function aprobar($id)
    {
        // Validación de entrada
        $request = $this->validateRequest([
            'comentario' => 'string|optional'
        ]);

        if (!$this->validateCSRF()) {
            return $this->errorResponse('Token de seguridad inválido', 403);
        }

        // Procesar usando servicio
        $resultado = $this->autorizacionService->procesarAutorizacion(
            $id, 
            'aprobar', 
            $request['comentario'] ?? null,
            $this->getUsuarioEmail()
        );

        if ($this->isAjaxRequest()) {
            return $this->jsonResponse($resultado);
        }

        if ($resultado['success']) {
            Redirect::back()->withSuccess('Autorización aprobada exitosamente')->send();
        } else {
            Redirect::back()->withError($resultado['error'])->send();
        }
    }

    /**
     * Rechaza una autorización
     */
    public function rechazar($id)
    {
        // Validación de entrada
        $request = $this->validateRequest([
            'motivo' => 'string|required'
        ]);

        if (!$this->validateCSRF()) {
            return $this->errorResponse('Token de seguridad inválido', 403);
        }

        // Procesar usando servicio
        $resultado = $this->autorizacionService->procesarAutorizacion(
            $id, 
            'rechazar', 
            $request['motivo'],
            $this->getUsuarioEmail()
        );

        return $this->handleResponse($resultado);
    }

    /**
     * Valida datos de entrada
     */
    private function validateRequest(array $rules): array
    {
        $data = [];
        
        foreach ($rules as $field => $rule) {
            $parts = explode('|', $rule);
            $type = $parts[0];
            $required = in_array('required', $parts);
            $optional = in_array('optional', $parts);

            $value = $_POST[$field] ?? null;

            if ($required && empty($value)) {
                throw new \InvalidArgumentException("Campo requerido: {$field}");
            }

            if (!$optional && !$required && !isset($_POST[$field])) {
                throw new \InvalidArgumentException("Campo faltante: {$field}");
            }

            if ($value !== null) {
                $data[$field] = $this->castValue($value, $type);
            }
        }

        return $data;
    }

    /**
     * Convierte valores según el tipo
     */
    private function castValue($value, string $type)
    {
        switch ($type) {
            case 'int':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'bool':
                return (bool) $value;
            case 'string':
            default:
                return (string) $value;
        }
    }

    /**
     * Maneja la respuesta de forma unificada
     */
    private function handleResponse(array $resultado)
    {
        if ($this->isAjaxRequest()) {
            return $this->jsonResponse($resultado);
        }

        if ($resultado['success']) {
            Redirect::back()->withSuccess($resultado['message'] ?? 'Operación exitosa')->send();
        } else {
            Redirect::back()->withError($resultado['error'])->send();
        }
    }

    /**
     * Respuesta de error estandarizada
     */
    private function errorResponse(string $message, int $code = 400): array
    {
        $response = [
            'success' => false,
            'error' => $message,
            'code' => $code
        ];

        if ($this->isAjaxRequest()) {
            $this->jsonResponse($response, $code);
        } else {
            Redirect::back()->withError($message)->send();
        }

        return $response;
    }
}