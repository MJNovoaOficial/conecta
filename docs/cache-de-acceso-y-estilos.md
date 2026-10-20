# Formularios de acceso y versiones de estilos

Alcance: caché de las pantallas de ingreso/registro/recuperación/restablecimiento,
y URLs de los dos CSS propios. No modifica autenticación, permisos, expiración
de sesiones, intentos de acceso, invitados ni datos guardados.

- `premium.css` y `responsive.css` usan SHA-256 de su contenido en `?v=`.
  La URL se conserva si no cambian; cambia incluso si el despliegue conserva la
  fecha de modificación. No se usa la hora actual ni se desactiva la caché de CSS.
- Los GET/HEAD de acceso, incluido `/` (el formulario real de ingreso), envían
  `Cache-Control: no-store, private`. No se aplica globalmente a tickets/descargas.
- Si el navegador restaura una de esas pantallas desde Atrás/Adelante (bfcache),
  se hace un GET nuevo. No hay temporizador, recarga periódica ni POST automático.
- El JavaScript propio permanece en las páginas y se renueva junto con su HTML.
  Las bibliotecas externas mantienen sus versiones explícitas; no se migra a Vite.
- Se conserva el aviso 419 y su enlace al ingreso si un formulario expira mientras
  está abierto. La protección CSRF no se elimina ni se reintentan contraseñas.

No se borran cookies, almacenamiento del navegador ni cachés globales, y no se
cierran sesiones por publicar. Una pestaña ya abierta puede necesitar una recarga
normal; el cambio no vacía retroactivamente copias antiguas ni sustituye la
actualización de cachés del servidor. No se debe forzar la recarga de formularios
de tickets, pues podrían contener texto o archivos todavía sin enviar.

## Despliegue

Después de actualizar el código en el servidor, desde la carpeta de Conecta:

```powershell
php artisan view:clear
```

Este ajuste web no requiere reiniciar el worker, `npm build`, migraciones,
seeders, SQL ni cambios en `.env` o IIS.
Si PHP tiene OPcache sin validación de cambios, el responsable del servidor debe
reciclar el proceso PHP según su procedimiento habitual de despliegue.

Verificar desde fuera del servidor que el HTML de acceso conserva `no-store,
private` y que las dos URLs CSS incluyen `?v=`. Una configuración externa de
IIS/proxy podría sobrescribir cabeceras; no ha sido modificada desde este arreglo.
