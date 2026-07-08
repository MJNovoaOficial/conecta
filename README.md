# Conecta

Sistema de mesa de ayuda y gestión de tickets de soporte técnico para Dimak, desarrollado con Laravel y PHP.

## Requisitos

- PHP 8.1 o superior
- Composer
- MySQL 5.7 o superior
- Redis (opcional, para colas)
- Node.js 18 o superior (para assets)

## Instalación

### Opción 1: Script automático

**Windows:**
```bash
setup.bat
```

**Linux/macOS:**
```bash
bash setup.sh
```

### Opción 2: Instalación manual

1. Clonar el repositorio e ingresar al directorio:
```bash
git clone <repository-url>
cd conecta
```

2. Instalar dependencias PHP:
```bash
composer install
```

3. Configurar el entorno:
```bash
cp .env.example .env
php artisan key:generate
```

4. Crear la base de datos y ejecutar migraciones:
```sql
CREATE DATABASE conecta;
```
```bash
php artisan migrate --seed
```

5. Instalar dependencias JavaScript y compilar assets:
```bash
npm install
npm run build
```

6. Crear enlace simbólico de almacenamiento:
```bash
php artisan storage:link
```

## Ejecución

En desarrollo se requieren tres procesos en terminales separadas:

```bash
# Terminal 1 - Servidor web
php artisan serve

# Terminal 2 - Procesador de colas
php artisan queue:work

# Terminal 3 - Compilación de assets (opcional en desarrollo)
npm run dev
```

La aplicación estará disponible en `http://localhost:8000`.

## Credenciales de prueba

| Usuario | Rol | Contraseña |
|---------|-----|------------|
| admin@dimak.local | Administrador | password |
| soporte1@dimak.local | Soporte | password |
| usuario@dimak.local | Usuario | password |

## Funcionalidades

### Gestión de usuarios
- Autenticación con login y registro
- Roles: Usuario, Soporte y Administrador
- Asociación con departamentos
- Perfiles con avatar

### Gestión de tickets
- Creación de tickets por cualquier usuario autenticado
- Número único automático (formato TK-YYYYMMDDHHmmss-XXXX)
- Estados: Abierto, En Proceso, Pendiente Usuario, Derivado, Resuelto, Cerrado
- Prioridades: Baja, Media, Alta, Crítica
- Categorías y tipos de dispositivo
- Adjuntos de archivos e imágenes (máximo 5 MB)
- Comentarios con historial completo
- Interfaz tipo foro/chat

### Asignación y derivación
- Asignación de tickets a personal de soporte
- Derivación entre departamentos
- Historial de cambios

### Notificaciones
- Correo al crear un ticket
- Notificaciones en actualizaciones y cambios de estado
- Procesamiento asíncrono mediante Laravel Queue

### Tiempo de respuesta
- Plazo máximo de 30 minutos para que el usuario responda cuando soporte solicita información
- Cierre automático del ticket si no hay respuesta
- El tiempo de espera del usuario no cuenta en el SLA de soporte

### Panel de administración
- Dashboard con estadísticas
- Gestión de usuarios y departamentos
- Control de permisos por rol

## Estructura del proyecto

```
conecta/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # Auth, Ticket, Admin
│   │   └── Middleware/      # Admin, seguridad, autenticación
│   ├── Models/              # User, Ticket, Department, etc.
│   ├── Notifications/       # Notificaciones por correo
│   ├── Jobs/                # Tareas en cola (cierre automático)
│   ├── Policies/            # Autorización
│   └── Providers/           # Service providers
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/views/
│   ├── auth/
│   ├── tickets/
│   ├── admin/
│   └── layouts/
├── routes/web.php
├── config/
├── public/
└── storage/
```

## Modelos de datos

| Modelo | Campos principales |
|--------|-------------------|
| User | name, email, password, phone, department_id, role, avatar_url, is_active |
| Ticket | ticket_number, user_id, department_id, title, description, status, category, device_type, priority, assigned_to, response_deadline_at |
| TicketComment | ticket_id, user_id, comment, ticket_status_at_comment, is_internal |
| TicketAttachment | ticket_id, comment_id, file_path, file_name, file_type, file_size |
| Department | name, description, is_active |
| TicketHistory | ticket_id, user_id, action, old_value, new_value, field_name |

## Roles y permisos

**Usuario**
- Ver y crear sus propios tickets
- Comentar y adjuntar archivos

**Soporte**
- Ver todos los tickets
- Asignar, derivar y cambiar estados
- Publicar comentarios internos

**Administrador**
- Acceso completo al sistema
- Gestión de usuarios y departamentos
- Acceso al panel de administración

## Rutas principales

```
GET  /                              Página de inicio
POST /login                         Iniciar sesión
GET  /register                      Registro de usuario
POST /logout                        Cerrar sesión

GET  /tickets                       Listar tickets
GET  /tickets/create                Formulario de creación
POST /tickets                       Guardar ticket
GET  /tickets/{id}                  Ver ticket
POST /tickets/{id}/comment          Agregar comentario
PUT  /tickets/{id}/status           Cambiar estado
POST /tickets/{id}/assign           Asignar ticket
POST /tickets/{id}/forward          Derivar ticket

GET  /admin/dashboard               Panel de administración
GET  /admin/users                   Gestión de usuarios
GET  /admin/departments             Gestión de departamentos
```

## Configuración de correo

Editar `.env` con los datos del proveedor SMTP:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=tu_usuario
MAIL_PASSWORD=tu_contraseña
MAIL_FROM_ADDRESS=noreply@conecta.local
```

## Mantenimiento

```bash
# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Ejecutar migraciones pendientes
php artisan migrate

# Ejecutar seeders
php artisan db:seed

# Reintentar trabajos fallidos en cola
php artisan queue:failed
php artisan queue:retry all
```

## Solución de problemas

**Error: Class not found**
```bash
composer dump-autoload
```

**Error: SQLSTATE[HY000]**
Verificar la conexión a MySQL en `.env`.

**La cola no procesa trabajos**
Confirmar que `php artisan queue:work` está en ejecución. Revisar trabajos fallidos con `php artisan queue:failed`.

**Los archivos adjuntos no se guardan**
Verificar permisos de escritura en `storage/` y que el enlace simbólico exista (`php artisan storage:link`).

**Correos no se envían**
Verificar configuración SMTP en `.env` y que el worker de colas esté activo.

## Tecnologías

| Componente | Tecnología |
|------------|------------|
| Framework | Laravel 11 |
| Base de datos | MySQL |
| Autenticación | Laravel Sanctum |
| Colas | Laravel Queue (database/redis) |
| Frontend | Blade + Bootstrap 5 |

## Documentación adicional

- [SECURITY.md](SECURITY.md) — Medidas de seguridad, checklist de despliegue y auditoría

## Licencia

Desarrollado para Dimak. Todos los derechos reservados.

Versión 1.0.0
