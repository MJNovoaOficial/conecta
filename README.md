# Conecta — Mesa de Ayuda Dimak

Sistema de gestión de tickets de soporte IT para Dimak, construido con Laravel 12.

---

## Requisitos

| Herramienta | Versión mínima |
|-------------|---------------|
| PHP         | 8.2+          |
| Composer    | 2.x           |
| MySQL       | 8.0+          |
| Node.js     | 18+           |
| npm         | 9+            |

---

## Instalación para desarrollo

> Estos pasos son para levantar la plataforma en un equipo de trabajo. **Para el
> servidor, seguir "Despliegue a producción"**: los pasos no son los mismos, y
> el `--seed` de aquí carga usuarios y tickets de ejemplo cuyas contraseñas
> están publicadas más abajo en este archivo.

### 1. Clonar el repositorio

```bash
git clone https://github.com/MJNovoaOficial/conecta.git
cd conecta
```

### 2. Instalar dependencias PHP

```bash
composer install
```

### 3. Configurar el entorno

```bash
cp .env.example .env
php artisan key:generate
```

Editar `.env` con los datos de la base de datos local:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=conecta
DB_USERNAME=root
DB_PASSWORD=tu_contraseña
```

### 4. Crear la base de datos

Crear la base de datos `conecta` en MySQL:

```sql
CREATE DATABASE conecta CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Ejecutar migraciones y seeders

```bash
php artisan migrate --seed
```

Esto crea todas las tablas y carga los datos iniciales (usuarios, categorías, configuración y tickets de ejemplo).

### 6. Instalar dependencias frontend

```bash
npm install
npm run build
```

### 7. Levantar el servidor de desarrollo

```bash
php artisan serve
```

La aplicación estará disponible en: **http://localhost:8000**

---

## Despliegue a producción

> Lo más importante de esta sección son los **dos procesos permanentes** del
> paso 6 y los **respaldos** del paso 7. Sin ellos la plataforma se ve
> funcionando y por dentro no envía ningún correo ni cierra ningún ticket, sin
> mostrar un solo error.

### 1. Preparar el servidor

El directorio público del sitio (IIS, Apache o el que se use) tiene que apuntar
a la carpeta **`public/`**, nunca a la raíz del proyecto. Si apunta a la raíz,
el archivo `.env` —con las credenciales de la base de datos— queda descargable
desde el navegador.

En el `php.ini`, para poder adjuntar los videos que se anunciaron:

```ini
upload_max_filesize = 60M
post_max_size       = 80M
```

Sin esto la plataforma sigue funcionando: avisa cuál es el tamaño máximo real
en vez de fallar con una pantalla de error, pero nadie podrá adjuntar un video.

### 2. Instalar la aplicación

