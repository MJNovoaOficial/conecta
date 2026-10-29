# Bitácora de proyectos de Conecta

## Funcionamiento

- Apartado interno «Bitácora de proyectos», disponible para cuentas activas de soporte y administración. No usa un nombre de departamento ni amplía los permisos de los usuarios normales o invitados.
- Cada publicación pertenece a un proyecto o sistema. Se puede seleccionar uno existente o crear su nombre al publicar; un mismo proyecto acumula tantas actualizaciones como necesite.
- Se registra título, descripción, tipo (creación, mejora o corrección), responsable y fecha del trabajo. La fecha de publicación se conserva por separado.
- Cada autor puede editar sus publicaciones; administración puede editar cualquiera. Los permisos se revalidan al guardar. Una edición antigua no sobrescribe una revisión más reciente. Al editar se puede conservar el responsable histórico aunque su cuenta esté desactivada, haya cambiado de rol o ya no exista. No se incorporaron acciones de borrado.
- Adjuntos privados: hasta tres por actualización, de 5 MB cada uno. Se comprueban contenido y extensión. Se conservan al editar; los nuevos se suman al total. No se reutilizan carpetas ni registros de archivos de tickets.
- Las imágenes JPG/JPEG, PNG, GIF y WEBP se muestran dentro del listado y de la publicación, con su proporción completa. Se pueden abrir en otra pestaña y descargar; los otros documentos conservan sus enlaces. Las vistas de imágenes exigen los mismos permisos y no hacen públicos los archivos. También funciona con imágenes ya guardadas; no se convierten ni se mueven.
- El Dashboard Gerencial añade «Actividad de proyectos»: proyectos distintos y actualizaciones del mes y del año actuales, desglose por tipo y los cinco proyectos con más actualizaciones del mes. Se usa la fecha del trabajo y el calendario de la zona de presentación de Conecta. Este resumen tiene sus períodos indicados y no cambia el filtro ni los indicadores de tickets. Se muestra solo con la bitácora habilitada y sus tablas disponibles; no amplía el acceso al dashboard.
- La consulta abre el mes actual y permite elegir un rango de fechas. El período se conserva al avanzar de página, incluso si cambia el mes. Los reportes PDF y Excel usan la **fecha del trabajo**, incluso si se publicó en otro mes. Ambos incluyen el mismo período y todos sus registros; si superan 500, se pide acotar las fechas, sin recortar silenciosamente el reporte.
- No envía correos ni notificaciones de campana, ni necesita worker o scheduler. Las notificaciones quedan para una ampliación futura, si se solicita.
- Textos sin HTML ejecutable; Excel guarda los contenidos como texto. Las descargas también exigen soporte o administración y no se guardan en la caché del navegador.

## Preparar la actualización del servidor existente

Este es un añadido a Conecta. No instala otra aplicación ni requiere paquetes nuevos de Composer o npm. Publicar el código en GitHub no lo activa por sí solo.

1. Respaldar la base de datos, `.env` y `storage/app` del servidor en una ubicación privada, con acceso restringido. Conservar la versión actualmente desplegada y verificar que el respaldo pueda recuperarse.
2. Actualizar el código aprobado en la carpeta habitual de Conecta, manteniendo inicialmente `PROJECT_LOG_ENABLED=false`. Conservar `.env`, `APP_KEY`, archivos subidos y datos del asistente.
3. Desde PowerShell, en el servidor, ejecutar **solo** esta migración:

```powershell
Set-Location 'C:\Sitios\conecta'
& 'C:\PHP\php.exe' artisan migrate --path=database/migrations/2026_10_28_120000_create_project_log_tables.php --force
```

La migración crea tres tablas nuevas: `project_log_projects`, `project_log_entries` y `project_log_attachments`. No actualiza ni elimina usuarios, tickets o configuraciones existentes. Comprueba que `usuarios` use InnoDB sin convertirla. Si informa tablas ya existentes o falla, detenerse y revisar la salida; no borrar tablas ni repetir a ciegas una migración parcial.

4. Agregar o actualizar una sola vez en `.env`:

```dotenv
PROJECT_LOG_ENABLED=true
```

5. Limpiar las rutas y vistas anteriores y actualizar la configuración:

```powershell
& 'C:\PHP\php.exe' artisan route:clear
& 'C:\PHP\php.exe' artisan view:clear
& 'C:\PHP\php.exe' artisan config:cache
```

No es necesario reiniciar la cola para esta función, que no agrega jobs. Si el despliegue habitual reinicia el worker, verificar por separado que vuelva a quedar en ejecución.

6. Comprobar con una cuenta de soporte: abrir «Bitácora» desde el menú superior o «Bitácora de proyectos» desde el listado de tickets; publicar un registro de prueba, editarlo y consultar su período en PDF y Excel. Con una cuenta normal, el apartado no debe aparecer y la URL directa debe denegar acceso. El sitio debe seguir funcionando con la función desactivada o antes de ejecutar la migración.

## Ampliación visual y resumen gerencial

Si la bitácora ya está activada, esta ampliación solo necesita actualizar el código y limpiar las rutas y vistas (`artisan route:clear` y `artisan view:clear`). No añade migraciones, variables, paquetes, tareas programadas ni cambios a los registros existentes. CSS y JavaScript conservan su versión automática para descargar los cambios.

## Desactivar sin perder registros

Cambiar `PROJECT_LOG_ENABLED=false` y ejecutar `php artisan config:cache` usando el PHP y la carpeta indicados. Las publicaciones y sus archivos quedan conservados. No ejecutar `migrate:fresh`, seeders, purgas o un rollback general. La migración se niega a eliminar las tablas si contienen registros.

## Alcance de las comprobaciones

Ampliación visual del 29/10/2026: 82 pruebas relacionadas (916 aserciones) correctas en PHP 8.5.12, incluidas 9 nuevas de imágenes y resumen. Se comprobaron 17 controles HTTP sobre IIS Express/FastCGI con rutas en caché y 15 combinaciones de vistas/ancho (320, 390, 768, 1024 y 1440 px). Se revisaron imágenes horizontales, verticales y panorámicas, apertura completa, permisos, conteos sin duplicar proyectos, cambios de mes/año y dashboard con función desactivada o sin tablas. No equivale a una comprobación en el servidor productivo.

Validación local del alcance preparado para publicación: 545 pruebas y 5.370 aserciones correctas con PHP 8.5.12, además de 35 comprobaciones HTTP en IIS Express 10/FastCGI con esa versión de PHP. La suite se ejecutó sobre una copia de main con únicamente los archivos de la bitácora, sin diagnósticos ajenos. Se usaron únicamente bases y archivos de pruebas separados: `conecta_testing` para la suite y `conecta_iis_testing` para IIS. El resultado no confirma despliegue ni reproduce la identidad, permisos y configuración exacta del IIS productivo. La función permanece desactivada por defecto hasta completar la migración y activación autorizadas.
