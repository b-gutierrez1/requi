<?php

namespace App\DTOs;

/**
 * DTO para datos de autorización
 * 
 * Responsabilidad: Transferencia de datos entre capas sin lógica de negocio
 */
class AutorizacionDTO
{
    public $id;
    public $ordenId;
    public $centroCostoId;
    public $centroCostoNombre;
    public $autorizadorEmail;
    public $autorizadorNombre;
    public $estado;
    public $monto;
    public $porcentaje;
    public $comentarios;
    public $fechaAutorizacion;
    public $proveedor;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->ordenId = $data['orden_compra_id'] ?? null;
        $this->centroCostoId = $data['centro_costo_id'] ?? null;
        $this->centroCostoNombre = $data['centro_nombre'] ?? null;
        $this->autorizadorEmail = $data['autorizador_email'] ?? null;
        $this->autorizadorNombre = $data['autorizador_nombre'] ?? null;
        $this->estado = $data['estado'] ?? 'pendiente';
        $this->monto = $data['monto'] ?? 0;
        $this->porcentaje = $data['porcentaje'] ?? 0;
        $this->comentarios = $data['comentarios'] ?? null;
        $this->fechaAutorizacion = $data['fecha_autorizacion'] ?? null;
        $this->proveedor = $data['proveedor'] ?? null;
    }

    /**
     * Verifica si la autorización está pendiente
     */
    public function esPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }

    /**
     * Verifica si la autorización está aprobada
     */
    public function estaAprobada(): bool
    {
        return $this->estado === 'aprobada';
    }

    /**
     * Verifica si la autorización fue rechazada
     */
    public function estaRechazada(): bool
    {
        return $this->estado === 'rechazada';
    }

    /**
     * Convierte a array para vistas
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'orden_compra_id' => $this->ordenId,
            'centro_costo_id' => $this->centroCostoId,
            'centro_nombre' => $this->centroCostoNombre,
            'autorizador_email' => $this->autorizadorEmail,
            'autorizador_nombre' => $this->autorizadorNombre,
            'estado' => $this->estado,
            'monto' => $this->monto,
            'porcentaje' => $this->porcentaje,
            'comentarios' => $this->comentarios,
            'fecha_autorizacion' => $this->fechaAutorizacion,
            'proveedor' => $this->proveedor
        ];
    }

    /**
     * Formatea el monto para display
     */
    public function getMontoFormateado(): string
    {
        return 'Q' . number_format($this->monto, 2);
    }
}