```bash
git clone https://github.com/MJNovoaOficial/conecta.git
cd conecta
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

### 3. Configurar el entorno

```bash
cp .env.example .env
php artisan key:generate
```

`.env.example` ya viene con los valores de producción. Lo que hay que completar:

| Variable | Qué poner |
|----------|-----------|
| `APP_URL` | La dirección real desde donde se entra. Con el valor equivocado, los enlaces de los correos apuntan a un sitio que no existe |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Un usuario propio de la aplicación, con permisos solo sobre esta base. No `root`, y nunca sin contraseña |
| `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD` | Los datos reales del correo saliente |
| `CHATBOT_ENABLED` | `false` si Ollama todavía no está instalado. Ver el paso 8 |

Verificar que `APP_DEBUG=false`. En `true`, cualquier error muestra el rastro
completo del código y las credenciales a quien tenga la pantalla delante.

### 4. Crear la base de datos y migrar

```sql
CREATE DATABASE conecta CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan migrate --force
php artisan storage:link
```

### 5. Cargar los datos iniciales

**No ejecutar `php artisan migrate --seed` ni `db:seed` a secas.** El seeder
completo carga 12 usuarios de ejemplo y tickets de prueba, con contraseñas que
están publicadas en este mismo archivo.

Primero, crear el administrador real:

```bash
php artisan tinker
```

```php
App\Models\User::create([
    'name'      => 'Nombre Apellido',
    'email'     => 'admin@dimak.cl',
    'password'  => Hash::make('una-contraseña-larga-y-propia'),
    'role'      => 'admin',
    'is_active' => true,
]);
```

Después, cargar solo lo que la plataforma necesita para funcionar:

```bash
php artisan db:seed --class=DepartmentSeeder --force
php artisan db:seed --class=CatalogoSeeder --force
php artisan db:seed --class=SystemSettingsSeeder --force
php artisan db:seed --class=PriorityRuleSeeder --force
php artisan db:seed --class=ArticuloSeeder --force
```

`ArticuloSeeder` carga los 26 artículos de la base de conocimiento y los
atribuye al primer usuario con rol `admin`: por eso va **después** de crear la
cuenta de administración.

Los que **no** se ejecutan en producción:

| Seeder | Por qué no |
|--------|-----------|
| `UserSeeder` | 12 usuarios de ejemplo con la misma contraseña conocida |
| `TicketSeeder` | Tickets de prueba, además con un formato de número distinto |
| `AdminUserSeeder` | Crea un administrador con la contraseña `secret123`. Si ya existe en la base, desactivarlo |

### 6. Los dos procesos permanentes

Esto es lo que más se olvida y lo que falla en silencio.

**Consumidor de la cola.** Sin esto **no sale ningún correo**: ni la
confirmación al invitado con su enlace de seguimiento, ni los avisos de
respuesta, ni las alertas de SLA. Se acumulan en la tabla `jobs` y ahí quedan.

```bash
php artisan queue:work --tries=3
```

**Programador de tareas.** Sin esto no se cierran los tickets que quedaron
esperando respuesta del solicitante ni se avisa antes de que venza un plazo.
Tiene que ejecutarse **cada minuto**.

```bash
php artisan schedule:run
```

En Windows se dejan como dos tareas programadas:

| Tarea | Disparador | Configuración |
|-------|-----------|---------------|
| `queue:work` | Al iniciar el equipo | "Ejecutar aunque el usuario no haya iniciado sesión" y, en Configuración, "Si la tarea falla, reiniciarla cada 1 minuto" |
| `schedule:run` | Diario, repetir cada 1 minuto indefinidamente | "Ejecutar aunque el usuario no haya iniciado sesión" |

**Después de cada despliegue**, reiniciar el consumidor para que tome el código
nuevo. Si no, sigue ejecutando la versión vieja hasta que alguien lo reinicie:

```bash
php artisan queue:restart
```

### 7. Respaldos

Es lo único de esta lista que no se puede arreglar después. Sin respaldos, un
error de una semana no tiene desde dónde volver.

Respaldo diario, como tarea programada:

```powershell
mysqldump -u USUARIO -pCLAVE --single-transaction --routines conecta `
  | Out-File "D:\respaldos\conecta_$(Get-Date -Format yyyy-MM-dd).sql" -Encoding utf8
```

Y activar el registro binario en el `my.ini` de MySQL, que permite recuperar
hasta el momento exacto de un borrado accidental:

```ini
log_bin = mysql-bin
expire_logs_days = 14
```

Comprobar que el respaldo **se puede restaurar**, no solo que el archivo existe.
Un respaldo que nunca se probó no es un respaldo.

### 8. El asistente DIMAKING (opcional)

La plataforma funciona sin esto. Con `CHATBOT_ENABLED=false`, el asistente
sigue entregando los artículos que corresponden, solo que sin explicarlos.

Si se instala Ollama:

- **Como servicio del sistema, no como aplicación de escritorio.** La
  aplicación de escritorio revisa actualizaciones cada hora y se reinstala
  sola; en el equipo de pruebas ese proceso borró la instalación y no la volvió
  a poner, dos veces en una semana. El servidor por sí solo (`ollama serve`) no
  tiene esa función, y como tarea programada arranca con el equipo en vez de
  cuando alguien inicia sesión.
