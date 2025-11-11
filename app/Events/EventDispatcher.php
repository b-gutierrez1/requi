<?php

namespace App\Events;

/**
 * Despachador de eventos simple
 * 
 * Responsabilidad: Gestionar eventos para desacoplar funcionalidades
 */
class EventDispatcher
{
    private static array $listeners = [];

    /**
     * Registra un listener para un evento
     */
    public static function listen(string $event, callable $listener): void
    {
        if (!isset(self::$listeners[$event])) {
            self::$listeners[$event] = [];
        }
        
        self::$listeners[$event][] = $listener;
    }

    /**
     * Dispara un evento
     */
    public static function dispatch(string $event, array $data = []): void
    {
        if (!isset(self::$listeners[$event])) {
            return;
        }

        foreach (self::$listeners[$event] as $listener) {
            try {
                call_user_func($listener, $data);
            } catch (\Exception $e) {
                error_log("Error en listener para evento {$event}: " . $e->getMessage());
            }
        }
    }
}

// Función helper global
if (!function_exists('event')) {
    function event(string $eventName, array $data = []): void
    {
        EventDispatcher::dispatch($eventName, $data);
    }
}