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

## DIMAKING, el asistente

DIMAKING responde con la base de conocimiento (`Ayuda` en el menú). Funciona
en dos modos:

- **Sin IA** (por defecto, `CHATBOT_ENABLED=false`): busca la guía que trata
  la consulta y muestra sus pasos dentro del chat.
- **Con IA**: además, un modelo de lenguaje que corre en un servidor de la
  empresa (Ollama) explica la guía con sus palabras. Ninguna consulta sale de
  la red interna, y el modelo no inventa: si no hay una guía sobre el tema, no
  responde.

En los dos modos, DIMAKING solo sabe lo que está en la base de conocimiento.
Si responde "No encontré nada" a preguntas comunes, faltan artículos, no IA.

### Cargar las guías de ejemplo

El proyecto trae 26 guías de soporte escritas (impresora, VPN, Outlook,
contraseñas, etc.). Conviene revisarlas antes, porque son genéricas:

```bash
php artisan db:seed --class=ArticuloSeeder --force
```

No duplica las que ya existen con el mismo título. Cada guía se asocia a su
categoría por nombre; si la categoría no existe, queda sin categoría.

### Activar la IA (opcional)

Requiere unos 5 GB de RAM libres para el modelo `qwen2.5:7b`. Sin tarjeta de
video cada respuesta tarda 15-20 segundos.

1. Instalar Ollama (https://ollama.com) en el servidor y descargar el modelo:
   ```bash
   ollama pull qwen2.5:7b
   ```
2. Dejar `ollama serve` corriendo al iniciar el servidor (por ejemplo, como
   tarea programada). **No depender de la aplicación de escritorio**: su
   actualizador automático dejó la instalación inutilizable dos veces durante
   las pruebas.
3. Comprobar que responde:
   ```bash
   curl http://127.0.0.1:11434/api/tags
   ```
4. En el `.env`:
   ```env
   CHATBOT_ENABLED=true
   CHATBOT_URL=http://127.0.0.1:11434
   CHATBOT_MODEL=qwen2.5:7b
   ```
5. `php artisan config:clear`

Para saber en qué modo está, pregunta en DIMAKING algo que calce con el título
de una guía (por ejemplo "la impresora no imprime"):

| Respuesta | Modo |
|---|---|
| "Esto es lo que encontré en las guías…" y los pasos | Sin IA |
| Un párrafo propio después de unos segundos | Con IA |
| Aviso amarillo "El asistente no está disponible…" | IA activada, pero Ollama no responde |

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
