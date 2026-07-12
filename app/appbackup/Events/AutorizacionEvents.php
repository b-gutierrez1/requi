<?php

namespace App\Events;

/**
 * Eventos relacionados con autorizaciones
 */
class AutorizacionEvents
{
    // Eventos de autorización
    const AUTORIZACION_CREADA = 'autorizacion.creada';
    const AUTORIZACION_APROBADA = 'autorizacion.aprobada'; 
    const AUTORIZACION_RECHAZADA = 'autorizacion.rechazada';
    const AUTORIZACION_COMPLETADA = 'autorizacion.completada';

    // Eventos de revisión
    const REVISION_APROBADA = 'revision.aprobada';
    const REVISION_RECHAZADA = 'revision.rechazada';

    /**
     * Registra todos los listeners de autorización
     */
    public static function register(): void
    {
        // Cuando se aprueba una autorización, enviar notificación
        EventDispatcher::listen(self::AUTORIZACION_APROBADA, function($data) {
            // Enviar email de notificación
            if (isset($data['orden_id'])) {
                // NotificationService::sendAuthorizationApproved($data['orden_id']);
                error_log("Evento: Autorización aprobada para orden {$data['orden_id']}");
            }
        });

        // Cuando se completa una autorización, actualizar estado
        EventDispatcher::listen(self::AUTORIZACION_COMPLETADA, function($data) {
            if (isset($data['orden_id'])) {
                error_log("Evento: Autorización completada para orden {$data['orden_id']}");
                // Aquí se puede agregar lógica adicional como logs de auditoría
            }
        });

        // Cuando se aprueba una revisión
        EventDispatcher::listen(self::REVISION_APROBADA, function($data) {
            if (isset($data['orden_id'])) {
                error_log("Evento: Revisión aprobada para orden {$data['orden_id']}");
                // Crear autorizaciones por centro de costo
                // NotificationService::notifyAuthorizers($data['orden_id']);
            }
        });
    }
}