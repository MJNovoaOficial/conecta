# Seguridad — Conecta Mesa de Ayuda v1.0

Este documento describe las medidas de seguridad implementadas en el sistema Conecta para Dimak.

---

## 1. Autenticación

### 1.1 Contraseñas

- Mínimo 12 caracteres
- Debe contener mayúsculas, minúsculas, números y símbolos especiales
- Hashing con BCrypt (estándar Laravel)
- No se almacenan en texto plano

### 1.2 Rate limiting

| Operación | Límite | Ventana |
|-----------|--------|---------|
| Login | 5 intentos | 15 minutos |
| Registro | 3 intentos | 1 hora |
| Crear ticket | 10 tickets | 1 hora |
| Agregar comentario | 20 comentarios | 1 hora |

Se retorna error HTTP 429 al exceder los límites.

### 1.3 Sesiones

- Duración: 480 minutos (8 horas)
- Regeneración en cada login
- Cookies seguras: solo HTTPS, HttpOnly, SameSite Strict
- Detección de cambio de IP: invalida la sesión si se detecta una IP diferente

### 1.4 Tokens API

- Laravel Sanctum para uso futuro en aplicaciones móviles
- Tokens separados de las sesiones web

---

## 2. Protección CSRF

- Middleware CSRF obligatorio en todas las rutas POST, PUT y DELETE
- Token `@csrf` en cada formulario Blade
- Sin excepciones configuradas en este proyecto

---

## 3. Validación de entradas

### 3.1 Sanitización

- `htmlspecialchars()` en entradas de usuario
- Recorte automático mediante middleware TrimStrings
- Validación de formato de email (RFC 5322)
- Nombres restringidos a letras y espacios
- Validación por expresiones regulares en título, categoría y tipo de dispositivo

### 3.2 Límites de caracteres

| Campo | Máximo |
|-------|--------|
| Títulos | 255 caracteres |
| Descripciones | 10.000 caracteres |
| Comentarios | 5.000 caracteres |
| Nombres de campos | 100–255 caracteres |

### 3.3 Validación de selecciones

- Priority: low, medium, high, critical
- Role: user, support, admin
- Status: estados permitidos del sistema
- Department: verificación de existencia en base de datos

---

## 4. Control de acceso

### 4.1 Middleware

- `auth`: requiere sesión activa
- `admin`: requiere rol de administrador
- `verified`: preparado para verificación de email

### 4.2 Políticas (TicketPolicy)

| Acción | Permisos |
|--------|----------|
| view | Creador, soporte o administrador |
| update | Creador, soporte asignado o administrador |
| delete | Solo administrador |

Los controladores validan permisos con `$this->authorize()`.

### 4.3 Filtrado por rol

- Usuario: solo sus propios tickets
- Soporte: tickets de su departamento
- Administrador: todos los tickets
- Comentarios internos: solo soporte y administrador

### 4.4 Restricciones de administrador

- El panel de administración requiere rol admin
- Un administrador no puede desactivar su propia cuenta

---

## 5. Seguridad de archivos

### 5.1 Validación de uploads

Extensiones permitidas:
- Documentos: pdf, doc, docx, xls, xlsx, ppt, pptx
- Imágenes: jpg, jpeg, png, gif
- Comprimidos: zip
- Texto: txt

Restricciones adicionales:
- Validación de tipo MIME real (no solo extensión)
- Tamaño máximo: 5 MB por archivo
- Límite: 5 archivos por ticket, 3 por comentario

### 5.2 Almacenamiento

- Ruta: `storage/app/tickets/` (fuera del web root)
- Nombres sanitizados sin caracteres especiales
- Directorios organizados por ticket
- Archivos no ejecutables

### 5.3 Descarga

- Solo usuarios autorizados pueden descargar
- Content-Type correcto para prevenir ejecución en navegador
- Cada descarga queda registrada en logs

---

## 6. Headers HTTP de seguridad

Se envían en cada respuesta:

```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=()
Content-Security-Policy: [configurado según recursos permitidos]
```

