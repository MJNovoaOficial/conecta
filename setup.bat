@REM Conecta - Setup Script for Windows
@REM Sistema de Mesa de Ayuda / Ticketera de Soporte para Dimak

@echo off
echo ================================
echo Conecta - Instalacion
echo ================================
echo.

echo 1. Verificando dependencias...
php -v >nul 2>&1 || (echo PHP no esta instalado & exit /b 1)
composer -V >nul 2>&1 || (echo Composer no esta instalado & exit /b 1)
node -v >nul 2>&1 || (echo Node.js no esta instalado & exit /b 1)
echo    OK - Dependencias encontradas
echo.

echo 2. Instalando dependencias PHP...
call composer install --no-interaction

echo    OK - Dependencias PHP instaladas
echo.

echo 3. Generando clave de aplicacion...
copy .env.example .env
call php artisan key:generate

echo    OK - Clave generada
echo.

echo 4. Base de datos
echo    Crea la base de datos 'conecta' en MySQL antes de continuar
pause

echo 5. Ejecutando migraciones...
call php artisan migrate --seed

echo    OK - Migraciones completadas
echo.

echo 6. Instalando dependencias de Node...
call npm install

echo    OK - Dependencias de Node instaladas
echo.

echo ================================
echo OK - Instalacion completada
echo ================================
echo.
echo Para iniciar la aplicacion:
echo   Terminal 1: php artisan serve
echo   Terminal 2: php artisan queue:work
echo   Terminal 3: npm run dev
echo.
echo La aplicacion estara disponible en: http://localhost:8000
echo.
echo Credenciales de prueba:
echo   Usuario: admin@dimak.local
echo   Contrasena: password
echo.
pause