- El modelo ocupa unos **5 GB de memoria** mientras está cargado.
- Si Ollama corre en otro equipo, exponerlo requiere `OLLAMA_HOST=0.0.0.0` y
  **restringir el firewall a la IP de la plataforma**: Ollama no tiene
  autenticación de ningún tipo.

Además, en `/admin/articulos` hay que marcar como públicos los artículos que
DIMAKING puede mostrar **antes** de iniciar sesión (los de acceso y
contraseñas). Si no se marca ninguno, el asistente de la pantalla de login
responde que no encontró nada.

### 9. Optimizar

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Repetir después de cada despliegue. Si se cambia el `.env`, hay que volver a
ejecutar `config:cache` o el cambio no tiene efecto.

### 10. Verificar que quedó bien

No basta con que la página cargue. Comprobar una por una:

| Qué probar | Qué tiene que pasar |
|-----------|---------------------|
| Entrar con el administrador creado en el paso 5 | Entra y ve el panel |
| Abrir un ticket como invitado, con un correo real | **Llega el correo** con el enlace de seguimiento |
| `SELECT COUNT(*) FROM jobs;` un minuto después | Devuelve 0. Si crece, el consumidor de la cola no está corriendo |
| Adjuntar un archivo de ~20 MB | Se adjunta sin error |
| Abrir el asistente en la pantalla de login | Responde algo sobre contraseñas |
| Revisar `storage/logs/laravel.log` | Sin errores nuevos |

La prueba del correo al invitado es la más importante: es la única que
demuestra, de punta a punta, que la cola está funcionando.

---

## Credenciales de acceso

> Son las cuentas que crea `UserSeeder` para desarrollo. **No existen en
> producción** y no deben crearse ahí: sus contraseñas están publicadas aquí.

| Rol | Email | Contraseña |
|-----|-------|-----------|
| **Administrador** | v.herrera@dimak.cl | Conecta2024!@ |
| **Soporte** | s.morales@dimak.cl | Conecta2024!@ |
| **Soporte** | c.reyes@dimak.cl | Conecta2024!@ |
| **Soporte** | m.fuentes@dimak.cl | Conecta2024!@ |

---

## Reset completo de la base de datos

> **Solo en un equipo de desarrollo.** Este comando **borra todas las tablas**,
> incluidos los tickets reales, y no se puede deshacer. En el servidor no se
> ejecuta nunca.

Para volver a un estado limpio con datos de ejemplo:

```bash
php artisan migrate:fresh --seed
```

---

## Stack tecnológico

- **Backend:** Laravel 12 (PHP 8.2)
- **Base de datos:** MySQL 8.0
- **PDF:** barryvdh/laravel-dompdf
- **Excel:** phpoffice/phpspreadsheet
- **Frontend:** Blade + CSS (sin frameworks CSS externos)
- **Iconos:** Font Awesome 6

---

## Estructura de roles

| Rol | Descripción |
|-----|-------------|
| `admin` | Acceso total: dashboard, reportes, configuración |
| `support` | Gestión de tickets asignados |
| `user` | Creación y seguimiento de sus tickets |

---

## Solución de problemas frecuentes

**Error: `SQLSTATE[HY000] [1049] Unknown database 'conecta'`**
→ Crear la base de datos manualmente en MySQL antes de ejecutar las migraciones.

**Error: `php_network_getaddresses: getaddrinfo failed`**
→ Verificar que MySQL esté corriendo y que los datos en `.env` sean correctos.

**Las vistas no cargan correctamente**
→ Ejecutar `php artisan view:clear && php artisan config:clear`

**Las exportaciones CSV/Excel/PDF no descargan**
→ Usar un navegador moderno (Chrome, Brave, Firefox). Edge puede tener restricciones con localhost.
