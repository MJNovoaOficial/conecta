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

## Instalación desde cero

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

## Credenciales de acceso

| Rol | Email | Contraseña |
|-----|-------|-----------|
| **Administrador** | v.herrera@dimak.cl | Conecta2024!@ |
| **Soporte** | s.morales@dimak.cl | Conecta2024!@ |
| **Soporte** | c.reyes@dimak.cl | Conecta2024!@ |
| **Soporte** | m.fuentes@dimak.cl | Conecta2024!@ |

---

## Reset completo de la base de datos

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
