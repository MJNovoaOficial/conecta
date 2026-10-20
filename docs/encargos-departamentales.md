# Encargos individuales con revisión de TI

Esta función es adicional al flujo actual. No convierte personas en soporte ni utiliza asignaciones históricas como autorización.

## Funcionamiento

1. El responsable TI del ticket o un administrador selecciona una persona activa, de otro departamento y con rol `user`. No puede ser el solicitante del caso.
2. El ticket mantiene `assigned_to`, departamento de origen y SLA. El encargo queda En trabajo y, si existía una espera del solicitante con plazo, vuelve a En Proceso sin ese plazo: se está esperando colaboración, no una respuesta del solicitante.
3. La persona ve únicamente el caso encargado, sus mensajes y adjuntos públicos. Puede responder y adjuntar utilizando el formulario del ticket. No ve notas internas ni sus archivos; tampoco puede tomar otros casos, reasignar, resolver, cerrar, reabrir o administrar.
4. La propuesta se guarda aparte, pendiente de revisión de TI, sin cambiar `solution_text`, `resolved_at` ni iniciar el cierre de una hora. El solicitante no ve borradores, instrucciones o correcciones privadas.
5. TI puede pedir ajustes o aprobar la versión vigente. Solo la aprobación registra la solución mediante el flujo existente y comienza la hora de cierre automático.
6. El encargo aprobado conserva acceso de consulta al mismo ciclo del caso. TI o administración pueden retirar ese acceso, incluso tras el cierre. Cambiar de responsable TI, derivar o reabrir invalida el encargo anterior; una notificación o pestaña antigua no devuelve permisos.

Los encargados no reciben avisos generales de tickets. Los avisos de su encargo y de la actividad pública del caso respetan los controles existentes de notificaciones y su preferencia de correo. Propuestas y correcciones privadas no se copian al cuerpo del correo. Un fallo de correo no invalida un guardado confirmado.

## Datos y compatibilidad

- Por defecto `TICKET_DELEGATION_ENABLED=false`: sin activar, el flujo anterior no consulta la tabla nueva ni muestra los controles.
- Aun con la opción activada, si falta la tabla, se mantiene el flujo anterior y las nuevas acciones no están disponibles.
- Solo se crea `ticket_delegations`. No se alteran ni repueblan usuarios, tickets, artículos, imágenes, manuales, conocimiento del asistente ni asignaciones históricas.
- No se cambian roles de personas ni el motor/configuración del asistente IA.
- Cada reemplazo crea otro encargo y revoca el permiso anterior sin borrar sus datos. Las versiones y la auditoría conservan las propuestas y revisiones.
- Las transacciones usan bloqueos cortos; correos y archivos no se procesan dentro de los bloqueos del encargo.
- La reversión de esta migración se niega si hay registros. Para retirar la función basta desactivarla; no es necesario borrar su historial.

## Despliegue controlado: no realizado automáticamente

Primero probar en una copia o entorno de preproducción con la versión actual de Conecta. Respaldar base y archivos antes del despliegue. Confirmar que `tickets` y `usuarios` usan InnoDB, y que la cuenta usada para migrar tiene permisos DDL. La migración comprueba InnoDB antes de crear su tabla; no convierte ni altera las tablas anteriores.

Con la opción todavía desactivada, aplicar **solo la migración nueva** desde la carpeta de la aplicación:

```powershell
php artisan migrate --path=database/migrations/2026_10_19_210000_create_ticket_delegations_table.php --force
```

No ejecutar `migrate:fresh`, `db:wipe`, seeders generales ni importaciones que reemplacen datos. No ejecutar pruebas PHPUnit sobre la base del servidor: usan una base desechable y solo se permiten `conecta_testing` o `:memory:`.

Tampoco ejecutar `migrate:rollback` general: recorrería migraciones históricas ajenas a este cambio, con posibles alteraciones y sentencias incompatibles con el motor. Para retirar esta función se usa la bandera desactivada, sin revertir tablas anteriores ni borrar encargos.

Verificar también que la cuenta de la aplicación pueda hacer `SELECT`, `INSERT` y `UPDATE` sobre `ticket_delegations`; no necesita borrar sus filas. No ampliar permisos sobre otras tablas por este motivo. Después de confirmar la nueva tabla, activar en el `.env` del servidor:

```dotenv
TICKET_DELEGATION_ENABLED=true
```

Actualizar configuración y vistas según el procedimiento de despliegue existente y reiniciar el worker para que lea el código/configuración nuevos:

```powershell
php artisan route:clear
php artisan config:cache
php artisan view:clear
php artisan queue:restart
```

No requiere otro scheduler ni otra tarea programada. Mantiene el cierre de una hora del job existente.

Para desactivar la función sin tocar sus datos, establecer `TICKET_DELEGATION_ENABLED=false`, regenerar la configuración y reiniciar el worker. Las cuentas, archivos y encargos permanecen en la base. Un caso activo cuyo encargo ya no esté disponible vuelve a poder resolverse con las acciones anteriores de TI mientras la función está apagada; no se resuelven casos automáticamente al desactivarla.

## Comprobación mínima de aceptación

Encargar un caso de prueba → comprobar que otro integrante del departamento no lo ve → responder y adjuntar como encargado → proponer → verificar que no figura Resuelto → pedir ajustes → reenviar → aprobar desde TI → comprobar el cierre a partir de una hora. Retirar otro encargo y comprobar que su URL y formulario anteriores ya no dan acceso. Confirmar que login, casos normales, invitados y consultas del asistente mantienen su funcionamiento.
