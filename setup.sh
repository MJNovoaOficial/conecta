#!/bin/bash

# Conecta - Setup Script
# Sistema de Mesa de Ayuda / Ticketera de Soporte para Dimak

echo "================================"
echo "Conecta - Instalación"
echo "================================"
echo ""

# Paso 1: Verificar dependencias
echo "1. Verificando dependencias..."
php -v > /dev/null 2>&1 || { echo "PHP no está instalado"; exit 1; }
composer -V > /dev/null 2>&1 || { echo "Composer no está instalado"; exit 1; }
node -v > /dev/null 2>&1 || { echo "Node.js no está instalado"; exit 1; }

echo "   ✓ Dependencias encontradas"
echo ""

# Paso 2: Instalar dependencias PHP
echo "2. Instalando dependencias PHP..."
composer install --no-interaction

echo "   ✓ Dependencias PHP instaladas"
echo ""

# Paso 3: Generar clave de aplicación
echo "3. Generando clave de aplicación..."
cp .env.example .env
php artisan key:generate

echo "   ✓ Clave generada"
echo ""

# Paso 4: Crear base de datos
echo "4. Base de datos"
echo "   Crea la base de datos 'conecta' en MySQL antes de continuar"
echo "   Presiona Enter cuando esté lista..."
read

# Paso 5: Ejecutar migraciones
echo "5. Ejecutando migraciones..."
php artisan migrate --seed

echo "   ✓ Migraciones completadas"
echo ""

# Paso 6: Instalar dependencias de Node
echo "6. Instalando dependencias de Node..."
npm install

echo "   ✓ Dependencias de Node instaladas"
echo ""

echo "================================"
echo "✓ Instalación completada"
echo "================================"
echo ""
echo "Para iniciar la aplicación:"
echo "  Terminal 1: php artisan serve"
echo "  Terminal 2: php artisan queue:work"
echo "  Terminal 3: npm run dev"
echo ""
echo "La aplicación estará disponible en: http://localhost:8000"
echo ""
echo "Credenciales de prueba:"
echo "  Usuario: admin@dimak.local"
echo "  Contraseña: password"
echo ""
