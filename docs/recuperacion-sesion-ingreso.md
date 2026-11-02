# Recuperación del ingreso sin borrar cookies

El login podía aceptar una contraseña correcta y devolver al usuario al formulario: conservaba una IP o actividad antigua de sesión. El detector de cambio de IP hacía logout sin limpiar esos datos, provocando rechazos al volver a ingresar.

La actualización renueva IP y actividad tras autenticar correctamente, tanto al ingresar como al registrarse con inicio automático. Si una sesión abierta debe finalizar por cambio de IP, la invalida, regenera su token y dirige al formulario real con el aviso correspondiente.

Se conservan validación de contraseñas, roles, CSRF, Recuérdame, inactividad y control de cambio de IP. No se añade espera ni límite de intentos de login/registro. La limpieza se limita a la sesión que corresponde invalidar; no hay purga masiva de sesiones o datos del negocio.

## Actualización del servidor

Antes de actualizar, conservar un respaldo privado de `.env` y `storage/app`, un respaldo reciente de la base y el identificador de la versión actual (`git rev-parse HEAD`). No cambiar `APP_KEY`. Hacerlo durante un momento de baja actividad; un reciclado del pool puede interrumpir solicitudes en curso.

Desde PowerShell:

```powershell
Set-Location 'C:\Sitios\conecta'
git status --short
git branch --show-current
git remote get-url origin
```

Confirmar que la copia está en `main` y que `origin` corresponde a `MJNovoaOficial/conecta`. Si hay archivos modificados o conflictos, detener la actualización y revisarlos; no usar `reset --hard`, `clean` ni reemplazar `.env` o `storage`.

```powershell
git fetch origin main
git log --oneline HEAD..origin/main
git pull --ff-only origin main
git log -1 --oneline
```

Revisar los commits pendientes antes del pull. Este fix no requiere migraciones; si aparecen otras actualizaciones pendientes, aplicar también las indicaciones específicas de esas versiones. Si el pull falla, detenerse y revisar el mensaje, sin forzar la actualización.

Si OPcache no comprueba cambios de archivos, reciclar el proceso PHP o el pool exclusivo de Conecta según el procedimiento del servidor. Este fix no cambia vistas, rutas ni configuración; limpiar vistas por sí solo no aplica los cambios de PHP. No modificar el worker o scheduler.

Probar ingreso normal y Recuérdame en el navegador que presentó el fallo, conservando sus cookies; probar también registro y dos cuentas en navegadores distintos. Una desconexión por cambio de IP debe mostrar el aviso y permitir un nuevo ingreso. Comparar la versión final con el commit publicado que acompañe esta guía.

Si surge un problema, conservar el error y revertir únicamente esta actualización de código mediante el procedimiento habitual. No restaurar una base antigua sobre los registros nuevos ni ejecutar rollback de migraciones para este fix.

No requiere migraciones, seeders, SQL, cambios de `.env`, scheduler o worker. No necesita que cada usuario borre cookies.

El fallo de IP antigua se reprodujo en laboratorio con la misma cadena de redirecciones del video. Para atribuir definitivamente ese intento de producción a la misma causa, correlacionar el registro «Cambio de IP detectado durante sesión» con el intento del 02/11/2026 a las 12:33 de Santiago / 15:33 UTC.

La actualización no resuelve una configuración externa que impida almacenar/enviar cookies, un dominio/ruta de cookie incorrectos o una pérdida de persistencia de sesión en el servidor. Esas condiciones requieren diagnóstico si el síntoma continúa.
