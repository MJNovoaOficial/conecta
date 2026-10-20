# Horas locales de tickets

El reloj de Laravel y las fechas persistidas siguen en UTC. `app.display_timezone`
define únicamente la presentación (`America/Santiago`, con los cambios de horario
de su zona). No se suma/resta un número fijo de horas ni se modifican registros.

`DisplayDate` convierte copias inmutables para las vistas de tickets, plazos,
correos de creación y reportes. Los filtros Desde/Hasta de tickets y reportes
interpretan días de Chile y consultan sus límites equivalentes en UTC, con fin
exclusivo para no perder registros cerca de medianoche ni en cambios de horario.

No cambia el cálculo del SLA, duración de sesión, notificaciones/destinatarios,
permisos ni los jobs de cierre. La mejora de encargos departamentales no forma
parte de este arreglo.

## Despliegue (después de publicar y actualizar el código)

Desde la carpeta de Conecta en el servidor, usando el PHP de la aplicación:

```powershell
php artisan config:cache
php artisan view:clear
php artisan queue:restart
```

La tarea existente debe mantener el worker en ejecución/reiniciarlo tras su
salida. No cambiar la zona de Windows, `app.timezone` (UTC), `.env`, ni la zona de
la conexión SQL. No ejecutar migraciones, seeders ni actualizaciones SQL de fechas.

Comprobar Configuración → General: muestra `America/Santiago`, la hora local y
aclara que las fechas internas siguen en UTC. Comparar una conversación y su
listado con la hora local. Los datos históricos se convierten al mostrarlos,
sin reescribirlos. El despliegue no reenvía correos ya enviados.
