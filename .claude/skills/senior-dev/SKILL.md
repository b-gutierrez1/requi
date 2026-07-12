---
name: senior-dev
description: Modo programador senior. Activar cuando el usuario pida revisión de código, refactoring, nuevas features, análisis de arquitectura, detección de bugs, o mejoras de calidad en el sistema de requisiciones.
allowed-tools: Read, Grep, Glob, Bash, Edit, Write, Task
---

# Senior Developer Mode

Cuando se activa esta skill, aplica los siguientes estándares en TODOS los cambios:

## Antes de escribir código
1. Lee los archivos relevantes completos — nunca modifiques código que no hayas leído
2. Verifica el impacto: busca todos los lugares donde se usa lo que vas a cambiar
3. Revisa la BD si el cambio toca modelos (`DESCRIBE tabla` con mysql)
4. Confirma que el formato de datos sea consistente (arrays vs objetos, FETCH_ASSOC vs objetos Model)

## Estándares de código
- **Sin hardcoding**: datos que cambian van en BD, no en el código
- **Sin código muerto**: elimina métodos, variables y archivos que ya no se usan
- **Sin duplicación**: si el mismo código aparece 3+ veces, extrae un método
- **Validación solo en boundaries**: input del usuario y APIs externas; confía en el código interno
- **Manejo de errores real**: try/catch con logs útiles, no swallowing silencioso de excepciones
- **Queries seguras**: siempre PDO con prepared statements, nunca concatenación en SQL

## Revisión de seguridad en cada cambio
- Verificar CSRF en todos los POST/PUT/DELETE
- Sanitizar input del usuario con `$this->sanitize()`
- Verificar permisos: `$this->isAdmin()`, `$this->getUsuarioId()`
- No exponer IDs internos o datos sensibles en respuestas JSON

## Stack del proyecto
- **PHP** sin framework (router propio en `routes/web.php`)
- **Modelos**: heredan de `app/Models/Model.php`, usan PDO, `FETCH_ASSOC` en métodos estáticos custom, objetos en `find()`
- **Vistas**: PHP puro en `app/Views/`, Bootstrap 5 + FontAwesome
- **Controladores**: heredan de `app/Controllers/Controller.php`
- **BD**: MySQL en XAMPP, base `bd_prueba`

## Al terminar cada cambio
1. Verificar sintaxis PHP: `/c/xampp/php/php -l archivo.php`
2. Verificar que no quedaron referencias rotas al código eliminado
3. Confirmar que el contrato de datos (JSON, arrays) sigue siendo compatible con el frontend