---

## 7. Logging y auditoría

### 7.1 Eventos registrados

- Login y logout (usuario, IP, user agent)
- Intentos de login fallidos (email e IP, sin contraseña)
- Registro de nuevos usuarios
- Cambios de usuario realizados por administradores
- Creación de tickets, comentarios y derivaciones
- Cambio de IP durante sesión activa

### 7.2 Datos no registrados

- Contraseñas
- Tokens de sesión o API
- Datos personales innecesarios

### 7.3 Archivo de logs

- Ubicación: `storage/logs/laravel.log`
- Nivel en producción: warning
- Rotación automática por tamaño

---

## 8. Configuración de producción

Variables requeridas en `.env`:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://conecta.dimak.local
DB_USERNAME=conecta_user
DB_PASSWORD=[contraseña fuerte]
LOG_LEVEL=warning
SESSION_SECURE_COOKIES=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict
```

El archivo `.env.example` no debe contener contraseñas reales, API keys ni `APP_KEY` (se genera con `php artisan key:generate`).

---

## 9. Seguridad de base de datos

- Tipos de dato correctos e índices en columnas de búsqueda frecuente
- Foreign keys para integridad referencial
- Mass assignment protegido mediante `$fillable`
- Usuario de base de datos dedicado (no root)
- Contraseña fuerte configurada en `.env`

---

## 10. Vulnerabilidades prevenidas

| Vulnerabilidad | Medida |
|----------------|--------|
| SQL Injection | Queries parametrizadas (Eloquent) |
| XSS | Sanitización, escape en Blade, CSP |
| CSRF | Tokens en formularios, cookies SameSite |
| Fuerza bruta | Rate limiting en login |
| Contraseñas débiles | Política de 12 caracteres mínimo |
| Secuestro de sesión | HttpOnly, Secure, SameSite, detección de IP |
| Clickjacking | X-Frame-Options: DENY |
| MIME sniffing | X-Content-Type-Options: nosniff |
| Fuga de información | APP_DEBUG=false, logging seguro |
| Archivos maliciosos | Whitelist MIME, sanitización de nombres |
| Acceso no autorizado | Policies, middleware, verificación de roles |

---

## 11. Checklist de despliegue

Antes de pasar a producción:

- [ ] Generar `APP_KEY` con `php artisan key:generate`
- [ ] Configurar `APP_ENV=production` y `APP_DEBUG=false`
- [ ] Configurar `APP_URL` con HTTPS
- [ ] Cambiar credenciales de base de datos (usuario dedicado, contraseña fuerte)
- [ ] Configurar `MAIL_FROM_ADDRESS` para notificaciones
- [ ] Instalar certificado SSL/TLS
- [ ] Ejecutar `php artisan migrate --seed` (primera vez)
- [ ] Ejecutar `php artisan storage:link`
- [ ] Ejecutar `composer install --no-dev`
- [ ] Ejecutar `php artisan config:cache`, `route:cache` y `view:cache`
- [ ] Configurar worker de colas y scheduler
- [ ] Verificar permisos en `storage/` y `bootstrap/cache/` (775)
- [ ] Deshabilitar listado de directorios
- [ ] Habilitar redirección HTTP a HTTPS
- [ ] Configurar backups de base de datos
- [ ] Configurar monitoreo de errores

---

## 12. Auditoría periódica

Se recomienda revisar cada 3 meses:

- Logs de seguridad (logins fallidos, cambios de administrador)
- Usuarios activos y permisos asignados
- Archivos adjuntos sospechosos
- Actualizaciones de Laravel y dependencias
- Vigencia del certificado SSL/TLS
- Funcionamiento de backups
- Capacitación de usuarios en phishing

---

## 13. Reporte de vulnerabilidades

Para reportar vulnerabilidades de seguridad:

- Email: seguridad@dimak.local
- No publicar en issues públicos
- Tiempo de respuesta esperado: 48 horas

---

Documento versión 1.0 — Última actualización: 2026-07-07 — Proyecto: Conecta (Dimak)
